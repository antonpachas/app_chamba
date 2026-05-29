# Despliegue Chamba en producción (shared hosting, sin SSH)

**Hosting destino:** `https://jaapsystem.com/v1/chamba`
**Carpeta del servidor:** `/home/jaapsyst/public_html/v1/chamba/`
**Estructura:** *flat* (Laravel y `public/` viven en la misma carpeta — adaptado al cPanel).

> Esta guía está optimizada para **cPanel + File Manager**. No se necesita SSH ni Composer en el servidor.

---

## 1) Generar el ZIP desde tu PC

Desde `d:\PROYECTOS\chamba\webapi`:

```powershell
# 1. Compilar el frontend (genera public/build/)
npm run build

# 2. Instalar deps de producción optimizadas (genera vendor/ liviano)
composer install --no-dev --optimize-autoloader

# 3. Generar el ZIP
powershell -ExecutionPolicy Bypass -File build-deploy-zip.ps1
```

> El script `build-deploy-zip.ps1` (incluido en el repo) hace los pasos 1-2-3 en uno. Si lo corres a mano, asegúrate de que en el zip estén:
>
> - `.htaccess` (raíz, con DirectorySlash Off + rewrite `/app`)
> - `.env` (regenerado desde `.env.production`, NO el de desarrollo)
> - `vendor/` (con autoloader optimizado)
> - `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`
> - `public/` (con `index.php`, `.htaccess`, `build/`, **sin** scripts `_*.php` viejos)
> - Esqueleto `storage/` con sus `.gitignore` (la app no arranca sin esto)
> - `public/_reset.php`, `public/_migrate.php`, `public/_import_geo.php` y `public/_cron.php` (utilitarios; migrador/import geo se borran al final; `_cron.php` solo si usas cron por URL)
> - `composer.json`, `composer.lock`, `artisan`

**Tamaño esperado:** ~8-10 MB · ~6500 archivos.

---

## 2) Subir y extraer en el hosting

### Para un **deploy limpio** (recomendado si hubo problemas)

1. cPanel → File Manager → entrá a `/home/jaapsyst/public_html/v1/chamba/`.
2. Seleccioná **TODO** dentro de esa carpeta (Ctrl+A) y **Delete**.
3. Subí `chamba_full.zip` a la carpeta vacía (botón Upload).
4. Click derecho sobre el zip → **Extract** → confirmar.
5. Borra el zip una vez extraído.

### Para un **update parcial** (si la app ya funcionaba)

1. Subí `chamba_full.zip` a la carpeta.
2. Click derecho → **Extract** → confirmar **"sobrescribir"**.
3. **Importante**: si el extractor te ofrece "limpiar antes de extraer" — **NO LO ELIJAS**. Eso borra `.env` y `vendor/` y rompe todo.
4. Borrá el zip.

---

## 3) Migrar la base de datos

Después de extraer (en cualquiera de los dos casos), corré las migraciones:

```
https://jaapsystem.com/v1/chamba/public/_migrate.php?token=<TU_TOKEN>
```

Donde `<TU_TOKEN>` es el valor de `CHAMBA_SETUP_TOKEN` en tu `.env` (por defecto: `5cb882d25fca17707a97a4186c893b90017175a6ce9c1750`).

**Modos del migrador:**

| URL | Qué hace |
|---|---|
| `?token=XXX` | `migrate --force` + limpia y recachea config/route/view |
| `?token=XXX&status=1` | Solo muestra estado de migraciones (no aplica nada) |
| `?token=XXX&seed=1` | Además ejecuta `db:seed --force` (categorías, planes, etc.) |
| `?token=XXX&fresh=1&confirm=YES` | **DESTRUCTIVO** — borra todas las tablas y reconstruye |

Al final del output debe aparecer la lista completa con `[N] Ran` en cada migración. Si alguna queda en `Pending`, copiame ese output y lo afino.

---

## 3b) Importar ubicación Perú (UBIGEO + coordenadas)

**No lo hace `_migrate.php`.** Después de migrar, importá el catálogo completo (~25 deptos, ~196 provincias, ~1 893 distritos):

```
https://jaapsystem.com/v1/chamba/public/_import_geo.php?token=<TU_TOKEN>
```

