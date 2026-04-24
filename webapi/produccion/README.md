# Despliegue rápido — `https://jaapsystem.com/v1/app_chamba/`

Esta carpeta solo tiene **plantillas** (`.htaccess` y textos de ayuda). El código de la API sigue en la raíz de `webapi/` (`app/`, `routes/`, `public/`, etc.).

---

## Qué quieres lograr

- En el navegador: **`https://jaapsystem.com/v1/app_chamba/`** (sin `/public/`).
- API (ejemplo): **`https://jaapsystem.com/v1/app_chamba/api/v1/...`**

En el servidor, la carpeta del proyecto Laravel suele ser algo como:

`public_html/v1/app_chamba/`

Dentro deben quedar `app/`, `bootstrap/`, `config/`, `public/`, `routes/`, … y el archivo **`.htaccess` de la raíz** que viene de esta carpeta `produccion/`.

---

## Paso 1 — Subir archivos (FTP, File Manager o ZIP)

Sube **todo el contenido de la carpeta `webapi/`** del repo a:

`public_html/v1/app_chamba/`

(o la ruta equivalente que te dé el hosting para esa URL).

### Sí sube (carpetas y archivos)

| Qué | Dónde queda en el servidor |
|-----|---------------------------|
| `app/` | `.../app_chamba/app/` |
| `bootstrap/` | `.../app_chamba/bootstrap/` |
| `config/` | `.../app_chamba/config/` |
| `database/` | `.../app_chamba/database/` |
| `public/` | `.../app_chamba/public/` |
| `resources/` | `.../app_chamba/resources/` |
| `routes/` | `.../app_chamba/routes/` |
| `storage/` | `.../app_chamba/storage/` |
| `artisan` | `.../app_chamba/artisan` |
| `composer.json`, `composer.lock` | raíz `app_chamba/` |
| `package.json`, `package-lock.json`, `vite.config.js` | raíz `app_chamba/` |
| `produccion/` (opcional) | solo sirve de guía; puedes no subirla en futuros despliegues |

### No hace falta subir (se regeneran en el servidor)

- `vendor/` → luego `composer install --no-dev`
- `node_modules/` → luego `npm ci`
- `public/build/` → luego `npm run build` (o súbelo desde tu PC si no hay Node en el hosting)
- `.git/` (opcional)

### Crítico: no subas `.env` del desarrollo

En el servidor **crea** `.env` (puedes copiar `.env.example` y editarlo). Ahí va `APP_URL`, base de datos, etc.

---

## Paso 2 — `.htaccess` en la raíz de Laravel (para quitar `/public/` de la URL)

En el servidor, en la **misma carpeta** donde están `artisan` y la carpeta `public/`:

1. Crea un archivo llamado **`.htaccess`** (con el punto al inicio).
2. Copia **todo** el contenido del archivo **`htaccess-RAIZ-Laravel.txt`** de esta carpeta `produccion/` y pégalo ahí.

Así, una petición a `.../app_chamba/` se redirige internamente a `public/` sin que el visitante tenga que escribir `/public/`.

> Si tu hosting te deja poner la **raíz del documento** directamente en `.../app_chamba/public/`, es **mejor** hacer eso y **no** uses este `.htaccess` de la raíz (Laravel queda más seguro y simple). En ese caso `APP_URL` sigue siendo `https://jaapsystem.com/v1/app_chamba` (sin `/public`).

---

## Paso 3 — `.env` en el servidor

Ejemplo mínimo de URL:

```env
APP_URL=https://jaapsystem.com/v1/app_chamba
```

Sin barra final obligatoria; **sin** `/public`.

Completa `DB_*`, `APP_KEY` (`php artisan key:generate`), `APP_ENV=production`, `APP_DEBUG=false`, etc.

---

## Paso 4 — Una vez en el servidor (SSH o terminal del hosting)

Desde la carpeta del proyecto (`app_chamba/`):

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

**Importante — login desde la app:** `php artisan migrate --force` crea la tabla **`personal_access_tokens`** (Laravel Sanctum). Si no la ejecutaste, el login falla con *“Table personal_access_tokens doesn’t exist”*. Si no tienes SSH y solo phpMyAdmin, ejecuta en la BD el script **`db-mysql/04-laravel-sanctum.sql`** del repo (una vez; no mezcles con migrate si ya creó la tabla).

Permisos de escritura (Linux típico):

```bash
chmod -R ug+rwx storage bootstrap/cache
```

(Ajusta usuario/grupo si tu panel lo indica.)

---

## Paso 5 — Si fallan CSS/JS o las rutas API (404)

Abre `public/.htaccess` y **justo debajo** de `RewriteEngine On` añade **una** línea (prueba la primera; si no va, la segunda):

```apache
RewriteBase /v1/app_chamba/public/
```

o, si el hosting ya sirve solo desde `public/`:

```apache
RewriteBase /v1/app_chamba/
```

Instrucciones detalladas del fragmento: **`snippet-RewriteBase-public.txt`** en esta misma carpeta.

Luego:

```bash
php artisan optimize:clear
php artisan optimize
```

---

## Resumen visual de la carpeta en el servidor

```text
public_html/v1/app_chamba/     ← URL: .../v1/app_chamba/
├── .htaccess                  ← contenido de produccion/htaccess-RAIZ-Laravel.txt
├── app/
├── artisan
├── bootstrap/
├── composer.json
├── config/
├── database/
├── public/                    ← Laravel entra aquí por dentro
│   ├── .htaccess              ← a veces + RewriteBase (ver snippet)
│   ├── index.php
│   └── build/                 ← tras npm run build
├── routes/
├── storage/
├── vendor/                    ← tras composer
├── .env                       ← solo en el servidor
└── ...
```

La documentación larga sigue en [README-PUBLICACION.md](../README-PUBLICACION.md).
