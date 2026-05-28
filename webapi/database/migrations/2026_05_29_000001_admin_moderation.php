<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_services') && ! Schema::hasColumn('provider_services', 'admin_hidden')) {
            DB::statement('ALTER TABLE provider_services ADD COLUMN admin_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
            DB::statement('ALTER TABLE provider_services ADD COLUMN admin_hidden_at TIMESTAMP NULL AFTER admin_hidden');
            DB::statement('ALTER TABLE provider_services ADD COLUMN admin_hidden_reason VARCHAR(500) NULL AFTER admin_hidden_at');
            DB::statement('ALTER TABLE provider_services ADD COLUMN admin_hidden_by BIGINT UNSIGNED NULL AFTER admin_hidden_reason');
            DB::statement('ALTER TABLE provider_services ADD INDEX idx_provider_services_admin_hidden (admin_hidden)');
            DB::statement('
                ALTER TABLE provider_services
                ADD CONSTRAINT fk_provider_services_admin_hidden_by
                FOREIGN KEY (admin_hidden_by) REFERENCES users(id) ON DELETE SET NULL
            ');
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'suspended_at')) {
            DB::statement('ALTER TABLE users ADD COLUMN suspended_at TIMESTAMP NULL AFTER status');
            DB::statement('ALTER TABLE users ADD COLUMN suspended_reason VARCHAR(500) NULL AFTER suspended_at');
            DB::statement('ALTER TABLE users ADD COLUMN suspended_by BIGINT UNSIGNED NULL AFTER suspended_reason');
            DB::statement('
                ALTER TABLE users
                ADD CONSTRAINT fk_users_suspended_by
                FOREIGN KEY (suspended_by) REFERENCES users(id) ON DELETE SET NULL
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('provider_services') && Schema::hasColumn('provider_services', 'admin_hidden_by')) {
            DB::statement('ALTER TABLE provider_services DROP FOREIGN KEY fk_provider_services_admin_hidden_by');
        }
        foreach (['admin_hidden_by', 'admin_hidden_reason', 'admin_hidden_at', 'admin_hidden'] as $col) {
            if (Schema::hasTable('provider_services') && Schema::hasColumn('provider_services', $col)) {
                DB::statement("ALTER TABLE provider_services DROP COLUMN {$col}");
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'suspended_by')) {
            DB::statement('ALTER TABLE users DROP FOREIGN KEY fk_users_suspended_by');
        }
        foreach (['suspended_by', 'suspended_reason', 'suspended_at'] as $col) {
            if (Schema::hasTable('users') && Schema::hasColumn('users', $col)) {
                DB::statement("ALTER TABLE users DROP COLUMN {$col}");
            }
        }
    }
};
