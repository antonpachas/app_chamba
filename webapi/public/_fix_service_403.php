<?php
/**
 * CHAMBA · Fix 403 al subir imágenes a servicios
 * --------------------------------------------------------------------
 * Causa: ServiceImageController::authorize() compara con !== sin cast a int.
 * En MariaDB las FK a veces vuelven como string ("7" !== 7 → true) y aborta
 * al dueño legítimo.
 *
 * Este script:
 *   1) Patcha ServiceImageController::authorize() para usar cast a int
 *      (alineado con el resto de controllers).
 *   2) Verifica sintaxis con php -l.
 *   3) Limpia caches.
 *
 * Sube a:  /v1/chamba/public/_fix_service_403.php
 * Y ábrelo.  BORRA cuando confirmes que funciona.
 */

@ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$appRoot  = realpath(__DIR__ . '/..');
$ctrlPath = $appRoot . '/app/Http/Controllers/Api/V1/Provider/ServiceImageController.php';

echo "CHAMBA · Fix 403 servicios/images\n";
echo str_repeat('=', 70) . "\n";
echo "App   : {$appRoot}\n";
echo "Fecha : " . date('Y-m-d H:i:s') . "\n\n";

if (! is_file($ctrlPath)) {
    echo "✗ No existe {$ctrlPath}\n";
    exit;
}

$source = file_get_contents($ctrlPath);
$marker = 'CHAMBA-IMG-AUTH-CAST';

if (strpos($source, $marker) !== false) {
    echo "[1] Ya patchado anteriormente, no se modifica.\n\n";
} else {
    $oldBlock = <<<'PHP'
    private function authorize(Request $request, ProviderService $service): void
    {
        $userId = $request->user()->id;
        $ownerId = $service->providerProfile?->user_id;
        if ($ownerId !== $userId && $request->user()->role !== 'admin') {
            abort(403);
        }
    }
PHP;

    $newBlock = <<<'PHP'
    private function authorize(Request $request, ProviderService $service): void
    {
        // CHAMBA-IMG-AUTH-CAST: en MariaDB las FK pueden volver como string
        // y "7" !== 7 → true, dando 403 al dueño. Cast a int para comparar.
        $userId = (int) $request->user()->id;
        $ownerId = (int) ($service->providerProfile?->user_id ?? 0);
        if ($ownerId !== $userId && $request->user()->role !== 'admin') {
            abort(403);
        }
    }
PHP;

    if (strpos($source, $oldBlock) === false) {
        echo "[1] No encontré el bloque exacto del método authorize().\n";
        echo "    Tal vez ya fue modificado previamente. Mira el archivo manualmente.\n";
        exit;
    }

    $patched = str_replace($oldBlock, $newBlock, $source);
    $bak = $ctrlPath . '.bak-' . date('Ymd-His');
    @copy($ctrlPath, $bak);
    echo "[1] Backup: " . basename($bak) . "\n";
    $w = @file_put_contents($ctrlPath, $patched);
    echo "    ✓ Escritos {$w} bytes.\n\n";
}

// Sintaxis PHP
echo "[2] Verificando sintaxis\n";
$lint = @shell_exec('php -l ' . escapeshellarg($ctrlPath) . ' 2>&1');
echo "    " . trim($lint ?: '(php -l no disponible)') . "\n";
if ($lint && stripos($lint, 'No syntax errors') === false) {
    echo "    ✗ Sintaxis inválida. Restaura desde el backup.\n";
    exit;
}
echo "\n";

// Limpiar caches
echo "[3] Limpiando caches\n";
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(
    \Illuminate\Http\Request::create('/_warm', 'GET')
);
foreach (['bootstrap/cache/config.php','bootstrap/cache/routes-v7.php','bootstrap/cache/services.php','bootstrap/cache/packages.php','bootstrap/cache/events.php'] as $rel) {
    $abs = $appRoot . '/' . $rel;
    if (is_file($abs)) { @unlink($abs); echo "    ✓ borrado: {$rel}\n"; }
}
@clearstatcache(true);
if (function_exists('opcache_reset')) { @opcache_reset(); echo "    ✓ opcache_reset()\n"; }

echo "\n" . str_repeat('=', 70) . "\n";
echo "✓ Fix aplicado. Refresca la app (Ctrl+F5) y vuelve a subir la imagen al\n";
echo "  servicio. Ya no debería dar 403.\n\n";
echo "BORRA después:\n";
echo "  public/_fix_service_403.php\n";
echo "  public/_diag_service.php\n";
echo "  app/Http/Controllers/Api/V1/Provider/ServiceImageController.php.bak-*\n";
