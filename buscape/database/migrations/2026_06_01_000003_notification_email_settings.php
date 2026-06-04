<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $now = now();
        $rows = [
            [
                'key' => 'notifications.email_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Activar correos de notificación',
                'description' => 'Interruptor general. Requiere SMTP configurado en el servidor (.env). Los avisos dentro de la app no dependen de esto.',
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notifications.email_new_contact',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Correo: nuevo contacto al proveedor',
                'description' => 'Cuando un cliente envía una solicitud sobre un anuncio.',
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notifications.email_chat_messages',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Correo: mensajes del chat',
                'description' => 'Aviso por correo cuando hay un mensaje nuevo en una solicitud (cliente o proveedor).',
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notifications.email_status_updates',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'label' => 'Correo: cambios de estado al cliente',
                'description' => 'Cuando el negocio marca la solicitud como vista o cerrada.',
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
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->whereIn('key', [
            'notifications.email_enabled',
            'notifications.email_new_contact',
            'notifications.email_chat_messages',
            'notifications.email_status_updates',
        ])->delete();
    }
};
