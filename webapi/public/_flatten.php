<?php
/**
 * Aplana la estructura Laravel para shared hosting:
 *   - Copia /public/* → /  (raíz del proyecto)
 *   - Ajusta /index.php para usar paths sin "../"
 *   - Inyecta usePublicPath() para que asset() siga funcionando
 *   - Reescribe /.htaccess con las reglas estándar de Laravel
 *
 * Sube a /v1/chamba/public/_flatten.php y visita la URL.
 * BORRA después de usar.
 */

header('Content-Type: text/plain; charset=utf-8');
@error_reporting(E_ALL);
@ini_set('display_errors', '1');

$publicDir = __DIR__;
$base = dirname(__DIR__);

echo "=== APLANANDO ESTRUCTURA LARAVEL ===\n";
echo "public/: $publicDir\n";
echo "raíz:    $base\n\n";

// 1) Copiar todo el contenido de public/ a la raíz
echo "--- 1. Copiando public/* → /\n";
$skip = ['_check.php','_fix.php','_log.php','_reset.php','_init.php','_flatten.php'];

function copyRecursive(string $src, string $dst): bool {
    if (is_dir($src)) {
        if (! is_dir($dst)) @mkdir($dst, 0755, true);
        foreach (array_diff(scandir($src), ['.', '..']) as $f) {
            if (! copyRecursive($src.'/'.$f, $dst.'/'.$f)) return false;
        }
        return true;
    }
    return @copy($src, $dst);
}

foreach (array_diff(scandir($publicDir), ['.', '..']) as $item) {
    if (in_array($item, $skip, true)) continue;
    $src = $publicDir.'/'.$item;
    $dst = $base.'/'.$item;
    if ($item === 'index.php' || $item === '.htaccess') {
        @unlink($dst);
    }
    if (file_exists($dst) && is_file($dst)) {
        @unlink($dst);
        if (copyRecursive($src, $dst)) echo "[ACTUALIZADO] $item\n";
    } elseif (file_exists($dst) && is_dir($dst)) {
        if (copyRecursive($src, $dst)) echo "[FUSIONADO  ] $item/\n";
    } else {
        if (copyRecursive($src, $dst)) echo "[COPIADO    ] $item\n";
        else echo "[ERROR      ] $item\n";
    }
}

// 2) Reescribir el index.php raíz con paths correctos y usePublicPath()
echo "\n--- 2. Reescribiendo /index.php\n";
$indexContent = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

(require_once __DIR__.'/bootstrap/app.php')
    ->usePublicPath(__DIR__)
    ->handleRequest(Request::capture());
PHP;

file_put_contents($base.'/index.php', $indexContent);
echo "[OK] /index.php reescrito (usePublicPath = raíz)\n";

// 3) Reescribir el .htaccess raíz con las reglas estándar de Laravel
echo "\n--- 3. Reescribiendo /.htaccess\n";
$htaccess = <<<'HT'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Header de autorización (Bearer/Basic)
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirige trailing slashes si no es carpeta
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Si no es archivo ni carpeta física, va al front controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

DirectoryIndex index.php

# Bloquea archivos sensibles
<FilesMatch "^(\.env|\.env\..+|composer\.json|composer\.lock|artisan|deploy\.sh|DEPLOY\.md|package\.json|package-lock\.json|vite\.config\.js|phpunit\.xml|_check\.php|_fix\.php|_log\.php|_reset\.php|_init\.php|_flatten\.php)$">
    Require all denied
</FilesMatch>

# Bloquea acceso directo a las carpetas privadas (sin chocar con /app del SPA)
RedirectMatch 403 ^/v1/chamba/(bootstrap|config|database|resources|routes|storage|tests|vendor)(/|$)
HT;

file_put_contents($base.'/.htaccess', $htaccess);
echo "[OK] /.htaccess reescrito (reglas estándar Laravel)\n";

// 4) Limpiar caches
echo "\n--- 4. Limpiando caches\n";
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) { @unlink($f); echo "borrado: ".basename($f)."\n"; }
foreach (glob($base.'/storage/framework/views/*.php') ?: [] as $f) { @unlink($f); }

echo "\n--- 5. Verificación\n";
foreach (['index.php', '.htaccess', 'build', 'img', 'site.webmanifest'] as $f) {
    echo (file_exists($base.'/'.$f) ? '[OK]   ' : '[MISS] ') . $f . "\n";
}

echo "\n=== LISTO ===\n";
echo "Visita: https://jaapsystem.com/v1/chamba/\n";
echo "(la app ahora arranca directo desde la raíz, sin /public/)\n";
echo "\nDespués puedes borrar la carpeta /v1/chamba/public/ entera (ya no se usa).\n";
echo "Y borra TODOS los archivos _check.php, _fix.php, _log.php, _reset.php, _init.php, _flatten.php.\n";
