<?php
/**
 * CHAMBA · Diagnóstico avanzado de routing
 * --------------------------------------------------------------------
 * Sube a:  /v1/chamba/public/_route_test.php
 * Y ábrelo en el navegador.
 *
 * Hace 4 pruebas:
 *   1) Lista los .htaccess de los niveles superiores y muestra su contenido.
 *   2) Verifica módulos Apache disponibles.
 *   3) Prueba múltiples URLs (con y sin rewrite) para aislar el problema.
 *   4) Diagnostica la causa raíz del 404.
 *
 * BORRA ESTE ARCHIVO al terminar.
 */

header('Content-Type: text/plain; charset=utf-8');
echo "CHAMBA · Diagnóstico de routing\n";
echo str_repeat('=', 70) . "\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$appRoot = realpath(__DIR__ . '/..');
echo "App root: {$appRoot}\n\n";

// ── 1) .htaccess de niveles superiores ──────────────────────────────
echo "[1] .HTACCESS EN NIVELES SUPERIORES\n";
echo str_repeat('-', 70) . "\n";

$paths = [];
$p = $appRoot;
for ($i = 0; $i < 5; $i++) {
    $paths[] = $p;
    $parent = dirname($p);
    if ($parent === $p) break;
    $p = $parent;
}

foreach ($paths as $dir) {
    $ht = $dir . '/.htaccess';
    $exists = is_file($ht);
    $size = $exists ? filesize($ht) : 0;
    $perm = $exists ? substr(sprintf('%o', fileperms($ht)), -4) : '----';
    echo "\n  {$ht}\n";
    echo "    Existe: " . ($exists ? "SI ({$size} bytes, perm {$perm})" : "no") . "\n";
    if ($exists && $size > 0 && $size < 5000) {
        $content = @file_get_contents($ht);
        if ($content !== false) {
            $lines = explode("\n", $content);
            echo "    Contenido (" . count($lines) . " líneas):\n";
            foreach ($lines as $i => $line) {
                if (trim($line) === '') continue;
                echo "      " . str_pad($i + 1, 3, ' ', STR_PAD_LEFT) . ": " . rtrim($line) . "\n";
            }
        }
    } elseif ($exists && $size >= 5000) {
        echo "    (archivo muy grande, mostrando primeras 20 líneas)\n";
        $lines = @file($ht, FILE_IGNORE_NEW_LINES);
        foreach (array_slice($lines, 0, 20) as $i => $line) {
            echo "      " . str_pad($i + 1, 3, ' ', STR_PAD_LEFT) . ": " . rtrim($line) . "\n";
        }
    }
}
echo "\n";

// ── 2) Servidor y módulos ────────────────────────────────────────────
echo "[2] SERVIDOR Y MÓDULOS\n";
echo str_repeat('-', 70) . "\n";
echo "  Server software : " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "  PHP version     : " . PHP_VERSION . "\n";
echo "  PHP SAPI        : " . PHP_SAPI . "\n";
echo "  Document root   : " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";

if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    echo "  apache_get_modules disponible: SI\n";
    foreach (['mod_rewrite', 'mod_dir', 'mod_alias', 'mod_headers'] as $mod) {
        echo "    {$mod}: " . (in_array($mod, $mods) ? '✓' : '✗') . "\n";
    }
} else {
    echo "  apache_get_modules: no disponible (probablemente LiteSpeed/CGI)\n";
}
echo "\n";

// ── 3) Pruebas de URLs ───────────────────────────────────────────────
echo "[3] PRUEBAS DE URLs (curl interno)\n";
echo str_repeat('-', 70) . "\n";

$tests = [
    ['ETAPA' => 'Sin rewrite (acceso directo)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/public/index.php',           'esperado' => '200 (SPA)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/public/api/v1/categories',    'esperado' => '200 JSON'],
    ['url' => 'https://jaapsystem.com/v1/chamba/public/api/v1/settings/public','esperado' => '200 JSON'],
    ['ETAPA' => 'Con rewrite desde raíz (depende de .htaccess raíz)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/categories',           'esperado' => '200 JSON si htaccess OK'],
    ['url' => 'https://jaapsystem.com/v1/chamba/app',                          'esperado' => '200 SPA si htaccess OK'],
    ['url' => 'https://jaapsystem.com/v1/chamba/foo-bar-xyz-nope',             'esperado' => '404 LARAVEL si htaccess OK / 404 SERVER si NO'],
    ['ETAPA' => 'Acceso por public/ explícito (alternativa si htaccess raíz falla)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/public/app',                   'esperado' => 'depende'],
];

foreach ($tests as $t) {
    if (isset($t['ETAPA'])) {
        echo "\n  --- " . $t['ETAPA'] . " ---\n";
        continue;
    }
    $ch = curl_init($t['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $resp   = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err    = curl_error($ch);
    curl_close($ch);

    $headers = substr($resp, 0, $hsize);
    $body    = substr($resp, $hsize);

    echo "\n  URL: " . $t['url'] . "\n";
    echo "    Esperado : " . $t['esperado'] . "\n";
    if ($err) {
        echo "    ERROR    : {$err}\n";
        continue;
    }
    $marker = ($code >= 200 && $code < 400) ? '✓' : '✗';
    echo "    HTTP     : {$marker} {$code}\n";

    // Detectar el origen del error
    $isLaravel = (stripos($headers, 'set-cookie:') !== false || stripos($body, 'laravel') !== false);
    $isServerError = (stripos($body, 'apache') !== false || stripos($body, 'litespeed') !== false || stripos($body, '<title>404 not found</title>') !== false);
    $contentType = '';
    if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $m)) $contentType = trim($m[1]);
    echo "    C-Type   : {$contentType}\n";

    if ($code === 404) {
        if ($isServerError) {
            echo "    Origen   : 404 del SERVIDOR (htaccess no aplica)\n";
        } elseif ($isLaravel || stripos($contentType, 'json') !== false) {
            echo "    Origen   : 404 de LARAVEL (htaccess SI aplica, ruta no existe)\n";
        } else {
            echo "    Origen   : indeterminado\n";
        }
    }

    // Snippet del body para inspección
    $snippet = trim(substr($body, 0, 200));
    $snippet = preg_replace('/\s+/', ' ', $snippet);
    echo "    Body[0:200]: " . $snippet . "\n";
}

echo "\n";

// ── 4) Diagnóstico final ─────────────────────────────────────────────
echo "[4] DIAGNÓSTICO Y SIGUIENTE PASO\n";
echo str_repeat('-', 70) . "\n";
echo "Interpretación:\n";
echo "  • Si /public/api/v1/categories devuelve 200 JSON pero /api/v1/categories\n";
echo "    devuelve 404 del servidor → el .htaccess raíz NO se aplica.\n";
echo "    SOLUCIÓN: usar /v1/chamba/public/ como base de la app.\n\n";
echo "  • Si ambos devuelven 200 → todo funciona, prueba en navegador:\n";
echo "    https://jaapsystem.com/v1/chamba/app\n\n";
echo "  • Si ambos devuelven 404 → revisa el log: storage/logs/laravel-*.log\n\n";
echo "IMPORTANTE: borra public/_route_test.php cuando termines.\n";
