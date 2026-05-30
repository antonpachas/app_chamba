<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El MVP tenía UNIQUE (user_id, provider_profile_id).
 * Los favoritos son por anuncio (provider_service_id); el índice viejo bloquea varios anuncios del mismo negocio.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('favorites')) {
            return;
        }

        try {
            Schema::table('favorites', function (Blueprint $table): void {
                $table->dropUnique('uk_favorites_user_provider');
            });
        } catch (\Throwable) {
            // Índice ya ausente en instalaciones nuevas.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('favorites')) {
            return;
        }

        try {
            Schema::table('favorites', function (Blueprint $table): void {
                $table->unique(['user_id', 'provider_profile_id'], 'uk_favorites_user_provider');
            });
        } catch (\Throwable) {
            // noop
        }
    }
};
