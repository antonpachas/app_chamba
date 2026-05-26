<?php

namespace App\Services;

use App\Models\ContactEvent;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    /**
     * Devuelve la suscripción activa o la crea como Free según el rol.
     */
    public function ensureSubscription(User $user): UserSubscription
    {
        $existing = $user->activeSubscription();
        if ($existing) {
            return $existing;
        }

        $audience = $user->role === 'proveedor' ? 'proveedor' : 'cliente';
        $freePlan = SubscriptionPlan::where('audience', $audience)->where('tier', 'free')->firstOrFail();

        return UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'auto_renew' => false,
        ]);
    }

    /**
     * Asigna trial Pro al registrarse (solo proveedores y si hay trial_days configurado).
     * Si ya tiene una suscripción Free activa, la convierte en trial Pro.
     */
    public function startProviderTrial(User $user): ?UserSubscription
    {
        if ($user->role !== 'proveedor') {
            return null;
        }

        $days = (int) chamba_setting('subscriptions.provider_trial_days', config('chamba.subscriptions.provider.trial_days', 0));
        if ($days <= 0) {
            return $this->ensureSubscription($user);
        }

        $existing = $user->activeSubscription();
        if ($existing && $existing->plan?->isPro()) {
            return $existing;
        }

        $proPlan = SubscriptionPlan::where('code', 'provider_pro')->firstOrFail();
        $now = CarbonImmutable::now();
        $trialEnd = $now->addDays($days);

        if ($existing) {
            $existing->update([
                'plan_id' => $proPlan->id,
                'status' => 'trial',
                'current_period_start' => $now,
                'current_period_end' => $trialEnd,
                'trial_ends_at' => $trialEnd,
                'next_billing_at' => $trialEnd,
                'auto_renew' => false,
                'cancelled_at' => null,
            ]);
            return $existing->refresh();
        }

        return UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $proPlan->id,
            'status' => 'trial',
            'current_period_start' => $now,
            'current_period_end' => $trialEnd,
            'trial_ends_at' => $trialEnd,
            'next_billing_at' => $trialEnd,
            'auto_renew' => false,
        ]);
    }

    /**
     * Cliente registra un pago Yape de su próximo periodo de membresía.
     */
    public function registerPayment(User $user, string $planCode, array $paymentData): SubscriptionPayment
    {
        $plan = SubscriptionPlan::where('code', $planCode)->where('is_active', true)->firstOrFail();

        if ($plan->audience === 'proveedor' && $user->role !== 'proveedor') {
            throw new RuntimeException('Este plan es exclusivo para proveedores.');
        }
        if ($plan->audience === 'cliente' && $user->role !== 'cliente') {
            throw new RuntimeException('Este plan es exclusivo para clientes.');
        }
        if (! $plan->isPro()) {
            throw new RuntimeException('No se requiere pago para este plan.');
        }

        return DB::transaction(function () use ($user, $plan, $paymentData) {
            $sub = $user->activeSubscription();
            if (! $sub) {
                $sub = UserSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => 'pending_payment',
                    'auto_renew' => false,
                ]);
            } else {
                $sub->update([
                    'plan_id' => $plan->id,
                    'status' => $sub->status === 'trial' ? 'trial' : 'pending_payment',
                ]);
            }

            return SubscriptionPayment::create([
                'subscription_id' => $sub->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'payment_method' => $paymentData['payment_method'] ?? 'yape',
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'status' => 'pendiente_revision',
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'notes' => $paymentData['notes'] ?? null,
                'proof_image_path' => $paymentData['proof_image_path'] ?? null,
            ]);
        });
    }

    /**
     * Admin confirma el pago: activa o renueva la suscripción.
     */
    public function confirmPayment(SubscriptionPayment $payment, User $admin): SubscriptionPayment
    {
        if ($payment->status !== 'pendiente_revision') {
            throw new RuntimeException('Este pago ya fue procesado.');
        }

        return DB::transaction(function () use ($payment, $admin) {
            $sub = $payment->subscription()->lockForUpdate()->first();
            $now = CarbonImmutable::now();

            $start = $sub->current_period_end && $sub->current_period_end->isFuture()
                ? CarbonImmutable::instance($sub->current_period_end)
                : $now;
            $end = $start->addMonth();

            $payment->update([
                'status' => 'confirmado',
                'confirmed_at' => $now,
                'confirmed_by' => $admin->id,
                'period_start' => $start,
                'period_end' => $end,
            ]);

            $sub->update([
                'status' => 'active',
                'current_period_start' => $start,
                'current_period_end' => $end,
                'next_billing_at' => $end,
                'cancelled_at' => null,
            ]);

            return $payment->refresh();
        });
    }

    public function rejectPayment(SubscriptionPayment $payment, User $admin, ?string $reason = null): SubscriptionPayment
    {
        if ($payment->status !== 'pendiente_revision') {
            throw new RuntimeException('Este pago ya fue procesado.');
        }

        $payment->update([
            'status' => 'rechazado',
            'confirmed_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);

        return $payment->refresh();
    }

    public function cancel(UserSubscription $sub): UserSubscription
    {
        $sub->update([
            'auto_renew' => false,
            'cancelled_at' => now(),
        ]);

        return $sub;
    }

    /**
     * Marca como past_due/expired las suscripciones cuyo periodo ya venció.
     */
    public function expireDueSubscriptions(): int
    {
        $graceDays = (int) chamba_setting('subscriptions.grace_days', config('chamba.subscriptions.grace_days', 5));
        $now = now();

        $passedTrials = UserSubscription::where('status', 'trial')
            ->where('trial_ends_at', '<', $now)
            ->get();
        foreach ($passedTrials as $sub) {
            $this->downgradeToFree($sub);
        }

        $pastDue = UserSubscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $now->copy()->subDays($graceDays))
            ->get();
        foreach ($pastDue as $sub) {
            $this->downgradeToFree($sub);
        }

        return $passedTrials->count() + $pastDue->count();
    }

    public function downgradeToFree(UserSubscription $sub): UserSubscription
    {
        $audience = $sub->plan?->audience ?? ($sub->user?->role === 'proveedor' ? 'proveedor' : 'cliente');
        $freePlan = SubscriptionPlan::where('audience', $audience)->where('tier', 'free')->first();

        $sub->update([
            'plan_id' => $freePlan?->id ?? $sub->plan_id,
            'status' => 'active',
            'current_period_start' => null,
            'current_period_end' => null,
            'trial_ends_at' => null,
            'next_billing_at' => null,
            'auto_renew' => false,
        ]);

        return $sub->refresh();
    }

    /**
     * Cuenta cuántos contactos consumió un proveedor en el mes corriente.
     */
    public function providerContactsThisMonth(User $providerUser): int
    {
        return ContactEvent::where('provider_user_id', $providerUser->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * Determina si un proveedor puede recibir un nuevo contacto este mes.
     */
    public function providerCanReceiveContact(User $providerUser): bool
    {
        $sub = $providerUser->activeSubscription();
        $plan = $sub?->plan;

        if ($plan && $plan->isPro()) {
            return true;
        }

        $features = $plan?->features ?? [];
        $limit = isset($features['contacts_per_month']) && $features['contacts_per_month'] !== null
            ? (int) $features['contacts_per_month']
            : (int) config('chamba.subscriptions.provider.free_contacts_per_month', 3);

        return $this->providerContactsThisMonth($providerUser) < $limit;
    }

    public function recordContact(User $clientUser, int $providerProfileId, int $providerUserId, ?int $serviceId = null, ?int $requestId = null, string $kind = 'contacto'): ContactEvent
    {
        return ContactEvent::create([
            'client_user_id' => $clientUser->id,
            'provider_profile_id' => $providerProfileId,
            'provider_user_id' => $providerUserId,
            'provider_service_id' => $serviceId,
            'service_request_id' => $requestId,
            'kind' => $kind,
            'created_at' => now(),
        ]);
    }
}
