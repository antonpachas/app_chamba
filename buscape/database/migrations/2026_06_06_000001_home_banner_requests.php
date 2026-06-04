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

        Schema::table('provider_services', function (Blueprint $table): void {
            if (! Schema::hasColumn('provider_services', 'home_featured_requested')) {
                $table->boolean('home_featured_requested')->default(false)->after('home_featured_ends_at');
            }
            if (! Schema::hasColumn('provider_services', 'home_featured_requested_at')) {
                $table->timestamp('home_featured_requested_at')->nullable()->after('home_featured_requested');
            }
            if (! Schema::hasColumn('provider_services', 'home_featured_rejected_at')) {
                $table->timestamp('home_featured_rejected_at')->nullable()->after('home_featured_requested_at');
            }
            if (! Schema::hasColumn('provider_services', 'home_featured_rejection_reason')) {
                $table->string('home_featured_rejection_reason', 500)->nullable()->after('home_featured_rejected_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_services')) {
            return;
        }

        Schema::table('provider_services', function (Blueprint $table): void {
            foreach ([
                'home_featured_rejection_reason',
                'home_featured_rejected_at',
                'home_featured_requested_at',
                'home_featured_requested',
            ] as $col) {
                if (Schema::hasColumn('provider_services', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
