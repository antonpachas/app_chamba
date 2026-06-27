<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServicePayment;
use App\Models\WalletWithdrawal;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class WalletController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }
        $wallet = $this->payments->ensureWallet($profile);

        $recentPayments = ServicePayment::query()
            ->where('provider_profile_id', $profile->id)
            ->with('serviceRequest.providerService:id,title')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (ServicePayment $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => $p->amount,
                'commission_amount' => $p->commission_amount,
                'net_amount' => $p->net_amount,
                'released_at' => $p->released_at,
                'service_title' => $p->serviceRequest?->providerService?->title,
            ]);

        $withdrawals = WalletWithdrawal::query()
            ->where('provider_profile_id', $profile->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => [
                'wallet' => $wallet,
                'recent_payments' => $recentPayments,
                'withdrawals' => $withdrawals,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:150',
            'yape_phone' => 'nullable|string|max:20',
        ]);

        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }
        $wallet = $this->payments->ensureWallet($profile);
        $wallet->fill($data)->save();

        return response()->json(['data' => $wallet]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method' => 'required|in:yape,plin,transferencia',
        ]);

        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }

        try {
            $w = $this->payments->requestWithdrawal($profile, (float) $data['amount'], $data['payout_method']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $w], 201);
    }
}
