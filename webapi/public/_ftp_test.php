<?php
/**
 * Diagnóstico de uploads para Chamba.
 *
 * Sube este archivo a /v1/chamba/_ftp_test.php (estructura aplanada) o
 * a /v1/chamba/public/_ftp_test.php (estructura clásica) y visita la URL.
 *
 * Imprime un reporte legible con:
 *   - Versión de PHP + extensiones críticas (gd, ftp, exif, fileinfo, openssl, curl)
 *   - Límites de upload (upload_max_filesize, post_max_size, etc.)
 *   - Lectura del .env de CHAMBA_FTP_*
 *   - Test de conexión FTP nativa (ftp_connect + ftp_login + ftp_pasv)
 *   - Test de creación de carpetas avatars/, services/, payments/
 *   - Test de subida y borrado de archivo
 *   - Test con Storage::disk('chamba_ftp') de Laravel
 *
 * BORRA este archivo cuando termines.
 */

@error_reporting(E_ALL);
@ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

function line(string $txt = ''): void { echo $txt."\n"; }
function section(string $t): void { line("\n=== $t ==="); }
function ok(string $t): void { line("  [OK]   $t"); }
function err(string $t): void { line("  [ERR]  $t"); }
function info(string $t): void { line("  [INFO] $t"); }

$root = __DIR__;
// Si está en /public/, sube un nivel para encontrar .env y vendor/
if (! file_exists($root.'/.env') && file_exists(dirname($root).'/.env')) {
    $root = dirname($root);
}

line('CHAMBA · diagnóstico de uploads');
line('App root detectado: '.$root);
line('Fecha: '.date('Y-m-d H:i:s'));

// ---- 1) PHP & extensiones ----
section('1. PHP & extensiones');
info('PHP '.PHP_VERSION.' ('.PHP_SAPI.')');
foreach (['gd','ftp','exif','fileinfo','openssl','curl','mbstring','pdo_mysql'] as $ext) {
    extension_loaded($ext) ? ok("ext $ext") : err("ext $ext NO cargada");
}
if (function_exists('gd_info')) {
    $g = gd_info();
    info('GD ' . ($g['GD Version'] ?? 'desconocido')
        . ' · JPEG='.($g['JPEG Support'] ?? false ? 'sí' : 'no')
        . ' · PNG='.($g['PNG Support'] ?? false ? 'sí' : 'no')
        . ' · WEBP='.($g['WebP Support'] ?? false ? 'sí' : 'no'));
}

// ---- 2) Límites de upload ----
section('2. Límites de upload (php.ini)');
foreach (['upload_max_filesize','post_max_size','max_file_uploads','memory_limit','max_input_time','max_execution_time'] as $k) {
    info("$k = ".ini_get($k));
}
info('file_uploads = '.(ini_get('file_uploads') ? 'On' : 'Off'));

