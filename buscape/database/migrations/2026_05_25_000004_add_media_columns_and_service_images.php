<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'avatar_path')) {
            DB::statement('ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER status');
        }

        if (! Schema::hasColumn('subscription_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE subscription_payments ADD COLUMN proof_image_path VARCHAR(255) NULL AFTER notes');
        }

        if (Schema::hasTable('service_payments') && ! Schema::hasColumn('service_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE service_payments ADD COLUMN proof_image_path VARCHAR(255) NULL AFTER notes');
        }

        if (! Schema::hasTable('service_images')) {
            DB::statement('CREATE TABLE service_images (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                provider_service_id BIGINT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_service_images_service FOREIGN KEY (provider_service_id) REFERENCES provider_services(id) ON DELETE CASCADE,
                INDEX idx_service_images_service (provider_service_id, sort_order)
            ) ENGINE=InnoDB');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
        if (Schema::hasColumn('service_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE service_payments DROP COLUMN proof_image_path');
        }
        if (Schema::hasColumn('subscription_payments', 'proof_image_path')) {
            DB::statement('ALTER TABLE subscription_payments DROP COLUMN proof_image_path');
        }
        if (Schema::hasColumn('users', 'avatar_path')) {
            DB::statement('ALTER TABLE users DROP COLUMN avatar_path');
        }
    }
};
