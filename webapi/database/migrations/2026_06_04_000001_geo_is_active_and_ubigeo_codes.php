<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table): void {
                if (! Schema::hasColumn('departments', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('longitude');
                }
                if (! Schema::hasColumn('departments', 'ubigeo_code')) {
                    $table->char('ubigeo_code', 2)->nullable()->after('name');
                    $table->unique('ubigeo_code', 'uk_departments_ubigeo_code');
                }
            });
        }

        if (Schema::hasTable('provinces')) {
            Schema::table('provinces', function (Blueprint $table): void {
                if (! Schema::hasColumn('provinces', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('longitude');
                }
                if (! Schema::hasColumn('provinces', 'ubigeo_code')) {
                    $table->char('ubigeo_code', 4)->nullable()->after('name');
                    $table->index('ubigeo_code', 'idx_provinces_ubigeo_code');
                }
            });
        }

        if (Schema::hasTable('districts')) {
            Schema::table('districts', function (Blueprint $table): void {
                if (! Schema::hasColumn('districts', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('longitude');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table): void {
                if (Schema::hasColumn('departments', 'ubigeo_code')) {
                    $table->dropUnique('uk_departments_ubigeo_code');
                    $table->dropColumn('ubigeo_code');
                }
                if (Schema::hasColumn('departments', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('provinces')) {
            Schema::table('provinces', function (Blueprint $table): void {
                if (Schema::hasColumn('provinces', 'ubigeo_code')) {
                    $table->dropIndex('idx_provinces_ubigeo_code');
                    $table->dropColumn('ubigeo_code');
                }
                if (Schema::hasColumn('provinces', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('districts') && Schema::hasColumn('districts', 'is_active')) {
            Schema::table('districts', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
