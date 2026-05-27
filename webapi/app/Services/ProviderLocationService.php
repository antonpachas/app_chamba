<?php

namespace App\Services;

use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProviderLocationService
{
    /**
     * Resuelve el máximo de sedes que puede tener el proveedor según su plan.
     */
    public function maxLocationsFor(User $user): int
    {
        $sub = $user->activeSubscription();
        $isPro = $sub && $sub->isPro();

        $key = $isPro ? 'provider.locations.max_pro' : 'provider.locations.max_free';
        $fallback = $isPro
            ? config('chamba.provider_locations.max_pro', 5)
            : config('chamba.provider_locations.max_free', 1);

        return (int) chamba_setting($key, $fallback);
    }

    /**
     * Crea una sede para el proveedor respetando el límite por plan.
     */
    public function create(ProviderProfile $profile, User $user, array $data): ProviderLocation
    {
        return DB::transaction(function () use ($profile, $user, $data) {
            $count = ProviderLocation::query()
                ->where('provider_profile_id', $profile->id)
                ->where('is_active', 1)
                ->count();
            $max = $this->maxLocationsFor($user);
            if ($count >= $max) {
                throw new RuntimeException("Tu plan permite hasta {$max} sede(s) activa(s). Sube a Pro o desactiva otra sede.");
            }

            // Si pidió que esta sea principal, despromover las demás.
            $isPrimary = ! empty($data['is_primary']) && (bool) $data['is_primary'];
            if ($isPrimary) {
                ProviderLocation::query()
                    ->where('provider_profile_id', $profile->id)
                    ->update(['is_primary' => 0]);
            } elseif ($count === 0) {
                // Primera sede que crea: forzar a principal.
                $isPrimary = true;
            }

            return ProviderLocation::query()->create([
                'provider_profile_id' => $profile->id,
                'label' => $data['label'],
                'address_text' => $data['address_text'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'province_id' => $data['province_id'] ?? null,
                'district_id' => $data['district_id'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_primary' => $isPrimary,
                'is_active' => true,
            ]);
        });
    }

    public function update(ProviderLocation $location, array $data): ProviderLocation
    {
        return DB::transaction(function () use ($location, $data) {
            if (array_key_exists('is_primary', $data) && $data['is_primary']) {
                ProviderLocation::query()
                    ->where('provider_profile_id', $location->provider_profile_id)
                    ->where('id', '!=', $location->id)
                    ->update(['is_primary' => 0]);
            }
            $location->fill($data)->save();
            return $location->refresh();
        });
    }

    public function delete(ProviderLocation $location): void
    {
        DB::transaction(function () use ($location) {
            $wasPrimary = $location->is_primary;
            $profileId = $location->provider_profile_id;
            $location->delete();

            if ($wasPrimary) {
                $next = ProviderLocation::query()
                    ->where('provider_profile_id', $profileId)
                    ->where('is_active', 1)
                    ->orderBy('id')
                    ->first();
                if ($next) {
                    $next->update(['is_primary' => 1]);
                }
            }
        });
    }
}
