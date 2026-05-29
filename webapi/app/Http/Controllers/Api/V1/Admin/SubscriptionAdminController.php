<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\PaginatesAdminResources;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\UserSubscription;
use App\Services\MediaStorageService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class SubscriptionAdminController extends Controller
{
    use PaginatesAdminResources;

    public function __construct(
        private readonly SubscriptionService $subs,
        private readonly MediaStorageService $media,
    ) {}

    public function payments(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'pendiente_revision');

        $paginator = SubscriptionPayment::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['user:id,full_name,email,phone,role', 'subscription.plan'])
            ->orderByDesc('created_at')
            ->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (SubscriptionPayment $p) => [
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
                'created_at' => $p->created_at,
                'user' => $p->user?->only(['id', 'full_name', 'email', 'phone', 'role']),
                'plan' => [
                    'code' => $p->subscription?->plan?->code,
                    'name' => $p->subscription?->plan?->name,
                    'tier' => $p->subscription?->plan?->tier,
                ],
        ]);
    }

    public function confirm(Request $request, int $payment): JsonResponse
    {
        $p = SubscriptionPayment::query()->findOrFail($payment);
        try {
            $this->subs->confirmPayment($p, $request->user());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $p->fresh()->load('subscription.plan', 'user')]);
    }

    public function reject(Request $request, int $payment): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $p = SubscriptionPayment::query()->findOrFail($payment);
        try {
            $this->subs->rejectPayment($p, $request->user(), $data['reason'] ?? null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $p->fresh()->load('subscription.plan', 'user')]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $tier = $request->query('tier');
        $status = $request->query('status');

        $paginator = UserSubscription::query()
            ->with(['user:id,full_name,email,phone,role', 'plan'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tier, fn ($q) => $q->whereHas('plan', fn ($pq) => $pq->where('tier', $tier)))
            ->orderByDesc('updated_at')
            ->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (UserSubscription $row) => $row->toArray());
    }
}
