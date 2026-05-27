# Chamba — Fase 2: Custodia, sedes, estados ampliados, historial unificado

Fecha: 2026-05-26

Esta fase reactiva el **modelo de custodia (escrow)** que estaba durmiente, agrega **sedes múltiples por proveedor**, **estados de solicitud** ampliados con evidencia de entrega, e **historial unificado de pagos** para el cliente. Todo configurable desde el admin.

---

## 1. Cambios de modelo de negocio

| Concepto | Antes (fase 1) | Ahora (fase 2) |
|---|---|---|
| Pago de servicios | Directo entre cliente y proveedor | **En custodia**: cliente paga a Chamba → Chamba paga al proveedor cuando se confirma la entrega |
| Comisión Chamba | 0% (no se cobraba) | **10%** sobre cada servicio liberado (configurable por categoría y por setting global) |
| Pago de membresía | Comprobante opcional | **Captura del Yape/Plin/transferencia es obligatoria** |
| Pago de servicios | (no existía) | **Captura obligatoria al registrar pago** |
| Pago al proveedor | Manual sin trazabilidad | **Admin sube comprobante** al marcar retiro como pagado |
| Sedes por proveedor | 1 sola (su distrito principal) | **1 (Free) / 5 (Pro)** — configurable |
| Estados de solicitud | nuevo, contactado, cerrado | **Ciclo completo escrow**: nuevo → contactado → cotizado → aceptado → pagado_pendiente → en_custodia → en_progreso → **entregado** → confirmado / disputado / reembolsado / cerrado |
| Evidencia de entrega | (no existía) | El proveedor sube fotos antes de marcar como entregado |
| Auto-liberación | (no existía) | Si el cliente no confirma en **7 días**, se libera automáticamente |
| Historial del cliente | Solo membresía o solo servicios | **Unificado**: membresía + servicios en un timeline con comprobantes |

---

## 2. Settings dinámicos nuevos

Editables desde **Admin → Configuración** sin redeploy.

| Clave | Tipo | Default | Descripción |
|---|---|---|---|
| `features.escrow` | boolean | **true** | Activa el modo custodia (ya estaba; ahora ON por defecto) |
| `escrow.commission_percent` | decimal | **10.00** | % que Chamba retiene sobre cada pago en custodia |
| `escrow.auto_release_days` | integer | **7** | Días sin acción del cliente antes de liberar el pago al proveedor |
| `escrow.evidence_min_photos` | integer | **1** | Mínimo de fotos que el proveedor debe subir para marcar como entregado |
| `escrow.dispute_window_days` | integer | **7** | Días que tiene el cliente para reportar disputa tras la entrega |
| `provider.locations.max_free` | integer | **1** | Sedes activas máximas plan Free |
| `provider.locations.max_pro` | integer | **5** | Sedes activas máximas plan Pro/Premium |
| `payments.proof_required` | boolean | **true** | Exige captura al registrar pagos (membresía y servicios) |
| `commission.default_rate` | decimal | **10.00** | Fallback global cuando no hay regla por categoría (era 15.00) |

Todos quedan registrados en `system_settings_log` cuando cambian.

---

## 3. Nuevas tablas y columnas

### Nuevas tablas

- **`provider_locations`** — sedes/direcciones por proveedor (FK a `provider_profiles` y `districts`). Una sede `is_primary`.
- **`service_request_events`** — audit log de transiciones de estado (`from_status`, `to_status`, `actor_user_id`, `actor_role`, `note`, `metadata` JSON).
- **`service_request_evidence`** — fotos subidas por el proveedor al marcar el trabajo como entregado.

### Columnas nuevas

- `service_payments.proof_image_path` — captura del cliente al registrar el pago.
- `wallet_withdrawals.proof_image_path` — comprobante del admin al pagar al proveedor.
- `service_requests.delivered_at` — cuándo el proveedor marcó como entregado.
- `service_requests.client_confirmed_at` — cuándo el cliente liberó el pago.
- `service_requests.auto_release_at` — fecha límite para auto-liberación.
- `service_requests.disputed_at` — cuándo el cliente reportó disputa.
- `service_requests.cancelled_at` — cuándo se canceló.

### ENUM ampliado

`service_requests.status` ahora soporta: `nuevo, contactado, cotizado, aceptado, pagado_pendiente, en_custodia, en_progreso, entregado, terminado, confirmado, cancelado, disputado, reembolsado, cerrado`.

---

## 4. Flujo de custodia end-to-end

