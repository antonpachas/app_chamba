<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        DB::statement("CREATE TABLE support_tickets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            subject VARCHAR(200) NOT NULL,
            category VARCHAR(40) NOT NULL DEFAULT 'otro',
            status VARCHAR(30) NOT NULL DEFAULT 'abierto',
            last_message_at TIMESTAMP NULL DEFAULT NULL,
            user_last_read_at TIMESTAMP NULL DEFAULT NULL,
            admin_last_read_at TIMESTAMP NULL DEFAULT NULL,
            closed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            CONSTRAINT fk_support_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_support_ticket_status (status),
            INDEX idx_support_ticket_user (user_id),
            INDEX idx_support_ticket_last_msg (last_message_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        DB::statement("CREATE TABLE support_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            support_ticket_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            is_staff TINYINT(1) NOT NULL DEFAULT 0,
            body VARCHAR(2000) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_support_msg_ticket FOREIGN KEY (support_ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            CONSTRAINT fk_support_msg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_support_msg_ticket_created (support_ticket_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
