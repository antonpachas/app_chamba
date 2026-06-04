<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_message_attachments')) {
            return;
        }

        DB::statement("CREATE TABLE support_message_attachments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            support_message_id BIGINT UNSIGNED NOT NULL,
            path VARCHAR(500) NOT NULL,
            sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT NULL,
            CONSTRAINT fk_support_att_msg FOREIGN KEY (support_message_id) REFERENCES support_messages(id) ON DELETE CASCADE,
            INDEX idx_support_att_msg (support_message_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_attachments');
    }
};
