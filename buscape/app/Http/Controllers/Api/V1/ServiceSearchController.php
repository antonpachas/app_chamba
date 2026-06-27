<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceSearchRequest;
use App\Models\Category;
use App\Models\District;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use Illuminate\Support\Collection;
use App\Models\ProviderService;
use App\Models\ProviderVisibilityEvent;
use App\Models\SearchEvent;
use App\Models\ServiceImage;
use App\Services\ListingGuestPreviewService;
use App\Services\ListingListFormatter;
use App\Services\ListingPresenterService;
use App\Services\MediaStorageService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ServiceSearchController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly MediaStorageService $media,
        private readonly ListingGuestPreviewService $guestPreview,
        private readonly ListingListFormatter $listFormatter,
        private readonly ListingPresenterService $presenter,
    ) {}

    public function index(ServiceSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $districtId = isset($data['district_id']) ? (int) $data['district_id'] : null;
        $ubigeo = isset($data['ubigeo']) ? (string) $data['ubigeo'] : null;
        if ($ubigeo && ! $districtId) {
            $districtId = District::query()->where('ubigeo', $ubigeo)->value('id');
        }
        $keyword = isset($data['keyword']) && $data['keyword'] !== '' ? (string) $data['keyword'] : null;
        $userLat = isset($data['user_lat']) ? (float) $data['user_lat'] : null;
        $userLng = isset($data['user_lng']) ? (float) $data['user_lng'] : null;
        $radiusKm = isset($data['radius_km']) ? (float) $data['radius_km'] : null;
        if ($userLat !== null && $userLng !== null && $radiusKm === null) {
            $radiusKm = 25.0;
        }

        $rows = $this->storedProcedures->searchProviderServices(
            $categoryId,
            $districtId,
            $keyword,
            $userLat,
            $userLng,
            $radiusKm,
        );

        $rows = array_map(static fn (object $row): array => (array) $row, $rows);

        // Merge: si filtra por distrito, también incluimos servicios cuyo proveedor tiene SEDE
        // activa en ese distrito (aunque su perfil principal sea otro).
        if ($districtId !== null) {
            $rows = $this->mergeMultiLocationResults($rows, $districtId, $categoryId, $keyword);
        }

        $rows = $this->filterVisibleListings($rows);
        $rows = $this->presenter->enrichSearchRows($rows);

        foreach ($rows as &$row) {
            $row['is_pro'] = false;
        }
        unset($row);

        $minRating = isset($data['min_rating']) ? (float) $data['min_rating'] : null;
        if ($minRating !== null) {
            $rows = array_values(array_filter($rows, function (array $row) use ($minRating): bool {
                $avg = (float) ($row['avg_rating'] ?? 0);
                $reviews = (int) ($row['total_reviews'] ?? 0);

                return $reviews > 0 && $avg >= $minRating;
            }));
        }

        $sort = isset($data['sort']) ? (string) $data['sort'] : null;
        $hasGps = $userLat !== null && $userLng !== null;
        if ($sort === null) {
            $sort = $hasGps ? 'nearest' : 'recent';
        }
        $rows = $this->sortListings($rows, $sort, $hasGps);

        $totalCount = count($rows);
        $isGuest = $request->user('sanctum') === null;
        $guestMax = $isGuest ? $this->guestPreview->maxSearchResults() : null;
        $guestLimited = $isGuest && $totalCount > $guestMax;

        if ($isGuest) {
            $rows = array_slice($rows, 0, $guestMax);
        }

        $perPage = max(6, min(48, (int) ($data['per_page'] ?? 24)));
        $effectiveTotal = count($rows);
        $lastPage = max(1, (int) ceil($effectiveTotal / $perPage));
        $page = max(1, min((int) ($data['page'] ?? 1), $lastPage));
        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($rows, $offset, $perPage);

        $pageProfileIds = collect($pageRows)->pluck('provider_profile_id')->filter()->unique()->values();
        $pageServiceIds = collect($pageRows)->pluck('service_id')->filter()->unique()->values();
        [$hoursByProfile, $hoursByLocation, $primaryLocByProfile] = $this->loadHoursContext($pageProfileIds);
        $hoursByService = $this->loadListingHours($pageServiceIds);
        $pageRows = $this->listFormatter->mapList($pageRows, $hoursByProfile, $hoursByLocation, $primaryLocByProfile, $hoursByService);

        if ($pageServiceIds->isNotEmpty()) {
            $imagesByService = ServiceImage::query()
                ->whereIn('provider_service_id', $pageServiceIds)
                ->orderBy('sort_order')
                ->get(['provider_service_id', 'path'])
                ->groupBy('provider_service_id');

            foreach ($pageRows as &$row) {
                $sid = (int) ($row['service_id'] ?? 0);
                $imgs = $imagesByService->get($sid, collect());
                $row['images'] = $imgs->map(fn ($i) => $this->media->publicUrl($i->path))->all();
                $row['cover_image_url'] = $imgs->isNotEmpty() ? $this->media->publicUrl($imgs->first()->path) : null;
            }
            unset($row);
        }

        $searchEvent = SearchEvent::create([
            'user_id' => $request->user('sanctum')?->id,
            'category_id' => $categoryId,
            'query' => $keyword,
            'district_id' => $districtId,
            'ubigeo' => $ubigeo,
            'user_lat' => $userLat,
            'user_lng' => $userLng,
            'radius_km' => $radiusKm,
            'results_count' => $totalCount,
            'created_at' => now(),
        ]);

        $this->trackSearchImpressions($pageRows, (int) ($request->user('sanctum')?->id ?? 0), (int) $searchEvent->id);

        $meta = [
            'sort' => $sort,
            'has_gps' => $hasGps,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $effectiveTotal,
                'total_matched' => $totalCount,
            ],
        ];

        if ($isGuest) {
            $pageRows = array_map(function (array $row): array {
                $row = $this->guestPreview->scrubRow($row);
                unset($row['whatsapp'], $row['contact_phone']);

                return $row;
            }, $pageRows);
            $meta = array_merge($meta, [
                'guest_preview' => true,
                'guest_limit' => $guestMax,
                'guest_total' => $totalCount,
                'guest_limited' => $guestLimited,
            ]);
        }

        return response()->json(['data' => $pageRows, 'meta' => $meta]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function trackSearchImpressions(array $rows, int $viewerUserId, int $searchEventId): void
    {
        $profileIds = collect($rows)
            ->pluck('provider_profile_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($profileIds->isEmpty()) {
            return;
        }

        $now = now();
        $payload = $profileIds->map(fn (int $providerProfileId) => [
            'provider_profile_id' => $providerProfileId,
            'provider_service_id' => null,
            'search_event_id' => $searchEventId > 0 ? $searchEventId : null,
            'viewer_user_id' => $viewerUserId > 0 ? $viewerUserId : null,
            'source' => 'search_result',
            'created_at' => $now,
        ])->all();

        ProviderVisibilityEvent::query()->insert($payload);
    }

    /**
     * Agrega resultados de servicios cuyo proveedor tiene una sede activa en el distrito buscado,
     * aunque no sea su distrito principal. Deduplica por service_id.
     */
    private function mergeMultiLocationResults(array $rows, int $districtId, ?int $categoryId, ?string $keyword): array
    {
        $existingServiceIds = collect($rows)->pluck('service_id')->filter()->values()->all();

        $providerIdsWithBranch = ProviderLocation::query()
            ->where('district_id', $districtId)
            ->where('is_active', 1)
            ->pluck('provider_profile_id')
            ->unique();

        $query = ProviderService::query()
            ->visible()
            ->where(function ($q) use ($districtId, $providerIdsWithBranch) {
                $q->where('district_id', $districtId);
                if ($providerIdsWithBranch->isNotEmpty()) {
                    $q->orWhereIn('provider_profile_id', $providerIdsWithBranch);
                }
            })
            ->with([
                'category:id,name',
                'district:id,name,province_id,latitude,longitude',
                'district.province:id,name,department_id',
                'district.province.department:id,name',
                'providerProfile:id,user_id,business_name,district_id,address_text,avg_rating,total_reviews,is_verified',
                'providerProfile.user:id,full_name',
                'providerProfile.district:id,name,province_id',
                'providerProfile.district.province:id,name,department_id',
                'providerProfile.district.province.department:id,name',
            ]);
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        if ($keyword !== null) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('address_text', 'like', "%{$keyword}%")
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$keyword}%"))
                  ->orWhereHas('providerProfile', fn ($pq) => $pq->where('business_name', 'like', "%{$keyword}%")
                      ->orWhere('address_text', 'like', "%{$keyword}%"));
            });
        }
        if (! empty($existingServiceIds)) {
            $query->whereNotIn('id', $existingServiceIds);
        }

        $extra = $query->orderByDesc('published_at')->orderByDesc('id')->limit(50)->get();

        foreach ($extra as $svc) {
            $prof = $svc->providerProfile;
            $branch = ProviderLocation::query()
                ->where('provider_profile_id', $prof?->id)
                ->where('district_id', $districtId)
                ->where('is_active', 1)
                ->first();
            $lat = $svc->latitude ?? $branch?->latitude;
            $lng = $svc->longitude ?? $branch?->longitude;
            $rows[] = [
                'service_id' => $svc->id,
                'listing_type' => $svc->listing_type ?? 'presencia',
                'title' => $svc->title,
                'provider_profile_id' => $prof?->id,
                'provider_user_id' => $prof?->user_id,
                'service_title' => $svc->title,
                'description' => $svc->description,
                'service_description' => $svc->description,
                'base_price' => $svc->base_price,
                'price_type' => $svc->price_type,
                'service_base_price' => $svc->base_price,
                'service_price_type' => $svc->price_type,
                'category_id' => $svc->category_id,
                'category_name' => $svc->category?->name,
                'provider_name' => $prof?->business_name ?: $prof?->user?->full_name,
                'business_name' => $prof?->business_name ?: $prof?->user?->full_name,
                'avg_rating' => $prof?->avg_rating,
                'total_reviews' => $prof?->total_reviews,
                'is_verified' => $prof?->is_verified,
                'district_id' => $svc->district_id ?? $branch?->district_id ?? $prof?->district_id,
                'district_name' => $svc->district?->name ?? $branch?->district?->name ?? $prof?->district?->name,
                'province_name' => $svc->district?->province?->name ?? $prof?->district?->province?->name,
                'department_name' => $svc->district?->province?->department?->name ?? $prof?->district?->province?->department?->name,
                'address_text' => $svc->address_text ?: $prof?->address_text,
                'location_label' => $svc->location_label,
                'provider_latitude' => $lat,
                'provider_longitude' => $lng,
                'distance_km' => null,
                'matched_by_location' => true,
            ];
        }

        return $rows;
    }

    /**
     * Orden: Pro primero, luego los más recientes (publicados / creados).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortListings(array $rows, string $sort, bool $hasGps): array
    {
        if ($rows === []) {
            return $rows;
        }

        $ids = collect($rows)->pluck('service_id')->filter()->unique()->values();
        $timestamps = ProviderService::query()
            ->whereIn('id', $ids)
            ->get(['id', 'published_at', 'created_at'])
            ->keyBy('id');

        foreach ($rows as &$row) {
            $sid = (int) ($row['service_id'] ?? 0);
            $listing = $timestamps->get($sid);
            $row['published_at'] = $listing?->published_at?->toIso8601String();
            $row['created_at'] = $listing?->created_at?->toIso8601String();
        }
        unset($row);

        usort($rows, function (array $a, array $b) use ($sort, $hasGps): int {
            if ($sort === 'nearest' && $hasGps) {
                $da = $this->distanceSortKey($a);
                $db = $this->distanceSortKey($b);
                if ($da !== $db) {
                    return $da <=> $db;
                }
            }

            if ($sort === 'rating') {
                $ra = $this->ratingSortKey($a);
                $rb = $this->ratingSortKey($b);
                if ($ra !== $rb) {
                    return $rb <=> $ra;
                }
            }

            $pro = (int) ($b['is_pro'] ?? 0) <=> (int) ($a['is_pro'] ?? 0);
            if ($pro !== 0) {
                return $pro;
            }

            $boost = (int) (($b['listing_type'] ?? '') === 'promocion') <=> (int) (($a['listing_type'] ?? '') === 'promocion');
            if ($boost !== 0) {
                return $boost;
            }

            $ta = strtotime((string) ($a['published_at'] ?? $a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string) ($b['published_at'] ?? $b['created_at'] ?? '')) ?: 0;
            if ($tb !== $ta) {
                return $tb <=> $ta;
            }

            return (int) ($b['service_id'] ?? 0) <=> (int) ($a['service_id'] ?? 0);
        });

        return $rows;
    }

    /**
     * @param  Collection<int, int>  $profileIds
     * @return array{0: array<int, array|null>, 1: array<int, array|null>, 2: array<int, int>}
     */
    private function loadHoursContext(Collection $profileIds): array
    {
        if ($profileIds->isEmpty()) {
            return [[], [], []];
        }

        $hoursByProfile = ProviderProfile::query()
            ->whereIn('id', $profileIds)
            ->get(['id', 'business_hours'])
            ->mapWithKeys(fn (ProviderProfile $p) => [$p->id => $p->business_hours])
            ->all();

        $primaryLocations = ProviderLocation::query()
            ->whereIn('provider_profile_id', $profileIds)
            ->where('is_active', 1)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get(['id', 'provider_profile_id', 'business_hours']);

        $primaryLocByProfile = [];
        $hoursByLocation = [];
        foreach ($primaryLocations as $loc) {
            $pid = (int) $loc->provider_profile_id;
            if (! isset($primaryLocByProfile[$pid])) {
                $primaryLocByProfile[$pid] = (int) $loc->id;
                $hoursByLocation[(int) $loc->id] = $loc->business_hours;
            }
        }

        return [$hoursByProfile, $hoursByLocation, $primaryLocByProfile];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $serviceIds
     * @return array<int, array|null>
     */
    private function loadListingHours($serviceIds): array
    {
        $ids = collect($serviceIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return ProviderService::query()
            ->whereIn('id', $ids)
            ->get(['id', 'business_hours'])
            ->mapWithKeys(fn (ProviderService $s) => [(int) $s->id => $s->business_hours])
            ->all();
    }

    private function distanceSortKey(array $row): float
    {
        $d = $row['distance_km'] ?? null;
        if ($d === null || $d === '') {
            return 99999.0;
        }

        return (float) $d;
    }

    private function ratingSortKey(array $row): float
    {
        $reviews = (int) ($row['total_reviews'] ?? 0);
        if ($reviews <= 0) {
            return -1.0;
        }

        return (float) ($row['avg_rating'] ?? 0);
    }

    /**
     * Excluye anuncios pausados o vencidos (Busca PE).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function filterVisibleListings(array $rows): array
    {
        $ids = collect($rows)->pluck('service_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return $rows;
        }

        $visibleIds = ProviderService::query()
            ->visible()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->flip();

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => $visibleIds->has((int) ($row['service_id'] ?? 0)),
        ));
    }
}
