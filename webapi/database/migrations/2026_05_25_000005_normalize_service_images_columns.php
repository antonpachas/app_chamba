<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_images')) return;

        if (! Schema::hasColumn('service_images', 'provider_service_id')) {
            DB::statement('ALTER TABLE service_images ADD COLUMN provider_service_id BIGINT UNSIGNED NULL AFTER id');
        }
        if (! Schema::hasColumn('service_images', 'path')) {
            DB::statement('ALTER TABLE service_images ADD COLUMN path VARCHAR(255) NULL AFTER provider_service_id');
        }
        if (! Schema::hasColumn('service_images', 'sort_order')) {
            DB::statement('ALTER TABLE service_images ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER path');
        }
        if (! Schema::hasColumn('service_images', 'updated_at')) {
            DB::statement('ALTER TABLE service_images ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        if (Schema::hasColumn('service_images', 'service_id')) {
            DB::statement('UPDATE service_images SET provider_service_id = service_id WHERE provider_service_id IS NULL');
        }
        if (Schema::hasColumn('service_images', 'image_url')) {
            DB::statement('UPDATE service_images SET path = image_url WHERE path IS NULL');
        }

        DB::statement('DELETE FROM service_images WHERE provider_service_id IS NULL OR path IS NULL OR path = ""');

        DB::statement('ALTER TABLE service_images MODIFY COLUMN provider_service_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE service_images MODIFY COLUMN path VARCHAR(255) NOT NULL');

        $hasFk = (bool) DB::selectOne("SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'service_images'
              AND CONSTRAINT_NAME = 'fk_service_images_service'")?->c;
        if (! $hasFk) {
            DB::statement('ALTER TABLE service_images ADD CONSTRAINT fk_service_images_service
                FOREIGN KEY (provider_service_id) REFERENCES provider_services(id) ON DELETE CASCADE');
        }

        $hasIdx = (bool) DB::selectOne("SELECT COUNT(*) AS c FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_images'
              AND INDEX_NAME = 'idx_service_images_service'")?->c;
        if (! $hasIdx) {
            DB::statement('CREATE INDEX idx_service_images_service ON service_images (provider_service_id, sort_order)');
        }

        if (Schema::hasColumn('service_images', 'service_id')) {
            try {
                DB::statement('ALTER TABLE service_images DROP COLUMN service_id');
            } catch (\Throwable $e) {
                // Si tiene FK al campo viejo, primero drop la FK que apunte a service_id
                $row = DB::selectOne("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_images'
                      AND COLUMN_NAME = 'service_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
                if ($row?->CONSTRAINT_NAME) {
                    DB::statement('ALTER TABLE service_images DROP FOREIGN KEY '.$row->CONSTRAINT_NAME);
                }
                DB::statement('ALTER TABLE service_images DROP COLUMN service_id');
            }
        }
        if (Schema::hasColumn('service_images', 'image_url')) {
            DB::statement('ALTER TABLE service_images DROP COLUMN image_url');
        }
    }

    public function down(): void
    {
        // No-op: no merece rollback porque pierde semántica.
    }
};
