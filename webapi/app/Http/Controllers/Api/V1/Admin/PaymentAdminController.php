<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServicePayment;
use App\Models\WalletWithdrawal;
use App\Services\MediaStorageService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly MediaStorageService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'pendiente_revision');

        $rows = ServicePayment::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with([
                'client:id,full_name,email,phone',
                'providerProfile.user:id,full_name,email',
                'serviceRequest.providerService:id,title',
            ])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ServicePayment $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => $p->amount,
                'commission_amount' => $p->commission_amount,
                'commission_rate' => $p->commission_rate,
                'net_amount' => $p->net_amount,
                'payment_method' => $p->payment_method,
                'payment_reference' => $p->payment_reference,
                'paid_at' => $p->paid_at,
                'confirmed_at' => $p->confirmed_at,
                'released_at' => $p->released_at,
                'created_at' => $p->created_at,
                'notes' => $p->notes,
                'proof_image_path' => $p->proof_image_path,
                'proof_image_url' => $this->media->publicUrl($p->proof_image_path),
                'client' => $p->client?->only(['id', 'full_name', 'email', 'phone']),
                'provider' => [
                    'profile_id' => $p->providerProfile?->id,
                    'name' => $p->providerProfile?->business_name ?: $p->providerProfile?->user?->full_name,
                ],
                'service_title' => $p->serviceRequest?->providerService?->title,
            ]),
        ]);
    }

    public function confirm(int $payment): JsonResponse
    {
        $p = ServicePayment::query()->findOrFail($payment);
        try {
            $this->payments->adminConfirmPayment($p);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['data' => $p->fresh()]);
    }

    public function reject(Request $request, int $payment): JsonResponse
    {
        $data = $request->validate(['notes' => 'nullable|string|max:500']);
        $p = ServicePayment::query()->findOrFail($payment);
        try {
            $this->payments->adminRejectPayment($p, $data['notes'] ?? null);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['data' => $p->fresh()]);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'solicitado');
        $rows = WalletWithdrawal::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['providerProfile.user:id,full_name,email'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (WalletWithdrawal $w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'payout_method' => $w->payout_method,
                'payout_reference' => $w->payout_reference,
                'status' => $w->status,
                'created_at' => $w->created_at,
                'paid_at' => $w->paid_at,
                'notes' => $w->notes,
                'proof_image_path' => $w->proof_image_path,
                'proof_image_url' => $this->media->publicUrl($w->proof_image_path),
                'provider' => [
                    'profile_id' => $w->providerProfile?->id,
                    'name' => $w->providerProfile?->business_name ?: $w->providerProfile?->user?->full_name,
                    'bank_name' => $w->providerProfile?->id ? optional(\App\Models\ProviderWallet::query()->firstWhere('provider_profile_id', $w->providerProfile->id))->bank_name : null,
                    'bank_account_number' => $w->providerProfile?->id ? optional(\App\Models\ProviderWallet::query()->firstWhere('provider_profile_id', $w->providerProfile->id))->bank_account_number : null,
                    'bank_account_holder' => $w->providerProfile?->id ? optional(\App\Models\ProviderWallet::query()->firstWhere('provider_profile_id', $w->providerProfile->id))->bank_account_holder : null,
                    'yape_phone' => $w->providerProfile?->id ? optional(\App\Models\ProviderWallet::query()->firstWhere('provider_profile_id', $w->providerProfile->id))->yape_phone : null,
                ],
            ]),
        ]);
    }

    public function payWithdrawal(Request $request, int $withdrawal): JsonResponse
    {
        $data = $request->validate([
            'payout_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'proof' => 'nullable|file|max:5120',
        ]);
        $w = WalletWithdrawal::query()->findOrFail($withdrawal);
        try {
            $proofPath = null;
            if ($request->hasFile('proof')) {
                $proofPath = $this->media->storeImage(
                    $request->file('proof'),
                    MediaStorageService::FOLDER_PAYMENT,
                    ['max_w' => 1600, 'max_h' => 1600],
                );
            }
            $this->payments->adminPayWithdrawal(
                $w,
                $data['payout_reference'] ?? null,
                $data['notes'] ?? null,
                $proofPath ? ['proof_image_path' => $proofPath] : [],
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'data' => array_merge($w->fresh()->toArray(), [
                'proof_image_url' => $this->media->publicUrl($w->fresh()->proof_image_path),
            ]),
        ]);
    }
}
