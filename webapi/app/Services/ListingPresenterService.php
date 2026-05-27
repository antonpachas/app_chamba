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
            'providerProfile:id,user_id,business_name,district_id,address_text,whatsapp,contact_phone,avg_rating,total_reviews,is_verified',
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

        return array_merge([
            'service_id' => $service->id,
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
            'address_text' => $prof?->address_text,
            'avg_rating' => $prof?->avg_rating,
            'total_reviews' => $prof?->total_reviews,
            'is_verified' => (bool) ($prof?->is_verified ?? false),
            'district_id' => $prof?->district_id,
            'district_name' => $prof?->district?->name,
            'province_name' => $prof?->district?->province?->name,
            'department_name' => $prof?->district?->province?->department?->name,
            'provider_latitude' => $prof?->district?->latitude,
            'provider_longitude' => $prof?->district?->longitude,
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
}
