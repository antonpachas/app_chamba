# Publicación de la API (Laravel) en el servidor

Guía para subir **Chamba Web API** a un hosting (cPanel, VPS, etc.). Laravel no genera un ejecutable: en el servidor instalas dependencias, compilas assets y generas cachés.

**Guía corta (qué subir, `.htaccess` listo para `jaapsystem.com/v1/app_chamba/`):** [produccion/README.md](produccion/README.md).

---

## Requisitos en el servidor

- **PHP 8.3+** con extensiones habituales: `openssl`, `pdo`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath` (revisa `composer.json`).
- **Composer** (instalación global o `composer.phar` en el proyecto).
- **MySQL** accesible desde el servidor (misma máquina o remoto) con la base ya creada y el esquema aplicado (`db-mysql/` en el repo raíz).
- **Node.js + npm** (recomendado) para ejecutar `npm run build` en el servidor.  
  Si el hosting **no** tiene Node, compila en tu PC y **sube la carpeta** `public/build/` completa (no está en Git por `.gitignore`).

---

## Tu URL no debe terminar en `/public/`

Si ahora entras por algo como `https://jaapsystem.com/v1/app_chamba/public/`, el servidor está usando como **raíz web** la carpeta del proyecto (`app_chamba/`) en lugar de **`app_chamba/public/`**. Laravel está pensado para que **solo** `public/` sea visible por HTTP; así ocultas `.env`, `vendor/` y el código fuente.

### Solución recomendada (cPanel / panel del hosting)

1. Abre la configuración del dominio o subcarpeta (por ejemplo **Dominios** → **Document Root** / **Raíz del documento**).
2. Cambia la raíz de `.../v1/app_chamba` a **`.../v1/app_chamba/public`** (la carpeta `public` que está *dentro* de Laravel).
3. En `.env` del servidor deja **`APP_URL`** sin `/public`, por ejemplo:
   - `APP_URL=https://jaapsystem.com/v1/app_chamba`
4. Borra cachés y regenera:
   - `php artisan optimize:clear`
   - `php artisan optimize`
5. Prueba la web en `https://jaapsystem.com/v1/app_chamba/` y la API en `https://jaapsystem.com/v1/app_chamba/api/v1/...` (o la ruta que tengas definida en `routes/api.php`).

La app móvil u otros clientes deben usar esa **base URL final** (sin `/public`).

### Si el hosting no te deja cambiar el document root

1. En el servidor, en la carpeta **padre** de `public/` (donde están `app/`, `routes/`, etc.), crea o edita un archivo **`.htaccess`**.
2. Usa el archivo del repo: [`produccion/htaccess-RAIZ-Laravel.txt`](produccion/htaccess-RAIZ-Laravel.txt) (cópialo como `.htaccess` en la raíz de Laravel; los pasos están en [`produccion/README.md`](produccion/README.md)).
3. Ajusta **`APP_URL`** en `.env` a la URL **canónica** que quieras (normalmente **sin** `/public`).
4. Si las rutas o los assets fallan, en algunos hostings hace falta añadir en el **`.htaccess` dentro de `public/`** una línea `RewriteBase` (pregunta a soporte la ruta exacta, p. ej. `/v1/app_chamba/public/`).

---

## Qué subir al servidor (archivos y carpetas)

### Opción A — Recomendada: `git clone` en el servidor

1. Clona el repositorio (o la carpeta `webapi` si el mono-repo está publicado así).
2. En el servidor **no** necesitas copiar manualmente `vendor/`, `node_modules/` ni `public/build/`: se regeneran con los comandos de más abajo.

### Opción B — Copia manual (FTP, ZIP, etc.)

**Sí debes copiar** (todo el proyecto Laravel salvo lo indicado):

| Ruta | Notas |
|------|--------|
| `app/` | Código de la aplicación |
| `bootstrap/` | Arranque de Laravel |
| `config/` | Configuración |
| `database/` | Migraciones, factories, seeders |
| `public/` | **Document root del sitio** debe apuntar aquí. Incluye `index.php`, `.htaccess` (si aplica). |
| `resources/` | Vistas, CSS/JS fuente |
| `routes/` | Rutas web y API |
| `storage/` | Estructura de carpetas (el servidor debe poder escribir aquí) |
| `artisan` | CLI de Laravel |
| `composer.json` | Obligatorio |
| `composer.lock` | Obligatorio (versiones fijas) |
| `package.json` | Para Vite |
| `package-lock.json` | Recomendado (`npm ci`) |
| `vite.config.js` | Build de front |
| `.env.example` | Referencia; en el servidor crearás `.env` |

