<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('favorites')) {
            return;
        }

        Schema::table('favorites', function (Blueprint $table): void {
            if (! Schema::hasColumn('favorites', 'provider_service_id')) {
                $table->unsignedBigInteger('provider_service_id')->nullable()->after('provider_profile_id');
            }
        });

        DB::statement('
            UPDATE favorites f
            JOIN (
                SELECT provider_profile_id, MIN(id) AS provider_service_id
                FROM provider_services
                GROUP BY provider_profile_id
            ) ps ON ps.provider_profile_id = f.provider_profile_id
            SET f.provider_service_id = ps.provider_service_id
            WHERE f.provider_service_id IS NULL
        ');

        Schema::table('favorites', function (Blueprint $table): void {
            $table->foreign('provider_service_id', 'fk_favorites_service')
                ->references('id')
                ->on('provider_services')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['user_id', 'provider_service_id'], 'uk_favorites_user_service');
            $table->index('provider_service_id', 'idx_favorites_service');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('favorites') || ! Schema::hasColumn('favorites', 'provider_service_id')) {
            return;
        }

        Schema::table('favorites', function (Blueprint $table): void {
            $table->dropUnique('uk_favorites_user_service');
            $table->dropIndex('idx_favorites_service');
            $table->dropForeign('fk_favorites_service');
            $table->dropColumn('provider_service_id');
        });
    }
};

