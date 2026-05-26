<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\MediaStorageService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subs,
        private readonly MediaStorageService $media,
    ) {}

    public function plans(Request $request): JsonResponse
    {
        $audience = $request->query('audience');

        $rows = SubscriptionPlan::query()
            ->where('is_active', true)
            ->when(in_array($audience, ['proveedor', 'cliente'], true), fn ($q) => $q->where('audience', $audience))
            ->orderBy('audience')
            ->orderByRaw("FIELD(tier,'free','pro','premium')")
            ->get();

        return response()->json([
            'data' => $rows,
            'platform_yape' => chamba_setting('payouts.platform_yape', config('chamba.payouts.platform_yape')),
            'platform_bank_name' => chamba_setting('payouts.platform_bank_name', config('chamba.payouts.platform_bank_name')),
            'platform_bank_account' => chamba_setting('payouts.platform_bank_account', config('chamba.payouts.platform_bank_account')),
            'platform_bank_holder' => chamba_setting('payouts.platform_bank_holder', config('chamba.payouts.platform_bank_holder')),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $sub = $this->subs->ensureSubscription($user);
        $sub->load('plan');

        $payments = $user->subscriptionPayments()
            ->with(['subscription.plan:id,code,name,tier,audience'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'payment_method' => $p->payment_method,
                'payment_reference' => $p->payment_reference,
                'status' => $p->status,
                'paid_at' => $p->paid_at,
                'confirmed_at' => $p->confirmed_at,
                'period_start' => $p->period_start,
                'period_end' => $p->period_end,
                'rejection_reason' => $p->rejection_reason,
                'notes' => $p->notes,
                'proof_image_path' => $p->proof_image_path,
                'proof_image_url' => $this->media->publicUrl($p->proof_image_path),
                'plan' => $p->subscription?->plan ? [
                    'code' => $p->subscription->plan->code,
                    'name' => $p->subscription->plan->name,
                    'tier' => $p->subscription->plan->tier,
                ] : null,
                'created_at' => $p->created_at,
            ]);

        $contactsThisMonth = $user->role === 'proveedor'
            ? $this->subs->providerContactsThisMonth($user)
            : null;

        $providerFreePlan = \App\Models\SubscriptionPlan::query()
            ->where('audience', 'proveedor')->where('tier', 'free')->first();
        $freeLimitFromPlan = $providerFreePlan?->features['contacts_per_month'] ?? null;
        $freeLimit = $freeLimitFromPlan !== null
            ? (int) $freeLimitFromPlan
            : (int) config('chamba.subscriptions.provider.free_contacts_per_month', 3);

        return response()->json([
            'subscription' => [
                'id' => $sub->id,
                'plan' => $sub->plan,
                'status' => $sub->status,
                'is_pro' => $sub->isPro(),
                'in_trial' => $sub->inTrial(),
                'trial_ends_at' => $sub->trial_ends_at,
                'current_period_end' => $sub->current_period_end,
                'next_billing_at' => $sub->next_billing_at,
                'auto_renew' => $sub->auto_renew,
            ],
            'payments' => $payments,
            'usage' => [
                'contacts_this_month' => $contactsThisMonth,
                'free_contacts_limit' => $freeLimit,
            ],
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_code' => 'required|string|exists:subscription_plans,code',
            'payment_method' => 'nullable|in:yape,plin,transferencia',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'proof' => 'nullable|file|max:5120',
        ]);

        try {
            if ($request->hasFile('proof')) {
                $data['proof_image_path'] = $this->media->storeImage(
                    $request->file('proof'),
                    MediaStorageService::FOLDER_PAYMENT,
                    ['max_w' => 1600, 'max_h' => 1600]
                );
            }

            $payment = $this->subs->registerPayment($request->user(), $data['plan_code'], $data);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => array_merge($payment->toArray(), [
                'proof_image_url' => $this->media->publicUrl($payment->proof_image_path),
            ]),
        ], 201);
    }

    public function cancel(Request $request): JsonResponse
    {
        $sub = $request->user()->activeSubscription();
        if (! $sub) {
            return response()->json(['message' => 'Sin suscripción activa.'], 404);
        }
        $this->subs->cancel($sub);

        return response()->json(['data' => $sub->fresh()->load('plan')]);
    }
}
