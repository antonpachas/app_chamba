# Despliegue en producción — `https://jaapsystem.com/v1/chamba`

Hosting: shared/cPanel con SSH en `jaapsystem.com`.
Estructura recomendada en el servidor:

```
/home/jaapsyst/
├── chamba_app/                                   ← código Laravel (PRIVADO, fuera del docroot)
│   ├── app/  bootstrap/  config/  routes/ ...
│   ├── public/                                   ← se sincroniza al docroot público
│   ├── vendor/
│   ├── storage/
│   ├── .env                                       (con credenciales reales)
│   └── deploy.sh
└── jaapsystem.com/                                ← docroot público de Apache/LiteSpeed
    └── v1/chamba/                                 ← URL pública = https://jaapsystem.com/v1/chamba
        ├── index.php   (apunta a /home/jaapsyst/chamba_app)
        ├── build/      (assets de Vite)
        ├── img/  storage/  site.webmanifest ...
        └── .htaccess
```

> **¿Por qué dos carpetas?** Es la configuración estándar de Laravel en shared hosting: el código sensible (vendor, .env, routes, app) **nunca** queda accesible por HTTP. Solo `public/` está expuesto. Si alguien intenta `https://jaapsystem.com/v1/chamba/.env` recibe 404.

---

## Requisitos en el servidor (verifica una sola vez)

PHP **8.2+** con:

```
gd  ftp  fileinfo  exif  pdo_mysql  mbstring  openssl  tokenizer  xml  ctype  bcmath
```

Si tu cPanel lo permite, actívalas desde **MultiPHP INI Editor** o **PHP Selector**. En jaapsystem.com (CloudLinux) están bajo `Selección de versión de PHP → Extensiones`.

Composer disponible en `$PATH`. Si no:

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar ~/bin/composer
```

---

## Primera publicación (deploy inicial)

### 1) Sube los archivos al servidor

Desde tu máquina (Windows PowerShell), comprime el proyecto sin `node_modules`, `vendor`, `.git`:

```powershell
cd d:\PROYECTOS\chamba\webapi
Compress-Archive -Path app,bootstrap,config,database,public,resources,routes,storage,tests,artisan,composer.json,composer.lock,package.json,package-lock.json,vite.config.js,deploy.sh,DEPLOY.md,.env.production -DestinationPath chamba_app.zip -Force
```

> Importante: el zip incluye **`public/build/`** ya construido (corrí `npm run build` antes), así que no necesitas Node.js en el servidor.

Súbelo por SSH:

```bash
scp chamba_app.zip jaapsyst@jaapsystem.com:/home/jaapsyst/
ssh jaapsyst@jaapsystem.com
```

Y dentro del servidor:

```bash
cd /home/jaapsyst/
mkdir -p chamba_app
unzip -o chamba_app.zip -d chamba_app/
cd chamba_app
chmod +x deploy.sh
```

### 2) Crea el `.env`

```bash
cp .env.production .env
nano .env   # revisa APP_URL, DB_*, MAIL_*, CHAMBA_FTP_*
```

Valores **críticos** ya configurados:

| Variable | Valor de producción |
|---|---|
| `APP_URL` | `https://jaapsystem.com/v1/chamba` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_HOST` | `45.56.67.32` |
| `DB_DATABASE` | `jaapsyst_chamba` |
| `CHAMBA_FTP_HOST` | `jaapsystem.com` |
| `SESSION_PATH` | `/v1/chamba` |
| `SESSION_SECURE_COOKIE` | `true` |

### 3) Corre el script

```bash
bash deploy.sh
```

Esto hace todo en orden:
1. `php artisan down` (modo mantenimiento)
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `php artisan config:cache route:cache view:cache event:cache`
5. Sincroniza `public/` → `/home/jaapsyst/jaapsystem.com/v1/chamba/`
6. Reescribe `index.php` para apuntar al app privado
7. Permisos `storage/` y `bootstrap/cache/`
8. `php artisan up`

### 4) Carga datos iniciales (solo primera vez)

```bash
php artisan db:seed --force            # categorías + planes + districts
php artisan chamba:seed-test-users --password=12345678
php artisan chamba:make-admin --email=jesusalexander96@hotmail.com --promote --password=12345678
```

### 5) Verifica que la app abre

- `https://jaapsystem.com/v1/chamba/` → debe redirigir al SPA `/v1/chamba/app`
- `https://jaapsystem.com/v1/chamba/api/v1/categories` → debe devolver JSON
- Login con admin (`jesusalexander96@hotmail.com / 12345678`)
- Sube una foto de perfil → confirma que aparece en el FTP `/avatars/`

---

## Actualizaciones (deploys siguientes)

Cada vez que hagas cambios localmente:

```powershell
# 1. Build local (Windows)
cd d:\PROYECTOS\chamba\webapi
npm run build

# 2. Re-empaqueta y sube
Compress-Archive -Path app,bootstrap,config,database,public,resources,routes,artisan,composer.json,composer.lock,deploy.sh -DestinationPath chamba_update.zip -Force
scp chamba_update.zip jaapsyst@jaapsystem.com:/home/jaapsyst/
```

```bash
# 3. En el servidor
ssh jaapsyst@jaapsystem.com
cd /home/jaapsyst
unzip -o chamba_update.zip -d chamba_app/
cd chamba_app
bash deploy.sh
```

---

## Configuración del cron (recomendado)

Para caducar trials/suscripciones automáticamente cada noche:

```bash
crontab -e
```

Agregar:

```
0 3 * * * cd /home/jaapsyst/chamba_app && php artisan chamba:expire-subscriptions >> storage/logs/cron.log 2>&1
```

---

## Troubleshooting rápido

**500 al abrir la URL** — revisa permisos:
```bash
chmod -R ug+rwX /home/jaapsyst/chamba_app/storage /home/jaapsyst/chamba_app/bootstrap/cache
```

**"could not find driver"** — falta `pdo_mysql` en la versión de PHP del cPanel. Activa la extensión.

**Imágenes no aparecen / FTP timeout** — confirma que las extensiones `gd`, `ftp`, `exif` estén activadas:
```bash
php -m | grep -E "gd|ftp|exif|fileinfo"
```

**Cambios no se reflejan** — cache de config. Corre:
```bash
php artisan optimize:clear
php artisan config:cache route:cache view:cache
```

**Subiste assets nuevos pero el navegador sigue mostrando los viejos** — el build de Vite usa hashes en el nombre, así que normalmente no pasa. Si pasa: hard refresh (Ctrl+F5) o agrega `?v=$(date +%s)` al `<link>` del `app.blade.php` (no recomendado).

**Logs**:
```bash
tail -f /home/jaapsyst/chamba_app/storage/logs/laravel-$(date +%F).log
```
