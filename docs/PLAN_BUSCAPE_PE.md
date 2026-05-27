# Plan de implementación — Busca PE

**Rama:** `Jesus/BuscaPe`  
**Visión:** Directorio de negocios y profesionales en Perú. El usuario busca cerca (GPS o ubigeo), ve anuncios y **contacta** por la plataforma. Sin custodia ni pagos entre usuarios. Ingresos: **suscripciones** + **publicidad** (AdSense + anuncios propios).

**Decisiones cerradas (vencimiento de anuncios):**

| # | Decisión |
|---|----------|
| 1 | Al vencer → **ocultar** (`is_active = false`), no borrar. Se puede **reactivar** si hay cupo. |
| 2 | Renovar/reactivar es **gratis** si aún le quedan cupos de anuncios activos según su plan. |
| 3 | Override por publicador: aplica a **anuncios nuevos** y **renovaciones explícitas**; admin puede extender un anuncio puntual. |
| 4 | Duración por defecto: **5 días**; configurable en admin (`listings.default_duration_days`). |

---

## Glosario (código → producto)

| Hoy en código | Busca PE (UI / docs) |
|---------------|----------------------|
| `provider_services` | **Anuncios** / listings |
| `provider_profiles` | **Negocio** / publicador |
| `proveedor` (rol) | Publicador de anuncios |
| `cliente` | Usuario que busca y contacta |
| Chamba | **Busca PE** |

*Renombre de tablas PHP opcional en fase tardía; prioridad: columnas nuevas + UI + API.*

---

## Arquitectura objetivo (resumen)

```
[Cliente] → GPS / ubigeo → Búsqueda → Anuncio → Contactar → [Negocio]
                              ↑
                    Solo activos y no vencidos
                    Filtrados por distancia / distrito

[Negocio] → Sedes + Anuncios (expires_at) → Cupo plan → Publicar / Renovar / Activar

[Admin] → Settings (días, límites, AdSense) → Override por negocio → Reportes → Kardex
```

---

## Fase 0 — Preparación (0.5–1 día)

**Objetivo:** Base limpia en rama sin tocar `main`.

- [ ] Confirmar `.env` / `APP_NAME=Busca PE`, `APP_URL` del deploy futuro.
- [ ] Crear `config/busca.php` (o renombrar gradualmente `chamba.php`) con defaults Busca PE.
- [ ] Documentar en README de rama el alcance y lo que se **desactiva** (escrow).
- [ ] Ajustar `QaSmokeCommand` para modo “sin escrow” (flag en settings).

**Entregable:** rama lista para migraciones sin sorpresas en producción.

---

## Fase 1 — Pivot de producto: solo contacto (2–3 días)

**Objetivo:** Quitar complejidad de custodia; flujo mínimo buscar → contactar.

### 1.1 Feature flags y settings

| Key (`system_settings`) | Default | Descripción |
|-------------------------|---------|-------------|
| `features.escrow` | `0` | Custodia apagada |
| `features.subscriptions` | `1` | Membresías activas |
| `features.quotes` | `0` | Cotizaciones opcionales (apagadas al inicio) |

### 1.2 Estados de solicitud simplificados

**Estados activos (ENUM reducido o validación en app):**

| Estado | Quién | Significado |
|--------|-------|-------------|
| `nuevo` | sistema | Cliente contactó / creó solicitud |
| `visto` | negocio | Negocio abrió / respondió |
| `cerrado` | cualquiera | Conversación terminada |
| `cancelado` | cliente | Cliente canceló |

Migración: mapear estados viejos → `cerrado` donde aplique.

### 1.3 Backend — desactivar / ocultar rutas

- Dejar de usar (o middleware `feature:escrow`): pagos cliente, wallet proveedor, retiros admin, entrega con evidencia, cotizaciones (si `features.quotes=0`).
- `ServiceRequestController::store`: solo crear contacto; límites cliente (fase 4).
- Cron: desregistrar o no-op `chamba:escrow:auto-release`.

### 1.4 Frontend

- Ocultar menús: Pagos, Ingresos, Retiros, Historial de pagos de servicio (mantener historial de suscripción si aplica).
- `RequestsView` cliente/proveedor: estados simples, botón contactar/cerrar.
- `features.js`: leer `features.escrow === false` por defecto.

### 1.5 Pruebas

- Smoke: cliente contacta → negocio ve solicitud → cerrar.
- Sin subida de comprobante en ningún flujo público.

**Entregable:** app usable como directorio + contacto.

---

## Fase 2 — Anuncios con vencimiento y cupos (3–4 días)

**Objetivo:** Reglas de publicación con tu lógica (5 días, ocultar, reactivar, cupo).

### 2.1 Base de datos

