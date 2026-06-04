<?php
/**
 * Migrador remoto para Chamba (no requiere SSH).
 *
 * Uso:
 *   https://jaapsystem.com/v1/chamba/public/_migrate.php?token=XXX
 *
 * El token debe coincidir con CHAMBA_SETUP_TOKEN en tu .env.
 *
 * Modos:
 *   ?token=XXX              → ejecuta migrate --force
 *   ?token=XXX&status=1     → solo muestra estado sin migrar
 *   ?token=XXX&seed=1       → además corre db:seed --force (categorías, planes, etc.)
 *   ?token=XXX&fresh=1      → DESTRUCTIVO: migrate:fresh (borra y reconstruye TODO)
 *
 * BORRA este archivo después de usarlo.
 */

set_time_limit(180);
header('Content-Type: text/plain; charset=utf-8');

$base = realpath(__DIR__ . '/..');
if (!$base) {
    http_response_code(500);
    exit("No pude resolver el directorio base.\n");
}

// ── Validación de token (sin cargar Laravel todavía) ──────────────
$envPath = $base . '/.env';
if (!file_exists($envPath)) {
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
    exit("CHAMBA_SETUP_TOKEN está vacío en .env. Define un token antes de usar este script.\n");
}
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';
if (!hash_equals($expectedToken, $given)) {
    http_response_code(403);
    exit("Token inválido.\n");
}

$status = isset($_GET['status']);
$seed   = isset($_GET['seed']);
$fresh  = isset($_GET['fresh']);

echo "=== CHAMBA · MIGRADOR REMOTO ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "App: $base\n";
echo "Modo: ";
if ($status) echo "STATUS (solo lectura)\n";
elseif ($fresh) echo "FRESH (DESTRUCTIVO: borra y reconstruye todo)\n";
else echo "MIGRATE --force\n";
if ($seed && !$status) echo "  + db:seed --force\n";
echo "\n";

// ── Bootstrap mínimo de Laravel ───────────────────────────────────
try {
    require_once $base . '/vendor/autoload.php';
    $app = require $base . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "[OK] Laravel bootstrap exitoso (env=" . $app->environment() . ")\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo "[ERROR bootstrap] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "  en " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit;
}

// ── DB ping antes de migrar ───────────────────────────────────────
try {
    $db = $app->make('db');
    $version = $db->select('SELECT VERSION() AS v')[0]->v ?? '?';
    $dbName = $db->getDatabaseName();
    echo "[OK] Conexión BD: $dbName (MySQL $version)\n\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo "[ERROR BD] " . $e->getMessage() . "\n";
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────
function runArtisan(string $cmd, array $params = []): string
{
    \Illuminate\Support\Facades\Artisan::call($cmd, $params);
    return \Illuminate\Support\Facades\Artisan::output();
}

// ── Status (solo lectura) ─────────────────────────────────────────
if ($status) {
    echo "=== MIGRATE:STATUS ===\n";
    echo runArtisan('migrate:status');
    echo "\nBORRA public/_migrate.php cuando termines.\n";
    exit;
}

// ── Modo FRESH (destructivo) ──────────────────────────────────────
if ($fresh) {
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'YES') {
        http_response_code(400);
        echo "Modo FRESH requiere confirmación explícita. Agrega &confirm=YES a la URL.\n";
        echo "ADVERTENCIA: esto BORRA todas las tablas y datos.\n";
        exit;
    }
    echo "=== MIGRATE:FRESH (DESTRUCTIVO) ===\n";
    try {
        echo runArtisan('migrate:fresh', ['--force' => true]);
    } catch (\Throwable $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        exit;
    }
} else {
    // ── Modo normal: migrate --force ───────────────────────────────
    echo "=== MIGRATE --force ===\n";
    try {
        echo runArtisan('migrate', ['--force' => true]);
    } catch (\Throwable $e) {
        echo "[ERROR migrate] " . $e->getMessage() . "\n";
        exit;
    }
}

// ── Seeders (opcional) ────────────────────────────────────────────
if ($seed) {
    echo "\n=== DB:SEED --force ===\n";
    try {
        echo runArtisan('db:seed', ['--force' => true]);
    } catch (\Throwable $e) {
        echo "[ERROR seed] " . $e->getMessage() . "\n";
    }
}

// ── Limpiar y recachear ───────────────────────────────────────────
echo "\n=== LIMPIAR Y RECACHEAR ===\n";
foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $c) {
    try { runArtisan($c); echo "[OK] $c\n"; } catch (\Throwable $e) { echo "[warn] $c: ".$e->getMessage()."\n"; }
}
foreach (['config:cache', 'route:cache', 'view:cache', 'event:cache'] as $c) {
    try { runArtisan($c); echo "[OK] $c\n"; } catch (\Throwable $e) { echo "[warn] $c: ".$e->getMessage()."\n"; }
}

echo "\n=== ESTADO FINAL ===\n";
echo runArtisan('migrate:status');

echo "\n=== HECHO ===\n";
echo "Probá:\n";
echo "  https://jaapsystem.com/v1/chamba/app\n";
echo "  https://jaapsystem.com/v1/chamba/api/v1/categories\n";
echo "\nIMPORTANTE: borra public/_migrate.php cuando termines.\n";
