<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Carbon;
use RuntimeException;

final class ListingLifecycleService
{
    public function effectiveDurationDays(ProviderProfile $profile): int
    {
        if ($profile->listing_duration_days_override !== null && (int) $profile->listing_duration_days_override > 0) {
            return (int) $profile->listing_duration_days_override;
        }

        return max(1, (int) chamba_setting('listings.default_duration_days', 5));
    }

    public function maxActiveListings(User $user): int
    {
        $plan = $this->activePlan($user);
        $features = $plan?->features ?? [];

        if (isset($features['max_active_listings']) && $features['max_active_listings'] !== null) {
            return max(0, (int) $features['max_active_listings']);
        }

        if (isset($features['max_services']) && $features['max_services'] !== null) {
            return max(0, (int) $features['max_services']);
        }

        return $plan && $plan->isPro() ? 20 : 1;
    }

    public function activeListingsCount(ProviderProfile $profile, ?int $excludeListingId = null): int
    {
        $q = ProviderService::query()
            ->where('provider_profile_id', $profile->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($excludeListingId !== null) {
            $q->where('id', '!=', $excludeListingId);
        }

        return $q->count();
    }

    public function hasQuota(ProviderProfile $profile, User $user, ?int $excludeListingId = null): bool
    {
        return $this->activeListingsCount($profile, $excludeListingId) < $this->maxActiveListings($user);
    }

    public function publish(ProviderService $listing, ProviderProfile $profile): ProviderService
    {
        $days = $this->effectiveDurationDays($profile);
        $now = now();

        $listing->published_at = $now;
        $listing->expires_at = $now->copy()->addDays($days);
        $listing->duration_days = $days;
        $listing->deactivated_at = null;
        $listing->is_active = true;
        $listing->save();

        return $listing->refresh();
    }

    public function renew(ProviderService $listing, ProviderProfile $profile, User $user): ProviderService
    {
        if (! (bool) chamba_setting('listings.allow_reactivate', true)) {
            throw new RuntimeException('La renovación de anuncios no está habilitada.');
        }

        if (! $this->hasQuota($profile, $user, (int) $listing->id)) {
            throw new RuntimeException('Alcanzaste el cupo de anuncios activos de tu plan. Pausa otro anuncio o mejora tu plan.');
        }

        return $this->publish($listing, $profile);
    }

    /**
     * Activar manualmente o reactivar tras vencimiento.
     */
    public function setActive(ProviderService $listing, ProviderProfile $profile, User $user, bool $active): ProviderService
    {
        if (! $active) {
            $listing->is_active = false;
            $listing->deactivated_at = now();
            $listing->save();

            return $listing->refresh();
        }

        $expired = $listing->expires_at !== null && Carbon::parse($listing->expires_at)->isPast();

        if ($expired || ! $listing->is_active) {
            if (! $this->hasQuota($profile, $user, (int) $listing->id)) {
                throw new RuntimeException('Alcanzaste el cupo de anuncios activos. Renueva cuando tengas espacio disponible.');
            }

            return $this->publish($listing, $profile);
        }

        $listing->is_active = true;
        $listing->deactivated_at = null;
        $listing->save();

        return $listing->refresh();
    }

    public function deactivateExpired(): int
    {
        if (! (bool) chamba_setting('listings.expire_cron_enabled', true)) {
            return 0;
        }

        return ProviderService::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);
    }

    public function isVisible(ProviderService $listing): bool
    {
        if ((bool) $listing->admin_hidden) {
            return false;
        }

        if (! $listing->is_active) {
            return false;
        }

        if ($listing->expires_at === null) {
            return true;
        }

        return Carbon::parse($listing->expires_at)->isFuture();
    }

    public function listingMeta(ProviderService $listing, ProviderProfile $profile, User $user): array
    {
        $expiresAt = $listing->expires_at ? Carbon::parse($listing->expires_at) : null;
        $visible = $this->isVisible($listing);
        $expired = $expiresAt !== null && $expiresAt->isPast();
        $max = $this->maxActiveListings($user);
        $active = $this->activeListingsCount($profile);
        $quotaOk = $this->hasQuota($profile, $user, $expired ? (int) $listing->id : null);

        return [
            'published_at' => $listing->published_at,
            'expires_at' => $listing->expires_at,
            'duration_days' => $listing->duration_days,
            'deactivated_at' => $listing->deactivated_at,
            'is_expired' => $expired,
            'is_visible' => $visible,
            'days_remaining' => $expiresAt && $expiresAt->isFuture()
                ? (int) now()->diffInDays($expiresAt, false)
                : 0,
            'can_renew' => (bool) chamba_setting('listings.allow_reactivate', true) && ($expired || ! $visible) && $quotaOk,
            'quota' => [
                'active' => $active,
                'max' => $max,
                'available' => max(0, $max - $active),
            ],
        ];
    }

    private function activePlan(User $user): ?SubscriptionPlan
    {
        $sub = $user->activeSubscription();

        return $sub?->plan;
    }
}
