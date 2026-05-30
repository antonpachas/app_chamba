<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreReviewRequest;
use App\Models\ProviderService;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReviewController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function status(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'cliente') {
            return response()->json([
                'message' => 'Solo las cuentas de cliente pueden valorar.',
            ], 403);
        }

        $serviceId = (int) $request->query('provider_service_id', 0);
        if ($serviceId <= 0) {
            return response()->json(['message' => 'Anuncio no indicado.'], 422);
        }

        $service = ProviderService::query()->find($serviceId);
        if ($service === null) {
            return response()->json(['message' => 'Anuncio no encontrado.'], 404);
        }

        $userId = (int) $request->user()->id;
        $profileId = (int) $service->provider_profile_id;
        $already = Review::query()
            ->where('client_user_id', $userId)
            ->where('provider_profile_id', $profileId)
            ->exists();

        return response()->json([
            'can_review' => ! $already,
            'already_reviewed' => $already,
            'provider_profile_id' => $profileId,
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        if ($request->user()?->role !== 'cliente') {
            return response()->json([
                'message' => 'Solo las cuentas de cliente pueden valorar.',
            ], 403);
        }

        $data = $request->validated();
        $userId = (int) $request->user()->id;
        $rating = (int) $data['rating'];
        $comment = $data['comment'] ?? null;

        $serviceRequestId = isset($data['service_request_id']) ? (int) $data['service_request_id'] : 0;

        if ($serviceRequestId <= 0) {
            $serviceRequestId = $this->resolveServiceRequestIdForListingReview(
                $userId,
                (int) $data['provider_service_id'],
            );
        }

        $serviceRequest = ServiceRequest::query()->findOrFail($serviceRequestId);

        if ((int) $serviceRequest->client_user_id !== $userId) {
            return response()->json([
                'message' => 'No autorizado.',
            ], 403);
        }

        $providerProfileId = $this->storedProcedures->createReview(
            $serviceRequestId,
            $userId,
            $rating,
            $comment,
        );

        return response()->json([
            'provider_profile_id' => $providerProfileId,
            'message' => 'Reseña registrada.',
        ], 201);
    }

    private function resolveServiceRequestIdForListingReview(int $userId, int $providerServiceId): int
    {
        $service = ProviderService::query()->findOrFail($providerServiceId);
        $profileId = (int) $service->provider_profile_id;

        if (Review::query()
            ->where('client_user_id', $userId)
            ->where('provider_profile_id', $profileId)
            ->exists()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'message' => 'Ya registraste una valoración para este negocio.',
                ], 422)
            );
        }

        $openRequest = ServiceRequest::query()
            ->where('client_user_id', $userId)
            ->where('provider_service_id', $providerServiceId)
            ->whereDoesntHave('review')
            ->orderByDesc('id')
            ->first();

        if ($openRequest !== null) {
            if ($openRequest->status !== 'cerrado') {
                $this->storedProcedures->closeServiceRequest((int) $openRequest->id);
            }

            return (int) $openRequest->id;
        }

        $requestId = $this->storedProcedures->createServiceRequest(
            $userId,
            $providerServiceId,
            'Valoración desde ficha de anuncio',
            'app',
        );
        $this->storedProcedures->closeServiceRequest($requestId);

        return $requestId;
    }
}
