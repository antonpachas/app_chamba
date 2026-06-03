<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Columna google_id para autenticación con Google
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('google_id', 255)->nullable()->unique()->after('email');
            });
        }

        // Corregir setting: el cron de vencimiento debe estar desactivado por defecto
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('key', 'listings.expire_cron_enabled')
                ->update(['value' => '0', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('google_id');
            });
        }
    }
};
