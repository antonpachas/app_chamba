<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\ProviderProfile;
use App\Models\ProviderWallet;
use App\Models\ServicePayment;
use App\Models\ServiceQuote;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    /**
     * Tasa de comisión vigente. Permite override por categoría;
     * si no hay registro por categoría, usa la regla global (category_id = NULL).
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

        return (float) chamba_setting('commission.default_rate', config('chamba.commission.default_rate', 15.00));
    }

    /**
     * Crea registro de pago en estado "pendiente_revision".
     * El cliente está reportando que ya pagó por Yape/Plin/Transferencia.
     */
    public function registerClientPayment(
        ServiceRequest $request,
        ServiceQuote $quote,
        string $paymentMethod,
        ?string $paymentReference,
        ?string $notes,
    ): ServicePayment {
        return DB::transaction(function () use ($request, $quote, $paymentMethod, $paymentReference, $notes) {
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
            ]);

            $request->update(['status' => 'pagado_pendiente']);

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
            $payment->serviceRequest()->update(['status' => 'en_custodia']);
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
            $payment->serviceRequest()->update(['status' => 'aceptado']);
            return $payment;
        });
    }

    /**
     * Cliente confirma "trabajo terminado" → libera el dinero al wallet del proveedor.
     */
    public function clientConfirmCompleted(ServicePayment $payment): ServicePayment
    {
        return DB::transaction(function () use ($payment) {
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
            $payment->serviceRequest()->update(['status' => 'confirmado']);

            return $payment;
        });
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
     * Admin marca un retiro como pagado al proveedor.
     */
    public function adminPayWithdrawal(\App\Models\WalletWithdrawal $w, ?string $reference, ?string $notes = null): \App\Models\WalletWithdrawal
    {
        return DB::transaction(function () use ($w, $reference, $notes) {
            if ($w->status !== 'solicitado') {
                throw new RuntimeException('Solo se pueden pagar retiros solicitados.');
            }
            $w->update([
                'status' => 'pagado',
                'payout_reference' => $reference,
                'paid_at' => now(),
                'notes' => $notes,
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
