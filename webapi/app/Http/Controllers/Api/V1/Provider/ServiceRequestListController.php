<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['data' => []]);
        }

        $serviceIds = ProviderService::query()
            ->where('provider_profile_id', $profile->id)
            ->pluck('id');

        $rows = ServiceRequest::query()
            ->whereIn('provider_service_id', $serviceIds)
            ->with([
                'client:id,full_name,phone',
                'providerService:id,title,base_price,price_type,category_id',
                'providerService.category:id,name',
                'quotes',
                'payments',
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(function (ServiceRequest $r) {
                $latestQuote = $r->quotes->sortByDesc('id')->first();
                $activePayment = $r->payments
                    ->whereIn('status', ['pendiente_revision', 'en_custodia', 'liberado'])
                    ->sortByDesc('id')
                    ->first();
                return [
                    'id' => $r->id,
                    'status' => $r->status,
                    'message' => $r->message,
                    'contact_channel' => $r->contact_channel,
                    'created_at' => $r->created_at,
                    'service' => $r->providerService ? [
                        'id' => $r->providerService->id,
                        'title' => $r->providerService->title,
                        'price_type' => $r->providerService->price_type,
                        'base_price' => $r->providerService->base_price,
                        'category' => $r->providerService->category?->only(['id', 'name']),
                    ] : null,
                    'client' => $r->client ? [
                        'id' => $r->client->id,
                        'name' => $r->client->full_name,
                        'phone' => $r->client->phone,
                    ] : null,
                    'latest_quote' => $latestQuote?->only(['id', 'amount', 'currency', 'estimated_days', 'notes', 'status', 'created_at']),
                    'active_payment' => $activePayment?->only(['id', 'status', 'amount', 'net_amount', 'commission_amount', 'commission_rate', 'payment_method']),
                ];
            }),
        ]);
    }

    public function updateStatus(Request $request, int $serviceRequest): JsonResponse
    {
        $allowedTransitions = [
            'nuevo' => ['contactado', 'cancelado'],
            'contactado' => ['cotizado', 'cancelado'],
            'aceptado' => ['en_progreso', 'cancelado'],
            'en_custodia' => ['en_progreso'],
            'en_progreso' => ['terminado', 'cancelado'],
            'terminado' => [],
        ];

        $data = $request->validate([
            'status' => 'required|string',
        ]);

        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }

        $sr = ServiceRequest::query()
            ->whereHas('providerService', fn ($q) => $q->where('provider_profile_id', $profile->id))
            ->findOrFail($serviceRequest);

        $current = (string) $sr->status;
        $next = (string) $data['status'];
        if (! isset($allowedTransitions[$current]) || ! in_array($next, $allowedTransitions[$current], true)) {
            return response()->json([
                'message' => "Transición no permitida: {$current} → {$next}.",
            ], 422);
        }

        $sr->update(['status' => $next]);
        return response()->json(['data' => ['id' => $sr->id, 'status' => $sr->status]]);
    }
}
