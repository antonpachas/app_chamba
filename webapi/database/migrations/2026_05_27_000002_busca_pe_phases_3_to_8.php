<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('districts') && ! Schema::hasColumn('districts', 'ubigeo')) {
            DB::statement('ALTER TABLE districts ADD COLUMN ubigeo CHAR(6) NULL AFTER name');
            DB::statement('CREATE INDEX idx_districts_ubigeo ON districts (ubigeo)');
        }

        if (Schema::hasTable('provider_locations') && ! Schema::hasColumn('provider_locations', 'ubigeo')) {
            DB::statement('ALTER TABLE provider_locations ADD COLUMN ubigeo CHAR(6) NULL AFTER district_id');
        }

        if (! Schema::hasTable('provider_service_locations')) {
            DB::statement('CREATE TABLE provider_service_locations (
                provider_service_id BIGINT UNSIGNED NOT NULL,
                provider_location_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (provider_service_id, provider_location_id),
                CONSTRAINT fk_psl_service FOREIGN KEY (provider_service_id) REFERENCES provider_services(id) ON DELETE CASCADE,
                CONSTRAINT fk_psl_location FOREIGN KEY (provider_location_id) REFERENCES provider_locations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('search_events')) {
            DB::statement('CREATE TABLE search_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                category_id BIGINT UNSIGNED NULL,
                query VARCHAR(255) NULL,
                district_id BIGINT UNSIGNED NULL,
                ubigeo CHAR(6) NULL,
                user_lat DECIMAL(10,7) NULL,
                user_lng DECIMAL(10,7) NULL,
                radius_km DECIMAL(8,2) NULL,
                results_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_search_events_created (created_at),
                INDEX idx_search_events_category (category_id, created_at),
                CONSTRAINT fk_search_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_search_events_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_search_events_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('ledger_entries')) {
            DB::statement('CREATE TABLE ledger_entries (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type ENUM("ingreso","egreso") NOT NULL,
                category VARCHAR(50) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                currency CHAR(3) NOT NULL DEFAULT "PEN",
                description VARCHAR(500) NULL,
                reference_type VARCHAR(50) NULL,
                reference_id BIGINT UNSIGNED NULL,
                occurred_at DATE NOT NULL,
                created_by_user_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ledger_occurred (occurred_at),
                INDEX idx_ledger_type (type, occurred_at),
                CONSTRAINT fk_ledger_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('platform_ads')) {
            DB::statement('CREATE TABLE platform_ads (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                link_url VARCHAR(500) NULL,
                placement ENUM("home","search","detail","all") NOT NULL DEFAULT "all",
                starts_at TIMESTAMP NULL,
                ends_at TIMESTAMP NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                impressions INT UNSIGNED NOT NULL DEFAULT 0,
                clicks INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_platform_ads_active (is_active, placement, sort_order)
            ) ENGINE=InnoDB');
        }

        if (! Schema::hasTable('platform_feedback')) {
            DB::statement('CREATE TABLE platform_feedback (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                rating TINYINT UNSIGNED NOT NULL,
                comment TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
            ) ENGINE=InnoDB');
        }

        $now = now();
        $settings = [
            ['limits.client_free_requests_per_month', '3', 'integer', 'limits', 'Solicitudes gratis / mes (cliente)', 'Máximo de contactos que puede enviar un cliente sin plan premium.'],
            ['limits.provider_free_requests_per_month', '2', 'integer', 'limits', 'Contactos gratis / mes (negocio)', 'Máximo de solicitudes que puede recibir/aceptar un negocio free al mes.'],
            ['limits.premium_unlimited', '1', 'boolean', 'limits', 'Premium = ilimitado', 'Si está activo, los planes premium no aplican límites de contactos.'],
            ['ads.adsense_enabled', '0', 'boolean', 'ads', 'Activar Google AdSense', 'Muestra bloques de AdSense en la web pública.'],
            ['ads.adsense_client_id', '', 'string', 'ads', 'ID del editor AdSense (ca-pub-...)', 'Copia el ID desde tu panel de Google AdSense.'],
            ['ads.adsense_slot_home', '', 'string', 'ads', 'Slot AdSense · Inicio', 'ID del bloque para la página de inicio.'],
            ['ads.adsense_slot_search', '', 'string', 'ads', 'Slot AdSense · Búsqueda', 'ID del bloque para resultados de búsqueda.'],
            ['ads.adsense_slot_detail', '', 'string', 'ads', 'Slot AdSense · Detalle', 'ID del bloque en detalle de anuncio.'],
            ['ads.custom_enabled', '1', 'boolean', 'ads', 'Anuncios propios (negocios)', 'Muestra banners subidos por el administrador.'],
            ['ads.custom_priority', 'custom_first', 'string', 'ads', 'Prioridad publicidad', 'custom_first = primero banners propios; adsense_first = primero AdSense.'],
        ];

        foreach ($settings as [$k, $v, $t, $g, $label, $desc]) {
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

        if (Schema::hasTable('subscription_plans')) {
            foreach (['provider_free', 'client_free', 'client_premium', 'provider_pro'] as $code) {
                $plan = DB::table('subscription_plans')->where('code', $code)->first();
                if (! $plan) {
                    continue;
                }
                $features = json_decode($plan->features ?? '{}', true) ?: [];
                if ($code === 'client_free') {
                    $features['max_requests_per_month'] = 3;
                    $features['no_ads'] = false;
                }
                if ($code === 'client_premium') {
                    $features['max_requests_per_month'] = null;
                    $features['no_ads'] = true;
                }
                if ($code === 'provider_free') {
                    $features['max_requests_received_per_month'] = 2;
                    $features['max_active_listings'] = $features['max_services'] ?? 1;
                }
                if ($code === 'provider_pro') {
                    $features['max_requests_received_per_month'] = null;
                    $features['max_active_listings'] = $features['max_services'] ?? 20;
                }
                DB::table('subscription_plans')->where('id', $plan->id)->update([
                    'features' => json_encode($features),
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_feedback');
        Schema::dropIfExists('platform_ads');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('search_events');
        Schema::dropIfExists('provider_service_locations');

        if (Schema::hasTable('provider_locations') && Schema::hasColumn('provider_locations', 'ubigeo')) {
            DB::statement('ALTER TABLE provider_locations DROP COLUMN ubigeo');
        }
        if (Schema::hasTable('districts') && Schema::hasColumn('districts', 'ubigeo')) {
            DB::statement('ALTER TABLE districts DROP COLUMN ubigeo');
        }
    }
};
