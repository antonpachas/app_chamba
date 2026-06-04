<?php
/**
 * CHAMBA · Fix final del 404 en /api/v1/media/* (v2 — restaura backup + patch correcto)
 * --------------------------------------------------------------------
 * El v1 dejaba dos `{` consecutivos por error → 500.
 * Este v2:
 *   1) Si hay backup .bak-*, restaura el más reciente.
 *   2) Reemplaza el método show() ENTERO con una versión correcta.
 *   3) Verifica sintaxis con php -l.
 *   4) Limpia caches.
 *   5) Verifica con curl que un avatar real responda 200.
 *
 * Sube a:  /v1/chamba/public/_fix_media_404.php
 * Y ábrelo.  BORRA cuando confirmes que funciona.
 */

@ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$appRoot  = realpath(__DIR__ . '/..');
$ctrlPath = $appRoot . '/app/Http/Controllers/Api/V1/MediaController.php';

echo "CHAMBA · Fix final 404 media (v2)\n";
echo str_repeat('=', 70) . "\n";
echo "App   : {$appRoot}\n";
echo "Fecha : " . date('Y-m-d H:i:s') . "\n\n";

// ── 1) Restaurar backup si existe ───────────────────────────────────
echo "[1] Restaurando backup\n";
$dir = dirname($ctrlPath);
$backups = glob($dir . '/MediaController.php.bak-*');
if ($backups) {
    rsort($backups);
    $latest = $backups[0];
    echo "    Backups encontrados: " . count($backups) . "\n";
    echo "    Restaurando desde: " . basename($latest) . "\n";
    if (@copy($latest, $ctrlPath)) {
        echo "    ✓ Restaurado.\n";
    } else {
        echo "    ✗ ERROR restaurando. Continuamos con el archivo actual.\n";
    }
} else {
    echo "    (sin backup, asumiendo archivo limpio)\n";
}
echo "\n";

// ── 2) Reemplazar el método show() ENTERO ────────────────────────────
echo "[2] Aplicando patch al método show()\n";
$source = file_get_contents($ctrlPath);
if ($source === false) {
    echo "    ✗ No pude leer {$ctrlPath}\n";
    exit;
}

