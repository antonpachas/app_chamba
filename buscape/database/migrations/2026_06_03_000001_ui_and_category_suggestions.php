<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_suggestions')) {
            Schema::create('category_suggestions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name', 120);
                $table->string('note', 500)->nullable();
                $table->string('status', 24)->default('pending');
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        if (Schema::hasTable('provider_profiles') && ! Schema::hasColumn('provider_profiles', 'cover_path')) {
            Schema::table('provider_profiles', function (Blueprint $table): void {
                $table->string('cover_path', 255)->nullable()->after('user_id');
            });
        }

        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $rows = [
            [
                'key' => 'ui.search_grid_columns_sm',
                'value' => '1',
                'type' => 'integer',
                'group' => 'ui',
                'label' => 'Columnas del listado de anuncios (móvil / pantallas pequeñas)',
                'description' => '1 o 2 columnas en la grilla de resultados en teléfonos y tablets estrechas.',
            ],
            [
                'key' => 'ui.search_grid_columns_md',
                'value' => '2',
                'type' => 'integer',
                'group' => 'ui',
                'label' => 'Columnas del listado (tablet en adelante)',
                'description' => 'Columnas desde breakpoint mediano (sm+). En PC suele verse bien con 2–4.',
            ],
        ];

        foreach ($rows as $row) {
            if (! DB::table('system_settings')->where('key', $row['key'])->exists()) {
                DB::table('system_settings')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_suggestions');

        if (Schema::hasTable('provider_profiles') && Schema::hasColumn('provider_profiles', 'cover_path')) {
            Schema::table('provider_profiles', function (Blueprint $table): void {
                $table->dropColumn('cover_path');
            });
        }

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')->whereIn('key', [
                'ui.search_grid_columns_sm',
                'ui.search_grid_columns_md',
            ])->delete();
        }
    }
};
