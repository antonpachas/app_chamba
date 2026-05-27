<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceSearchRequest;
use App\Models\Category;
use App\Models\District;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\ServiceImage;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\MediaStorageService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ServiceSearchController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
        private readonly MediaStorageService $media,
    ) {}

    public function index(ServiceSearchRequest $request): JsonResponse
    {
        $data = $request->validated();

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $districtId = isset($data['district_id']) ? (int) $data['district_id'] : null;
        $keyword = isset($data['keyword']) && $data['keyword'] !== '' ? (string) $data['keyword'] : null;
        $userLat = isset($data['user_lat']) ? (float) $data['user_lat'] : null;
        $userLng = isset($data['user_lng']) ? (float) $data['user_lng'] : null;
        $radiusKm = isset($data['radius_km']) ? (float) $data['radius_km'] : null;

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

        $profileIds = collect($rows)->pluck('provider_profile_id')->filter()->unique()->values();
        $proUserIds = collect();
        if ($profileIds->isNotEmpty()) {
            $userIds = ProviderProfile::query()->whereIn('id', $profileIds)->pluck('user_id', 'id');
            $proUserIds = UserSubscription::query()
                ->join('subscription_plans', 'subscription_plans.id', '=', 'user_subscriptions.plan_id')
                ->whereIn('user_subscriptions.user_id', $userIds->values())
                ->whereIn('user_subscriptions.status', ['trial', 'active'])
                ->whereIn('subscription_plans.tier', ['pro', 'premium'])
                ->pluck('user_subscriptions.user_id');

            foreach ($rows as &$row) {
                $pid = (int) ($row['provider_profile_id'] ?? 0);
                $uid = (int) ($userIds[$pid] ?? 0);
                $row['is_pro'] = $proUserIds->contains($uid);
            }
            unset($row);

            usort($rows, function (array $a, array $b) {
                $rank = (int) ($b['is_pro'] ?? 0) - (int) ($a['is_pro'] ?? 0);
                return $rank;
            });
        }

        $serviceIds = collect($rows)->pluck('service_id')->filter()->unique()->values();
        if ($serviceIds->isNotEmpty()) {
            $imagesByService = ServiceImage::query()
                ->whereIn('provider_service_id', $serviceIds)
                ->orderBy('sort_order')
                ->get(['provider_service_id', 'path'])
                ->groupBy('provider_service_id');

            foreach ($rows as &$row) {
                $sid = (int) ($row['service_id'] ?? 0);
                $imgs = $imagesByService->get($sid, collect());
                $row['images'] = $imgs->map(fn ($i) => $this->media->publicUrl($i->path))->all();
                $row['cover_image_url'] = $imgs->isNotEmpty() ? $this->media->publicUrl($imgs->first()->path) : null;
            }
            unset($row);
        }

        return response()->json(['data' => $rows]);
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

        if ($providerIdsWithBranch->isEmpty()) {
            return $rows;
        }

        $query = ProviderService::query()
            ->whereIn('provider_profile_id', $providerIdsWithBranch)
            ->where('is_active', 1)
            ->with([
                'category:id,name',
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
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }
        if (! empty($existingServiceIds)) {
            $query->whereNotIn('id', $existingServiceIds);
        }

        $extra = $query->limit(50)->get();

        foreach ($extra as $svc) {
            $prof = $svc->providerProfile;
            $branch = ProviderLocation::query()
                ->where('provider_profile_id', $prof?->id)
                ->where('district_id', $districtId)
                ->where('is_active', 1)
                ->first();
            $rows[] = [
                'service_id' => $svc->id,
                'provider_profile_id' => $prof?->id,
                'provider_user_id' => $prof?->user_id,
                'service_title' => $svc->title,
                'service_description' => $svc->description,
                'service_base_price' => $svc->base_price,
                'service_price_type' => $svc->price_type,
                'category_id' => $svc->category_id,
                'category_name' => $svc->category?->name,
                'business_name' => $prof?->business_name ?: $prof?->user?->full_name,
                'avg_rating' => $prof?->avg_rating,
                'total_reviews' => $prof?->total_reviews,
                'is_verified' => $prof?->is_verified,
                'district_id' => $branch?->district_id ?? $prof?->district_id,
                'district_name' => $branch?->district?->name ?? $prof?->district?->name,
                'province_name' => $prof?->district?->province?->name,
                'department_name' => $prof?->district?->province?->department?->name,
                'provider_latitude' => $branch?->latitude,
                'provider_longitude' => $branch?->longitude,
                'distance_km' => null,
                'matched_by_location' => true,
            ];
        }

        return $rows;
    }
}
