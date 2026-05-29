<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Services\ListingListFormatter;
use App\Services\ListingPresenterService;
use Illuminate\Http\JsonResponse;

final class HomeFeaturedController extends Controller
{
    public function __construct(
        private readonly ListingPresenterService $presenter,
        private readonly ListingListFormatter $listFormatter,
    ) {}

    public function index(): JsonResponse
    {
        $now = now();

        $services = ProviderService::query()
            ->visible()
            ->where('home_featured', true)
            ->where(function ($q) use ($now): void {
                $q->whereNull('home_featured_starts_at')->orWhere('home_featured_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now): void {
                $q->whereNull('home_featured_ends_at')->orWhere('home_featured_ends_at', '>', $now);
            })
            ->orderBy('home_featured_sort')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $rows = $services->map(function (ProviderService $service): array {
            $prof = $service->providerProfile;
            $uid = (int) ($prof?->user_id ?? 0);
            $isPro = $uid > 0 ? $this->presenter->resolveIsPro($uid) : false;
            $row = $this->presenter->toSearchRow($service, $isPro);
            $row['home_featured'] = true;

            return $row;
        })->all();

        $profileIds = collect($rows)->pluck('provider_profile_id')->filter()->unique()->values();
        $rows = $this->listFormatter->mapList($rows);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
            ],
        ]);
    }
}
