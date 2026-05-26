<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ServicePayment;
use App\Models\ServiceQuote;
use App\Models\ServiceRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): JsonResponse
    {
        $rows = ServicePayment::query()
            ->where('client_user_id', $request->user()->id)
            ->with(['providerProfile.user:id,full_name', 'serviceRequest.providerService:id,title'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ServicePayment $p) => [
                'id' => $p->id,
                'status' => $p->status,
                'amount' => $p->amount,
                'commission_amount' => $p->commission_amount,
                'net_amount' => $p->net_amount,
                'payment_method' => $p->payment_method,
                'payment_reference' => $p->payment_reference,
                'paid_at' => $p->paid_at,
                'confirmed_at' => $p->confirmed_at,
                'released_at' => $p->released_at,
                'service_title' => $p->serviceRequest?->providerService?->title,
                'provider_name' => $p->providerProfile?->business_name ?: $p->providerProfile?->user?->full_name,
                'created_at' => $p->created_at,
            ]),
            'platform_payout_info' => [
                'yape' => chamba_setting('payouts.platform_yape', config('chamba.payouts.platform_yape')),
                'bank_name' => chamba_setting('payouts.platform_bank_name', config('chamba.payouts.platform_bank_name')),
                'bank_account' => chamba_setting('payouts.platform_bank_account', config('chamba.payouts.platform_bank_account')),
                'bank_holder' => chamba_setting('payouts.platform_bank_holder', config('chamba.payouts.platform_bank_holder')),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_quote_id' => 'required|integer|exists:service_quotes,id',
            'payment_method' => 'required|in:yape,plin,transferencia,otro',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $quote = ServiceQuote::query()->findOrFail($data['service_quote_id']);
        $sr = ServiceRequest::query()->findOrFail($quote->service_request_id);
        if ((int) $sr->client_user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        try {
            $payment = $this->payments->registerClientPayment(
                $sr,
                $quote,
                $data['payment_method'],
                $data['payment_reference'] ?? null,
                $data['notes'] ?? null,
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $payment], 201);
    }

    public function confirmCompleted(Request $request, int $payment): JsonResponse
    {
        $p = ServicePayment::query()
            ->where('client_user_id', $request->user()->id)
            ->findOrFail($payment);

        try {
            $this->payments->clientConfirmCompleted($p);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $p->fresh()]);
    }
}
