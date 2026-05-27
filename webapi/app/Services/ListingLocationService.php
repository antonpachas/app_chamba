<?php

namespace App\Services;

use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use Illuminate\Support\Facades\DB;

final class ListingLocationService
{
    /**
     * @param  array<int>|null  $locationIds  null = todas las sedes activas
     */
    public function sync(ProviderService $listing, ProviderProfile $profile, ?array $locationIds): void
    {
        DB::transaction(function () use ($listing, $profile, $locationIds) {
            if ($locationIds === null || $locationIds === []) {
                $ids = ProviderLocation::query()
                    ->where('provider_profile_id', $profile->id)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->all();
            } else {
                $ids = ProviderLocation::query()
                    ->where('provider_profile_id', $profile->id)
                    ->whereIn('id', $locationIds)
                    ->pluck('id')
                    ->all();
            }

            $listing->locations()->sync($ids);
        });
    }
}