| URL | Qué hace |
|---|---|
| `?token=XXX` | Importación completa (puede tardar 2–10 min; no cierres la pestaña) |
| `?token=XXX&status=1` | Solo muestra cuántos deptos/provincias/distritos hay en BD |
| `?token=XXX&download=1` | Fuerza descarga del CSV en el servidor y luego importa |
| `?token=XXX&batch=50&offset=0` | Importa por lotes (**recomendado**; 50 filas por request). Al final muestra la URL del siguiente lote |
| `?token=XXX&release_lock=1` | Quita el bloqueo si un import anterior quedó colgado (`Lock wait timeout`) |

**Importante:** no abras varios lotes a la vez ni corras `php artisan chamba:import-peru-ubigeo` en tu PC al mismo tiempo que el navegador — MySQL bloquea las tablas y verás error 1205.

**Conteos esperados al terminar:** ~25 departamentos, ~196 provincias, ~1893 distritos.

El ZIP generado con `build-deploy-zip.ps1` incluye `storage/app/ubigeo_distrito.csv` si existe en tu PC (recomendado). Si no, el script intenta descargarlo desde GitHub en el servidor.

**Borra `public/_import_geo.php` cuando termines** (junto con `_migrate.php`).

---

## 4) Limpiar caches + verificar bootstrap

```
https://jaapsystem.com/v1/chamba/public/_reset.php
```

Esto:
- Crea el esqueleto de `storage/` si falta.
- Hace `chmod` 775 sobre `storage/` y `bootstrap/cache/`.
- Borra los caches compilados (config, routes, views, services).
- Simula una request a `/app` para confirmar que Laravel responde HTTP 200.

Al final muestra:
```
.env existe: SI
vendor/autoload.php existe: SI
.htaccess existe: SI
public/index.php existe: SI
Laravel handle() -> HTTP 200
```

Si alguno de esos sale en NO o el handle no es 200, hay un problema concreto que se resuelve mirando el log (`storage/logs/laravel-YYYY-MM-DD.log`).

---

## 4b) Configurar cron (anuncios + suscripciones)

Sin esto, los anuncios no se ocultan solos al vencer y las suscripciones Premium no bajan a Free.

**Guía completa (recomendada):** [`docs/GUIA_CRON_CPANEL.md`](../docs/GUIA_CRON_CPANEL.md) — qué hace cada tarea, paso a paso en cPanel, pruebas y problemas frecuentes.

Resumen rápido:

1. Prueba manual: `https://jaapsystem.com/v1/chamba/public/_cron.php?token=<TU_TOKEN>&task=all`
2. En **cPanel → Cron Jobs**, crea las dos tareas con `php artisan` (ver guía) o una sola con `curl` a `_cron.php`.

---

## 5) Probar las URLs reales

| URL | Esperado |
|---|---|
| `https://jaapsystem.com/v1/chamba/app` | El SPA carga |
| `https://jaapsystem.com/v1/chamba/app/` | 301 → `/app` → SPA carga |
| `https://jaapsystem.com/v1/chamba/app/acceder` | Pantalla de login |
| `https://jaapsystem.com/v1/chamba/api/v1/categories` | JSON con categorías |
| `https://jaapsystem.com/v1/chamba/api/v1/subscriptions/plans` | JSON con planes |

### Si te sale 404 en `/app` o `/api/*` (después de extraer)

**Causa**: el `.htaccess` raíz no se aplicó correctamente y/o `public/index.php` no tiene el patch de baseUrl. Pasa cuando el extractor de cPanel filtra dotfiles o el zip no incluye el patch.

**Fix automático**:

```
https://jaapsystem.com/v1/chamba/public/_fix_htaccess.php
```

Esto:
- Reescribe `.htaccess` raíz con la versión correcta.
- Patcha `public/index.php` con el strip de `SCRIPT_NAME` (necesario para que Symfony detecte la baseUrl en estructura aplanada).
- Limpia caches.
- Verifica con curl interno que las URLs respondan 200.

Detalles técnicos del problema: ver `.cursor/rules/deploy-jaapsystem.mdc` (sección "Gotcha crítica: baseUrl en estructura aplanada").

### Si los chunks de JS dan 404 (`/build/assets/*.js` sin prefijo)

