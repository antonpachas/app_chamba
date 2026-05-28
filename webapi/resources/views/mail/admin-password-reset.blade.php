<x-mail::message>
# Contraseña restablecida

Hola **{{ $user->full_name }}**,

Un administrador de **Busca PE** generó una contraseña temporal para tu cuenta (**{{ $user->email }}**).

**Contraseña temporal:**

`{{ $temporaryPassword }}`

Inicia sesión y cámbiala de inmediato en **Mi cuenta** por seguridad.

<x-mail::button :url="rtrim(config('app.url'), '/').'/app/login'">
Iniciar sesión
</x-mail::button>

Si no esperabas este cambio, contacta a soporte de inmediato.

Saludos,<br>
{{ config('app.name', 'Busca PE') }}
</x-mail::message>
