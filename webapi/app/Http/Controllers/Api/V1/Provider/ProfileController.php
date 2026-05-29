<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreProviderProfileRequest;
use App\Http\Requests\Api\V1\Provider\UpdateProviderProfileRequest;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Models\ProviderProfile;
use App\Services\BusinessHoursService;
use App\Services\MediaStorageService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly BusinessHoursService $businessHours,
        private readonly MediaStorageService $media,
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
        $profile->razon_social = $data['razon_social'] ?? null;
        $profile->ruc = isset($data['ruc']) && $data['ruc'] !== '' ? $data['ruc'] : null;
        $profile->save();

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
        }

        $profile->razon_social = $data['razon_social'] ?? $profile->razon_social;
        $profile->ruc = array_key_exists('ruc')
            ? ($data['ruc'] !== '' && $data['ruc'] !== null ? $data['ruc'] : null)
            : $profile->ruc;
        $profile->save();

        $profile->refresh();

        return response()->json([
            'data' => ProviderProfileResource::make($profile),
        ]);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Crea tu perfil de negocio primero.'], 404);
        }

        $request->validate([
            'cover' => 'required|file|max:8192',
        ]);

        try {
            $oldPath = $profile->cover_path;
            $newPath = $this->media->storeImage(
                $request->file('cover'),
                MediaStorageService::FOLDER_COVER,
                ['max_w' => 1600, 'max_h' => 600],
            );
            $profile->cover_path = $newPath;
            $profile->save();
            if ($oldPath && $oldPath !== $newPath) {
                $this->media->delete($oldPath);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'cover_path' => $profile->cover_path,
                'cover_url' => $this->media->publicUrl($profile->cover_path),
            ],
        ]);
    }

    public function deleteCover(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Perfil no encontrado.'], 404);
        }

        if ($profile->cover_path) {
            $this->media->delete($profile->cover_path);
            $profile->cover_path = null;
            $profile->save();
        }

        return response()->json(['data' => ['cover_path' => null, 'cover_url' => null]]);
    }
}
