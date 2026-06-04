<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ── Fix de baseUrl para deploys en subdirectorio "aplanado" ─────────────
// Cuando la app vive en /v1/chamba/ (estructura aplanada: Laravel y su
// carpeta public/ comparten el mismo directorio web) y el .htaccess raíz
// reescribe a public/index.php, Symfony detecta SCRIPT_NAME =
// /v1/chamba/public/index.php pero REQUEST_URI = /v1/chamba/app (sin
// /public/), entonces no logra extraer la baseUrl y todas las rutas
// devuelven 404. Lo solucionamos haciendo que SCRIPT_NAME parezca vivir
// directamente en el prefijo, sin /public/.
$prefix = getenv('APP_PATH_PREFIX');
if ($prefix === false || $prefix === '') {
    $envPath = __DIR__ . '/../.env';
    if (is_file($envPath)) {
        $env = @file_get_contents($envPath);
        if ($env && preg_match('/^APP_URL\s*=\s*"?([^"\r\n]+)"?/m', $env, $m)) {
            $parsed = parse_url(trim($m[1]));
            if (!empty($parsed['path'])) {
                $prefix = trim($parsed['path'], '/');
            }
        }
    }
}
$prefix = is_string($prefix) ? trim($prefix, '/') : '';
if ($prefix !== '' && isset($_SERVER['REQUEST_URI'])) {
    $base = '/' . $prefix;
    $req  = $_SERVER['REQUEST_URI'];
    // No tocamos accesos directos a /v1/chamba/public/* (scripts de diagnóstico).
    if (strncmp($req, $base . '/public/', strlen($base) + 8) !== 0) {
        $fakeScript = $base . '/index.php';
        $_SERVER['SCRIPT_NAME']    = $fakeScript;
        $_SERVER['PHP_SELF']       = $fakeScript;
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
