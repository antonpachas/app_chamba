<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            return;
        }

        $now = now();
        $rows = [
            [
                'key' => 'listings.guest_search_max',
                'value' => '24',
                'type' => 'integer',
                'group' => 'listings',
                'label' => 'Máx. anuncios en búsqueda (invitado)',
                'description' => 'Cuántos resultados ve un visitante sin iniciar sesión en cada búsqueda.',
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'listings.guest_description_max',
                'value' => '280',
                'type' => 'integer',
                'group' => 'listings',
                'label' => 'Caracteres de descripción (invitado)',
                'description' => 'Longitud máxima de la descripción del anuncio visible sin cuenta.',
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rows as $row) {
            if (! DB::table('system_settings')->where('key', $row['key'])->exists()) {
                DB::table('system_settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->whereIn('key', [
            'listings.guest_search_max',
            'listings.guest_description_max',
        ])->delete();
    }
};
