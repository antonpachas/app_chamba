<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceRequestController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $data = $request->validated();

        $id = $this->storedProcedures->createServiceRequest(
            (int) $request->user()->id,
            (int) $data['provider_service_id'],
            $data['message'] ?? null,
            $data['contact_channel'],
        );

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
