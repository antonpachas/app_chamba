<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provider_services')) {
            return;
        }

        Schema::table('provider_services', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_services', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_services') || ! Schema::hasColumn('provider_services', 'business_hours')) {
            return;
        }

        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn('business_hours');
        });
    }
};
