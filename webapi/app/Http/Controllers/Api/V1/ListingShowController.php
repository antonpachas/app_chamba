<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ProviderVisibilityEvent;
use App\Models\User;
use App\Services\ListingGuestPreviewService;
use App\Services\ListingLifecycleService;
use App\Services\ListingPresenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingShowController extends Controller
{
    public function __construct(
        private readonly ListingPresenterService $presenter,
        private readonly ListingLifecycleService $listings,
        private readonly ListingGuestPreviewService $guestPreview,
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

        $this->trackListingView($request, $service);

        $row = $this->presenter->toSearchRow($service, $isPro);
        $row = $this->applyViewerDistance($request, $row);
        if ($user === null) {
            $row = $this->guestPreview->scrubRow($row);
        }

        return response()->json([
            'data' => $row,
            'meta' => $user === null ? ['guest_preview' => true] : null,
        ]);
    }

    private function trackListingView(Request $request, ProviderService $service): void
    {
        $viewer = $request->user('sanctum');
        $ownerUserId = (int) ($service->providerProfile?->user_id ?? 0);
        if ($viewer && (int) $viewer->id === $ownerUserId) {
            return;
        }

        ProviderVisibilityEvent::query()->create([
            'provider_profile_id' => (int) ($service->provider_profile_id ?? 0),
            'provider_service_id' => (int) $service->id,
            'search_event_id' => null,
            'viewer_user_id' => $viewer?->id,
            'source' => 'listing_detail',
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function applyViewerDistance(Request $request, array $row): array
    {
        $userLat = $request->query('user_lat');
        $userLng = $request->query('user_lng');
        $lat = $row['provider_latitude'] ?? null;
        $lng = $row['provider_longitude'] ?? null;
        if ($userLat === null || $userLng === null || $lat === null || $lng === null) {
            return $row;
        }

        $row['distance_km'] = round($this->haversineKm((float) $userLat, (float) $userLng, (float) $lat, (float) $lng), 2);

        return $row;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function canViewInactive(?User $user, ProviderService $service): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $ownerId = (int) ($service->providerProfile?->user_id ?? 0);

        return (int) $user->id === $ownerId;
    }
}
