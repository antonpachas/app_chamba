<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Garantiza que `favorites` exista y tenga `created_at` (sin `updated_at`).
 * Idempotente para producción (schema MVP original).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('provider_profile_id');
                $table->unsignedBigInteger('provider_service_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id', 'fk_favorites_user')
                    ->references('id')->on('users')
                    ->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('provider_profile_id', 'fk_favorites_provider')
                    ->references('id')->on('provider_profiles')
                    ->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('provider_service_id', 'fk_favorites_service')
                    ->references('id')->on('provider_services')
                    ->cascadeOnUpdate()->cascadeOnDelete();
                $table->unique(['user_id', 'provider_service_id'], 'uk_favorites_user_service');
                $table->index('user_id', 'idx_favorites_user');
                $table->index('provider_profile_id', 'idx_favorites_provider');
                $table->index('provider_service_id', 'idx_favorites_service');
            });

            return;
        }

        if (! Schema::hasColumn('favorites', 'created_at')) {
            Schema::table('favorites', function (Blueprint $table): void {
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Sin revert automático: evita romper producción.
    }
};
