<?php
/**
 * Reset de OPcache + parser real del log.
 * Sube a /v1/chamba/public/_reset.php y visita la URL.
 * BORRA después.
 */

header('Content-Type: text/plain; charset=utf-8');
@error_reporting(E_ALL);
@ini_set('display_errors', '1');

$base = dirname(__DIR__);

echo "=== OPCACHE STATUS ===\n";
if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(false);
    if ($st) {
        echo "Habilitado: " . ($st['opcache_enabled'] ? 'SI' : 'NO') . "\n";
        echo "Scripts cacheados: " . ($st['opcache_statistics']['num_cached_scripts'] ?? '?') . "\n";
        echo "Memoria usada: " . round(($st['memory_usage']['used_memory'] ?? 0) / 1024 / 1024, 1) . " MB\n";
    } else {
        echo "opcache_get_status no devolvió datos (puede estar restringido).\n";
    }
} else {
    echo "OPcache extension NO presente.\n";
}

echo "\n=== RESET ===\n";
$reset = false;
if (function_exists('opcache_reset')) {
    $reset = @opcache_reset();
    echo "opcache_reset(): " . ($reset ? 'OK' : 'FAIL/null') . "\n";
}
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    echo "clearstatcache(): OK\n";
}
if (function_exists('apcu_clear_cache')) {
    @apcu_clear_cache();
    echo "apcu_clear_cache(): intentado\n";
}

// Toca archivos clave para forzar a OPcache a re-leer
foreach ([$base.'/public/index.php', $base.'/bootstrap/app.php', $base.'/.htaccess'] as $f) {
    if (file_exists($f)) {
        @touch($f);
        if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
        echo "touch + invalidate: $f\n";
    }
}

echo "\n=== LOG: PRIMEROS ERRORES DE HOY ===\n";
$logs = glob("$base/storage/logs/*.log") ?: [];
if (! $logs) {
    echo "(sin logs)\n";
} else {
    usort($logs, fn($a, $b) => filemtime($b) - filemtime($a));
    $latest = $logs[0];
    echo "Archivo: " . basename($latest) . " (mod: " . date('Y-m-d H:i:s T', filemtime($latest)) . ")\n";

    $lines = file($latest);
    if ($lines) {
        // Tomar las primeras 60 líneas que contienen "ERROR:" o stack trace inicial
        $shown = 0;
        $inTrace = false;
        $totalLines = count($lines);
        // Leer desde el final hacia atrás para encontrar el ÚLTIMO bloque de error
        $blocks = [];
        $current = '';
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/local\.ERROR|production\.ERROR/', $line)) {
                $current = $line . $current;
                $blocks[] = $current;
                $current = '';
                if (count($blocks) >= 1) break;
            } else {
                $current = $line . $current;
            }
        }
        if ($blocks) {
            $error = $blocks[0];
            // Mostrar la línea ERROR + las primeras 25 líneas de stack
            $errorLines = explode("\n", $error);
            echo "----- ÚLTIMO ERROR -----\n";
            foreach (array_slice($errorLines, 0, 35) as $l) {
                echo $l . "\n";
            }
        } else {
            echo "(no se encontró línea ERROR)\n";
        }
    }
}

echo "\n=== AHORA ===\n";
echo "1. Sustituye el .htaccess raíz por la versión que te paso.\n";
echo "2. Visita https://jaapsystem.com/v1/chamba/setup?token=TU_TOKEN&admin_email=...\n";
echo "3. Si sigue fallando, revisa el log otra vez con _log.php (debería mostrar paths con /chamba/, no /chamba_app/).\n";