**Tabla `provider_services` (anuncios)** — columnas nuevas:

```sql
published_at   TIMESTAMP NULL
expires_at     TIMESTAMP NULL
deactivated_at TIMESTAMP NULL   -- vencimiento automático o manual
duration_days  SMALLINT UNSIGNED NULL  -- snapshot al publicar (auditoría)
```

**Tabla `provider_profiles`:**

```sql
listing_duration_days_override SMALLINT UNSIGNED NULL
```

**`system_settings` (seed):**

| Key | Default |
|-----|---------|
| `listings.default_duration_days` | `5` |
| `listings.expire_cron_enabled` | `1` |
| `listings.allow_reactivate` | `1` |

**Índice:** `(is_active, expires_at)` para búsqueda.

### 2.2 Servicio `ListingLifecycleService`

Responsabilidades:

1. `effectiveDurationDays(ProviderProfile $profile): int`  
   → override del perfil si existe, si no `chamba_setting('listings.default_duration_days', 5)`.

2. `publish(Listing $listing): void`  
   → `published_at = now()`, `expires_at = now()->addDays($days)`, `duration_days = $days`, `is_active = true`.

3. `renew(Listing $listing, User $owner): void`  
   → Verificar cupo (fase 2.3); si OK, misma lógica que publish (nueva ventana desde **ahora**).

4. `reactivate(Listing $listing, User $owner): void`  
   → Solo si `expires_at < now()` o `!is_active`; consume cupo; recalcula `expires_at`.

5. `deactivateExpired(): int` (cron)  
   → `WHERE expires_at < NOW() AND is_active = 1` → `is_active = 0`, `deactivated_at = now()`.

### 2.3 Cupo de anuncios (“anuncios por publicar”)

Usar `subscription_plans.features.max_services` renombrado conceptualmente a **max_active_listings**:

| Plan | max_active_listings |
|------|---------------------|
| Free | 1 |
| Pro | 20 (configurable admin) |

**Regla:**

```text
cupo_disponible = max_active_listings - count(anuncios WHERE is_active = 1 AND expires_at > now())
```

- **Crear** anuncio nuevo: requiere `cupo_disponible >= 1`.
- **Renovar / reactivar** anuncio vencido/oculto: requiere `cupo_disponible >= 1` (mismo cupo; no es pago extra).
- **Activar** manualmente (`PATCH status`): si ya venció, tratar como reactivar (nueva `expires_at`); si no venció, solo toggle sin consumir cupo extra.

Respuesta API si no hay cupo: `422` + `code: listing_quota_reached`.

### 2.4 API

| Método | Ruta | Acción |
|--------|------|--------|
| POST | `provider/services` | Crear + auto-publish si cupo OK |
| PATCH | `provider/services/{id}/status` | Activar/desactivar (validar vencimiento) |
| POST | `provider/services/{id}/renew` | Renovar (recalcula expires_at) |
| GET | `provider/services` | Incluir `expires_at`, `days_remaining`, `can_renew` |

**Búsqueda pública** (`ServiceSearchController`):

```php
->where('is_active', true)
->where(function ($q) {
    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
})
```

### 2.5 Admin

- Settings: campo numérico “Días de publicación por defecto” (default 5).
- En detalle/edición de negocio (`provider_profiles`): “Días extra de publicación (override)” + nota.
- En listado admin de anuncios (nuevo): extender `expires_at` manual, forzar desactivar.

### 2.6 Frontend proveedor

- `ServicesView.vue` → “Mis anuncios”: badge “Vence en X días”, “Vencido”, botones **Renovar** / **Activar** / **Desactivar**.
- Mostrar: “Tienes 1/1 anuncios activos” según plan.

### 2.7 Cron

```bash
php artisan busca:listings:expire
# Schedule daily 02:00
```

**Entregable:** anuncios viven 5 días (configurable), se ocultan solos, se renuevan con cupo.

---

## Fase 3 — Ubicación: GPS, ubigeo, sedes y anuncio ↔ sedes (4–5 días)

**Objetivo:** “Listar lo que hay alrededor”.

### 3.1 Sedes (`provider_locations`)

Columnas nuevas:

```sql
ubigeo VARCHAR(6) NULL
```

Frontend `LocationsView.vue`:

- Campos lat/lng (manual o “Usar mi ubicación”).
- Autocompletar ubigeo desde distrito seleccionado (tabla `districts` si tiene código; si no, derivar de API geo).

### 3.2 Anuncio ↔ sedes (pivot)

```sql
provider_service_locations (
  provider_service_id BIGINT,
  provider_location_id BIGINT,
  PRIMARY KEY (...),
  FOREIGN KEY ...
)
```

