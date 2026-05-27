<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreServiceRequestRequest;
use App\Models\ProviderService;
use App\Models\ServiceRequest;
use App\Services\NotificationService;
use App\Services\StoredProcedureService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly SubscriptionService $subscriptions,
        private readonly NotificationService $notifications,
    ) {}

    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $data = $request->validated();

        $service = ProviderService::with('providerProfile.user')->findOrFail((int) $data['provider_service_id']);
        $providerUser = $service->providerProfile?->user;

        if (! $providerUser) {
            return response()->json(['message' => 'Servicio no disponible.'], 404);
        }

        if (! $this->subscriptions->clientCanCreateRequest($request->user())) {
            return response()->json([
                'message' => 'Alcanzaste el cupo gratuito de contactos del mes. Mejora a Premium para contactos ilimitados.',
                'code' => 'client_free_limit_reached',
            ], 422);
        }

        if (! $this->subscriptions->providerCanReceiveRequest($providerUser)) {
            return response()->json([
                'message' => 'Este negocio alcanzó el cupo gratuito de contactos del mes. Intenta con otro anuncio o más tarde.',
                'code' => 'provider_free_limit_reached',
            ], 422);
        }

        $id = $this->storedProcedures->createServiceRequest(
            (int) $request->user()->id,
            (int) $data['provider_service_id'],
            $data['message'] ?? null,
            $data['contact_channel'],
        );

        $this->subscriptions->recordContact(
            $request->user(),
            (int) $service->provider_profile_id,
            (int) $providerUser->id,
            (int) $service->id,
            (int) $id,
            'solicitud',
        );

        $serviceRequest = ServiceRequest::query()->find($id);
        if ($serviceRequest !== null) {
            $this->notifications->notifyProviderNewRequest($serviceRequest);
        }

        return response()->json([
            'service_request_id' => $id,
        ], 201);
    }

    public function close(Request $request, int $serviceRequest): JsonResponse
    {
        $model = ServiceRequest::query()->findOrFail($serviceRequest);

        if ((int) $model->client_user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado.',
            ], 403);
        }

        $this->storedProcedures->closeServiceRequest((int) $model->id);

        return response()->json([
            'message' => 'Solicitud cerrada.',
        ]);
    }
}
