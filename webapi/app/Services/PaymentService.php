<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\ProviderProfile;
use App\Models\ProviderWallet;
use App\Models\ServicePayment;
use App\Models\ServiceQuote;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    /**
     * Tasa de comisión vigente. Orden de precedencia:
     *  1) Regla por categoría en commission_settings.
     *  2) Regla global (category_id = NULL) en commission_settings.
     *  3) Setting dinámico escrow.commission_percent.
     *  4) Setting dinámico commission.default_rate (legacy).
     *  5) Config file.
     */
    public function commissionRateFor(?int $categoryId): float
    {
        $row = CommissionSetting::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId)
                  ->orWhereNull('category_id');
            })
            ->orderByRaw('category_id IS NULL')
            ->first();

        if ($row !== null) {
            return (float) $row->rate;
        }

        $escrowPct = chamba_setting('escrow.commission_percent', null);
        if ($escrowPct !== null && $escrowPct !== '') {
            return (float) $escrowPct;
        }

        return (float) chamba_setting('commission.default_rate', config('chamba.commission.default_rate', 10.00));
    }

    /**
     * Registra un evento de transición en service_request_events.
     */
    public function logRequestEvent(
        ServiceRequest $request,
        ?string $from,
        string $to,
        ?int $actorUserId = null,
        ?string $actorRole = null,
        ?string $note = null,
        ?array $metadata = null,
    ): void {
        ServiceRequestEvent::query()->create([
            'service_request_id' => $request->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actorUserId ?? Auth::id(),
            'actor_role' => $actorRole ?? Auth::user()?->role,
            'note' => $note,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Crea registro de pago en estado "pendiente_revision".
     * El cliente está reportando que ya pagó por Yape/Plin/Transferencia.
     *
     * @param  array  $opts  ['proof_image_path' => ?string]
     */
    public function registerClientPayment(
        ServiceRequest $request,
        ServiceQuote $quote,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
        array $opts = [],
    ): ServicePayment {
        return DB::transaction(function () use ($request, $quote, $paymentMethod, $paymentReference, $notes, $opts) {
            if ($quote->service_request_id !== $request->id) {
                throw new RuntimeException('La cotización no pertenece a la solicitud.');
            }
            if ($quote->status !== 'aceptada') {
                throw new RuntimeException('La cotización no está aceptada.');
            }
            if (ServicePayment::query()
                ->where('service_quote_id', $quote->id)
                ->whereIn('status', ['pendiente_revision', 'en_custodia', 'liberado'])
                ->exists()) {
                throw new RuntimeException('Ya existe un pago en proceso para esta cotización.');
            }

            $providerService = $request->providerService()->first();
            $categoryId = $providerService?->category_id;
            $rate = $this->commissionRateFor($categoryId !== null ? (int) $categoryId : null);
            $amount = (float) $quote->amount;
            $commission = round($amount * $rate / 100, 2);
            $net = round($amount - $commission, 2);
            $providerProfileId = (int) $providerService?->provider_profile_id;
            if ($providerProfileId <= 0) {
                throw new RuntimeException('No se encontró el perfil del proveedor.');
            }

            $payment = ServicePayment::query()->create([
                'service_request_id' => $request->id,
                'service_quote_id' => $quote->id,
                'client_user_id' => $request->client_user_id,
                'provider_profile_id' => $providerProfileId,
                'amount' => $amount,
                'currency' => $quote->currency ?? 'PEN',
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'net_amount' => $net,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status' => 'pendiente_revision',
                'paid_at' => now(),
                'notes' => $notes,
                'proof_image_path' => $opts['proof_image_path'] ?? null,
            ]);

            $prev = (string) $request->status;
            $request->update(['status' => 'pagado_pendiente']);
            $this->logRequestEvent($request, $prev, 'pagado_pendiente', null, 'cliente',
                'Cliente registró pago. Esperando confirmación del admin.',
                ['payment_id' => $payment->id, 'amount' => $amount, 'method' => $paymentMethod]);

            return $payment;
        });
    }

    /**
     * Admin confirma que el pago llegó: pasa a "en_custodia" (escrow).
     */
    public function adminConfirmPayment(ServicePayment $payment): ServicePayment
    {
        return DB::transaction(function () use ($payment) {
            if ($payment->status !== 'pendiente_revision') {
                throw new RuntimeException('Solo se pueden confirmar pagos pendientes de revisión.');
            }
            $payment->update([
                'status' => 'en_custodia',
                'confirmed_at' => now(),
            ]);
            $sr = $payment->serviceRequest()->first();
            $prev = (string) $sr->status;
            $sr->update(['status' => 'en_custodia']);
            $this->logRequestEvent($sr, $prev, 'en_custodia', null, 'admin',
                'Admin confirmó pago. Dinero retenido en custodia.',
                ['payment_id' => $payment->id]);
            return $payment;
        });
    }

    /**
     * Admin rechaza el pago (no se confirma transferencia, etc.).
     */
    public function adminRejectPayment(ServicePayment $payment, ?string $notes = null): ServicePayment
    {
        return DB::transaction(function () use ($payment, $notes) {
            if ($payment->status !== 'pendiente_revision') {
                throw new RuntimeException('Solo se pueden rechazar pagos pendientes de revisión.');
            }
            $payment->update([
                'status' => 'rechazado',
                'notes' => trim(($payment->notes ? $payment->notes."\n" : '').'[admin] '.($notes ?? 'Rechazado.')),
            ]);
            $sr = $payment->serviceRequest()->first();
            $prev = (string) $sr->status;
            $sr->update(['status' => 'aceptado']);
            $this->logRequestEvent($sr, $prev, 'aceptado', null, 'admin',
                'Admin rechazó el pago: '.($notes ?? 'sin detalle'),
                ['payment_id' => $payment->id]);
            return $payment;
        });
    }

    /**
     * Proveedor marca el trabajo como "entregado". Inicia ventana de auto-liberación.
     * Requiere al menos N evidencias subidas (configurable por escrow.evidence_min_photos).
     */
    public function providerMarkDelivered(ServiceRequest $request, int $evidenceCount): ServiceRequest
    {
        return DB::transaction(function () use ($request, $evidenceCount) {
            if (! in_array($request->status, ['en_custodia', 'en_progreso'], true)) {
                throw new RuntimeException('El trabajo solo puede marcarse como entregado si está en custodia o en progreso.');
            }
            $minPhotos = (int) chamba_setting('escrow.evidence_min_photos', 1);
            if ($evidenceCount < $minPhotos) {
                throw new RuntimeException("Debes subir al menos {$minPhotos} foto(s) de evidencia antes de marcar como entregado.");
            }

            $autoDays = (int) chamba_setting('escrow.auto_release_days', 7);
            $prev = (string) $request->status;

            $request->update([
                'status' => 'entregado',
                'delivered_at' => now(),
                'auto_release_at' => now()->addDays($autoDays),
            ]);
            $this->logRequestEvent($request, $prev, 'entregado', null, 'proveedor',
                "Proveedor marcó como entregado. Auto-liberación en {$autoDays} días si el cliente no responde.",
                ['evidence_count' => $evidenceCount, 'auto_release_days' => $autoDays]);
            return $request;
        });
    }

    /**
     * Cliente confirma "trabajo terminado" → libera el dinero al wallet del proveedor.
     */
    public function clientConfirmCompleted(ServicePayment $payment, ?int $actorUserId = null): ServicePayment
    {
        return DB::transaction(function () use ($payment, $actorUserId) {
            if ($payment->status !== 'en_custodia') {
                throw new RuntimeException('El pago debe estar en custodia para liberarse.');
            }

            $wallet = ProviderWallet::query()->firstOrCreate(
                ['provider_profile_id' => $payment->provider_profile_id],
            );
            $wallet->balance = (float) $wallet->balance + (float) $payment->net_amount;
            $wallet->total_earned = (float) $wallet->total_earned + (float) $payment->net_amount;
            $wallet->save();

            $payment->update([
                'status' => 'liberado',
                'released_at' => now(),
            ]);
            $sr = $payment->serviceRequest()->first();
            $prev = (string) $sr->status;
            $sr->update([
                'status' => 'confirmado',
                'client_confirmed_at' => now(),
            ]);
            $this->logRequestEvent($sr, $prev, 'confirmado', $actorUserId, 'cliente',
                'Cliente confirmó el trabajo. Pago liberado al wallet del proveedor.',
                ['payment_id' => $payment->id, 'net_amount' => $payment->net_amount]);

            return $payment;
        });
    }

    /**
     * Cliente reporta una disputa (no quedó satisfecho con el trabajo).
     */
    public function clientReportDispute(ServicePayment $payment, ?string $reason, ?int $actorUserId = null): ServicePayment
    {
        return DB::transaction(function () use ($payment, $reason, $actorUserId) {
            if (! in_array($payment->status, ['en_custodia'], true)) {
                throw new RuntimeException('Solo se puede disputar un pago en custodia.');
            }
            $payment->update([
                'notes' => trim(($payment->notes ? $payment->notes."\n" : '').'[disputa] '.($reason ?? 'Sin detalle.')),
            ]);
            $sr = $payment->serviceRequest()->first();
            $prev = (string) $sr->status;
            $sr->update([
                'status' => 'disputado',
                'disputed_at' => now(),
                'auto_release_at' => null,
            ]);
            $this->logRequestEvent($sr, $prev, 'disputado', $actorUserId, 'cliente',
                'Cliente reportó disputa: '.($reason ?? 'sin detalle'),
                ['payment_id' => $payment->id]);
            return $payment;
        });
    }

    /**
     * Auto-libera todos los pagos en custodia cuyo trabajo fue entregado
     * hace más de N días sin que el cliente haya confirmado ni disputado.
     * Devuelve la cantidad de pagos liberados.
     */
    public function autoReleaseExpired(): int
    {
        $now = now();
        $payments = ServicePayment::query()
            ->where('status', 'en_custodia')
            ->whereHas('serviceRequest', function ($q) use ($now) {
                $q->where('status', 'entregado')
                  ->whereNotNull('auto_release_at')
                  ->where('auto_release_at', '<=', $now);
            })
            ->get();

        $count = 0;
        foreach ($payments as $payment) {
            try {
                $this->clientConfirmCompleted($payment, null);
                $sr = $payment->serviceRequest()->first();
                $this->logRequestEvent($sr, 'confirmado', 'confirmado', null, 'sistema',
                    'Auto-liberación: el cliente no confirmó dentro del plazo.',
                    ['auto' => true]);
                $count++;
            } catch (\Throwable) {
                // Si algún pago falla, seguimos con el resto.
            }
        }
        return $count;
    }

    /**
     * Crea/recupera el wallet del proveedor.
     */
    public function ensureWallet(ProviderProfile $profile): ProviderWallet
    {
        return ProviderWallet::query()->firstOrCreate(
            ['provider_profile_id' => $profile->id],
        );
    }

    /**
     * Solicita un retiro del wallet del proveedor.
     */
    public function requestWithdrawal(ProviderProfile $profile, float $amount, string $payoutMethod): \App\Models\WalletWithdrawal
    {
        return DB::transaction(function () use ($profile, $amount, $payoutMethod) {
            $wallet = $this->ensureWallet($profile);
            if ($amount <= 0) {
                throw new RuntimeException('El monto debe ser mayor que cero.');
            }
            if ((float) $wallet->balance < $amount) {
                throw new RuntimeException('Saldo insuficiente.');
            }
            $wallet->balance = (float) $wallet->balance - $amount;
            $wallet->save();

            return \App\Models\WalletWithdrawal::query()->create([
                'provider_profile_id' => $profile->id,
                'amount' => $amount,
                'payout_method' => $payoutMethod,
                'status' => 'solicitado',
            ]);
        });
    }

    /**
     * Admin marca un retiro como pagado al proveedor (subiendo opcionalmente comprobante).
     *
     * @param  array  $opts  ['proof_image_path' => ?string]
     */
    public function adminPayWithdrawal(\App\Models\WalletWithdrawal $w, ?string $reference, ?string $notes = null, array $opts = []): \App\Models\WalletWithdrawal
    {
        return DB::transaction(function () use ($w, $reference, $notes, $opts) {
            if ($w->status !== 'solicitado') {
                throw new RuntimeException('Solo se pueden pagar retiros solicitados.');
            }
            $w->update([
                'status' => 'pagado',
                'payout_reference' => $reference,
                'paid_at' => now(),
                'notes' => $notes,
                'proof_image_path' => $opts['proof_image_path'] ?? $w->proof_image_path,
            ]);
            $wallet = ProviderWallet::query()->firstWhere('provider_profile_id', $w->provider_profile_id);
            if ($wallet) {
                $wallet->total_withdrawn = (float) $wallet->total_withdrawn + (float) $w->amount;
                $wallet->save();
            }
            return $w;
        });
    }
}
