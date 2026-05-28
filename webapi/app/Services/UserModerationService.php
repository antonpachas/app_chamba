<?php

namespace App\Services;

use App\Mail\AccountSuspendedMail;
use App\Models\ProviderService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class UserModerationService
{
    /**
     * @return array{user: User, email_sent: bool}
     */
    public function suspend(User $user, User $admin, ?string $reason = null, bool $hideListings = true): array
    {
        if ($user->role === 'admin') {
            throw new \RuntimeException('No puedes suspender a otro administrador.');
        }

        $reasonText = trim((string) $reason);
        if ($reasonText === '') {
            throw new \RuntimeException('Debes indicar el motivo de la deshabilitación.');
        }

        $updated = DB::transaction(function () use ($user, $admin, $reasonText, $hideListings): User {
            $user->status = 'suspendido';
            $user->suspended_at = now();
            $user->suspended_reason = $reasonText;
            $user->suspended_by = (int) $admin->id;
            $user->save();

            $user->tokens()->delete();

            if ($hideListings && $user->providerProfile) {
                ProviderService::query()
                    ->where('provider_profile_id', (int) $user->providerProfile->id)
                    ->where('admin_hidden', false)
                    ->update([
                        'admin_hidden' => true,
                        'admin_hidden_at' => now(),
                        'admin_hidden_reason' => 'Cuenta deshabilitada: '.$reasonText,
                        'admin_hidden_by' => (int) $admin->id,
                    ]);
            }

            return $user->fresh(['providerProfile']);
        });

        $emailSent = $this->sendSuspensionEmail($updated, $reasonText);

        return ['user' => $updated, 'email_sent' => $emailSent];
    }

    private function sendSuspensionEmail(User $user, string $reason): bool
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new AccountSuspendedMail($user, $reason));

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar correo de cuenta deshabilitada', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function activate(User $user): User
    {
        $user->status = 'activo';
        $user->suspended_at = null;
        $user->suspended_reason = null;
        $user->suspended_by = null;
        $user->save();

        return $user->fresh(['providerProfile']);
    }
}