```
CLIENTE                  PROVEEDOR                ADMIN              SISTEMA
   │                        │                       │                   │
   │ crea solicitud         │                       │                   │
   ├───────► nuevo          │                       │                   │
   │                        │ marca contactado      │                   │
   │                        ├──► contactado         │                   │
   │                        │ envía cotización      │                   │
   │                        ├──► cotizado           │                   │
   │ acepta cotización      │                       │                   │
   ├──► aceptado            │                       │                   │
   │ paga + sube captura    │                       │                   │
   ├──► pagado_pendiente    │                       │                   │
   │                        │                       │ confirma          │
   │                        │                       ├──► en_custodia    │
   │                        │ trabaja               │                   │
   │                        ├──► en_progreso        │                   │
   │                        │ sube evidencias       │                   │
   │                        │ marca entregado       │                   │
   │                        ├──► entregado          │                   │ programa auto-release
   │ confirma → liberado    │                       │                   │
   │   o disputa            │                       │                   │
   │   o no responde 7 días │                       │                   ├──► auto-libera
   │                        │                       │                   │
   │                        │ solicita retiro       │                   │
   │                        │                       │ paga + comprobante│
   │                        │                       ├──► pagado         │
```

### Endpoints clave del flujo

| Acción | Endpoint | Método | Rol |
|---|---|---|---|
| Crear solicitud | `/v1/client/service-requests` | POST | cliente |
| Marcar contactado, etc. | `/v1/provider/service-requests/{id}/status` | PATCH | proveedor |
| Enviar cotización | `/v1/provider/quotes` | POST | proveedor |
| Aceptar cotización | `/v1/client/quotes/{id}` | PATCH | cliente |
| Registrar pago (con captura) | `/v1/client/payments` | POST (multipart) | cliente |
| Confirmar pago | `/v1/admin/payments/{id}/confirm` | POST | admin |
| Rechazar pago | `/v1/admin/payments/{id}/reject` | POST | admin |
| Subir evidencia (1..10 fotos) | `/v1/provider/service-requests/{id}/evidence` | POST (multipart) | proveedor |
| Eliminar evidencia | `/v1/provider/service-requests/{id}/evidence/{evId}` | DELETE | proveedor |
| Marcar entregado | `/v1/provider/service-requests/{id}/deliver` | POST | proveedor |
| Confirmar trabajo terminado | `/v1/client/payments/{id}/confirm-completed` | POST | cliente |
| Reportar disputa | `/v1/client/payments/{id}/dispute` | POST | cliente |
| Solicitar retiro | `/v1/provider/wallet/withdrawals` | POST | proveedor |
| Marcar retiro pagado (con captura) | `/v1/admin/withdrawals/{id}/pay` | POST (multipart) | admin |

### Auto-liberación

Comando `php artisan chamba:escrow:auto-release` (programado diariamente a las 03:30) revisa todos los pagos en `en_custodia` cuya solicitud está en `entregado` con `auto_release_at <= now()`, y los pasa a `liberado` sumando al wallet del proveedor.

---

## 5. Sedes / Locations

### Endpoints

| Acción | Endpoint | Método |
|---|---|---|
| Listar sedes | `/v1/provider/locations` | GET |
| Crear sede | `/v1/provider/locations` | POST |
| Actualizar | `/v1/provider/locations/{id}` | PUT |
| Eliminar | `/v1/provider/locations/{id}` | DELETE |

### Búsqueda multi-sede

`GET /v1/services/search?district_id=N` ahora incluye **dos pasadas**:

1. Servicios del proveedor cuyo **perfil principal** está en ese distrito (vía SP `sp_search_provider_services`).
2. Servicios de proveedores que tienen una **sede activa** en ese distrito (vía Eloquent, merge por `service_id`).

Los resultados que vienen de la pasada de sedes traen el campo `matched_by_location: true` para que la UI los marque.

### Migración de datos

La migración `2026_05_26_000003_provider_locations.php` crea automáticamente una sede principal (`is_primary=1`) para cada `provider_profile` existente, copiando su `district_id` y `address_text`.

---

## 6. Historial unificado del cliente

`GET /v1/client/history?type=all|membership|service` devuelve un timeline mezclado con:

- **Membresía** (`subscription_payments`): plan, periodo, comprobante.
- **Servicios** (`service_payments`): servicio, proveedor, monto bruto/neto/comisión, comprobante.