- Al crear/editar anuncio: multiselect de sedes del negocio.
- Si no elige ninguna → **aparece en todas las sedes activas** (regla por defecto recomendada).
- Búsqueda: filtrar anuncios que tengan al menos una sede en el distrito/ radio solicitado.

### 3.3 Búsqueda pública

**Parámetros API** `GET /services/search`:

| Param | Descripción |
|-------|-------------|
| `lat`, `lng`, `radius_km` | GPS + radio (Haversine en SQL o PHP) |
| `department_id`, `province_id`, `district_id` | Filtro ubigeo clásico |
| `ubigeo` | 6 dígitos → resolver a distrito |
| `q`, `category_id` | Texto y categoría |

**Frontend `SearchView.vue`:**

- Botón “Cerca de mí” (`navigator.geolocation`).
- Selector dept/prov/dist o input ubigeo.
- Orden: distancia ASC si hay coords; si no, por relevancia/fecha.

### 3.4 Opcional anuncio con ubicación propia

Si un profesional no tiene sede: en el anuncio permitir dept/prov/dist + lat/lng (columnas en `provider_services` o sede virtual “Única”).

**Entregable:** búsqueda geolocalizada usable en móvil.

---

## Fase 4 — Suscripciones y límites (2–3 días)

**Objetivo:** Monetización solo por planes; límites configurables.

### 4.1 Settings y planes

| Concepto | Free cliente | Premium cliente | Free publicador | Pro publicador |
|----------|--------------|-----------------|-----------------|----------------|
| Solicitudes/mes | 3 | ilimitado | — | — |
| Contactos recibidos/mes | — | — | 2 aceptar/ver* | ilimitado |
| Anuncios activos | — | — | 1 | 20 |
| Sin publicidad | no | sí | — | opcional |

\*Definir “aceptar” = marcar solicitud como `visto` o contar `service_requests` nuevos del mes.

**Features JSON** en `subscription_plans`:

```json
{
  "max_active_listings": 1,
  "max_contacts_per_month": 2,
  "max_client_requests_per_month": 3,
  "no_ads": false
}
```

### 4.2 Backend

- `SubscriptionService::clientCanCreateRequest()`
- `SubscriptionService::publisherCanReceiveContact()` (renombrar desde provider)
- Integrar en `ServiceRequestController::store` y listados.

### 4.3 Cliente premium

- Sin banners AdSense / custom ads en layout (`meta.no_ads` desde `/auth/me`).
- Pago suscripción sin comprobante obligatorio si decides solo validación manual admin (o mantener comprobante solo para activar plan).

**Entregable:** límites enforced en API + mensajes claros en UI.

---

## Fase 5 — Rebrand Busca PE (1–2 días, en paralelo parcial)

- Textos: Login, Home, Header, Footer, emails, `site.webmanifest`.
- Assets: `public/img/busca-pe-icon.png` (reemplazar chamba-icon).
- `resources/views/chamba/app.blade.php` → `busca/app.blade.php` (rutas web).
- Variables globales: `window.BUSCA_*` (mantener alias `CHAMBA_*` deprecado una versión).
- CSS: tokens de color si cambian.
- Categorías seed: tipos de negocio (comercio, ocio, servicios, profesional).

**Entregable:** marca coherente en UI.

---

## Fase 6 — Admin: reportes y kardex (3–4 días)

### 6.1 Eventos de búsqueda

```sql
search_events (
  id, user_id NULL, category_id NULL, query VARCHAR(255),
  district_id NULL, lat DECIMAL(10,7) NULL, lng DECIMAL(10,7) NULL,
  results_count INT, created_at
)
```

Registrar en `ServiceSearchController::index` (async o insert ligero).

**Reporte:** categorías más buscadas / términos top, filtro `from` / `to`.

### 6.2 Kardex

```sql
ledger_entries (
  id, type ENUM('ingreso','egreso'),
  category VARCHAR(50),  -- suscripcion, publicidad, hosting, ...
  amount DECIMAL(12,2), currency CHAR(3),
  description VARCHAR(500),
  reference_type VARCHAR(50) NULL, reference_id BIGINT NULL,
  occurred_at DATE, created_by_user_id, created_at
)
```

- **Ingresos automáticos:** al confirmar pago de suscripción → crear `ledger_entry` ingreso.
- **Egresos:** solo admin manual (formulario).
- **Vista admin:** saldo periodo, tabla filtrable, export CSV.

### 6.3 Dashboard admin renovado

- KPIs: suscripciones activas, MRR, anuncios activos/vencidos hoy, contactos del mes.
- Quitar KPIs escrow o ocultarlos si flag off.

---

## Fase 7 — Publicidad (3–4 días)

### 7.1 Google AdSense (admin)

Settings (grupo `ads`):

