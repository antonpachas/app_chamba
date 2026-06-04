<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    /** Redirige a Google. El role deseado viaja en sesión para no perderse tras el redirect. */
    public function redirectToGoogle(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $role = in_array($request->query('role'), ['cliente', 'proveedor'], true)
            ? $request->query('role')
            : 'cliente';

        session(['google_oauth_role' => $role]);

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Google llama aquí tras la autenticación.
     * Devuelve un HTML mínimo que envía el token al padre vía postMessage y cierra el popup.
     */
    public function handleGoogleCallback(): Response
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->popupError('No se pudo completar el inicio de sesión con Google.');
        }

        $email     = $googleUser->getEmail();
        $googleId  = $googleUser->getId();
        $name      = $googleUser->getName() ?: $email;
        $role      = session('google_oauth_role', 'cliente');

        if (! $email) {
            return $this->popupError('Google no compartió el correo electrónico. Verifica los permisos.');
        }

        // 1. Buscar por google_id (usuario ya vinculado)
        $user = User::query()->where('google_id', $googleId)->first();

        // 2. Buscar por email (usuario existente que aún no vinculó Google)
        if (! $user) {
            $user = User::query()->where('email', $email)->first();
            if ($user) {
                $user->google_id = $googleId;
                $user->save();
            }
        }

        // 3. Crear cuenta nueva
        if (! $user) {
            $user = User::query()->create([
                'full_name'     => $name,
                'email'         => $email,
                'google_id'     => $googleId,
                'password_hash' => Hash::make(Str::random(40)),
                'role'          => $role,
                'status'        => 'activo',
            ]);

            try {
                if ($role === 'proveedor') {
                    $this->subscriptions->startProviderTrial($user);
                } else {
                    $this->subscriptions->ensureSubscription($user);
                }
            } catch (Throwable) {
                // Suscripción no crítica al crear cuenta
            }
        }

        if ($user->status === 'suspendido') {
            return $this->popupError('Tu cuenta está suspendida. Contacta a soporte.');
        }

        $token = $user->createToken('google-web')->plainTextToken;

        $payload = json_encode([
            'type'  => 'google_auth_success',
            'token' => $token,
            'user'  => [
                'id'        => $user->id,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'role'      => $user->role,
                'status'    => $user->status,
                'avatar_url'=> $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            ],
        ], JSON_UNESCAPED_UNICODE);

        return response($this->popupSuccessHtml($payload), 200)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function popupSuccessHtml(string $jsonPayload): string
    {
        $origin = rtrim(config('app.url'), '/');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="utf-8"><title>Conectando…</title></head>
        <body>
        <script>
        (function () {
            var payload = {$jsonPayload};
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, '{$origin}');
            }
            window.close();
        })();
        </script>
        <p style="font-family:sans-serif;text-align:center;padding:2rem">
            Conectando con Busca PE…
        </p>
        </body>
        </html>
        HTML;
    }

    private function popupError(string $message): Response
    {
        $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $origin = rtrim(config('app.url'), '/');

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="utf-8"><title>Error</title></head>
        <body>
        <script>
        (function () {
            if (window.opener && !window.opener.closed) {
                window.opener.postMessage({ type: 'google_auth_error', message: '{$safe}' }, '{$origin}');
            }
            window.close();
        })();
        </script>
        <p style="font-family:sans-serif;text-align:center;padding:2rem;color:#c00">
            {$safe}
        </p>
        </body>
        </html>
        HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }
}
