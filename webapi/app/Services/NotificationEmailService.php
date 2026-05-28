<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Envío de correos por eventos de notificación (opcional vía admin).
 * Hoy solo valida configuración; el envío real se activará cuando exista SMTP y plantillas.
 */
final class NotificationEmailService
{
    public function isGloballyEnabled(): bool
    {
        return (bool) chamba_setting('notifications.email_enabled', false);
    }

    public function shouldSendForType(string $notificationType): bool
    {
        if (! $this->isGloballyEnabled()) {
            return false;
        }

        return match ($notificationType) {
            'service_request.new' => (bool) chamba_setting('notifications.email_new_contact', true),
            'service_request.message' => (bool) chamba_setting('notifications.email_chat_messages', true),
            'service_request.seen', 'service_request.closed' => (bool) chamba_setting('notifications.email_status_updates', true),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function trySend(User $user, string $notificationType, string $subject, ?string $body = null, ?array $data = null): void
    {
        if (! $this->shouldSendForType($notificationType)) {
            return;
        }

        $email = trim((string) $user->email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        // Punto de extensión: Mail::to($email)->queue(new PlatformNotificationMail(...));
        if (config('app.debug')) {
            Log::debug('[Busca PE] Correo de notificación pendiente de implementar', [
                'to' => $email,
                'type' => $notificationType,
                'subject' => $subject,
            ]);
        }
    }
}
