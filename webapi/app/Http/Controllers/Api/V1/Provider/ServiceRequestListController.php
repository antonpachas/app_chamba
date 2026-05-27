<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ServiceRequest;
use App\Services\MediaStorageService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestListController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly MediaStorageService $media,
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
                'quotes',
                'payments',
                'evidence',
                'events',
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
                    'delivered_at' => $r->delivered_at,
                    'auto_release_at' => $r->auto_release_at,
                    'disputed_at' => $r->disputed_at,
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
                    'active_payment' => $activePayment ? array_merge(
                        $activePayment->only(['id', 'status', 'amount', 'net_amount', 'commission_amount', 'commission_rate', 'payment_method']),
                        ['proof_image_url' => $this->media->publicUrl($activePayment->proof_image_path)],
                    ) : null,
                    'evidence' => $r->evidence->map(fn ($e) => [
                        'id' => $e->id,
                        'url' => $this->media->publicUrl($e->path),
                        'caption' => $e->caption,
                        'sort_order' => $e->sort_order,
                    ])->values(),
                    'timeline' => $r->events->map(fn ($e) => [
                        'id' => $e->id,
                        'from_status' => $e->from_status,
                        'to_status' => $e->to_status,
                        'actor_role' => $e->actor_role,
                        'note' => $e->note,
                        'created_at' => $e->created_at,
                    ])->values(),
                ];
            }),
        ]);
    }

    public function updateStatus(Request $request, int $serviceRequest): JsonResponse
    {
        // Máquina de estados del lado proveedor.
        // Transiciones que requieren evidencia (entregado) o que cambian dinero (confirmado, liberado)
        // se hacen por endpoints especializados (deliver, dispute, confirm).
        $allowedTransitions = [
            'nuevo' => ['contactado', 'cancelado'],
            'contactado' => ['cotizado', 'cancelado'],
            'cotizado' => ['cancelado'],
            'aceptado' => ['en_progreso', 'cancelado'],
            'en_custodia' => ['en_progreso'],
            'en_progreso' => ['cancelado'], // terminado/entregado se hace por endpoint deliver con evidencia
            'entregado' => [],
            'terminado' => [],
            'disputado' => [],
        ];

        $data = $request->validate([
            'status' => 'required|string',
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
            $sr, $current, $next, $request->user()->id, 'proveedor', $data['note'] ?? null,
        );
        return response()->json(['data' => ['id' => $sr->id, 'status' => $sr->status]]);
    }
}
