<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ListingLifecycleService;
use App\Services\ListingPresenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingShowController extends Controller
{
    public function __construct(
        private readonly ListingPresenterService $presenter,
        private readonly ListingLifecycleService $listings,
    ) {}

    public function show(Request $request, int $listing): JsonResponse
    {
        $service = ProviderService::query()->find($listing);
        if ($service === null) {
            return response()->json(['message' => 'Anuncio no encontrado.'], 404);
        }

        $user = $request->user('sanctum');
        if (! $this->listings->isVisible($service) && ! $this->canViewInactive($user, $service)) {
            return response()->json(['message' => 'Anuncio no disponible.'], 404);
        }

        $providerUserId = (int) ($service->providerProfile?->user_id ?? 0);
        $isPro = $providerUserId > 0 && $this->presenter->resolveIsPro($providerUserId);

        return response()->json([
            'data' => $this->presenter->toSearchRow($service, $isPro),
        ]);
    }

    private function canViewInactive(?User $user, ProviderService $service): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $profileId = (int) ($service->provider_profile_id ?? 0);
        if ($user->role === 'proveedor' && (int) ($user->providerProfile?->id ?? 0) === $profileId) {
            return true;
        }

        return ServiceRequest::query()
            ->where('client_user_id', (int) $user->id)
            ->where('provider_service_id', (int) $service->id)
            ->exists();
    }
}
