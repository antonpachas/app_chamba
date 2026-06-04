<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->json('business_hours')->nullable()->after('address_text');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table): void {
            $table->dropColumn('business_hours');
        });
    }
};
