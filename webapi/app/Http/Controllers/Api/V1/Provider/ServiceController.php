<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateServiceStatusRequest;
use App\Http\Resources\Api\V1\ProviderServiceResource;
use App\Models\ProviderService;
use App\Services\ListingLifecycleService;
use App\Services\ListingLocationService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ServiceController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly ListingLifecycleService $listings,
        private readonly ListingLocationService $listingLocations,
    ) {}

    public function index(Request $request)
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $profileId = (int) $profile->id;

        $services = ProviderService::query()
            ->with(['category', 'images', 'locations'])
            ->where('provider_profile_id', $profileId)
            ->orderByDesc('id')
            ->paginate(20);

        $user = $request->user();
        $active = $this->listings->activeListingsCount($profile);
        $max = $this->listings->maxActiveListings($user);

        return ProviderServiceResource::collection($services)->additional([
            'quota' => [
                'active' => $active,
                'max' => $max,
                'available' => max(0, $max - $active),
            ],
            'default_duration_days' => $this->listings->effectiveDurationDays($profile),
        ])->response();
    }

    public function store(StoreProviderServiceRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Debes crear tu perfil de proveedor primero.',
            ], 422);
        }

        if (! $this->listings->hasQuota($profile, $request->user())) {
            return response()->json([
                'message' => 'Alcanzaste el cupo de anuncios activos de tu plan. Pausa uno o mejora tu plan.',
                'code' => 'listing_quota_reached',
            ], 422);
        }

        $data = $request->validated();

        try {
            $serviceId = $this->storedProcedures->createProviderService(
                (int) $profile->id,
                (int) $data['category_id'],
                $data['title'],
                $data['description'],
                isset($data['base_price']) ? (float) $data['base_price'] : null,
                $data['price_type'],
            );

            $service = ProviderService::query()->with('category')->findOrFail($serviceId);
            $this->listings->publish($service, $profile);
            $this->listingLocations->sync($service, $profile, $data['location_ids'] ?? null);
            $service->refresh()->load(['category', 'locations']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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

        $profileId = (int) $profile->id;

        $model = ProviderService::query()
            ->where('id', $service)
            ->where('provider_profile_id', $profileId)
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

        if (array_key_exists('location_ids', $data)) {
            $this->listingLocations->sync($model, $profile, $data['location_ids']);
        }

        $model->refresh()->load(['category', 'locations']);

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

        $profileId = (int) $profile->id;

        $model = ProviderService::query()
            ->where('id', $service)
            ->where('provider_profile_id', $profileId)
            ->firstOrFail();

        $isActive = (bool) $request->validated('is_active');

        try {
            $model = $this->listings->setActive($model, $profile, $request->user(), $isActive);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'listing_quota_reached',
            ], 422);
        }

        $model->load('category');

        return response()->json([
            'data' => ProviderServiceResource::make($model),
        ]);
    }

    public function renew(Request $request, int $service): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }

        $profileId = (int) $profile->id;

        $model = ProviderService::query()
            ->where('id', $service)
            ->where('provider_profile_id', $profileId)
            ->firstOrFail();

        try {
            $model = $this->listings->renew($model, $profile, $request->user());
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'listing_quota_reached',
            ], 422);
        }

        $model->load('category');

        return response()->json([
            'data' => ProviderServiceResource::make($model),
            'message' => 'Anuncio renovado.',
        ]);
    }
}