**Síntoma**: la app carga, pero al navegar a una vista (Dashboard, Perfil, etc.) en DevTools → Network ves líneas en rojo:
```
https://jaapsystem.com/build/assets/DashboardView-XXX.js → 404
```
**fíjate**: la URL **NO tiene `/v1/chamba/`** entre el host y `/build/`.

**Causa**: `vite.config.js` no tiene el `base` derivado del `APP_URL`. Vue Router carga vistas con `import()` dinámico y Vite usa el `base` (default `/`) para resolver esos chunks.

**Fix**: en `vite.config.js`, derivar `base` desde `APP_URL` del `.env.production`:

```javascript
import { defineConfig, loadEnv } from 'vite';
export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const base = env.APP_URL ? new URL(env.APP_URL).pathname.replace(/\/?$/, '/') : '/';
    return { base, /* ... */ };
});
```

Después corre **`npm run build`** y sube **toda la carpeta `public/build/`** al servidor (sobreescribe la existente).

### Si imágenes de perfil/servicio/comprobante dan 404 en `/api/v1/media/...`

**Síntoma**: subir avatar/imagen funciona (se guarda en FTP, visibility public), pero al cargarla en el navegador da 404. Las otras rutas `/api/v1/*` sí responden 200.

**Causa**: en Laravel 11 + LiteSpeed, los argumentos `defaults('folder', '...')` declarados en la ruta **no se inyectan automáticamente como parámetro del método del controller** cuando el orden no coincide con la URL. `MediaController::show()` recibe `$folder = ''` (vacío) y aborta con 404 al no estar en el whitelist.

**Fix permanente**: el archivo `app/Http/Controllers/Api/V1/MediaController.php` debe leer el folder del Request, no del argumento:

```php
public function show(Request $request, string $name, ?string $folder = null): Response
{
    $folder = $folder
        ?: (string) ($request->route()?->defaults['folder'] ?? '')
        ?: (string) ($request->route()?->parameter('folder') ?? '');
    // …resto del método
}
```

**Fix automático en servidor**:

```
https://jaapsystem.com/v1/chamba/public/_fix_media_404.php
```

Restaura backup (si lo necesita), reemplaza el método `show()` completo, verifica sintaxis con `php -l`, limpia caches y prueba con curl que un avatar real responde HTTP 200 + `Content-Type: image/*`.

Detalles en `.cursor/rules/deploy-jaapsystem.mdc` (sección "Gotcha: inyección de defaults() en controller").

---

## 6) Limpieza de seguridad (¡importante!)

Borrá del servidor (file manager):

```
public/_migrate.php
public/_reset.php
public/_cron.php
public/_fix_htaccess.php
public/_diag.php
public/_ftp_test.php
public/_route_test.php
chamba_full.zip
```

Y editá `.env` para **vaciar** el token (el migrador queda deshabilitado):

```
CHAMBA_SETUP_TOKEN=
```

---

## Variables críticas en `.env`

| Variable | Valor de producción |
|---|---|
| `APP_URL` | `https://jaapsystem.com/v1/chamba` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:2rLjslFS0IGtOqcCqBeR9istlC5Op8wyd+KscjIZsuA=` |
| `DB_HOST` | `45.56.67.32` |
| `DB_DATABASE` | `jaapsyst_chamba` |
| `DB_USERNAME` | `jaapsyst_chamba_ad` |
| `DB_PASSWORD` | `@nt0nP4ch4505` |
| `CHAMBA_FTP_HOST` | `jaapsystem.com` |
| `CHAMBA_FTP_USERNAME` | `ftp_chamba@jaapsystem.com` |
| `CHAMBA_FTP_PASSWORD` | `@nt0nP4ch4505` |
| `CHAMBA_FEATURE_ESCROW` | `true` (Fase 2) |
| `SESSION_PATH` | `/v1/chamba` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CHAMBA_SETUP_TOKEN` | `5cb882d25fca17…` (vaciar tras setup) |

---

## Troubleshooting

### 403 al abrir `/v1/chamba/app/`
- La carpeta `app/` de Laravel choca con la ruta `/app/` del SPA.
- Fix: el `.htaccess` del zip incluye `DirectorySlash Off` y `RewriteRule ^app($|/.+) public/index.php [L]`. Confirma que el `.htaccess` en raíz tiene ese contenido.

### 404 Not Found en todas las URLs
- Falta `public/index.php` o se borró el `.htaccess`. Reextraé el zip.

