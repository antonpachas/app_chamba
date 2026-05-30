<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            // ── Límites free ─────────────────────────────────────────────────
            [
                'key' => 'limits.client_free_unlimited',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'limits',
                'label' => 'Clientes free: contactos ilimitados',
                'description' => 'Si está activo, los clientes sin suscripción pueden contactar negocios sin límite mensual.',
            ],

            // ── SMTP ──────────────────────────────────────────────────────────
            [
                'key' => 'mail.host',
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'SMTP Host',
                'description' => 'Servidor SMTP (ej. smtp.gmail.com, smtp.mailgun.org).',
            ],
            [
                'key' => 'mail.port',
                'value' => '587',
                'type' => 'integer',
                'group' => 'notifications',
                'label' => 'SMTP Puerto',
                'description' => 'Puerto del servidor SMTP (465=SSL, 587=TLS).',
            ],
            [
                'key' => 'mail.encryption',
                'value' => 'tls',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'SMTP Cifrado',
                'description' => 'Tipo de cifrado: tls o ssl.',
            ],
            [
                'key' => 'mail.username',
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'SMTP Usuario',
                'description' => 'Correo electrónico o usuario para autenticación SMTP.',
            ],
            [
                'key' => 'mail.password',
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'SMTP Contraseña',
                'description' => 'Contraseña o App Password para autenticación SMTP.',
            ],
            [
                'key' => 'mail.from_address',
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Correo remitente',
                'description' => 'Dirección de correo que aparece como remitente en todos los correos.',
            ],
            [
                'key' => 'mail.from_name',
                'value' => 'Busca PE',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Nombre remitente',
                'description' => 'Nombre que aparece como remitente en los correos enviados.',
            ],

            // ── Plantillas ───────────────────────────────────────────────────
            [
                'key' => 'mail.template.registro_cliente.subject',
                'value' => '¡Bienvenido a Busca PE, {nombre}!',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Asunto: Registro cliente',
                'description' => 'Asunto del correo de bienvenida al registrarse como cliente. Usa {nombre}, {email}.',
            ],
            [
                'key' => 'mail.template.registro_cliente.body',
                'value' => '<h2>¡Hola, {nombre}!</h2><p>Tu cuenta en <strong>Busca PE</strong> ha sido creada exitosamente.</p><p>Ya puedes explorar negocios, guardar favoritos y contactar directamente con los proveedores que necesites.</p><p>Si tienes dudas, responde este correo o visita nuestro soporte.</p><p>¡Bienvenido!</p>',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Cuerpo HTML: Registro cliente',
                'description' => 'Contenido HTML del correo de bienvenida. Variables: {nombre}, {email}.',
            ],
            [
                'key' => 'mail.template.registro_proveedor.subject',
                'value' => 'Tu negocio en Busca PE, {nombre}',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Asunto: Registro proveedor',
                'description' => 'Asunto del correo de bienvenida al registrarse como proveedor/negocio.',
            ],
            [
                'key' => 'mail.template.registro_proveedor.body',
                'value' => '<h2>¡Hola, {nombre}!</h2><p>Tu cuenta de negocio en <strong>Busca PE</strong> está lista.</p><p>Completa tu perfil y publica tus primeros anuncios para que los clientes puedan encontrarte.</p><p>Recuerda que tienes acceso a todos los clientes del directorio. ¡Mucho éxito!</p>',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Cuerpo HTML: Registro proveedor',
                'description' => 'Contenido HTML del correo de bienvenida al proveedor. Variables: {nombre}, {email}.',
            ],
            [
                'key' => 'mail.template.recuperar_password.subject',
                'value' => 'Restablecer contraseña en Busca PE',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Asunto: Recuperar contraseña',
                'description' => 'Asunto del correo de recuperación de contraseña.',
            ],
            [
                'key' => 'mail.template.recuperar_password.body',
                'value' => '<h2>Hola, {nombre}</h2><p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Busca PE</strong>.</p><p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p><p><a href="{enlace}" style="background:#003874;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a></p><p>Este enlace expira en 60 minutos. Si no solicitaste este cambio, ignora este correo.</p>',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Cuerpo HTML: Recuperar contraseña',
                'description' => 'Contenido HTML del correo de recuperación. Variables: {nombre}, {email}, {enlace}.',
            ],
            [
                'key' => 'mail.template.nuevo_contacto.subject',
                'value' => 'Nueva solicitud de contacto en tu anuncio',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Asunto: Nuevo contacto (proveedor)',
                'description' => 'Asunto del correo que recibe el proveedor cuando alguien contacta su anuncio.',
            ],
            [
                'key' => 'mail.template.nuevo_contacto.body',
                'value' => '<h2>Hola, {nombre_proveedor}</h2><p>Un cliente está interesado en tu anuncio <strong>{titulo_anuncio}</strong>.</p><p>Mensaje: <em>{mensaje}</em></p><p>Ingresa a Busca PE para responder.</p>',
                'type' => 'string',
                'group' => 'notifications',
                'label' => 'Cuerpo HTML: Nuevo contacto (proveedor)',
                'description' => 'Correo enviado al proveedor cuando recibe un nuevo contacto. Variables: {nombre_proveedor}, {titulo_anuncio}, {mensaje}.',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('system_settings')->insertOrIgnore(array_merge($row, [
                'is_editable' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        $keys = [
            'limits.client_free_unlimited',
            'mail.host', 'mail.port', 'mail.encryption', 'mail.username', 'mail.password',
            'mail.from_address', 'mail.from_name',
            'mail.template.registro_cliente.subject', 'mail.template.registro_cliente.body',
            'mail.template.registro_proveedor.subject', 'mail.template.registro_proveedor.body',
            'mail.template.recuperar_password.subject', 'mail.template.recuperar_password.body',
            'mail.template.nuevo_contacto.subject', 'mail.template.nuevo_contacto.body',
        ];
        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
