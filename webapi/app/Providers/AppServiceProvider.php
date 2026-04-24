<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $email = $notifiable->getEmailForPasswordReset();
            $query = http_build_query([
                'token' => $token,
                'email' => $email,
            ]);
            $path = '/app?'.$query;
            $url = URL::to($path);

            return (new MailMessage)
                ->subject('Restablecer contraseña — Chamba')
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en Chamba.')
                ->action('Restablecer contraseña', $url)
                ->line('Este enlace caduca en '.(int) config('auth.passwords.users.expire').' minutos.')
                ->line('Si no fuiste tú, puedes ignorar este correo.');
        });
    }
}