### 500 Internal Server Error
1. Visita `public/_reset.php` — te dice exactamente qué falta (`.env`, `vendor/`, `storage/`).
2. Si dice "Laravel handle() ERROR: ...", copiame ese error.
3. Mira `storage/logs/laravel-YYYY-MM-DD.log` desde el File Manager.

### `Permission denied` al escribir en `storage/` o `bootstrap/cache/`
- En el File Manager, click derecho sobre la carpeta → **Change Permissions** → `0775` (recursivo).
- O usa `public/_reset.php` que aplica `chmod 0775` automáticamente.

### Las imágenes no se ven (`<img>` con 403)
- Solo afecta a `payments/*`. Esas URLs son **firmadas con expiración** (24 h). Si el usuario está mirando un comprobante viejo de hace >24h, debe recargar.
- Las URLs se generan en `MediaStorageService::publicUrl()` con `URL::temporarySignedRoute`. Verifica que `APP_URL` esté correcto (sin trailing slash).

### `bootstrap Laravel ERROR: Target class [env] does not exist`
- Este error solo aparece si llamás `$app->environment()` **sin** que Laravel haya bootstrapeado (falso positivo del script). El `_reset.php` actual ya **no** lo dispara — usa `handle()` que sí bootstrapea.

---

## Anatomía del proyecto en el servidor (después del deploy)

```
/home/jaapsyst/public_html/v1/chamba/
├── .env                            ← credenciales
├── .htaccess                       ← rewrite /app, /api, etc.
├── app/                            ← código Laravel (Models, Controllers, Services)
├── artisan
├── bootstrap/
│   ├── app.php
│   └── cache/                      ← se llena al cachear (write-only para el web user)
├── composer.json / composer.lock
├── config/
├── database/
│   └── migrations/                 ← 13 migraciones (4 son de Fase 2)
├── public/                         ← este es el "docroot lógico" servido por Apache
│   ├── .htaccess                   ← rewrite interno
│   ├── index.php                   ← front controller
│   └── build/                      ← JS/CSS compilados por Vite
├── resources/                      ← vistas blade + fuentes Vue (no se sirven directamente)
├── routes/
│   ├── api.php                     ← /api/v1/*
│   ├── console.php                 ← schedule (auto-release escrow)
│   └── web.php                     ← /, /portada, /app, /app/{any}
├── storage/                        ← caches/logs/sessions (DEBE existir, write-able)
│   ├── app/
│   ├── framework/{cache,sessions,views,testing}/
│   └── logs/
└── vendor/                         ← deps de composer (NO subir tu vendor de dev)
```

---

## Cron (recomendado en producción)

Documentación detallada: **[`docs/GUIA_CRON_CPANEL.md`](../docs/GUIA_CRON_CPANEL.md)** (para qué sirve, cPanel paso a paso, URL `_cron.php`, fallos frecuentes).

| Comando | Qué hace |
|---------|----------|
| `busca:listings:expire` | Oculta anuncios vencidos (ej. 5 días) |
| `chamba:expire-subscriptions` | Premium/trial vencido → plan Free |

Prueba manual: `https://jaapsystem.com/v1/chamba/public/_cron.php?token=<TU_TOKEN>&task=all`

---

## Errores que hemos resuelto y cómo evitarlos

| Bug histórico | Causa | Fix |
|---|---|---|
| 500 "Please provide a valid cache path" | `storage/framework/views` no existía | `_reset.php` lo crea automáticamente |
| 403 al abrir `/app/` | `app/` físico choca con SPA, DirectoryIndex falla | `.htaccess` con `DirectorySlash Off` + rewrite explícito |
| 404 al abrir `/app/` | Laravel route `/app/{any?}` no matchea trailing slash | Doble ruta `web.php`: `/app` y `/app/{any}` + redirect 301 en `.htaccess` |
| 500 tras extraer zip | `.env` o `vendor/` borrados por opción "clean before extract" | Usar zip **completo** que incluye ambos; no usar "clean" |
| Comprobantes de pago no se ven en `<img>` | Endpoint protegido con `auth:sanctum` que el `<img>` no envía | URLs firmadas con `URL::temporarySignedRoute` (24h) |

---

## Versión / fecha

Generado: 2026-05-26 · Cubre Fase 2 (custodia + sedes + estados expandidos + comprobantes obligatorios).
