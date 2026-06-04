<?php
/**
 * Busca PE · Ejecutar tareas programadas (cron) sin SSH
 *
 * Equivalente a los comandos que deberían correr cada noche:
 *   - busca:listings:expire      → oculta anuncios vencidos
 *   - chamba:expire-subscriptions → baja suscripciones/trials vencidos a Free
 *
 * Uso (token = CHAMBA_SETUP_TOKEN del .env):
 *   https://TU-DOMINIO/v1/chamba/public/_cron.php?token=XXX
 *   https://TU-DOMINIO/v1/chamba/public/_cron.php?token=XXX&task=listings
 *   https://TU-DOMINIO/v1/chamba/public/_cron.php?token=XXX&task=subscriptions
 *   https://TU-DOMINIO/v1/chamba/public/_cron.php?token=XXX&task=all
 *
 * En cPanel puedes programar un Cron Job que llame esta URL con curl (ver DEPLOY.md).
 *
 * BORRA este archivo cuando no lo necesites (o deja solo el cron por URL con token fuerte).
 */

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

$base = realpath(__DIR__ . '/..');
if ($base === false) {
    http_response_code(500);
    exit("No pude resolver el directorio de la app.\n");
}

// ── Token (sin cargar Laravel) ───────────────────────────────────
$envPath = $base . '/.env';
if (! is_file($envPath)) {
    http_response_code(500);
    exit("Falta .env en {$envPath}\n");
}
$envText = file_get_contents($envPath);
$expectedToken = '';
if (preg_match('/^CHAMBA_SETUP_TOKEN=(.*)$/m', $envText, $m)) {
    $expectedToken = trim($m[1], "\"' \r\n");
}
if ($expectedToken === '') {
    http_response_code(403);
    exit("CHAMBA_SETUP_TOKEN está vacío en .env. Define un token secreto antes de usar este script.\n");
}
$given = isset($_GET['token']) ? (string) $_GET['token'] : '';
if (! hash_equals($expectedToken, $given)) {
    http_response_code(403);
    exit("Token inválido.\n");
}

$task = isset($_GET['task']) ? strtolower(trim((string) $_GET['task'])) : 'all';
$allowed = ['all', 'listings', 'subscriptions'];
if (! in_array($task, $allowed, true)) {
    http_response_code(400);
    exit("task inválido. Usa: all | listings | subscriptions\n");
}

echo "=== BUSCA PE · CRON REMOTO ===\n";
echo 'Fecha: ' . date('Y-m-d H:i:s T') . "\n";
echo "App: {$base}\n";
echo "Tarea: {$task}\n\n";

// Skeleton storage por si acaba de desplegar
foreach (['storage/logs', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views'] as $rel) {
    $dir = $base . '/' . $rel;
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
        echo "[ok] Creado {$rel}\n";
    }
}

require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

$results = [];

$runListings = $task === 'all' || $task === 'listings';
$runSubs = $task === 'all' || $task === 'subscriptions';

if ($runListings) {
    $enabled = (bool) (function_exists('chamba_setting') ? chamba_setting('listings.expire_cron_enabled', true) : true);
    echo "--- busca:listings:expire ---\n";
    if (! $enabled) {
        echo "Omitido: listings.expire_cron_enabled = 0 en configuración.\n\n";
        $results['listings'] = 'skipped';
    } else {
        try {
            $code = Artisan::call('busca:listings:expire');
            echo trim(Artisan::output()) . "\n";
            echo 'Exit code: ' . $code . "\n\n";
            $results['listings'] = $code === 0 ? 'ok' : 'error';
        } catch (Throwable $e) {
            echo 'ERROR: ' . $e->getMessage() . "\n\n";
            $results['listings'] = 'error';
        }
    }
}

if ($runSubs) {
    echo "--- chamba:expire-subscriptions ---\n";
    try {
        $code = Artisan::call('chamba:expire-subscriptions');
        echo trim(Artisan::output()) . "\n";
        echo 'Exit code: ' . $code . "\n\n";
        $results['subscriptions'] = $code === 0 ? 'ok' : 'error';
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n\n";
        $results['subscriptions'] = 'error';
    }
}

echo str_repeat('=', 50) . "\n";
echo "Resumen: " . json_encode($results, JSON_UNESCAPED_UNICODE) . "\n";
echo "\nPrograma en cPanel un Cron Job diario (ver DEPLOY.md).\n";
echo "Ejemplo URL:\n";
echo "  " . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : 'https://tu-dominio') . dirname($_SERVER['SCRIPT_NAME'] ?? '') . "/_cron.php?token=TU_TOKEN&task=all\n";
echo "\nIMPORTANTE: borra public/_cron.php si ya configuraste cron por URL con token en servidor.\n";
