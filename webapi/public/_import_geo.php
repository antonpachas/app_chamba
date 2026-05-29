<?php
/**
 * Importador remoto de UBIGEO Perú (departamentos, provincias, distritos).
 *
 * Uso:
 *   https://jaapsystem.com/v1/chamba/public/_import_geo.php?token=XXX&batch=50&offset=0
 *
 * Modos:
 *   ?token=XXX&status=1        → conteos en BD
 *   ?token=XXX&release_lock=1  → quita bloqueo si quedó colgado un import anterior
 *   ?token=XXX&batch=50&offset=0 → lotes (recomendado en producción)
 *
 * BORRA este archivo después de usarlo.
 */

@ini_set('display_errors', '0');
@error_reporting(E_ALL);
set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/plain; charset=utf-8');

$base = realpath(__DIR__ . '/..');
if (! $base) {
    http_response_code(500);
    exit("No pude resolver el directorio base.\n");
}

$envPath = $base.'/.env';
if (! file_exists($envPath)) {
    http_response_code(500);
    exit("Falta .env en $envPath\n");
}
$envText = file_get_contents($envPath);
$expectedToken = '';
if (preg_match('/^CHAMBA_SETUP_TOKEN=(.*)$/m', $envText, $m)) {
    $expectedToken = trim($m[1], "\"' \r\n");
}
if ($expectedToken === '') {
    http_response_code(403);
    exit("CHAMBA_SETUP_TOKEN está vacío en .env.\n");
}
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';
if (! hash_equals($expectedToken, $given)) {
    http_response_code(403);
    exit("Token inválido.\n");
}

$lockPath = $base.'/storage/app/geo_import.lock';
$statusOnly = isset($_GET['status']);
$releaseLock = isset($_GET['release_lock']);
$forceDownload = isset($_GET['download']);
$batch = isset($_GET['batch']) ? max(25, min(150, (int) $_GET['batch'])) : 50;
$offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
$useBatch = ! isset($_GET['full']);

$csvPath = $base.'/storage/app/ubigeo_distrito.csv';
$csvUrl = 'https://raw.githubusercontent.com/jmcastagnetto/ubigeo-peru-aumentado/main/ubigeo_distrito.csv';

echo "=== CHAMBA · IMPORT UBIGEO PERÚ ===\n";
echo 'Fecha: '.date('Y-m-d H:i:s')."\n";
echo "App: $base\n";

if ($releaseLock) {
    if (is_file($lockPath)) {
        @unlink($lockPath);
        echo "[OK] Bloqueo geo_import.lock eliminado.\n";
    } else {
        echo "No había archivo de bloqueo.\n";
    }
    if ($statusOnly) {
        exit;
    }
}

// ── Bootstrap Laravel ─────────────────────────────────────────────
try {
    require_once $base.'/vendor/autoload.php';
    $app = require $base.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    \Illuminate\Support\Facades\Config::set('app.debug', false);
    echo "[OK] Laravel bootstrap (env=".$app->environment().")\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo '[ERROR bootstrap] '.$e->getMessage()."\n";
    exit;
}

if (function_exists('ob_implicit_flush')) {
    ob_implicit_flush(true);
}
while (ob_get_level() > 0) {
    @ob_end_flush();
}

function geoCounts(): string
{
    $dept = \App\Models\Department::query()->count();
    $prov = \App\Models\Province::query()->count();
    $dist = \App\Models\District::query()->count();

    return "Departamentos: $dept\nProvincias: $prov\nDistritos: $dist\n";
}

if ($statusOnly) {
    echo "\n=== CONTEO ACTUAL ===\n";
    echo geoCounts();
    echo "\nEsperado: ~25 deptos, ~196 provincias, ~1893 distritos.\n";
    echo "Importar por lotes: ?_import_geo.php?token=TOKEN&batch=50&offset=0\n";
    echo "Si hubo error de bloqueo: &release_lock=1\n";
    exit;
}

