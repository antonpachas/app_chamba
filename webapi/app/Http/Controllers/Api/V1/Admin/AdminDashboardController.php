<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderWallet;
use App\Models\ServicePayment;
use App\Models\ServiceRequest;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\WalletWithdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class AdminDashboardController extends Controller
{
    public function show(): JsonResponse
    {
        $usersByRole = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $payments = ServicePayment::query()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount),0) as gross, COALESCE(SUM(commission_amount),0) as commission, COALESCE(SUM(net_amount),0) as net')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $withdrawals = WalletWithdrawal::query()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $walletsTotalBalance = (float) (ProviderWallet::query()->sum('balance') ?? 0);

        $requestsActive = ServiceRequest::query()
            ->whereIn('status', ['nuevo', 'contactado', 'cotizado', 'aceptado', 'pagado_pendiente', 'en_custodia', 'en_progreso', 'terminado'])
            ->count();

        $latestPayments = ServicePayment::query()
            ->with([
                'client:id,full_name,email',
                'providerProfile.user:id,full_name',
                'serviceRequest.providerService:id,title',
            ])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (ServicePayment $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => $p->amount,
                'commission_amount' => $p->commission_amount,
                'net_amount' => $p->net_amount,
                'created_at' => $p->created_at,
                'client_name' => $p->client?->full_name,
                'provider_name' => $p->providerProfile?->business_name ?: $p->providerProfile?->user?->full_name,
                'service_title' => $p->serviceRequest?->providerService?->title,
            ]);

        $totalCommissionEarned = (float) (ServicePayment::query()
            ->whereIn('status', ['en_custodia', 'liberado'])
            ->sum('commission_amount') ?? 0);

        $totalGrossInEscrow = (float) (ServicePayment::query()
            ->where('status', 'en_custodia')
            ->sum('amount') ?? 0);

        $subscriptionStats = UserSubscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'user_subscriptions.plan_id')
            ->whereIn('user_subscriptions.status', ['trial', 'active'])
            ->selectRaw('subscription_plans.tier, COUNT(*) as cnt')
            ->groupBy('subscription_plans.tier')
            ->pluck('cnt', 'tier');

        $subPaymentsByStatus = SubscriptionPayment::query()
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount),0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $mrr = (float) (UserSubscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'user_subscriptions.plan_id')
            ->where('user_subscriptions.status', 'active')
            ->where('subscription_plans.price', '>', 0)
            ->sum('subscription_plans.price'));

        $latestSubPayments = SubscriptionPayment::query()
            ->with(['user:id,full_name,email,role', 'subscription.plan:id,code,name,tier'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (SubscriptionPayment $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => $p->amount,
                'created_at' => $p->created_at,
                'user_name' => $p->user?->full_name,
                'user_role' => $p->user?->role,
                'plan_name' => $p->subscription?->plan?->name,
                'plan_tier' => $p->subscription?->plan?->tier,
            ]);

        return response()->json([
            'data' => [
                'kpis' => [
                    'users_clients' => (int) ($usersByRole['cliente'] ?? 0),
                    'users_providers' => (int) ($usersByRole['proveedor'] ?? 0),
                    'users_admins' => (int) ($usersByRole['admin'] ?? 0),
                    'providers_with_profile' => (int) ProviderProfile::query()->count(),
                    'requests_active' => $requestsActive,
                    'payments_pending_review' => (int) ($payments['pendiente_revision']->cnt ?? 0),
                    'payments_in_escrow' => (int) ($payments['en_custodia']->cnt ?? 0),
                    'payments_released' => (int) ($payments['liberado']->cnt ?? 0),
                    'gross_in_escrow' => $totalGrossInEscrow,
                    'commission_total' => $totalCommissionEarned,
                    'wallets_total_balance' => $walletsTotalBalance,
                    'withdrawals_pending' => (int) ($withdrawals['solicitado']->cnt ?? 0),
                    'withdrawals_pending_amount' => (float) ($withdrawals['solicitado']->total_amount ?? 0),

                    // Suscripciones
                    'subs_pro_active' => (int) ($subscriptionStats['pro'] ?? 0),
                    'subs_premium_active' => (int) ($subscriptionStats['premium'] ?? 0),
                    'subs_free_active' => (int) ($subscriptionStats['free'] ?? 0),
                    'subs_payments_pending' => (int) ($subPaymentsByStatus['pendiente_revision']->cnt ?? 0),
                    'subs_payments_pending_amount' => (float) ($subPaymentsByStatus['pendiente_revision']->total_amount ?? 0),
                    'mrr' => $mrr,
                ],
                'latest_payments' => $latestPayments,
                'latest_subscription_payments' => $latestSubPayments,
                'features' => [
                    'escrow' => (bool) chamba_setting('features.escrow', config('chamba.features.escrow')),
                    'subscriptions' => (bool) chamba_setting('features.subscriptions', config('chamba.features.subscriptions')),
                ],
            ],
        ]);
    }
}
