<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provider_locations')) {
            DB::statement('CREATE TABLE provider_locations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                provider_profile_id BIGINT UNSIGNED NOT NULL,
                label VARCHAR(100) NOT NULL,
                address_text VARCHAR(255) NULL,
                department_id BIGINT UNSIGNED NULL,
                province_id BIGINT UNSIGNED NULL,
                district_id BIGINT UNSIGNED NOT NULL,
                latitude DECIMAL(10,7) NULL,
                longitude DECIMAL(10,7) NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_provloc_profile
                    FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_provloc_district
                    FOREIGN KEY (district_id) REFERENCES districts(id),
                INDEX idx_provloc_profile (provider_profile_id),
                INDEX idx_provloc_district (district_id),
                INDEX idx_provloc_active (is_active),
                INDEX idx_provloc_primary (provider_profile_id, is_primary)
            ) ENGINE=InnoDB');

            // Migración inicial: por cada provider_profile existente que tenga district_id, creamos su sede principal.
            DB::statement("
                INSERT INTO provider_locations
                    (provider_profile_id, label, address_text, district_id, is_primary, is_active, created_at, updated_at)
                SELECT
                    pp.id,
                    'Sede principal',
                    pp.address_text,
                    pp.district_id,
                    1,
                    1,
                    NOW(),
                    NOW()
                FROM provider_profiles pp
                WHERE pp.district_id IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_locations');
    }
};
