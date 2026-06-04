<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Busca PE: custodia apagada por defecto
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('key', 'features.escrow')
                ->update(['value' => '0', 'updated_at' => now()]);
        }

        $now = now();
        $listingSettings = [
            ['listings.default_duration_days', '5', 'integer', 'listings',
                'Días de publicación (por defecto)',
                'Duración de cada anuncio al publicar o renovar, si el negocio no tiene override.'],
            ['listings.expire_cron_enabled', '1', 'boolean', 'listings',
                'Cron de vencimiento automático',
                'Si está activo, el comando diario oculta anuncios vencidos.'],
            ['listings.allow_reactivate', '1', 'boolean', 'listings',
                'Permitir reactivar anuncios',
                'Los negocios pueden reactivar o renovar si tienen cupo disponible.'],
        ];

        foreach ($listingSettings as [$k, $v, $t, $g, $label, $desc]) {
            if (! Schema::hasTable('system_settings')) {
                break;
            }
            DB::table('system_settings')->insertOrIgnore([
                'key' => $k,
                'value' => $v,
                'type' => $t,
                'group' => $g,
                'label' => $label,
                'description' => $desc,
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('provider_profiles') && ! Schema::hasColumn('provider_profiles', 'listing_duration_days_override')) {
            DB::statement('ALTER TABLE provider_profiles ADD COLUMN listing_duration_days_override SMALLINT UNSIGNED NULL AFTER district_id');
        }

        if (Schema::hasTable('provider_services')) {
            if (! Schema::hasColumn('provider_services', 'published_at')) {
                DB::statement('ALTER TABLE provider_services ADD COLUMN published_at TIMESTAMP NULL AFTER is_active');
            }
            if (! Schema::hasColumn('provider_services', 'expires_at')) {
                DB::statement('ALTER TABLE provider_services ADD COLUMN expires_at TIMESTAMP NULL AFTER published_at');
            }
            if (! Schema::hasColumn('provider_services', 'deactivated_at')) {
                DB::statement('ALTER TABLE provider_services ADD COLUMN deactivated_at TIMESTAMP NULL AFTER expires_at');
            }
            if (! Schema::hasColumn('provider_services', 'duration_days')) {
                DB::statement('ALTER TABLE provider_services ADD COLUMN duration_days SMALLINT UNSIGNED NULL AFTER deactivated_at');
            }

            $defaultDays = 5;
            DB::statement("
                UPDATE provider_services
                SET published_at = COALESCE(published_at, created_at, NOW()),
                    duration_days = COALESCE(duration_days, {$defaultDays}),
                    expires_at = COALESCE(
                        expires_at,
                        DATE_ADD(COALESCE(published_at, created_at, NOW()), INTERVAL {$defaultDays} DAY)
                    )
                WHERE expires_at IS NULL OR published_at IS NULL
            ");

            DB::statement('
                UPDATE provider_services
                SET is_active = 0, deactivated_at = COALESCE(deactivated_at, NOW())
                WHERE expires_at IS NOT NULL AND expires_at < NOW() AND is_active = 1
            ');
        }

        if (Schema::hasTable('service_requests')) {
            DB::statement("ALTER TABLE service_requests MODIFY status VARCHAR(40) NOT NULL DEFAULT 'nuevo'");

            DB::table('service_requests')
                ->whereIn('status', [
                    'contactado', 'cotizado', 'aceptado', 'pagado_pendiente',
                    'en_custodia', 'en_progreso', 'entregado', 'terminado', 'disputado',
                ])
                ->update(['status' => 'visto']);

            DB::table('service_requests')
                ->whereIn('status', ['confirmado', 'reembolsado', 'cerrado'])
                ->update(['status' => 'cerrado']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->whereIn('key', [
                'listings.default_duration_days',
                'listings.expire_cron_enabled',
                'listings.allow_reactivate',
            ])->delete();
        }

        if (Schema::hasTable('provider_profiles') && Schema::hasColumn('provider_profiles', 'listing_duration_days_override')) {
            DB::statement('ALTER TABLE provider_profiles DROP COLUMN listing_duration_days_override');
        }

        foreach (['duration_days', 'deactivated_at', 'expires_at', 'published_at'] as $col) {
            if (Schema::hasTable('provider_services') && Schema::hasColumn('provider_services', $col)) {
                DB::statement("ALTER TABLE provider_services DROP COLUMN {$col}");
            }
        }
    }
};
