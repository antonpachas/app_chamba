<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreProviderProfileRequest;
use App\Http\Requests\Api\V1\Provider\UpdateProviderProfileRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\ProviderProfile;
use App\Services\BusinessHoursService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly BusinessHoursService $businessHours,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        return response()->json([
            'data' => ProviderProfileResource::make($profile),
        ]);
    }

    public function store(StoreProviderProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $profileId = $this->storedProcedures->createProviderProfile(
            (int) $request->user()->id,
            $data['business_name'] ?? null,
            $data['description'] ?? null,
            $data['whatsapp'] ?? null,
            $data['contact_phone'] ?? null,
            $data['address_text'] ?? null,
            (int) $data['district_id'],
        );

        $profile = ProviderProfile::query()->findOrFail($profileId);

        return response()->json([
            'data' => ProviderProfileResource::make($profile),
        ], 201);
    }

    public function update(UpdateProviderProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $data = $request->validated();

        $this->storedProcedures->updateProviderProfile(
            (int) $profile->id,
            $data['business_name'] ?? null,
            $data['description'] ?? null,
            $data['whatsapp'] ?? null,
            $data['contact_phone'] ?? null,
            $data['address_text'] ?? null,
            (int) $data['district_id'],
        );

        if (array_key_exists('business_hours', $data)) {
            $profile->business_hours = $this->businessHours->normalizeInput($data['business_hours']);
            $profile->save();
        }

        $profile->refresh();

        return response()->json([
            'data' => ProviderProfileResource::make($profile),
        ]);
    }
}
