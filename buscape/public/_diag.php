<?php
/**
 * Diagnóstico completo del 500.
 * Visita: https://jaapsystem.com/v1/chamba/public/_diag.php
 * Borra el archivo después.
 */
header('Content-Type: text/plain; charset=utf-8');

$base = realpath(__DIR__ . '/..');
echo "=== INFO BÁSICA ===\n";
echo "PHP " . PHP_VERSION . " (" . php_sapi_name() . ")\n";
echo "App root: $base\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

echo "=== ESTRUCTURA RAÍZ ===\n";
foreach (scandir($base) as $f) {
    if ($f === '.' || $f === '..') continue;
    $p = $base . '/' . $f;
    $type = is_dir($p) ? 'DIR ' : 'FILE';
    $size = is_file($p) ? number_format(filesize($p)) : '-';
    echo "  [$type] $f (size=$size)\n";
}

echo "\n=== ARCHIVOS CRÍTICOS ===\n";
$critical = [
    'public/index.php',
    'bootstrap/app.php',
    'vendor/autoload.php',
    '.env',
    '.htaccess',
    'public/.htaccess',
    'routes/web.php',
    'routes/api.php',
    'app/Services/MediaStorageService.php',
    'app/Http/Controllers/Api/V1/MediaController.php',
];
foreach ($critical as $c) {
    $p = $base . '/' . $c;
    if (file_exists($p)) {
        echo "  [OK]   $c (" . number_format(filesize($p)) . " bytes)\n";
    } else {
        echo "  [FALTA] $c\n";
    }
}

echo "\n=== CARPETAS DE STORAGE ===\n";
$dirs = [
    'storage', 'storage/app', 'storage/framework', 'storage/framework/cache',
    'storage/framework/sessions', 'storage/framework/views', 'storage/logs',
    'bootstrap/cache', 'vendor', 'public/build',
];
foreach ($dirs as $d) {
    $p = $base . '/' . $d;
    if (is_dir($p)) {
        $writable = is_writable($p) ? 'writable' : 'NO WRITABLE';
        echo "  [OK]   $d ($writable)\n";
    } else {
        echo "  [FALTA] $d\n";
    }
}

echo "\n=== CACHES COMPILADOS ===\n";
foreach (['config.php', 'routes-v7.php', 'services.php', 'packages.php', 'events.php'] as $f) {
    $p = $base . '/bootstrap/cache/' . $f;
    if (file_exists($p)) {
        echo "  EXISTE: bootstrap/cache/$f (" . number_format(filesize($p)) . " bytes, "
            . date('Y-m-d H:i:s', filemtime($p)) . ")\n";
    }
}

echo "\n=== .ENV (claves principales) ===\n";
if (file_exists($base . '/.env')) {
    $env = file_get_contents($base . '/.env');
    foreach (['APP_URL', 'APP_ENV', 'APP_DEBUG', 'APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'] as $k) {
        if (preg_match("/^$k=(.+)$/m", $env, $m)) {
            $v = trim($m[1]);
            if ($k === 'APP_KEY') $v = substr($v, 0, 25) . '…';
            if ($k === 'DB_HOST') $v = $v;
            echo "  $k = $v\n";
        }
    }
} else {
    echo "  .env NO existe\n";
}

echo "\n=== ÚLTIMO LOG DE LARAVEL ===\n";
$logsDir = $base . '/storage/logs';
if (is_dir($logsDir)) {
    $logs = glob($logsDir . '/laravel-*.log');
    if ($logs) {
        usort($logs, fn($a, $b) => filemtime($b) - filemtime($a));
        $latest = $logs[0];
        echo "Archivo: " . basename($latest) . " (mod: " . date('Y-m-d H:i:s', filemtime($latest)) . ")\n";
        echo "Tamaño: " . number_format(filesize($latest)) . " bytes\n\n";
        echo "----- ÚLTIMOS 8000 BYTES -----\n";
        $fp = fopen($latest, 'r');
        if ($fp) {
            $size = filesize($latest);
            $offset = max(0, $size - 8000);
            fseek($fp, $offset);
            echo fread($fp, 8000);
            fclose($fp);
        }
    } else {
        echo "(sin archivos de log)\n";
    }
} else {
    echo "storage/logs/ NO existe\n";
}

echo "\n\n=== INTENTAR BOOTSTRAP DE LARAVEL ===\n";
try {
    require $base . '/vendor/autoload.php';
    echo "  [OK] autoload.php cargado\n";

    $app = require_once $base . '/bootstrap/app.php';
    echo "  [OK] bootstrap/app.php devolvió: " . (is_object($app) ? get_class($app) : gettype($app)) . "\n";

    if (method_exists($app, 'environment')) {
        echo "  Environment: " . $app->environment() . "\n";
        echo "  Debug: " . (config('app.debug') ? 'true' : 'false') . "\n";
    }
} catch (\Throwable $e) {
    echo "  [ERROR] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "  En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Stack (top 10):\n";
    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 10) as $i => $t) {
        $loc = isset($t['file']) ? $t['file'] . ':' . ($t['line'] ?? '?') : '(internal)';
        echo "    #$i $loc " . ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '') . "()\n";
    }
}

echo "\n=== FIN ===\n";
echo "IMPORTANTE: borra public/_diag.php cuando termines.\n";
