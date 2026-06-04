<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_visibility_events')) {
            return;
        }

        DB::statement('CREATE TABLE provider_visibility_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            provider_profile_id BIGINT UNSIGNED NOT NULL,
            provider_service_id BIGINT UNSIGNED NULL,
            search_event_id BIGINT UNSIGNED NULL,
            viewer_user_id BIGINT UNSIGNED NULL,
            source ENUM("search_result","listing_detail","public_profile") NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_pve_profile FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id) ON DELETE CASCADE,
            CONSTRAINT fk_pve_service FOREIGN KEY (provider_service_id) REFERENCES provider_services(id) ON DELETE SET NULL,
            CONSTRAINT fk_pve_search_event FOREIGN KEY (search_event_id) REFERENCES search_events(id) ON DELETE SET NULL,
            CONSTRAINT fk_pve_viewer FOREIGN KEY (viewer_user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_pve_profile_created (provider_profile_id, created_at),
            INDEX idx_pve_source_created (source, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_visibility_events');
    }
};

