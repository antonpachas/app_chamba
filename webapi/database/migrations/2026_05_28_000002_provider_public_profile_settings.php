<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            [
                'providers.public_profile_enabled',
                '1',
                'boolean',
                'providers',
                'Perfiles públicos de negocios',
                'Permite ver la página del negocio con todos sus anuncios activos desde un anuncio o la búsqueda.',
            ],
            [
                'providers.show_contact_on_public_profile',
                '1',
                'boolean',
                'providers',
                'Mostrar WhatsApp/teléfono en perfil público',
                'Si está activo, el perfil público muestra botones de contacto directo.',
            ],
        ];

        foreach ($rows as [$key, $value, $type, $group, $label, $description]) {
            $exists = DB::table('system_settings')->where('key', $key)->exists();
            if ($exists) {
                continue;
            }
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'label' => $label,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'providers.public_profile_enabled',
            'providers.show_contact_on_public_profile',
        ])->delete();
    }
};
