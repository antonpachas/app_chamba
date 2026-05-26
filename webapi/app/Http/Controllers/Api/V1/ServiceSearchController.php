<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceSearchRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceImage;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\MediaStorageService;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;

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
}
