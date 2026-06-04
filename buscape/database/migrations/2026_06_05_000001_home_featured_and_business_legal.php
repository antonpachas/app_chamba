<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provider_services')) {
            Schema::table('provider_services', function (Blueprint $table): void {
                if (! Schema::hasColumn('provider_services', 'home_featured')) {
                    $table->boolean('home_featured')->default(false)->after('admin_hidden_by');
                }
                if (! Schema::hasColumn('provider_services', 'home_featured_sort')) {
                    $table->unsignedSmallInteger('home_featured_sort')->nullable()->after('home_featured');
                }
                if (! Schema::hasColumn('provider_services', 'home_featured_starts_at')) {
                    $table->timestamp('home_featured_starts_at')->nullable()->after('home_featured_sort');
                }
                if (! Schema::hasColumn('provider_services', 'home_featured_ends_at')) {
                    $table->timestamp('home_featured_ends_at')->nullable()->after('home_featured_starts_at');
                }
            });
        }

        if (Schema::hasTable('provider_profiles')) {
            Schema::table('provider_profiles', function (Blueprint $table): void {
                if (! Schema::hasColumn('provider_profiles', 'razon_social')) {
                    $table->string('razon_social', 200)->nullable()->after('business_name');
                }
                if (! Schema::hasColumn('provider_profiles', 'ruc')) {
                    $table->char('ruc', 11)->nullable()->after('razon_social');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('provider_services')) {
            Schema::table('provider_services', function (Blueprint $table): void {
                $cols = ['home_featured', 'home_featured_sort', 'home_featured_starts_at', 'home_featured_ends_at'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('provider_services', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('provider_profiles')) {
            Schema::table('provider_profiles', function (Blueprint $table): void {
                foreach (['ruc', 'razon_social'] as $col) {
                    if (Schema::hasColumn('provider_profiles', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
