<?php
/**
 * CHAMBA · Diagnóstico de propiedad del servicio
 * --------------------------------------------------------------------
 * Sube a:  /v1/chamba/public/_diag_service.php
 *
 * Uso:
 *   ?id=5            → muestra info del servicio
 *   ?id=5&my=correo  → además compara con tu user
 *   ?listusers=1     → lista los users con provider_profile (para identificar el tuyo)
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

function safe_get($obj, $prop, $default = '(n/a)') {
    if (!is_object($obj)) return $default;
    return $obj->{$prop} ?? $default;
}

// ── Modo: listar users con provider_profile ─────────────────────────
if (($_GET['listusers'] ?? '') === '1') {
    echo "[USUARIOS PROVEEDOR]\n";
    $rows = DB::table('users')
        ->leftJoin('provider_profiles', 'provider_profiles.user_id', '=', 'users.id')
        ->whereNotNull('provider_profiles.id')
        ->orderBy('users.id')
        ->get([
            'users.id as user_id',
            'users.email',
            'provider_profiles.id as profile_id',
            'provider_profiles.full_name',
        ]);
    foreach ($rows as $r) {
        echo "  USER #{$r->user_id} <{$r->email}>  →  PROFILE #{$r->profile_id} ({$r->full_name})\n";

        // Sus servicios
        $svcs = DB::table('provider_services')
            ->where('provider_profile_id', $r->profile_id)
            ->orderBy('id')
            ->get(['id', 'title', 'is_active']);
        if (count($svcs) === 0) {
            echo "    (sin servicios)\n";
        } else {
            foreach ($svcs as $s) {
                echo "    · servicio #{$s->id}: {$s->title} " . ($s->is_active ? '[activo]' : '[inactivo]') . "\n";
            }
        }
    }
    exit;
}

$serviceId = (int) ($_GET['id'] ?? 4);
$myEmail   = (string) ($_GET['my'] ?? '');

// 1) Servicio
$svc = DB::table('provider_services')->where('id', $serviceId)->first();
if (! $svc) {
    echo "✗ No existe servicio con id={$serviceId}\n\n";
    echo "Últimos 10 servicios en BD:\n";
    foreach (DB::table('provider_services')->orderByDesc('id')->limit(10)->get(['id','provider_profile_id','title']) as $s) {
        echo "  · #{$s->id} (provider_profile_id={$s->provider_profile_id}) {$s->title}\n";
    }
    exit;
}

echo "[SERVICIO #{$serviceId}]\n";
echo "  title              : " . safe_get($svc, 'title') . "\n";
echo "  provider_profile_id: " . safe_get($svc, 'provider_profile_id') . "\n";
echo "  is_active          : " . safe_get($svc, 'is_active') . "\n";
echo "  created_at         : " . safe_get($svc, 'created_at') . "\n";

// 2) Provider profile dueño
$prof = DB::table('provider_profiles')->where('id', $svc->provider_profile_id)->first();
echo "\n[PROVIDER PROFILE dueño]\n";
if (! $prof) {
    echo "  ✗ provider_profile_id={$svc->provider_profile_id} NO existe\n";
    exit;
}
echo "  id        : " . safe_get($prof, 'id') . "\n";
echo "  user_id   : " . safe_get($prof, 'user_id') . "\n";
echo "  full_name : " . safe_get($prof, 'full_name', '(sin nombre)') . "\n";

// 3) User dueño
$owner = DB::table('users')->where('id', $prof->user_id)->first();
echo "\n[USER dueño del provider profile]\n";
if (! $owner) {
    echo "  ✗ user_id={$prof->user_id} NO existe (FK rota)\n";
} else {
    // Listamos solo columnas seguras
    $cols = DB::getSchemaBuilder()->getColumnListing('users');
    echo "  id    : " . safe_get($owner, 'id') . "\n";
    echo "  email : " . safe_get($owner, 'email') . "\n";
    if (in_array('full_name', $cols, true))   echo "  full_name : " . safe_get($owner, 'full_name', '(sin nombre)') . "\n";
    if (in_array('name', $cols, true))        echo "  name      : " . safe_get($owner, 'name', '(sin nombre)') . "\n";
    if (in_array('role', $cols, true))        echo "  role      : " . safe_get($owner, 'role', '(sin role)') . "\n";
    if (in_array('user_type', $cols, true))   echo "  user_type : " . safe_get($owner, 'user_type', '(n/a)') . "\n";
}

// 4) Tu usuario
if ($myEmail) {
    $me = DB::table('users')->where('email', $myEmail)->first();
    echo "\n[TU USUARIO con email={$myEmail}]\n";
    if (! $me) {
        echo "  ✗ No existe usuario con ese email\n";
    } else {
        $cols = DB::getSchemaBuilder()->getColumnListing('users');
        echo "  id    : " . safe_get($me, 'id') . "\n";
        if (in_array('full_name', $cols, true)) echo "  full_name : " . safe_get($me, 'full_name', '(sin nombre)') . "\n";
        if (in_array('name', $cols, true))      echo "  name      : " . safe_get($me, 'name', '(sin nombre)') . "\n";
        if (in_array('role', $cols, true))      echo "  role      : " . safe_get($me, 'role', '(sin role)') . "\n";

        $myProf = DB::table('provider_profiles')->where('user_id', $me->id)->first();
        if ($myProf) {
            echo "  provider_profile_id: {$myProf->id}\n";

            $tuyos = DB::table('provider_services')
                ->where('provider_profile_id', $myProf->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id','title']);
            echo "\n[TUS SERVICIOS] (" . count($tuyos) . ")\n";
            foreach ($tuyos as $s) echo "  · #{$s->id} {$s->title}\n";
        } else {
            echo "  provider_profile: NO TIENES (no eres proveedor con esta cuenta)\n";
        }

        echo "\n[VEREDICTO authorize() para subir foto al servicio #{$serviceId}]\n";
        $myRole = safe_get($me, 'role', '');
        if ($prof->user_id == $me->id) {
            echo "  ✓ Eres el dueño. NO debería dar 403.\n";
            echo "  Si igual da 403: 1) cierra sesión y vuelve a entrar (token nuevo).\n";
            echo "                   2) ctrl+F5 para recargar JS bundle.\n";
        } elseif ($myRole === 'admin') {
            echo "  ✓ Eres admin: el código permite subir aunque no seas dueño.\n";
        } else {
            echo "  ✗ El servicio #{$serviceId} pertenece al USER#{$prof->user_id} ({$owner->email}).\n";
            echo "    Tu user es #{$me->id} ({$me->email}).\n";
            echo "    Por eso da 403.\n";
        }
    }
} else {
    echo "\n→ Para diagnóstico completo abre con tu email:\n";
    echo "  ?id={$serviceId}&my=tu@correo.com\n";
    echo "→ O lista todos los proveedores y sus servicios:\n";
    echo "  ?listusers=1\n";
}

echo "\nBORRA _diag_service.php cuando termines.\n";
