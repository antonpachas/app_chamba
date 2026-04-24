<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreReviewRequest;
use App\Models\ServiceRequest;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;

final class ReviewController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();

        $serviceRequest = ServiceRequest::query()->findOrFail($data['service_request_id']);

        if ((int) $serviceRequest->client_user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado.',
            ], 403);
        }

        $providerProfileId = $this->storedProcedures->createReview(
            (int) $data['service_request_id'],
            (int) $request->user()->id,
            (int) $data['rating'],
            $data['comment'] ?? null,
        );

        return response()->json([
            'provider_profile_id' => $providerProfileId,
            'message' => 'Reseña registrada.',
        ], 201);
    }
}