// Detectar marcador (idempotencia)
$marker = 'CHAMBA-MEDIA-FOLDER-FIX';
if (strpos($source, $marker) !== false) {
    echo "    Ya patchado anteriormente (marker presente). Continuamos a limpiar caches.\n\n";
} else {
    // Reemplazar el método show() ENTERO con regex
    $newMethod = <<<'PHP'
public function show(Request $request, string $name, ?string $folder = null): Response
    {
        // CHAMBA-MEDIA-FOLDER-FIX: el defaults('folder','...') de la ruta no se
        // inyecta como argumento en este Laravel/hosting; lo leemos del Request.
        $folder = $folder
            ?: (string) ($request->route()?->defaults['folder'] ?? '')
            ?: (string) ($request->route()?->parameter('folder') ?? '');

        if (! in_array($folder, ['avatars', 'services', 'payments'], true)) {
            abort(404);
        }

        if (! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            abort(400, 'Nombre inválido.');
        }

        $path = "{$folder}/{$name}";

        $cacheKey = "media:{$path}";
        $cached = Cache::get($cacheKey);
        if (! $cached) {
            $cached = $this->media->read($path);
            if (! $cached) abort(404);
            Cache::put($cacheKey, $cached, now()->addMinutes(30));
        }

        return response($cached['contents'], 200, [
            'Content-Type' => $cached['mime'],
            'Cache-Control' => $folder === 'payments' ? 'private, max-age=300' : 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
PHP;

    // Match el método show completo (con su firma original y todo el body hasta la `}` de cierre)
    $pattern = '/public function show\(Request \$request,[^)]*\): Response\s*\{.*?\n    \}/s';
    if (!preg_match($pattern, $source)) {
        echo "    ✗ No encontré el método show() con la firma esperada.\n";
        echo "    Posible que el archivo esté en estado intermedio. Comparto los primeros 600 chars:\n";
        echo "    --- inicio archivo ---\n    " . str_replace("\n", "\n    ", substr($source, 0, 600)) . "\n    --- fin ---\n";
        exit;
    }
    $patched = preg_replace($pattern, $newMethod, $source, 1);
    if ($patched === $source || $patched === null) {
        echo "    ✗ El replace no cambió nada.\n";
        exit;
    }

    $bak = $ctrlPath . '.bak-fix-v2-' . date('Ymd-His');
    @copy($ctrlPath, $bak);
    echo "    Backup pre-patch: " . basename($bak) . "\n";
    $w = @file_put_contents($ctrlPath, $patched);
    echo "    ✓ Escritos {$w} bytes al MediaController.php\n";
}

// ── 3) Verificar sintaxis con php -l ─────────────────────────────────
echo "\n[3] Verificando sintaxis PHP\n";
$lintCmd = 'php -l ' . escapeshellarg($ctrlPath) . ' 2>&1';
$lintOut = @shell_exec($lintCmd);
echo "    " . trim($lintOut ?: '(php -l no disponible)') . "\n";
if ($lintOut && stripos($lintOut, 'No syntax errors') === false) {
    echo "    ✗ Sintaxis INVÁLIDA. Restaurando backup original…\n";
    if ($backups) {
        @copy($backups[count($backups) - 1], $ctrlPath);
        echo "    ✓ Restaurado.\n";
    }
    exit;
}
echo "\n";

// ── 4) Bootear Laravel y limpiar caches ──────────────────────────────
echo "[4] Limpiando caches\n";
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::create('/_warm', 'GET'));

foreach (['bootstrap/cache/config.php','bootstrap/cache/routes-v7.php','bootstrap/cache/services.php','bootstrap/cache/packages.php','bootstrap/cache/events.php'] as $rel) {
    $abs = $appRoot . '/' . $rel;
    if (is_file($abs)) { @unlink($abs); echo "    ✓ borrado: {$rel}\n"; }
}
try {
    \Illuminate\Support\Facades\Cache::flush();
    echo "    ✓ Cache::flush()\n";
} catch (\Throwable $e) {
    echo "    ! Cache::flush: {$e->getMessage()}\n";
}
@clearstatcache(true);
if (function_exists('opcache_reset')) { @opcache_reset(); echo "    ✓ opcache_reset()\n"; }
echo "\n";

// ── 5) Verificación HTTP ─────────────────────────────────────────────
echo "[5] Verificación HTTP\n";
function _httpGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15, CURLOPT_HEADER => false,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return [$code, $ct, $body];
}

$testAvatar = null;
try {
    $row = \Illuminate\Support\Facades\DB::table('users')
        ->whereNotNull('avatar_path')->where('avatar_path', '<>', '')
        ->orderByDesc('updated_at')->first(['avatar_path']);
    $testAvatar = $row?->avatar_path;
} catch (\Throwable) {}

$tests = [];
if ($testAvatar) {
    $tests[] = ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($testAvatar, '/'), 'label' => 'avatar real',          'expect' => 200];
}
$tests[] =       ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/media/avatars/inexistente.png',                                'label' => 'avatar inexistente',  'expect' => 404];
$tests[] =       ['url' => 'https://jaapsystem.com/v1/chamba/api/v1/categories',                                                   'label' => 'control categorías',  'expect' => 200];

$allOk = true;
foreach ($tests as $t) {
    [$code, $ct, $body] = _httpGet($t['url']);
    $ok = $code === $t['expect'];
    $allOk = $allOk && $ok;
    $m = $ok ? '✓' : '✗';
    echo "    {$m} {$t['label']}: HTTP {$code} ({$ct}) [esperado {$t['expect']}]\n";
    if ($code === 200 && str_starts_with($ct, 'image/')) {
        echo "       size: " . strlen($body) . " bytes ¡imagen funciona!\n";
    }
}

echo "\n" . str_repeat('=', 70) . "\n";
if ($allOk) {
    echo "✓✓✓ FIX EXITOSO. Refresca tu perfil en el navegador.\n\n";
    echo "Ahora BORRA del servidor (file manager):\n";
    foreach (['_fix_media_404.php','_media_test.php','_fix_htaccess.php','_route_test.php','_diag.php','_migrate.php','_reset.php','_ftp_test.php'] as $f) {
        echo "  public/{$f}\n";
    }
    echo "  app/Http/Controllers/Api/V1/MediaController.php.bak-*\n";
} else {
    echo "✗ Aún hay problemas. Pásame esta salida.\n";
}
