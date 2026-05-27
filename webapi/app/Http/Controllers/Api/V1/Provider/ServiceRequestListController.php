<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ServiceRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestListController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

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
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $rows->map(function (ServiceRequest $r) {
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
                ];
            }),
        ]);
    }

    public function updateStatus(Request $request, int $serviceRequest): JsonResponse
    {
        $allowedTransitions = [
            'nuevo' => ['visto', 'cerrado', 'cancelado'],
            'visto' => ['cerrado', 'cancelado'],
            'cerrado' => [],
            'cancelado' => [],
        ];

        $data = $request->validate([
            'status' => 'required|string|in:visto,cerrado,cancelado',
            'note' => 'nullable|string|max:500',
        ]);

        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }

        $sr = ServiceRequest::query()
            ->whereHas('providerService', fn ($q) => $q->where('provider_profile_id', $profile->id))
            ->findOrFail($serviceRequest);

        $current = (string) $sr->status;
        if (in_array($current, ServiceRequest::LEGACY_STATUSES, true)) {
            $current = 'visto';
        }

        $next = (string) $data['status'];
        if (! isset($allowedTransitions[$current]) || ! in_array($next, $allowedTransitions[$current], true)) {
            return response()->json([
                'message' => "Transición no permitida: {$current} → {$next}.",
            ], 422);
        }

        $payload = ['status' => $next];
        if ($next === 'cancelado') {
            $payload['cancelled_at'] = now();
        }

        $sr->update($payload);

        $this->payments->logRequestEvent(
            $sr, $current, $next, (int) $request->user()->id, 'proveedor', $data['note'] ?? null,
        );

        return response()->json(['data' => ['id' => $sr->id, 'status' => $sr->status]]);
    }
}
