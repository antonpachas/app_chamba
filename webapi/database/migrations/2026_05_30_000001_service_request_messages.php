<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_request_messages')) {
            return;
        }

        DB::statement("CREATE TABLE service_request_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            service_request_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            author_role VARCHAR(20) NOT NULL,
            body VARCHAR(2000) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_srm_request FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
            CONSTRAINT fk_srm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_srm_request_created (service_request_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_messages');
    }
};
