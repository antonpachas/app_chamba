<x-mail::message>
# Cuenta deshabilitada

Hola **{{ $user->full_name }}**,

Tu cuenta en **Busca PE** fue deshabilitada por el equipo de administración.

**Motivo:**

{{ $reason }}

No podrás iniciar sesión hasta que un administrador reactive tu cuenta. Si crees que se trata de un error, responde a este correo o contacta a soporte.

Gracias,<br>
{{ config('app.name', 'Busca PE') }}
</x-mail::message>