**No hace falta copiar** (se generan en el servidor o no van a producción):

| Ruta | Motivo |
|------|--------|
| `vendor/` | `composer install --no-dev` |
| `node_modules/` | `npm ci` o `npm install` |
| `public/build/` | `npm run build` (o súbelo desde tu PC si no hay Node) |
| `public/hot` | Solo desarrollo con Vite |
| `.git/` | Opcional si despliegas por ZIP |
| `tests/` | Opcional en producción |

**No subas a Git ni copies tal cual sin editar:**

| Archivo | Acción |
|---------|--------|
| `.env` | **Créalo en el servidor** (puedes partir de `.env.example`), con `APP_KEY`, base de datos, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`. |

**Fuera de esta carpeta pero necesario para la base de datos:**

- Scripts en `../db-mysql/` (desde la raíz del repo `chamba`): ejecútalos contra MySQL **antes** o según tu plan (esquema, rutinas, seed). La API asume que esa BD está lista.

---

## Pasos de publicación (orden sugerido)

### 1. Subir el código

Git clone o copia de archivos según la opción anterior.

### 2. Document root del hosting

Configura el dominio o subdominio para que el **web root** sea la carpeta **`public/`** del proyecto (no la raíz donde está `composer.json`).

### 3. Variables de entorno

```bash
cp .env.example .env   # o créalo a mano
php artisan key:generate
```

Edita `.env` en el servidor:

- `APP_NAME`, `APP_ENV=production`, `APP_DEBUG=false`
- `APP_URL=https://tu-dominio.com` (con `https` si usas SSL)
- `DB_*` apuntando a tu MySQL
- `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` según lo que uses (en muchos despliegues: `file` / `file` / `sync`)

### 4. Dependencias PHP (producción)

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### 5. Assets front (Vite)

Con Node en el servidor:

```bash
npm ci
npm run build
```

Sin Node: en tu máquina local ejecuta `npm run build` y sube por FTP/SFTP la carpeta **`public/build/`** (incluye `manifest.json` y la subcarpeta `assets/`).

### 6. Enlace de almacenamiento (si usas archivos públicos en `storage/app/public`)

```bash
php artisan storage:link
```

(Solo si tu proyecto sirve uploads u otros ficheros vía `public/storage`.)

### 7. Permisos de escritura

El usuario del servidor web (p. ej. `www-data`, `nobody`) debe poder escribir en:

- `storage/`
- `bootstrap/cache/`

Ejemplo en Linux:

```bash
chmod -R ug+rwx storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

(Ajusta usuario/grupo a los que use tu hosting.)

### 8. Migraciones Laravel

Solo las migraciones del propio Laravel (p. ej. tablas de Sanctum), **no** sustituyen al SQL de `db-mysql/`:

```bash
php artisan migrate --force
```

Si alguna migración choca con tablas creadas por tus scripts SQL, resuélvelo en el servidor (o alinea nombres) antes de desplegar.

### 9. Optimizar para producción

```bash
php artisan optimize
```

Esto cachea configuración, rutas, vistas y eventos. Tras **cambiar** `.env`, rutas o config, ejecuta:

```bash
php artisan optimize:clear
php artisan optimize
```

### 10. Comprobar

- Abre `https://tu-dominio.com` (página welcome si está activa).
- Prueba un endpoint: `GET https://tu-dominio.com/api/v1/...` con cabecera `Accept: application/json`.

---

## Resumen rápido: checklist de archivos

- **Incluidos en el despliegue:** `app`, `bootstrap`, `config`, `database`, `public` (sin depender solo de `build` si vas a compilar en servidor), `resources`, `routes`, `storage`, `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`, `.env` (creado en servidor).
- **Generados en servidor:** `vendor/`, `node_modules/`, `public/build/` (vía Composer/npm), cachés en `bootstrap/cache/`.
- **Nunca versionar con secretos:** `.env` (solo en el servidor o gestor de secretos).

---

## Problemas frecuentes

| Síntoma | Qué revisar |
|---------|-------------|
| Pantalla en blanco / 500 | `storage/logs/laravel.log`, `APP_DEBUG` temporalmente (solo diagnóstico), permisos de `storage` y `bootstrap/cache`. |
| CSS/JS rotos | Existe `public/build/manifest.json` y se ejecutó `npm run build` o se subió `public/build/`. |
| Rutas 404 salvo `/` | Document root debe ser `public/`. Revisa reglas Apache/Nginx para Laravel. |
| Error de APP_KEY | `php artisan key:generate` con `.env` presente. |

Para documentación de endpoints sigue usando el [README.md](README.md) principal de la API.