| Key | UI label |
|-----|----------|
| `ads.adsense_enabled` | Activar AdSense |
| `ads.adsense_client_id` | ID del editor (ca-pub-XXXX) |
| `ads.adsense_slot_home` | Slot página inicio |
| `ads.adsense_slot_search` | Slot búsqueda |
| `ads.adsense_slot_detail` | Slot detalle anuncio |

Pantalla admin: formulario con ayuda en español + vista previa “pegado” del script.

Componente Vue `AdSlot.vue`: inyecta script solo si `!user.premium` y settings enabled.

### 7.2 Anuncios propios (negocios locales)

```sql
platform_ads (
  id, title, image_path, link_url,
  placement ENUM('home','search','detail','all'),
  starts_at, ends_at, is_active, sort_order,
  impressions INT DEFAULT 0, clicks INT DEFAULT 0
)
```

Admin CRUD + subida imagen FTP/media.

Rotación en `AdSlot` custom antes/después de AdSense según prioridad configurable.

**Entregable:** dos fuentes de ingreso publicitario + premium sin ads.

---

## Fase 8 — Valoraciones (2 días)

| Tipo | Regla |
|------|-------|
| Cliente → negocio | Tras solicitud `cerrado` (ya existe, ajustar estado) |
| Negocio → cliente | Opcional, solo admin ve o perfil interno |
| Usuario → plataforma | Formulario `/feedback` guardado en `platform_feedback` |

**Entregable:** confianza en el directorio sin complicar contacto.

---

## Fase 9 — QA, documentación y deploy (2 días)

- Actualizar `docs/GUIA_PRUEBAS.md` flujos Busca PE.
- Comando `php artisan busca:qa-smoke` (renombrar/extender).
- `build-deploy-zip.ps1`: validaciones Busca PE.
- Migraciones ordenadas + script `_migrate.php` en hosting.
- Checklist deploy subdirectorio (`/v1/busca-pe/` o el path que definas).

---

## Orden de ejecución recomendado

```
Fase 0 → Fase 1 (contacto) → Fase 2 (vencimiento) → Fase 3 (geo)
    → Fase 4 (límites) → Fase 5 (marca, paralelo)
    → Fase 6 (admin) → Fase 7 (ads) → Fase 8 (reviews) → Fase 9 (QA)
```

**MVP publicable (mínimo viable):** Fases 0 + 1 + 2 + 3 + 5 + 9.  
**Monetización completa:** + Fase 4 + 7.  
**Operación negocio:** + Fase 6 + 8.

---

## Estimación total

| Fases | Días aprox. |
|-------|-------------|
| 0–2 | 6–8 |
| 3–4 | 6–8 |
| 5–8 | 9–12 |
| 9 | 2 |
| **Total** | **23–30 días** de desarrollo enfocado |

---

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|--------|------------|
| Stored procedures antiguos (Chamba) | Envolver en servicios PHP nuevos; deprecar SPs gradualmente |
| ENUM estados solicitud en MySQL | Migración única + mapeo datos viejos |
| Búsqueda por GPS lenta | Índice espacial o bounding box; cache por distrito |
| AdSense rechazado en dominio nuevo | Módulo custom ads funciona sin AdSense |
| Confusión cupo vs renovar | UI explícita: “Usas 1 de 1 cupos activos” |

---

## Próximo paso inmediato

Comenzar **Fase 1 + Fase 2** en código:

1. Migración `expires_at` + settings `listings.default_duration_days = 5`.
2. `ListingLifecycleService` + cron expire.
3. Endpoints renew/reactivate + cupo.
4. Apagar escrow en settings y UI.

Cuando apruebes este plan, la implementación seguirá el orden de fases arriba commit por commit en `Jesus/BuscaPe`.

---

## Progreso de implementación

| Fase | Estado | Notas |
|------|--------|-------|
| 0 | Parcial | Rama `Jesus/BuscaPe`, config escrow default false |
| 1 | **Hecho** | Escrow apagado, contactos simples |
| 2 | **Hecho** | Vencimiento anuncios 5 días, cupo, renew |
| 3 | **Hecho** | GPS/ubigeo búsqueda, sedes con coords, pivot anuncio↔sedes |
| 4 | **Hecho** | Límites cliente 3 / negocio 2 contactos/mes, premium sin ads |
| 5 | **Hecho** | Marca Busca PE en UI principal, rutas `/anuncio`, `/proveedor/anuncios` |
| 6 | **Hecho** | Reportes búsqueda + kardex admin |
| 7 | **Hecho** | AdSense settings + banners propios admin |
| 8 | Parcial | Feedback plataforma (`POST /feedback`); reviews cliente→proveedor existentes |
| 9 | Pendiente | Actualizar `GUIA_PRUEBAS.md` y smoke tests |
