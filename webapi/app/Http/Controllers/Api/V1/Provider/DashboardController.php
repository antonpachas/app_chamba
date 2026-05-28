<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderVisibilityEvent;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $rows = $this->storedProcedures->getProviderDashboard((int) $profile->id);
        $row = $rows[0] ?? null;

        if ($row === null) {
            return response()->json([
                'message' => 'No se pudo obtener el resumen.',
            ], 500);
        }

        $profileId = (int) $profile->id;
        $from = now()->subDays(30);
        $events30 = ProviderVisibilityEvent::query()
            ->where('provider_profile_id', $profileId)
            ->where('created_at', '>=', $from)
            ->selectRaw("
                SUM(CASE WHEN source = 'search_result' THEN 1 ELSE 0 END) AS search_impressions_30d,
                SUM(CASE WHEN source = 'public_profile' THEN 1 ELSE 0 END) AS profile_views_30d,
                SUM(CASE WHEN source = 'listing_detail' THEN 1 ELSE 0 END) AS listing_views_30d
            ")
            ->first();

        $eventsAll = ProviderVisibilityEvent::query()
            ->where('provider_profile_id', $profileId)
            ->selectRaw("
                SUM(CASE WHEN source = 'search_result' THEN 1 ELSE 0 END) AS search_impressions_total,
                SUM(CASE WHEN source = 'public_profile' THEN 1 ELSE 0 END) AS profile_views_total,
                SUM(CASE WHEN source = 'listing_detail' THEN 1 ELSE 0 END) AS listing_views_total
            ")
            ->first();

        return response()->json([
            'data' => array_merge((array) $row, [
                'search_impressions_30d' => (int) ($events30?->search_impressions_30d ?? 0),
                'profile_views_30d' => (int) ($events30?->profile_views_30d ?? 0),
                'listing_views_30d' => (int) ($events30?->listing_views_30d ?? 0),
                'search_impressions_total' => (int) ($eventsAll?->search_impressions_total ?? 0),
                'profile_views_total' => (int) ($eventsAll?->profile_views_total ?? 0),
                'listing_views_total' => (int) ($eventsAll?->listing_views_total ?? 0),
            ]),
        ]);
    }
}
