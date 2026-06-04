<?php
/**
 * Crea las carpetas que Laravel necesita y que el tarball no incluyó (estaban vacías).
 * Sube a /v1/chamba/public/_init.php y visita la URL.
 * BORRA después.
 */

header('Content-Type: text/plain; charset=utf-8');
@error_reporting(E_ALL);

$base = dirname(__DIR__);

$dirs = [
    'storage',
    'storage/app',
    'storage/app/private',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/logs',
    'bootstrap/cache',
];

echo "=== CREANDO CARPETAS ===\n";
foreach ($dirs as $d) {
    $full = $base . '/' . $d;
    if (! file_exists($full)) {
        $ok = @mkdir($full, 0775, true);
        echo ($ok ? '[CREADO ]' : '[ERROR  ]') . " $d\n";
    } else {
        echo "[OK     ] $d (ya existe)\n";
    }
    @chmod($full, 0775);
}

echo "\n=== PERMISOS ===\n";
foreach ($dirs as $d) {
    $full = $base . '/' . $d;
    $perms = file_exists($full) ? substr(sprintf('%o', fileperms($full)), -4) : '----';
    $w = is_writable($full) ? 'W' : '!w';
    echo "$perms $w  $d\n";
}

// Limpiar caches que se hayan podido escribir mal
echo "\n=== LIMPIEZA DE CACHE ===\n";
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
    @unlink($f);
    echo "borrado: " . basename($f) . "\n";
}
foreach (glob($base.'/storage/framework/views/*.php') ?: [] as $f) {
    @unlink($f);
}
$flag = $base.'/storage/app/setup-completed.flag';
if (file_exists($flag)) {
    @unlink($flag);
    echo "borrado flag setup-completed\n";
}

// Limpiar log viejo para no confundirnos en el próximo intento
$today = date('Y-m-d');
$logFile = $base.'/storage/logs/laravel-'.$today.'.log';
if (file_exists($logFile) && filesize($logFile) > 1000) {
    @copy($logFile, $logFile.'.archive');
    @file_put_contents($logFile, '');
    echo "log de hoy archivado y truncado\n";
}

// Probar bootstrap rápido para confirmar que ya arranca
echo "\n=== PRUEBA RÁPIDA ===\n";
try {
    require $base.'/vendor/autoload.php';
    $app = require_once $base.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    echo "Bootstrap: OK\n";
    echo "APP_URL: " . config('app.url') . "\n";
    echo "View compiled path: " . config('view.compiled') . "\n";
    echo "Cache store: " . config('cache.default') . "\n";

    // Compilar una view dummy para asegurar que escribe en views/
    try {
        \Illuminate\Support\Facades\Blade::compileString('<h1>{{ $x }}</h1>');
        echo "Blade compile: OK\n";
    } catch (\Throwable $e) {
        echo "Blade compile: ERROR " . $e->getMessage() . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "  en " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== SIGUIENTE PASO ===\n";
echo "Visita: https://jaapsystem.com/v1/chamba/setup?token=5cb882d25fca17707a97a4186c893b90017175a6ce9c1750&admin_email=jesusalexander96@hotmail.com&admin_password=12345678\n";
