<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\SubscriptionPlan;
use App\Models\User;
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

    public function maxPresenciaListings(User $user): int
    {
        $plan = $this->activePlan($user);
        $features = $plan?->features ?? [];

        if (isset($features['max_presencia'])) {
            return max(0, (int) $features['max_presencia']);
        }

        $isPro = $plan && $plan->isPro();

        return (int) chamba_setting(
            $isPro ? 'listings.presencia.max_pro' : 'listings.presencia.max_free',
            $isPro ? 10 : 2,
        );
    }

    public function maxPromocionListings(User $user): int
    {
        $plan = $this->activePlan($user);
        $features = $plan?->features ?? [];

        if (isset($features['max_promocion'])) {
            return max(0, (int) $features['max_promocion']);
        }

        $isPro = $plan && $plan->isPro();

        return (int) chamba_setting(
            $isPro ? 'listings.promocion.max_pro' : 'listings.promocion.max_free',
            $isPro ? 3 : 0,
        );
    }

    /** @deprecated Use maxPresencia + maxPromocion */
    public function maxActiveListings(User $user): int
    {
        return $this->maxPresenciaListings($user) + $this->maxPromocionListings($user);
    }

    public function activeListingsCount(ProviderProfile $profile, ?int $excludeListingId = null): int
    {
        return $this->activeCountByType($profile, ProviderService::TYPE_PRESENCIA, $excludeListingId)
            + $this->activeCountByType($profile, ProviderService::TYPE_PROMOCION, $excludeListingId);
    }

    public function activeCountByType(ProviderProfile $profile, string $type, ?int $excludeListingId = null): int
    {
        $q = ProviderService::query()
            ->where('provider_profile_id', $profile->id)
            ->where('listing_type', $type)
            ->where('is_active', true);

        if ($type === ProviderService::TYPE_PROMOCION) {
            $q->whereNotNull('expires_at')->where('expires_at', '>', now());
        }

        if ($excludeListingId !== null) {
            $q->where('id', '!=', $excludeListingId);
        }

        return $q->count();
    }

    public function hasQuota(ProviderProfile $profile, User $user, ?int $excludeListingId = null): bool
    {
        return $this->hasQuotaForType($profile, $user, ProviderService::TYPE_PRESENCIA, $excludeListingId)
            || $this->hasQuotaForType($profile, $user, ProviderService::TYPE_PROMOCION, $excludeListingId);
    }

    public function hasQuotaForType(ProviderProfile $profile, User $user, string $type, ?int $excludeListingId = null): bool
    {
        $max = $type === ProviderService::TYPE_PROMOCION
            ? $this->maxPromocionListings($user)
            : $this->maxPresenciaListings($user);

        return $this->activeCountByType($profile, $type, $excludeListingId) < $max;
    }

    public function assertCanCreateType(User $user, string $type): void
    {
        if (! in_array($type, [ProviderService::TYPE_PRESENCIA, ProviderService::TYPE_PROMOCION], true)) {
            throw new RuntimeException('Tipo de ficha no válido.');
        }

        if ($type === ProviderService::TYPE_PROMOCION && $this->maxPromocionListings($user) < 1) {
            throw new RuntimeException('Tu plan no incluye anuncios destacados. Mejora a Pro para publicar promociones.');
        }
    }

    public function publish(ProviderService $listing, ProviderProfile $profile): ProviderService
    {
        $now = now();
        $listing->published_at = $now;
        $listing->deactivated_at = null;
        $listing->is_active = true;

        if ($listing->isPresencia()) {
            $listing->expires_at = null;
            $listing->duration_days = null;
        } else {
            $days = $this->effectiveDurationDays($profile);
            $listing->expires_at = $now->copy()->addDays($days);
            $listing->duration_days = $days;
        }

        $listing->save();

        return $listing->refresh();
    }

    public function renew(ProviderService $listing, ProviderProfile $profile, User $user): ProviderService
    {
        if ($listing->isPresencia()) {
            throw new RuntimeException('Las fichas de presencia no vencen; no requieren renovación.');
        }

        if (! (bool) chamba_setting('listings.allow_reactivate', true)) {
            throw new RuntimeException('La renovación de anuncios no está habilitada.');
        }

        if (! $this->hasQuotaForType($profile, $user, ProviderService::TYPE_PROMOCION, (int) $listing->id)) {
            throw new RuntimeException('Alcanzaste el cupo de anuncios destacados. Pausa otro o mejora tu plan.');
        }

        return $this->publish($listing, $profile);
    }

    public function setActive(ProviderService $listing, ProviderProfile $profile, User $user, bool $active): ProviderService
    {
        if (! $active) {
            $listing->is_active = false;
            $listing->deactivated_at = now();
            $listing->save();

            return $listing->refresh();
        }

        $type = $listing->listing_type ?? ProviderService::TYPE_PRESENCIA;
        $expired = $listing->expires_at !== null && Carbon::parse($listing->expires_at)->isPast();

        if ($expired || ! $listing->is_active) {
            if (! $this->hasQuotaForType($profile, $user, $type, (int) $listing->id)) {
                throw new RuntimeException('Alcanzaste el cupo de fichas activas para tu plan.');
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
            ->where('listing_type', ProviderService::TYPE_PROMOCION)
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

        if ($listing->isPresencia()) {
            return true;
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
        $expired = $listing->isPromocion() && $expiresAt !== null && $expiresAt->isPast();
        $type = $listing->listing_type ?? ProviderService::TYPE_PRESENCIA;
        $quotaOk = $this->hasQuotaForType($profile, $user, $type, $expired ? (int) $listing->id : null);

        return [
            'listing_type' => $type,
            'published_at' => $listing->published_at,
            'expires_at' => $listing->expires_at,
            'duration_days' => $listing->duration_days,
            'deactivated_at' => $listing->deactivated_at,
            'is_expired' => $expired,
            'is_visible' => $visible,
            'days_remaining' => $expiresAt && $expiresAt->isFuture()
                ? (int) now()->diffInDays($expiresAt, false)
                : 0,
            'can_renew' => $listing->isPromocion()
                && (bool) chamba_setting('listings.allow_reactivate', true)
                && ($expired || ! $visible)
                && $quotaOk,
            'quota' => $this->quotaPayload($profile, $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quotaPayload(ProviderProfile $profile, User $user): array
    {
        $presenciaActive = $this->activeCountByType($profile, ProviderService::TYPE_PRESENCIA);
        $promocionActive = $this->activeCountByType($profile, ProviderService::TYPE_PROMOCION);
        $maxPresencia = $this->maxPresenciaListings($user);
        $maxPromocion = $this->maxPromocionListings($user);

        return [
            'active' => $presenciaActive + $promocionActive,
            'max' => $maxPresencia + $maxPromocion,
            'available' => max(0, ($maxPresencia - $presenciaActive) + ($maxPromocion - $promocionActive)),
            'presencia' => [
                'active' => $presenciaActive,
                'max' => $maxPresencia,
                'available' => max(0, $maxPresencia - $presenciaActive),
            ],
            'promocion' => [
                'active' => $promocionActive,
                'max' => $maxPromocion,
                'available' => max(0, $maxPromocion - $promocionActive),
            ],
        ];
    }

    private function activePlan(User $user): ?SubscriptionPlan
    {
        $sub = $user->activeSubscription();

        return $sub?->plan;
    }
}
