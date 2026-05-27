<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderLocation;
use App\Services\ProviderLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class LocationController extends Controller
{
    public function __construct(private readonly ProviderLocationService $locations) {}

    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['data' => [], 'max_locations' => 0]);
        }

        $rows = ProviderLocation::query()
            ->where('provider_profile_id', $profile->id)
            ->with(['district:id,name,province_id', 'district.province:id,name,department_id', 'district.province.department:id,name'])
            ->orderByDesc('is_primary')
            ->orderBy('label')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ProviderLocation $l) => $this->formatLocation($l)),
            'max_locations' => $this->locations->maxLocationsFor($request->user()),
            'active_count' => $rows->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Crea primero tu perfil de proveedor.'], 422);
        }

        try {
            $loc = $this->locations->create($profile, $request->user(), $data);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $loc->load(['district.province.department']);
        return response()->json(['data' => $this->formatLocation($loc)], 201);
    }

    public function update(Request $request, int $location): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }
        $loc = ProviderLocation::query()
            ->where('provider_profile_id', $profile->id)
            ->findOrFail($location);

        $data = $this->validatedData($request, partial: true);
        try {
            $this->locations->update($loc, $data);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $loc->load(['district.province.department']);
        return response()->json(['data' => $this->formatLocation($loc)]);
    }

    public function destroy(Request $request, int $location): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }
        $loc = ProviderLocation::query()
            ->where('provider_profile_id', $profile->id)
            ->findOrFail($location);

        $this->locations->delete($loc);
        return response()->json(['data' => ['id' => $location, 'deleted' => true]]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $rules = [
            'label' => ($partial ? 'sometimes|' : '').'required|string|max:100',
            'address_text' => 'nullable|string|max:255',
            'department_id' => 'nullable|integer|exists:departments,id',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'district_id' => ($partial ? 'sometimes|' : '').'required|integer|exists:districts,id',
            'ubigeo' => 'nullable|string|size:6',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_primary' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
        return $request->validate($rules);
    }

    private function formatLocation(ProviderLocation $l): array
    {
        return [
            'id' => $l->id,
            'label' => $l->label,
            'address_text' => $l->address_text,
            'department_id' => $l->department_id ?? $l->district?->province?->department_id,
            'province_id' => $l->province_id ?? $l->district?->province_id,
            'district_id' => $l->district_id,
            'ubigeo' => $l->ubigeo ?? $l->district?->ubigeo,
            'district_name' => $l->district?->name,
            'province_name' => $l->district?->province?->name,
            'department_name' => $l->district?->province?->department?->name,
            'latitude' => $l->latitude,
            'longitude' => $l->longitude,
            'is_primary' => (bool) $l->is_primary,
            'is_active' => (bool) $l->is_active,
            'created_at' => $l->created_at,
        ];
    }
}
