<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Endpoint de bootstrap remoto para shared hosting sin acceso a terminal.
 *
 * Uso:
 *   GET /setup?token=XXX
 *      → corre migrate --force, limpia/recachea config, opcionalmente crea admin.
 *   GET /setup?token=XXX&force=1   → permite re-ejecutarlo.
 *   GET /setup?token=XXX&admin_email=...&admin_password=...
 *      → además promueve un usuario a admin.
 *
 * El token se define en .env como CHAMBA_SETUP_TOKEN. Si está vacío, el endpoint
 * responde 403 (queda inutilizado en producción una vez completado el setup).
 */
final class SetupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) env('CHAMBA_SETUP_TOKEN', '');
        $token = (string) $request->query('token', '');

        if ($expected === '' || ! hash_equals($expected, $token)) {
            abort(403, 'Setup deshabilitado o token inválido.');
        }

        $flag = storage_path('app/setup-completed.flag');
        $force = (bool) $request->query('force', false);
        if (file_exists($flag) && ! $force) {
            return response()->json([
                'success' => false,
                'message' => 'Setup ya fue ejecutado anteriormente. Pasa &force=1 para repetir.',
                'completed_at' => @file_get_contents($flag),
            ], 200);
        }

        $output = [];

        // 1. Migraciones
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output['migrate'] = trim(Artisan::output());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'step' => 'migrate',
                'error' => $e->getMessage(),
            ], 500);
        }

        // 2. Limpiar caché viejo
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $cmd) {
            try { Artisan::call($cmd); } catch (\Throwable) { /* noop */ }
        }

        // 3. Reconstruir caché de producción
        foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $cmd) {
            try { Artisan::call($cmd); } catch (\Throwable $e) {
                $output['cache_warning_'.$cmd] = $e->getMessage();
            }
        }
        $output['cache'] = 'reconstruido';

        // 4. (Opcional) crear/promover admin
        if ($email = $request->query('admin_email')) {
            $password = (string) $request->query('admin_password', '12345678');
            try {
                Artisan::call('chamba:make-admin', [
                    '--email' => $email,
                    '--promote' => true,
                    '--password' => $password,
                ]);
                $output['admin'] = "Admin {$email} listo. Contraseña: {$password}";
            } catch (\Throwable $e) {
                $output['admin_error'] = $e->getMessage();
            }
        }

        // 5. (Opcional) probar conexión FTP
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('chamba_ftp');
            $disk->put('.healthcheck', 'ok');
            $exists = $disk->exists('.healthcheck');
            $disk->delete('.healthcheck');
            $output['ftp'] = $exists ? 'OK conexión y escritura al FTP' : 'WARN: no se pudo verificar';
        } catch (\Throwable $e) {
            $output['ftp_error'] = $e->getMessage();
        }

        @file_put_contents($flag, now()->toIso8601String());

        return response()->json([
            'success' => true,
            'output' => $output,
            'app_url' => config('app.url'),
            'next_step' => 'Visita '.config('app.url').'/app y haz login.',
            'security' => 'IMPORTANTE: borra CHAMBA_SETUP_TOKEN de tu .env cuando hayas terminado.',
        ]);
    }
}
