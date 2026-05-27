<?php
/**
 * CHAMBA · Diagnóstico de propiedad del servicio
 * --------------------------------------------------------------------
 * Sube a:  /v1/chamba/public/_diag_service.php
 *
 * Uso:    https://jaapsystem.com/v1/chamba/public/_diag_service.php?id=4
 *
 * Muestra:
 *   - Datos del servicio
 *   - El provider_profile dueño + su user (email)
 *   - Tu user logueado (si pasas tu email con &my=tucorreo)
 *   - Si el authorize() pasaría o fallaría con 403
 *
 * BORRA cuando termines.
 */

@ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$appRoot = realpath(__DIR__ . '/..');
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    \Illuminate\Http\Request::create('/_warm', 'GET')
);

use Illuminate\Support\Facades\DB;

echo "CHAMBA · Diagnóstico de servicio\n";
echo str_repeat('=', 70) . "\n\n";

$serviceId = (int) ($_GET['id'] ?? 4);
$myEmail   = (string) ($_GET['my'] ?? '');

// 1) Servicio
$svc = DB::table('provider_services')->where('id', $serviceId)->first();
if (! $svc) {
    echo "✗ No existe servicio con id={$serviceId}\n";
    echo "Últimos 10 servicios en BD:\n";
    foreach (DB::table('provider_services')->orderByDesc('id')->limit(10)->get(['id','provider_profile_id','title']) as $s) {
        echo "  · #{$s->id} (provider_profile_id={$s->provider_profile_id}) {$s->title}\n";
    }
    exit;
}

echo "[SERVICIO]\n";
foreach ((array) $svc as $k => $v) {
    if (in_array($k, ['title','provider_profile_id','category_id','is_active','created_at','updated_at'], true)) {
        echo "  {$k}: " . (is_scalar($v) ? $v : json_encode($v)) . "\n";
    }
}

// 2) Provider profile dueño
$prof = DB::table('provider_profiles')->where('id', $svc->provider_profile_id)->first();
echo "\n[PROVIDER PROFILE dueño]\n";
if (! $prof) {
    echo "  ✗ provider_profile_id={$svc->provider_profile_id} NO existe ← este es el problema\n";
} else {
    echo "  id: {$prof->id}\n";
    echo "  user_id: " . ($prof->user_id ?? '(null)') . "\n";
    echo "  full_name: " . ($prof->full_name ?? '(sin nombre)') . "\n";

    // 3) User dueño
    $owner = DB::table('users')->where('id', $prof->user_id)->first();
    echo "\n[USER dueño del provider profile]\n";
    if (! $owner) {
        echo "  ✗ user_id={$prof->user_id} NO existe ← problema de integridad\n";
    } else {
        echo "  id: {$owner->id}\n";
        echo "  email: {$owner->email}\n";
        echo "  name: {$owner->name}\n";
        echo "  role: " . ($owner->role ?? '(sin role)') . "\n";
    }
}

// 4) Tu usuario (si pasaste email)
if ($myEmail) {
    $me = DB::table('users')->where('email', $myEmail)->first();
    echo "\n[TU USUARIO con email={$myEmail}]\n";
    if (! $me) {
        echo "  ✗ No existe usuario con ese email\n";
    } else {
        echo "  id: {$me->id}\n";
        echo "  role: " . ($me->role ?? '(sin role)') . "\n";

        // ¿Tienes provider_profile?
        $myProf = DB::table('provider_profiles')->where('user_id', $me->id)->first();
        if ($myProf) {
            echo "  provider_profile_id: {$myProf->id}\n";

            // Servicios tuyos
            $tuyos = DB::table('provider_services')
                ->where('provider_profile_id', $myProf->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id','title']);
            echo "\n[TUS SERVICIOS] (" . count($tuyos) . ")\n";
            foreach ($tuyos as $s) echo "  · #{$s->id} {$s->title}\n";
        } else {
            echo "  provider_profile: NO TIENES (no eres proveedor)\n";
        }

        // Veredicto
        echo "\n[VEREDICTO authorize()]\n";
        if (! $prof) {
            echo "  ✗ El servicio {$serviceId} no tiene provider_profile válido.\n";
        } elseif ($prof->user_id === $me->id) {
            echo "  ✓ Eres dueño del servicio {$serviceId}. NO debería dar 403.\n";
            echo "  Si igual da 403, quizás el header Authorization no llega o el token está expirado.\n";
        } elseif ($me->role === 'admin') {
            echo "  ✓ Eres admin. Tienes permiso aunque no seas dueño.\n";
        } else {
            echo "  ✗ El servicio {$serviceId} pertenece a USER#{$prof->user_id} (no a vos USER#{$me->id}).\n";
            echo "  → Por eso da 403. Solo puedes subir imágenes a TUS servicios.\n";
        }
    }
} else {
    echo "\nPara verificar tu autorización, abre con tu email:\n";
    echo "  ?id={$serviceId}&my=tu@correo.com\n";
}

echo "\n";
echo "BORRA _diag_service.php cuando termines.\n";
