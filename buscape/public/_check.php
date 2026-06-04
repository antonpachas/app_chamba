<?php
/**
 * Diagnóstico de instalación. Sube este archivo a /v1/chamba/public/_check.php
 * y visita https://jaapsystem.com/v1/chamba/_check.php
 *
 * Reporta en JSON el estado de cada cosa que Laravel necesita.
 * BORRA este archivo después de usarlo.
 */

header('Content-Type: application/json; charset=utf-8');
@error_reporting(E_ALL);
@ini_set('display_errors', '1');

$report = [];

$report['php_version'] = PHP_VERSION;
$report['php_sapi'] = PHP_SAPI;
$report['os'] = PHP_OS_FAMILY;

$ext = ['gd','ftp','fileinfo','exif','pdo_mysql','mbstring','openssl','tokenizer','xml','ctype','bcmath','curl','zip'];
foreach ($ext as $e) {
    $report['ext_'.$e] = extension_loaded($e) ? 'OK' : 'FALTA';
}

$base = dirname(__DIR__);                         // /v1/chamba
$report['app_base_dir'] = $base;
$report['cwd'] = getcwd();

$report['env_exists']            = file_exists($base.'/.env');
$report['env_production_exists'] = file_exists($base.'/.env.production');
$report['vendor_autoload']       = file_exists($base.'/vendor/autoload.php');
$report['bootstrap_app']         = file_exists($base.'/bootstrap/app.php');
$report['storage_writable']      = is_writable($base.'/storage');
$report['bootstrap_cache_writable'] = is_writable($base.'/bootstrap/cache');

$report['storage_perms'] = file_exists($base.'/storage') ? substr(sprintf('%o', fileperms($base.'/storage')), -4) : 'no existe';
$report['bootstrap_cache_perms'] = file_exists($base.'/bootstrap/cache') ? substr(sprintf('%o', fileperms($base.'/bootstrap/cache')), -4) : 'no existe';

if ($report['env_exists']) {
    $envContent = @file_get_contents($base.'/.env');
    if ($envContent) {
        foreach (['APP_URL', 'APP_ENV', 'APP_KEY', 'DB_HOST', 'DB_DATABASE', 'CHAMBA_FTP_HOST', 'CHAMBA_SETUP_TOKEN'] as $k) {
            if (preg_match('/^'.preg_quote($k, '/').'=(.*)$/m', $envContent, $m)) {
                $v = trim($m[1], "\"' ");
                if (in_array($k, ['APP_KEY','CHAMBA_SETUP_TOKEN'])) {
                    $v = $v ? substr($v, 0, 14).'…' : '(vacío)';
                }
                $report['env_'.$k] = $v;
            } else {
                $report['env_'.$k] = '(no definido)';
            }
        }
    }
}

$report['rewrite_module_apache'] = function_exists('apache_get_modules')
    ? (in_array('mod_rewrite', apache_get_modules()) ? 'OK' : 'FALTA')
    : 'desconocido (probablemente LiteSpeed)';

if ($report['env_exists'] && $report['vendor_autoload'] && $report['bootstrap_app']) {
    try {
        require $base.'/vendor/autoload.php';
        $app = require_once $base.'/bootstrap/app.php';
        $report['laravel_bootstrap'] = 'OK';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $report['laravel_kernel'] = 'OK';
        try {
            $pdo = $app->make('db')->connection()->getPdo();
            $report['db_connect'] = 'OK ('.$pdo->getAttribute(\PDO::ATTR_SERVER_VERSION).')';
        } catch (\Throwable $e) {
            $report['db_connect'] = 'ERROR: '.$e->getMessage();
        }
    } catch (\Throwable $e) {
        $report['laravel_bootstrap'] = 'ERROR: '.$e->getMessage();
        $report['laravel_trace'] = explode("\n", $e->getTraceAsString())[0] ?? '';
    }
}

$logDir = $base.'/storage/logs';
$report['log_dir_exists'] = is_dir($logDir);
if (is_dir($logDir)) {
    $logs = glob($logDir.'/*.log');
    if ($logs) {
        usort($logs, fn($a,$b) => filemtime($b) - filemtime($a));
        $latest = $logs[0];
        $report['latest_log'] = basename($latest);
        $content = @file_get_contents($latest);
        if ($content) {
            $report['latest_log_tail'] = substr($content, -3000);
        }
    } else {
        $report['latest_log'] = '(no hay logs)';
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
