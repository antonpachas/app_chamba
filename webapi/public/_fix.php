<?php
/**
 * Reparación automática del .env (line endings) y caches viejas.
 * Sube este archivo a /v1/chamba/public/_fix.php y visítalo:
 *   https://jaapsystem.com/v1/chamba/_fix.php
 *
 * Hace:
 *   1. Detecta CR/LF en .env y reescribe en formato Unix.
 *   2. Limpia bootstrap/cache/{config,routes,services,packages,events}.php.
 *   3. Limpia storage/framework/views/*.php.
 *   4. Borra el flag de setup-completed para permitir un nuevo /setup.
 *
 * BORRA este archivo después de usarlo.
 */

header('Content-Type: application/json; charset=utf-8');
@error_reporting(E_ALL);
@ini_set('display_errors', '1');

$base = dirname(__DIR__);
$out = ['app_base_dir' => $base];

$envPath = $base.'/.env';
if (! file_exists($envPath)) {
    echo json_encode(['error' => '.env no existe en '.$envPath], JSON_PRETTY_PRINT);
    exit;
}

$raw = file_get_contents($envPath);
$crCount = substr_count($raw, "\r");
$out['env_size_before'] = strlen($raw);
$out['env_carriage_returns_before'] = $crCount;

if ($crCount > 0) {
    // Convierte CRLF/CR → LF
    $clean = str_replace(["\r\n", "\r"], "\n", $raw);
    // Backup por si acaso
    @copy($envPath, $envPath.'.crlf-backup-'.date('YmdHis'));
    file_put_contents($envPath, $clean);
    $out['env_fixed'] = true;
    $out['env_size_after'] = strlen($clean);
    $out['env_carriage_returns_after'] = substr_count($clean, "\r");
} else {
    $out['env_fixed'] = false;
    $out['env_note'] = 'No habia CRLF, .env esta limpio';
}

$caches = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/events.php',
];
$cleared = [];
foreach ($caches as $c) {
    $full = $base.'/'.$c;
    if (file_exists($full)) {
        @unlink($full);
        $cleared[] = $c;
    }
}
$out['caches_cleared'] = $cleared;

$views = glob($base.'/storage/framework/views/*.php') ?: [];
foreach ($views as $v) @unlink($v);
$out['compiled_views_cleared'] = count($views);

$flag = $base.'/storage/app/setup-completed.flag';
if (file_exists($flag)) {
    @unlink($flag);
    $out['setup_flag_removed'] = true;
}

// Sanity check rápido sobre los nuevos valores
$envContent = file_get_contents($envPath);
foreach (['APP_URL', 'APP_ENV', 'DB_HOST', 'CHAMBA_SETUP_TOKEN'] as $k) {
    if (preg_match('/^'.preg_quote($k, '/').'=(.*)$/m', $envContent, $m)) {
        $v = trim($m[1], "\"' \r\n");
        $out['env_'.$k.'_after'] = ($k === 'CHAMBA_SETUP_TOKEN' && $v !== '')
            ? substr($v, 0, 14).'…'
            : $v;
    }
}

$out['next_step'] = 'Ahora visita: '.($out['env_APP_URL_after'] ?? 'tu_app_url').'/setup?token=TU_TOKEN&admin_email=jesusalexander96@hotmail.com&admin_password=12345678';
$out['cleanup'] = 'Borra _fix.php y _check.php cuando termines.';

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