// ── Un solo import a la vez ───────────────────────────────────────
if (is_file($lockPath)) {
    $age = time() - (int) filemtime($lockPath);
    if ($age < 900) {
        http_response_code(409);
        echo "[OCUPADO] Ya hay un import geo en curso (hace {$age}s).\n";
        echo "Cierra otras pestañas y la consola local (php artisan chamba:import-peru-ubigeo).\n";
        echo "Si estás seguro de que terminó, usá:\n";
        echo "  .../_import_geo.php?token=TU_TOKEN&release_lock=1\n";
        exit;
    }
    @unlink($lockPath);
    echo "[aviso] Bloqueo antiguo eliminado automáticamente.\n";
}
file_put_contents($lockPath, (string) getmypid());
register_shutdown_function(static function () use ($lockPath): void {
    @unlink($lockPath);
});

if ($useBatch) {
    echo "Modo: LOTE (batch=$batch, offset=$offset)\n\n";
} else {
    echo "Modo: IMPORTACIÓN COMPLETA (puede tardar mucho; preferí lotes con batch=50)\n\n";
}

if (! is_file($csvPath) || $forceDownload) {
    echo "Descargando CSV…\n";
    $ctx = stream_context_create([
        'http' => ['timeout' => 120, 'user_agent' => 'ChambaGeoImport/1.0'],
    ]);
    $body = @file_get_contents($csvUrl, false, $ctx);
    if ($body === false || trim($body) === '') {
        @unlink($lockPath);
        http_response_code(500);
        exit("[ERROR] No se pudo descargar el CSV.\n");
    }
    if (! is_dir(dirname($csvPath))) {
        mkdir(dirname($csvPath), 0755, true);
    }
    file_put_contents($csvPath, $body);
    echo '[OK] CSV guardado ('.number_format(strlen($body))." bytes)\n";
} else {
    echo '[OK] CSV local: '.number_format(filesize($csvPath))." bytes\n";
}

echo "\n=== ANTES ===\n";
echo geoCounts();

echo "\n=== IMPORTANDO ===\n";
flush();

$params = ['--file' => $csvPath];
if ($useBatch) {
    $params['--offset'] = (string) $offset;
    $params['--limit'] = (string) $batch;
}

$exitCode = 1;
$output = '';
try {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('chamba:import-peru-ubigeo', $params);
    $output = \Illuminate\Support\Facades\Artisan::output();
    echo $output;
} catch (\Throwable $e) {
    @unlink($lockPath);
    http_response_code(500);
    echo "\n[ERROR] ".$e->getMessage()."\n";
    if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait')) {
        echo "\n→ Cierra importaciones en tu PC y en otras pestañas del navegador.\n";
        echo "→ Espera 2 minutos y repetí el MISMO offset.\n";
        echo "→ O liberá: ...&release_lock=1\n";
    }
    exit;
}

@unlink($lockPath);

if ($exitCode !== 0) {
    http_response_code(500);
    echo "\n[ERROR] El comando terminó con código $exitCode.\n";
    if (str_contains($output, 'Bloqueo en BD')) {
        echo "Repetí el mismo offset después de release_lock=1\n";
    }
    exit;
}

echo "\n=== DESPUÉS ===\n";
echo geoCounts();

if ($useBatch) {
    $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $dataLines = max(0, count($lines) - 1);
    $next = $offset + $batch;
    if ($next < $dataLines) {
        $token = rawurlencode($given);
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $nextUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            .'://'.$_SERVER['HTTP_HOST']
            .$scriptDir
            ."/_import_geo.php?token={$token}&batch={$batch}&offset={$next}";
        echo "\n=== SIGUIENTE LOTE ===\n";
        echo "Progreso: $next / $dataLines filas.\n\n";
        echo $nextUrl."\n";
        exit;
    }
    echo "\n[OK] Todos los lotes completados.\n";
}

echo "\n=== HECHO ===\n";
echo "Borrá public/_import_geo.php cuando termines.\n";
