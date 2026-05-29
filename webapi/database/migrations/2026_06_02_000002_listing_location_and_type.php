<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provider_services')) {
            return;
        }

        Schema::table('provider_services', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_services', 'listing_type')) {
                $table->string('listing_type', 20)->default('presencia')->after('description');
            }
            if (! Schema::hasColumn('provider_services', 'location_label')) {
                $table->string('location_label', 120)->nullable()->after('listing_type');
            }
            if (! Schema::hasColumn('provider_services', 'address_text')) {
                $table->string('address_text', 500)->nullable()->after('location_label');
            }
            if (! Schema::hasColumn('provider_services', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('address_text');
            }
            if (! Schema::hasColumn('provider_services', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('provider_services', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable()->after('province_id');
            }
            if (! Schema::hasColumn('provider_services', 'ubigeo')) {
                $table->char('ubigeo', 6)->nullable()->after('district_id');
            }
            if (! Schema::hasColumn('provider_services', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('ubigeo');
            }
            if (! Schema::hasColumn('provider_services', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        if (Schema::hasColumn('provider_services', 'listing_type')) {
            DB::table('provider_services')
                ->whereNull('listing_type')
                ->orWhere('listing_type', '')
                ->update(['listing_type' => 'presencia']);

            DB::table('provider_services')
                ->where('listing_type', 'presencia')
                ->whereNotNull('expires_at')
                ->update([
                    'expires_at' => null,
                    'duration_days' => null,
                ]);
        }

        $this->backfillLocationFromPrimaryBranch();
        $this->seedSettings();
        $this->updatePlanFeatures();
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_services')) {
            return;
        }

        Schema::table('provider_services', function (Blueprint $table) {
            foreach ([
                'longitude', 'latitude', 'ubigeo', 'district_id', 'province_id',
                'department_id', 'address_text', 'location_label', 'listing_type',
            ] as $col) {
                if (Schema::hasColumn('provider_services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function backfillLocationFromPrimaryBranch(): void
    {
        if (! Schema::hasTable('provider_locations')) {
            return;
        }

        $services = DB::table('provider_services')
            ->whereNull('district_id')
            ->get(['id', 'provider_profile_id']);

        foreach ($services as $svc) {
            $loc = DB::table('provider_locations')
                ->where('provider_profile_id', $svc->provider_profile_id)
                ->where('is_active', 1)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->first();

            if ($loc) {
                DB::table('provider_services')->where('id', $svc->id)->update([
                    'location_label' => $loc->label,
                    'address_text' => $loc->address_text,
                    'department_id' => $loc->department_id,
                    'province_id' => $loc->province_id,
                    'district_id' => $loc->district_id,
                    'ubigeo' => $loc->ubigeo,
                    'latitude' => $loc->latitude,
                    'longitude' => $loc->longitude,
                ]);

                continue;
            }

            $prof = DB::table('provider_profiles')->where('id', $svc->provider_profile_id)->first();
            if ($prof?->district_id) {
                $dist = DB::table('districts')->where('id', $prof->district_id)->first();
                DB::table('provider_services')->where('id', $svc->id)->update([
                    'address_text' => $prof->address_text,
                    'district_id' => $prof->district_id,
                    'province_id' => $dist->province_id ?? null,
                    'department_id' => $dist ? DB::table('provinces')->where('id', $dist->province_id)->value('department_id') : null,
                    'ubigeo' => $dist->ubigeo ?? null,
                    'latitude' => $dist->latitude ?? null,
                    'longitude' => $dist->longitude ?? null,
                ]);
            }
        }
    }

    private function seedSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $now = now();
        $rows = [
            ['listings.presencia.max_free', '2', 'integer', 'listings', 'Máx. fichas presencia (plan Free)', 'Sedes / aparición permanente en el mapa.'],
            ['listings.presencia.max_pro', '10', 'integer', 'listings', 'Máx. fichas presencia (plan Pro)', null],
            ['listings.promocion.max_free', '0', 'integer', 'listings', 'Máx. anuncios destacados (Free)', 'Prioridad en búsqueda; con vencimiento.'],
            ['listings.promocion.max_pro', '3', 'integer', 'listings', 'Máx. anuncios destacados (Pro)', null],
        ];

        foreach ($rows as [$key, $value, $type, $group, $label, $desc]) {
            if (! DB::table('system_settings')->where('key', $key)->exists()) {
                DB::table('system_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                    'description' => $desc,
                    'is_editable' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function updatePlanFeatures(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        foreach (['provider_free' => ['max_presencia' => 2, 'max_promocion' => 0], 'provider_pro' => ['max_presencia' => 10, 'max_promocion' => 3]] as $code => $limits) {
            $plan = DB::table('subscription_plans')->where('code', $code)->first();
            if (! $plan) {
                continue;
            }
            $features = json_decode((string) $plan->features, true) ?: [];
            $features['max_presencia'] = $limits['max_presencia'];
            $features['max_promocion'] = $limits['max_promocion'];
            $features['max_active_listings'] = $limits['max_presencia'] + $limits['max_promocion'];
            DB::table('subscription_plans')->where('id', $plan->id)->update([
                'features' => json_encode($features),
                'updated_at' => now(),
            ]);
        }
    }
};
