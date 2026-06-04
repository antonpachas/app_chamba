<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        DB::statement('CREATE TABLE user_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(160) NOT NULL,
            body VARCHAR(500) NULL,
            data JSON NULL,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_notifications_user_read (user_id, read_at, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
