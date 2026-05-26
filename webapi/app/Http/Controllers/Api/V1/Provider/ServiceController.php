<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateServiceStatusRequest;
use App\Http\Resources\Api\V1\ProviderServiceResource;
use App\Models\ProviderService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function index(Request $request)
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $services = ProviderService::query()
            ->with(['category', 'images'])
            ->where('provider_profile_id', $profile->id)
            ->orderByDesc('id')
            ->paginate(20);

        return ProviderServiceResource::collection($services)->response();
    }

    public function store(StoreProviderServiceRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Debes crear tu perfil de proveedor primero.',
            ], 422);
        }

        $data = $request->validated();

        $serviceId = $this->storedProcedures->createProviderService(
            (int) $profile->id,
            (int) $data['category_id'],
            $data['title'],
            $data['description'],
            isset($data['base_price']) ? (float) $data['base_price'] : null,
            $data['price_type'],
        );

        $service = ProviderService::query()->with('category')->findOrFail($serviceId);

        return response()->json([
            'data' => ProviderServiceResource::make($service),
        ], 201);
    }

    public function update(UpdateProviderServiceRequest $request, int $service): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $model = ProviderService::query()
            ->where('id', $service)
            ->where('provider_profile_id', $profile->id)
            ->firstOrFail();

        $data = $request->validated();

        $this->storedProcedures->updateProviderService(
            (int) $model->id,
            (int) $data['category_id'],
            $data['title'],
            $data['description'],
            isset($data['base_price']) ? (float) $data['base_price'] : null,
            $data['price_type'],
        );

        $model->refresh()->load('category');

        return response()->json([
            'data' => ProviderServiceResource::make($model),
        ]);
    }

    public function updateStatus(UpdateServiceStatusRequest $request, int $service): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $model = ProviderService::query()
            ->where('id', $service)
            ->where('provider_profile_id', $profile->id)
            ->firstOrFail();

        $this->storedProcedures->setServiceStatus((int) $model->id, (bool) $request->validated('is_active'));

        $model->refresh()->load('category');

        return response()->json([
            'data' => ProviderServiceResource::make($model),
        ]);
    }
}
