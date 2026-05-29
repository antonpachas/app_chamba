<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateProviderServiceRequest;
use App\Http\Requests\Api\V1\Provider\UpdateServiceStatusRequest;
use App\Http\Resources\Api\V1\ProviderServiceResource;
use App\Models\ProviderService;
use App\Services\BusinessHoursService;
use App\Services\ListingLifecycleService;
use App\Services\ListingLocationFieldsService;
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
        private readonly ListingLocationFieldsService $listingLocation,
        private readonly BusinessHoursService $businessHours,
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
            ->with(['category', 'images', 'district'])
            ->where('provider_profile_id', $profileId)
            ->orderByDesc('id')
            ->paginate(20);

        $user = $request->user();

        return ProviderServiceResource::collection($services)->additional([
            'quota' => $this->listings->quotaPayload($profile, $user),
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

        $data = $request->validated();
        $type = $data['listing_type'] ?? ProviderService::TYPE_PRESENCIA;
        $user = $request->user();

        try {
            $this->listings->assertCanCreateType($user, $type);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $this->listings->hasQuotaForType($profile, $user, $type)) {
            return response()->json([
                'message' => 'Alcanzaste el cupo de fichas activas para tu plan. Pausa una o mejora a Pro.',
                'code' => 'listing_quota_reached',
            ], 422);
        }

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
            $service->listing_type = $type;
            $service->save();
            $this->listingLocation->applyToListing($service, $data);
            if (array_key_exists('business_hours', $data)) {
                $service->business_hours = $this->businessHours->normalizeInput($data['business_hours']);
                $service->save();
            }
            $this->listings->publish($service, $profile);
            $service->refresh()->load(['category', 'district']);
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

        if (array_key_exists('listing_type', $data) && $data['listing_type'] !== $model->listing_type) {
            try {
                $this->listings->assertCanCreateType($request->user(), $data['listing_type']);
            } catch (Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $model->listing_type = $data['listing_type'];
            $model->save();
            if ($model->isPresencia()) {
                $model->expires_at = null;
                $model->duration_days = null;
                $model->save();
            }
        }

        $this->listingLocation->applyToListing($model, $data);

        if (array_key_exists('business_hours', $data)) {
            $model->business_hours = $this->businessHours->normalizeInput($data['business_hours']);
            $model->save();
        }

        $model->refresh()->load(['category', 'district']);

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

        if ($isActive && (bool) $model->admin_hidden) {
            $reason = trim((string) ($model->admin_hidden_reason ?? ''));
            $reasonMsg = $reason !== '' ? " Motivo: {$reason}" : '';
            return response()->json([
                'message' => 'Este anuncio fue ocultado por moderación. Contacta a soporte de Busca PE.'.$reasonMsg,
                'code' => 'listing_admin_hidden',
            ], 422);
        }

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