Cada item incluye `kind`, `concept`, `concept_detail`, `amount`, `payment_method`, `status`, `proof_image_url`, fechas. Además se devuelven totales: `totals.membership`, `totals.service`, `totals.all`.

Visible en el SPA en `/cliente/historial`.

---

## 7. Frontend (vistas nuevas/modificadas)

| Vista | Ruta | Cambios |
|---|---|---|
| `client/RequestsView.vue` | `/cliente/solicitudes` | Form de pago exige captura, timeline de eventos, evidencia del proveedor, botones Confirmar / Disputar |
| `client/HistoryView.vue` | `/cliente/historial` | **Nueva**. Timeline unificado + totales |
| `provider/RequestsView.vue` | `/proveedor/solicitudes` | Galería de evidencias, uploader, botón "Marcar como entregado", ve captura del cliente |
| `provider/LocationsView.vue` | `/proveedor/sedes` | **Nueva**. CRUD de sedes con límite por plan |
| `admin/PaymentsView.vue` | `/admin/pagos` | Columna comprobante (thumbnail clickeable) |
| `admin/WithdrawalsView.vue` | `/admin/retiros` | Permite subir foto al marcar como pagado |

---

## 8. Stores Pinia nuevos/actualizados

- `stores/clientRequests.js`: agrega `loadHistory`, `disputePayment`, soporta `FormData` con `proof` en `pay`, expone `proofRequired`.
- `stores/providerRequests.js`: agrega `uploadEvidence`, `deleteEvidence`, `markDelivered`.
- `stores/providerLocations.js`: **nuevo**. CRUD + límite por plan + getters `canAddMore`/`remaining`.

---

## 9. Despliegue de fase 2 a producción

### Paso 1 — Subir código

```bash
cd webapi
npm ci && npm run build
# (igual que antes: comprimir y subir vía cPanel)
```

### Paso 2 — Migrar BD

Vía `chamba:setup` o tinker:

```bash
php artisan migrate --force
```

Se aplican 4 migraciones nuevas (`2026_05_26_000001`..`000004`).

### Paso 3 — Verificar settings

Logueado como admin, ir a **Configuración** y confirmar que aparecen los grupos:
- **Custodia (modo escrow)**: `escrow.commission_percent`, `auto_release_days`, etc.
- **Membresías**: `provider.locations.max_*`.
- **Pagos a la plataforma**: `payments.proof_required`.

### Paso 4 — Programar el cron

En cPanel → Cron Jobs, agregar:

```cron
30 3 * * *  cd /home/jaapsyst/public_html/v1/chamba && php artisan schedule:run >> /dev/null 2>&1
```

Esto ejecuta `chamba:escrow:auto-release` diariamente.

### Paso 5 — Probar

Recorre el flujo escrow con el cliente y proveedor demo:

```bash
php artisan chamba:qa-smoke --base=https://jaapsystem.com/api/v1
```

Esperado: 51+ checks OK.

---

## 10. Diagnóstico de FTP

Si los uploads fallan, hay dos cosas nuevas:

1. **Carpetas remotas se crean automáticamente** ahora (`MediaStorageService::storeImage` llama `makeDirectory` antes de `put`).
2. **El disco `chamba_ftp` está con `throw=true`**, así que cualquier error de FTP (credenciales, permisos, conexión) se propaga con mensaje legible — antes fallaba silenciosamente devolviendo `false`.

Si subes el script `_ftp_test.php` (raíz aplanada de la app) podrás verificar PHP extensiones, php.ini, conexión FTP nativa y `Storage::disk('chamba_ftp')`.

---

## 11. QA smoke ampliado

`php artisan chamba:qa-smoke` ahora cubre **54 checks** incluyendo:

- Pago de membresía **con comprobante obligatorio**.
- Sedes (listar, crear segunda, eliminar).
- Búsqueda con `matched_by_location`.
- Flujo escrow end-to-end (solicitud → cotización → aceptación → pago con captura → admin confirma → evidencia → entregado → cliente libera → wallet acreditado).
- Historial unificado.
- Settings de fase 2 visibles.

---

## 12. Roll-back rápido

Si algo sale muy mal, se puede desactivar fase 2 sin tocar código:

- **Apagar custodia** (volvemos al modelo libre): admin → Configuración → `features.escrow = 0`.
- **Quitar captura obligatoria**: `payments.proof_required = 0`.
- **Subir el límite Free de sedes**: `provider.locations.max_free = 99`.

Esto NO revierte las migraciones, pero deshabilita los comportamientos de fase 2 desde la app.
