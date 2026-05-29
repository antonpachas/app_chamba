<?php

namespace App\Services;

use App\Models\ProviderService;
use App\Models\ServiceImage;
use App\Models\UserSubscription;
use Illuminate\Support\Collection;

final class ListingPresenterService
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly ListingLifecycleService $listings,
        private readonly BusinessHoursService $businessHours,
        private readonly ListingLocationFieldsService $listingLocation,
        private readonly ListingPublicIdService $publicIds,
    ) {}

    /**
     * Formato compatible con búsqueda / detalle del SPA.
     *
     * @return array<string, mixed>
     */
    public function toSearchRow(ProviderService $service, bool $isPro = false): array
    {
        $service->loadMissing([
            'category:id,name',
            'district:id,name,province_id,latitude,longitude,ubigeo',
            'district.province:id,name,department_id',
            'district.province.department:id,name',
            'providerProfile:id,user_id,business_name,district_id,address_text,business_hours,whatsapp,contact_phone,avg_rating,total_reviews,is_verified',
            'providerProfile.user:id,full_name',
            'providerProfile.district:id,name,province_id,latitude,longitude',
            'providerProfile.district.province:id,name,department_id',
            'providerProfile.district.province.department:id,name',
            'images',
        ]);

        $prof = $service->providerProfile;
        $images = $service->images ?? collect();
        $imageUrls = $images->map(fn ($i) => $this->media->publicUrl($i->path))->values()->all();

        $meta = $prof && $prof->user
            ? $this->listings->listingMeta($service, $prof, $prof->user)
            : [];

        $departmentId = $service->department_id ?? $service->district?->province?->department_id;
        $provinceId = $service->province_id ?? $service->district?->province_id;

        $row = array_merge([
            'service_id' => $service->id,
            'listing_ref' => $this->publicIds->encode((int) $service->id),
            'title' => $service->title,
            'description' => $service->description,
            'base_price' => $service->base_price,
            'price_type' => $service->price_type,
            'category_id' => $service->category_id,
            'category_name' => $service->category?->name,
            'provider_profile_id' => $prof?->id,
            'provider_user_id' => $prof?->user_id,
            'provider_name' => $prof?->business_name ?: $prof?->user?->full_name,
            'whatsapp' => $prof?->whatsapp,
            'contact_phone' => $prof?->contact_phone,
            'listing_type' => $service->listing_type ?? ProviderService::TYPE_PRESENCIA,
            'location_label' => $service->location_label,
            'address_text' => $service->address_text ?: $prof?->address_text,
            'avg_rating' => $prof?->avg_rating,
            'total_reviews' => $prof?->total_reviews,
            'is_verified' => (bool) ($prof?->is_verified ?? false),
            'department_id' => $departmentId,
            'province_id' => $provinceId,
            'district_id' => $service->district_id ?? $prof?->district_id,
            'ubigeo' => $service->ubigeo ?? $service->district?->ubigeo,
            'latitude' => $service->latitude,
            'longitude' => $service->longitude,
            'district_name' => $service->district?->name ?? $prof?->district?->name,
            'province_name' => $service->district?->province?->name ?? $prof?->district?->province?->name,
            'department_name' => $service->district?->province?->department?->name ?? $prof?->district?->province?->department?->name,
            'provider_latitude' => $service->latitude ?? $service->district?->latitude ?? $prof?->district?->latitude,
            'provider_longitude' => $service->longitude ?? $service->district?->longitude ?? $prof?->district?->longitude,
            'distance_km' => null,
            'images' => $imageUrls,
            'cover_image_url' => $imageUrls[0] ?? null,
            'is_pro' => $isPro,
            'is_active' => (bool) $service->is_active,
            'is_visible' => $this->listings->isVisible($service),
            'is_expired' => $meta['is_expired'] ?? false,
        ], array_filter([
            'published_at' => $service->published_at,
            'expires_at' => $service->expires_at,
        ], static fn ($v) => $v !== null));

        return $this->appendBusinessHours($row, $prof?->business_hours, null, $service->business_hours);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function appendBusinessHours(array $row, mixed $profileHours, mixed $locationHours = null, mixed $listingHours = null): array
    {
        $resolved = $this->businessHours->resolveForListing(
            is_array($profileHours) ? $profileHours : null,
            is_array($locationHours) ? $locationHours : null,
            is_array($listingHours) ? $listingHours : null,
        );
        if ($resolved === null) {
            return $row;
        }
        $summary = $this->businessHours->summarize($resolved);
        $row['business_hours'] = $summary['schedule'];
        $row['is_open_now'] = $summary['is_open_now'];
        $row['hours_summary'] = $summary['hours_summary'];
        if (is_array($listingHours) && $listingHours !== []) {
            $row['hours_source'] = 'listing';
        } elseif (is_array($locationHours) && $locationHours !== []) {
            $row['hours_source'] = 'location';
        }

        return $row;
    }

    public function resolveIsPro(int $providerUserId): bool
    {
        return UserSubscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'user_subscriptions.plan_id')
            ->where('user_subscriptions.user_id', $providerUserId)
            ->whereIn('user_subscriptions.status', ['trial', 'active'])
            ->whereIn('subscription_plans.tier', ['pro', 'premium'])
            ->exists();
    }

    /**
     * @param  Collection<int, ServiceImage>  $images
     * @return list<string>
     */
    public function imageUrls(Collection $images): array
    {
        return $images->map(fn ($i) => $this->media->publicUrl($i->path))->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function enrichSearchRows(array $rows): array
    {
        $ids = collect($rows)->pluck('service_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return $rows;
        }

        $services = ProviderService::query()
            ->with(['district.province.department'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($rows as &$row) {
            $sid = (int) ($row['service_id'] ?? 0);
            $service = $services->get($sid);
            if ($service) {
                $row = $this->listingLocation->applyListingGeoToSearchRow($service, $row);
            }
        }
        unset($row);

        return $rows;
    }
}
