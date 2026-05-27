<?php
/**
 * CHAMBA · Fix integral de routing en subdirectorio
 * --------------------------------------------------------------------
 * Sube a:  /v1/chamba/public/_fix_htaccess.php
 * Y ábrelo en el navegador.
 *
 * Hace 4 cosas:
 *   1) Reescribe `.htaccess` raíz con la versión correcta.
 *   2) Patcha `public/index.php` para pelar el prefijo /v1/chamba del
 *      REQUEST_URI antes de que Laravel arranque (necesario en hostings
 *      LiteSpeed/Apache donde Symfony no detecta la baseUrl).
 *   3) Limpia caches de Laravel (config/route/view).
 *   4) Verifica con curl que /app y /api/* respondan HTTP 200.
 *
 * BORRA ESTE ARCHIVO cuando confirmes que todo funciona.
 */

header('Content-Type: text/plain; charset=utf-8');

$appRoot   = realpath(__DIR__ . '/..');
$htaccess  = $appRoot . '/.htaccess';
$indexPhp  = __DIR__ . '/index.php';

echo "CHAMBA · Fix integral de routing\n";
echo str_repeat('=', 70) . "\n";
echo "App root : {$appRoot}\n";
echo "Fecha    : " . date('Y-m-d H:i:s') . "\n\n";

// ── 1) .htaccess raíz ────────────────────────────────────────────────
echo "[1] .HTACCESS RAÍZ\n";
echo str_repeat('-', 70) . "\n";

$htContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    Options -MultiViews -Indexes

    <IfModule mod_dir.c>
        DirectorySlash Off
    </IfModule>

    RewriteEngine On
    RewriteBase /v1/chamba/

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteRule ^public/ - [L]

    RewriteCond %{DOCUMENT_ROOT}/v1/chamba/public/$1 -f
    RewriteRule ^(.+)$ public/$1 [L]

    RewriteRule ^app/$ /v1/chamba/app [L,R=301]

    RewriteRule ^(.*)$ public/index.php [L]
</IfModule>

DirectoryIndex public/index.php

<FilesMatch "^(\.env|\.env\..+|composer\.json|composer\.lock|artisan|deploy\.sh|DEPLOY\.md|package\.json|package-lock\.json|vite\.config\.js|phpunit\.xml)$">
    Require all denied
</FilesMatch>

RedirectMatch 403 ^/v1/chamba/(bootstrap|config|database|resources|routes|storage|tests|vendor)(/|$)
HTACCESS;

if (is_file($htaccess)) {
    $bak = $htaccess . '.bak-' . date('Ymd-His');
    @copy($htaccess, $bak);
    echo "  Backup    : " . basename($bak) . "\n";
}
$w = @file_put_contents($htaccess, $htContent);
if ($w === false) {
    echo "  ✗ ERROR escribiendo .htaccess (permisos)\n";
} else {
    @chmod($htaccess, 0644);
    echo "  ✓ .htaccess escrito ({$w} bytes)\n";
}
echo "\n";

// ── 2) Patch a public/index.php ──────────────────────────────────────
echo "[2] PATCH public/index.php (strip de prefijo)\n";
echo str_repeat('-', 70) . "\n";

$current = @file_get_contents($indexPhp);
if ($current === false) {
    echo "  ✗ ERROR leyendo public/index.php\n";
} else {
    $marker = '// ── Fix de prefijo para deploys en subdirectorio';
    if (strpos($current, $marker) !== false) {
        echo "  ℹ Ya parchado, no se modifica.\n";
    } else {
        $patch = <<<'PHP'
// ── Fix de baseUrl para deploys en subdirectorio "aplanado" ─────────────
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
    if (strncmp($req, $base . '/public/', strlen($base) + 8) !== 0) {
        $fakeScript = $base . '/index.php';
        $_SERVER['SCRIPT_NAME']    = $fakeScript;
        $_SERVER['PHP_SELF']       = $fakeScript;
        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
    }
}

PHP;
        // Inyectar el patch justo después del define('LARAVEL_START', ...)
        $patched = preg_replace(
            '/(define\(\s*[\'"]LARAVEL_START[\'"][^;]*;\s*\n)/',
            "$1\n" . $patch,
            $current,
            1
        );
        if ($patched === $current || $patched === null) {
            echo "  ✗ No se encontró 'define(LARAVEL_START...)'. Patch manual requerido.\n";
        } else {
            $bak = $indexPhp . '.bak-' . date('Ymd-His');
            @copy($indexPhp, $bak);
            echo "  Backup    : " . basename($bak) . "\n";
            $wp = @file_put_contents($indexPhp, $patched);
            if ($wp === false) {
                echo "  ✗ ERROR escribiendo public/index.php\n";
            } else {
                echo "  ✓ public/index.php parchado ({$wp} bytes)\n";
            }
        }
    }
}
echo "\n";

// ── 3) Limpiar caches ────────────────────────────────────────────────
echo "[3] LIMPIAR CACHES\n";
echo str_repeat('-', 70) . "\n";
$cacheTargets = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/events.php',
];
foreach ($cacheTargets as $rel) {
    $abs = $appRoot . '/' . $rel;
    if (is_file($abs)) {
        if (@unlink($abs)) {
            echo "  ✓ borrado: {$rel}\n";
        } else {
            echo "  ✗ no se pudo borrar: {$rel}\n";
        }
    }
}
$viewsDir = $appRoot . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $n = 0;
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) {
        if (@unlink($f)) $n++;
    }
    echo "  ✓ vistas compiladas borradas: {$n}\n";
}
@clearstatcache(true);
if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "  ✓ opcache reseteado\n";
}
echo "\n";

// ── 4) Verificación HTTP ─────────────────────────────────────────────
echo "[4] VERIFICACIÓN HTTP\n";
echo str_repeat('-', 70) . "\n";

$urls = [
    ['url' => 'https://jaapsystem.com/v1/chamba/app',                     'esperado' => '200 (HTML SPA)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/categories',        'esperado' => '200 (JSON)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/settings/public',   'esperado' => '200 (JSON)'],
    ['url' => 'https://jaapsystem.com/v1/chamba/foo-bar-xyz',              'esperado' => '404 Laravel'],
];

foreach ($urls as $t) {
    $ch = curl_init($t['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $err  = curl_error($ch);
    curl_close($ch);

    $body = $resp ? substr($resp, $hsz) : '';
    echo "  " . $t['url'] . "\n";
    echo "    Esperado : " . $t['esperado'] . "\n";
    if ($err) {
        echo "    ERROR cURL: {$err}\n";
        continue;
    }
    $marker = $code >= 200 && $code < 400 ? '✓' : ($code === 404 ? '·' : '✗');
    echo "    HTTP     : {$marker} {$code}\n";
    $snippet = preg_replace('/\s+/', ' ', trim(substr($body, 0, 180)));
    echo "    Body[0:180]: {$snippet}\n\n";
}

echo str_repeat('=', 70) . "\n";
echo "Si /app y /api/v1/categories responden 200, abre en el navegador:\n";
echo "  https://jaapsystem.com/v1/chamba/app\n\n";
echo "BORRA estos archivos cuando confirmes que funciona:\n";
echo "  public/_fix_htaccess.php\n";
echo "  public/_route_test.php\n";
echo "  public/_diag.php\n";
echo "  public/_migrate.php\n";
echo "  public/_reset.php\n";
echo "  public/_ftp_test.php\n";
