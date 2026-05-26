#!/usr/bin/env bash
###############################################################################
# Chamba — script de despliegue/actualización en producción.
#
# Uso (en el servidor, vía SSH, dentro de /home/jaapsyst/chamba_app/):
#   bash deploy.sh
#
# Qué hace:
#   1. Activa modo mantenimiento.
#   2. Sincroniza .env.production → .env (si NO existe .env aún).
#   3. composer install --no-dev --optimize-autoloader
#   4. Migraciones (php artisan migrate --force).
#   5. Cachea config/rutas/vistas para producción.
#   6. Copia /public a /home/jaapsyst/jaapsystem.com/v1/chamba (docroot).
#   7. Permisos de storage/ y bootstrap/cache/.
#   8. Quita modo mantenimiento.
###############################################################################

set -euo pipefail

APP_DIR="/home/jaapsyst/chamba_app"
PUBLIC_DIR="/home/jaapsyst/jaapsystem.com/v1/chamba"

cd "$APP_DIR"

echo "==> [1/8] Modo mantenimiento ON"
php artisan down --render="errors::503" --retry=60 || true

echo "==> [2/8] .env"
if [ ! -f .env ]; then
    cp .env.production .env
    echo "    .env creado desde .env.production"
fi

echo "==> [3/8] composer install --no-dev"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> [4/8] migrate --force"
php artisan migrate --force

echo "==> [5/8] cache de configuración"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> [6/8] sincronizando public/ → $PUBLIC_DIR"
mkdir -p "$PUBLIC_DIR"
rsync -a --delete \
    --exclude '.htaccess' \
    "$APP_DIR/public/" "$PUBLIC_DIR/"

# Si el public_html no tiene .htaccess copiamos uno (preserva customizaciones).
if [ ! -f "$PUBLIC_DIR/.htaccess" ]; then
    cp "$APP_DIR/public/.htaccess" "$PUBLIC_DIR/.htaccess"
fi

# Patch de index.php para que apunte al APP_DIR privado.
cat > "$PUBLIC_DIR/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Carpeta privada del proyecto Laravel (fuera del docroot).
$appBase = '/home/jaapsyst/chamba_app';

if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/vendor/autoload.php';

(require_once $appBase.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
PHP

echo "==> [7/8] permisos"
chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

echo "==> [8/8] modo mantenimiento OFF"
php artisan up

echo "✅ Deploy completado: https://jaapsystem.com/v1/chamba"