// ---- 3) .env CHAMBA_FTP_* ----
section('3. .env (CHAMBA_FTP_*)');
$envFile = $root.'/.env';
if (! file_exists($envFile)) {
    err('No existe '.$envFile);
    line("\nDiagnóstico abortado.");
    exit;
}
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $l) {
    $l = trim($l);
    if ($l === '' || str_starts_with($l, '#')) continue;
    $pos = strpos($l, '=');
    if ($pos === false) continue;
    $k = trim(substr($l, 0, $pos));
    $v = trim(substr($l, $pos + 1));
    if (strlen($v) >= 2 && $v[0] === '"' && substr($v, -1) === '"') $v = substr($v, 1, -1);
    $env[$k] = $v;
}
$host   = $env['CHAMBA_FTP_HOST']     ?? '';
$user   = $env['CHAMBA_FTP_USERNAME'] ?? '';
$pass   = $env['CHAMBA_FTP_PASSWORD'] ?? '';
$port   = (int) ($env['CHAMBA_FTP_PORT'] ?? 21);
$root_  = $env['CHAMBA_FTP_ROOT']     ?? '/';
$passive= filter_var($env['CHAMBA_FTP_PASSIVE'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
$ssl    = filter_var($env['CHAMBA_FTP_SSL'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$pub    = $env['CHAMBA_FTP_PUBLIC_URL'] ?? '';

info("HOST     = ".($host ?: '(vacío)'));
info("USERNAME = ".($user ?: '(vacío)'));
info("PASSWORD = ".($pass ? '(definida, '.strlen($pass).' chars)' : '(vacío)'));
info("PORT     = $port");
info("ROOT     = $root_");
info("PASSIVE  = ".($passive ? 'true' : 'false'));
info("SSL      = ".($ssl ? 'true' : 'false'));
info("PUBLIC   = ".($pub ?: '(vacío -> usará proxy)'));

if ($host === '' || $user === '' || $pass === '') {
    err('Faltan credenciales FTP. Revisa .env.');
    exit;
}

// ---- 4) Conexión FTP nativa ----
section('4. Conexión FTP nativa (ftp_*)');
if (! extension_loaded('ftp')) {
    err('Sin extensión ftp habilitada -> el cliente FTP de Laravel no podrá conectar.');
} else {
    $conn = $ssl ? @ftp_ssl_connect($host, $port, 15) : @ftp_connect($host, $port, 15);
    if (! $conn) { err("ftp_connect falló a $host:$port"); }
    else {
        ok("conectado a $host:$port".($ssl?' (TLS)':''));
        if (! @ftp_login($conn, $user, $pass)) {
            err('ftp_login falló (usuario/clave incorrectos o IP bloqueada).');
            ftp_close($conn);
        } else {
            ok('login correcto');
            @ftp_pasv($conn, $passive);
            ok('modo '.($passive?'pasivo':'activo'));

            $cwd = @ftp_pwd($conn);
            info('pwd = '.$cwd);

            if ($root_ && $root_ !== '/' && $root_ !== $cwd) {
                if (@ftp_chdir($conn, $root_)) ok("chdir $root_");
                else err("no pude entrar a $root_");
            }
            $cwd = @ftp_pwd($conn);

            $items = @ftp_nlist($conn, '.') ?: [];
            info('contenido actual ('.count($items).' items): '.implode(', ', array_slice($items, 0, 15)));

            foreach (['avatars','services','payments'] as $folder) {
                if (in_array($folder, $items, true)) {
                    ok("ya existe carpeta $folder/");
                } elseif (@ftp_mkdir($conn, $folder)) {
                    ok("creada carpeta $folder/");
                } else {
                    err("no pude crear $folder/ (¿permisos?)");
                }
            }

            // Subir archivo de prueba
            $local = tempnam(sys_get_temp_dir(), 'ftp_');
            file_put_contents($local, "chamba upload test ".date('c'));
            $remote = 'payments/_test_'.bin2hex(random_bytes(4)).'.txt';

            if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
                ok("subida exitosa: $remote");
                if (@ftp_delete($conn, $remote)) ok('borrado del archivo de prueba: OK');
                else err('no pude borrar archivo de prueba');
            } else {
                err("ftp_put falló para $remote");
            }
            @unlink($local);
            @ftp_close($conn);
        }
    }
}

// ---- 5) Test con Storage::disk('chamba_ftp') ----
section('5. Test con Laravel Storage::disk');
$autoload = $root.'/vendor/autoload.php';
$bootstrap = $root.'/bootstrap/app.php';
if (! file_exists($autoload) || ! file_exists($bootstrap)) {
    err('No encuentro vendor/autoload.php o bootstrap/app.php desde '.$root);
} else {
    try {
        require $autoload;
        $app = require_once $bootstrap;
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->bootstrap();

        $disk = \Illuminate\Support\Facades\Storage::disk('chamba_ftp');
        $testKey = 'payments/_laravel_test_'.bin2hex(random_bytes(4)).'.txt';
        $payload = 'laravel storage test '.date('c');

        if ($disk->put($testKey, $payload)) {
            ok("put: $testKey");
            $back = $disk->get($testKey);
            if ($back === $payload) ok('get: contenido coincide');
            else err('get: contenido NO coincide');
            if ($disk->delete($testKey)) ok('delete: OK');
            else err('delete: falló');
        } else {
            err('Storage::disk(chamba_ftp)->put devolvió false');
        }
    } catch (\Throwable $e) {
        err('Excepción de Laravel: '.$e->getMessage());
        $log = $root.'/storage/logs/laravel-'.date('Y-m-d').'.log';
        if (file_exists($log)) {
            $size = filesize($log);
            $offset = max(0, $size - 4000);
            $tail = file_get_contents($log, false, null, $offset);
            line("\n--- Últimos 4 KB de $log ---\n".$tail);
        }
    }
}

section('FIN');
line('Si todo está [OK] arriba, el FTP/Storage funcionan y el problema');
line('está en el frontend o el endpoint (CORS, sesión, validación).');
line('Si algo está [ERR], reporta esa línea exacta.');
line('');
line('IMPORTANTE: borra _ftp_test.php cuando termines.');
