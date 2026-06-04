# Chamba — API (Laravel)

API REST versión **v1** para la aplicación de servicios locales. Las rutas viven bajo el prefijo **`/api/v1`**.

**Publicación en servidor:** guía rápida (incluye `.htaccess` para `jaapsystem.com/v1/app_chamba/`) → [produccion/README.md](produccion/README.md). Detalle extra → [README-PUBLICACION.md](README-PUBLICACION.md).

## Base URL

Asumiendo `php artisan serve` en el puerto por defecto:

```text
http://127.0.0.1:8000/api/v1
```

Sustituye el host y puerto según tu `APP_URL` o tu despliegue.

## Convenciones generales

| Aspecto | Valor |
|--------|--------|
| Formato | JSON (`Content-Type: application/json` en peticiones con cuerpo) |
| Codificación | UTF-8 |
| Autenticación (rutas protegidas) | Bearer token (Laravel Sanctum) |

**Cabecera recomendada en todas las peticiones** (sobre todo para errores de validación en JSON):

```http
Accept: application/json
```

**Rutas protegidas** (además de `Accept`):

```http
Authorization: Bearer {token}
```

El token lo devuelven `POST /auth/register` y `POST /auth/login` en el campo `token`.

---

## Errores habituales

| Código | Situación |
|--------|-----------|
| **401** | Sin token, token inválido o revocado |
| **403** | Usuario autenticado pero sin el rol requerido (`proveedor` / `cliente`) |
| **404** | Recurso no encontrado (por ejemplo, perfil de proveedor inexistente) |
| **422** | Validación fallida (`errors` por campo) o regla de negocio / SP (`message` desde `DomainException`) |
| **500** | Error interno no controlado |

**Validación Laravel** (ejemplo):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

**Regla de negocio / stored procedure** (ejemplo):

```json
{
  "message": "El email ya se encuentra registrado"
}
```

---

## Endpoints

A continuación, **`{BASE}`** = `http://127.0.0.1:8000/api/v1` (ajusta a tu entorno).

---

### 1. Registro de usuario

**URL:** `POST {BASE}/auth/register`  
**Auth:** no

**Body (JSON):**

| Campo | Tipo | Obligatorio | Notas |
|-------|------|-------------|--------|
| `full_name` | string | sí | máx. 150 |
| `email` | string | sí | email válido |
| `password` | string | sí | mín. 8 |
| `password_confirmation` | string | sí | debe coincidir con `password` |
| `role` | string | sí | `cliente` o `proveedor` |
| `phone` | string | no | máx. 20 |

**Ejemplo request:**

```json
{
  "full_name": "Ana Gómez",
  "email": "ana@example.com",
  "password": "secreto123",
  "password_confirmation": "secreto123",
  "role": "cliente",
  "phone": "51999111222"
}
```

**Response `201 Created`:**

```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "full_name": "Ana Gómez",
    "email": "ana@example.com",
    "phone": "51999111222",
    "role": "cliente",
    "status": "activo",
    "provider_profile": null
  }
}
```

Si el usuario es `proveedor` y aún no tiene perfil, `provider_profile` será `null` hasta que cree uno con `POST /provider/profile`.

---

### 2. Inicio de sesión

**URL:** `POST {BASE}/auth/login`  
**Auth:** no

**Body (JSON):**

| Campo | Tipo | Obligatorio |
|-------|------|-------------|
| `email` | string | sí |
| `password` | string | sí |

**Ejemplo request:**

```json
{
  "email": "ana@example.com",
  "password": "secreto123"
}
```

**Response `200 OK`:** misma forma que registro (`token`, `token_type`, `user`).

**Response `422`:** credenciales incorrectas (mensaje bajo la clave `email` en `errors`).

---

### 2.1 Solicitar enlace de recuperación de contraseña

**URL:** `POST {BASE}/auth/forgot-password`  
**Auth:** no  
**Límite:** 6 solicitudes por minuto (por IP).

**Body (JSON):**

| Campo | Tipo | Obligatorio |
|-------|------|-------------|
| `email` | string | sí |

**Response `200 OK`:** siempre un mensaje genérico (no revela si el correo existe). Requiere correo configurado en el servidor (`MAIL_*` en `.env`).

**Response `429`:** demasiadas solicitudes seguidas.

El correo incluye un enlace a la app web (`/app?token=…&email=…`) para elegir la nueva contraseña.

---

### 2.2 Restablecer contraseña (con token del correo)

**URL:** `POST {BASE}/auth/reset-password`  
**Auth:** no  
**Límite:** 10 intentos por minuto (por IP).

**Body (JSON):**

| Campo | Tipo | Obligatorio |
|-------|------|-------------|
| `email` | string | sí |
| `token` | string | sí (el del enlace) |
| `password` | string | sí (mín. 8) |
| `password_confirmation` | string | sí |

**Response `200 OK`:** `message` indicando éxito.  
**Response `422`:** token inválido o expirado, u otras validaciones.

En base de datos debe existir la tabla `password_reset_tokens` (`php artisan migrate` o script `db-mysql/05-password-reset-tokens.sql`).

---

### 3. Cerrar sesión (revocar token actual)

**URL:** `POST {BASE}/auth/logout`  
**Auth:** sí (Sanctum)

**Body:** vacío.

**Response `200 OK`:**

```json
{
  "message": "Sesión cerrada."
}
```

---

### 4. Usuario actual

**URL:** `GET {BASE}/auth/me`  
**Auth:** sí

**Response `200 OK`:**

```json
{
  "user": {
    "id": 1,
    "full_name": "Ana Gómez",
    "email": "ana@example.com",
    "phone": "51999111222",
    "role": "proveedor",
    "status": "activo",
    "provider_profile": {
      "id": 3,
      "business_name": "Mi taller",
      "description": "...",
      "whatsapp": "51999...",
      "contact_phone": "51999...",
      "address_text": "...",
      "district_id": 10,
      "is_verified": false,
      "avg_rating": "4.50",
      "total_reviews": 2
    }
  }
}
```

Si el usuario es cliente o el proveedor no tiene perfil cargado en relación, `provider_profile` puede ser `null`.

---

### 5. Listar categorías activas

**URL:** `GET {BASE}/categories`  
**Auth:** no

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Llantero",
      "slug": "llantero"
    }
  ]
}
```

---

### 6. Catálogo geográfico

#### 6.1 Departamentos

**URL:** `GET {BASE}/geo/departments`  
**Auth:** no

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Lima",
      "latitude": "-12.0463740",
      "longitude": "-77.0427930"
    }
  ]
}
```

*(Los decimales pueden serializarse como string según el driver PHP / MySQL.)*

#### 6.2 Provincias

**URL:** `GET {BASE}/geo/provinces?department_id={id}`  
**Auth:** no

**Query:**

| Parámetro | Obligatorio |
|-----------|-------------|
| `department_id` | sí |

**Response `200 OK`:** `{ "data": [ { "id", "department_id", "name", "latitude", "longitude" } ] }`

#### 6.3 Distritos

**URL:** `GET {BASE}/geo/districts?province_id={id}`  
**Auth:** no

**Query:**

| Parámetro | Obligatorio |
|-----------|-------------|
| `province_id` | sí |

**Response `200 OK`:** `{ "data": [ { "id", "province_id", "name", "latitude", "longitude" } ] }`

---

### 7. Búsqueda de servicios (stored procedure)

**URL:** `GET {BASE}/services/search`  
**Auth:** no

**Query (todos opcionales):**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `category_id` | integer | Filtrar por categoría |
| `district_id` | integer | Filtrar por distrito del proveedor |
| `keyword` | string | Texto en título, descripción, categoría o nombre |
| `user_lat` | number | Latitud del usuario (para radio) |
| `user_lng` | number | Longitud del usuario |
| `radius_km` | number | Radio en km (0.1–200); suele usarse junto con `user_lat` / `user_lng` |

**Ejemplo:** `GET {BASE}/services/search?category_id=1&district_id=5&keyword=llanta&user_lat=-12.12&user_lng=-77.03&radius_km=10`

**Response `200 OK`:** cada elemento refleja las columnas devueltas por `sp_search_provider_services` (entre otras):

- `service_id`, `title`, `description`, `base_price`, `price_type`
- `category_id`, `category_name`
- `provider_profile_id`, `provider_name`, `whatsapp`, `contact_phone`, `address_text`, `avg_rating`, `total_reviews`
- `district_id`, `district_name`, `province_id`, `province_name`, `department_id`, `department_name`
- `provider_latitude`, `provider_longitude`, `distance_km`

```json
{
  "data": [
    {
      "service_id": 1,
      "title": "Cambio de llanta a domicilio",
      "description": "...",
      "base_price": "45.00",
      "price_type": "desde",
      "category_id": 1,
      "category_name": "Llantero",
      "provider_profile_id": 2,
      "provider_name": "Llantera Rápida",
      "whatsapp": "51999...",
      "contact_phone": "999...",
      "address_text": "Av. Ejemplo 123",
      "avg_rating": "5.00",
      "total_reviews": 1,
      "district_id": 5,
      "district_name": "Miraflores",
      "province_id": 1,
      "province_name": "Lima",
      "department_id": 1,
      "department_name": "Lima",
      "provider_latitude": "-12.1211500",
      "provider_longitude": "-77.0297800",
      "distance_km": "1.234"
    }
  ]
}
```

---

## Rutas de proveedor (`role:proveedor`)

Prefijo: `{BASE}/provider/...`

---

### 8. Ver perfil de proveedor

**URL:** `GET {BASE}/provider/profile`  
**Auth:** sí

**Response `200 OK`:** `{ "data": { ...mismo shape que provider_profile en /auth/me... } }`  
**Response `404`:** aún no existe perfil.

---

### 9. Crear perfil de proveedor

**URL:** `POST {BASE}/provider/profile`  
**Auth:** sí

**Body (JSON):**

| Campo | Obligatorio |
|-------|-------------|
| `district_id` | sí |
| `business_name` | no |
| `description` | no |
| `whatsapp` | no |
| `contact_phone` | no |
| `address_text` | no |

**Response `201 Created`:** `{ "data": { ...ProviderProfileResource... } }`  
**Response `422`:** por ejemplo, perfil ya existente (mensaje del SP).

---

### 10. Actualizar perfil de proveedor

**URL:** `PUT {BASE}/provider/profile`  
**Auth:** sí

**Body:** mismos campos que creación (según validación actual, `district_id` sigue siendo obligatorio).

**Response `200 OK`:** `{ "data": { ... } }`  
**Response `404`:** sin perfil previo.

---

### 11. Listar mis servicios publicados

**URL:** `GET {BASE}/provider/services`  
**Auth:** sí

**Response `200 OK`:** respuesta paginada de Laravel + `ProviderServiceResource` (incluye relación `category` cuando aplica). Estructura típica:

```json
{
  "data": [
    {
      "id": 1,
      "title": "...",
      "description": "...",
      "base_price": "45.00",
      "price_type": "desde",
      "is_active": true,
      "category": {
        "id": 1,
        "name": "Llantero",
        "slug": "llantero"
      }
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 20, "total": 1 }
}
```

**Response `404`:** usuario sin perfil de proveedor.

---

### 12. Crear servicio

**URL:** `POST {BASE}/provider/services`  
**Auth:** sí

**Body (JSON):**

| Campo | Obligatorio | Valores / notas |
|-------|-------------|------------------|
| `category_id` | sí | existe en `categories` |
| `title` | sí | máx. 180 |
| `description` | sí | texto |
| `base_price` | no | numérico ≥ 0 |
| `price_type` | sí | `fijo`, `desde`, `cotizar` |

**Response `201 Created`:** `{ "data": { ...ProviderServiceResource... } }`  
**Response `422`:** sin perfil de proveedor o error del SP.

---

### 13. Actualizar servicio

**URL:** `PUT {BASE}/provider/services/{service}`  
**Auth:** sí (`{service}` = id del servicio **tuyo**)

**Body:** igual que creación.

**Response `200 OK`:** `{ "data": { ... } }`  
**Response `404`:** servicio no existe o no pertenece a tu perfil.

---

### 14. Activar / desactivar servicio

**URL:** `PATCH {BASE}/provider/services/{service}/status`  
**Auth:** sí

**Body (JSON):**

```json
{
  "is_active": true
}
```

**Response `200 OK`:** `{ "data": { ...servicio actualizado... } }`

---

### 15. Dashboard de proveedor

**URL:** `GET {BASE}/provider/dashboard`  
**Auth:** sí

**Response `200 OK`:** objeto único en `data` con los campos devueltos por `sp_get_provider_dashboard` (por ejemplo: totales de servicios, solicitudes pendientes, etc.).  
**Response `404`:** sin perfil de proveedor.

---

## Rutas de cliente (`role:cliente`)

Prefijo: `{BASE}/client/...`

---

### 16. Crear solicitud de contacto a un servicio

**URL:** `POST {BASE}/client/service-requests`  
**Auth:** sí

**Body (JSON):**

| Campo | Obligatorio |
|-------|-------------|
| `provider_service_id` | sí |
| `contact_channel` | sí (`telefono`, `whatsapp`, `app`) |
| `message` | no |

**Response `201 Created`:**

```json
{
  "service_request_id": 12
}
```

**Response `422`:** error del SP (cliente inactivo, servicio no disponible, etc.).

---

### 17. Cerrar solicitud

**URL:** `POST {BASE}/client/service-requests/{serviceRequest}/close`  
**Auth:** sí (`{serviceRequest}` = id numérico)

**Body:** vacío.

**Response `200 OK`:** `{ "message": "Solicitud cerrada." }`  
**Response `403`:** la solicitud no pertenece al cliente autenticado.

---

### 18. Crear reseña (una por solicitud)

**URL:** `POST {BASE}/client/reviews`  
**Auth:** sí

**Body (JSON):**

| Campo | Obligatorio |
|-------|-------------|
| `service_request_id` | sí |
| `rating` | sí (entero 1–5) |
| `comment` | no |

**Response `201 Created`:**

```json
{
  "provider_profile_id": 3,
  "message": "Reseña registrada."
}
```

**Response `403`:** la solicitud no es del cliente.  
**Response `422`:** solicitud ya reseñada o error del SP.

---

### 19. Listar favoritos

**URL:** `GET {BASE}/client/favorites`  
**Auth:** sí

**Response `200 OK`:** array en `data` con filas devueltas por `sp_list_user_favorites` (campos como `favorite_id`, `provider_profile_id`, `provider_name`, ubicación, etc.).

---

### 20. Alternar favorito (agregar / quitar)

**URL:** `POST {BASE}/client/favorites/toggle`  
**Auth:** sí

**Body (JSON):**

```json
{
  "provider_profile_id": 3
}
```

**Response `200 OK`:**

```json
{
  "action": "added"
}
```

o `"removed"`.

---

## Aplicación web (misma instalación Laravel)

- **`GET /`** redige a **`/app`** (app web principal).
- **`GET /app`** — login, registro, modo invitado y búsqueda contra **`/api/v1`** (token en `localStorage`).
- **`GET /portada`** — página informativa con enlace a `/app` y la URL de la API.
- Requiere `npm run build` (o `npm run dev`) para compilar `resources/js/chamba-app.js` y los estilos.

Si en producción sigues viendo la plantilla “Let’s get started” de Laravel, el servidor **no tiene desplegado** este código (o hay `php artisan route:cache` antiguo): sube `routes/web.php`, `resources/views/chamba/`, `resources/js/chamba-app.js`, `vite.config.js` y la carpeta `public/build/` generada, luego `php artisan optimize:clear`.

## Arranque rápido (desarrollo)

1. Configura `.env` (MySQL apuntando a la base creada con los scripts en `../db-mysql/`).
2. `composer install`
3. `php artisan key:generate` (si aún no hay `APP_KEY`)
4. `php artisan migrate` (incluye **Sanctum** → tabla `personal_access_tokens` para login/token de la app; el esquema de negocio viene del SQL en `../db-mysql/`). En producción: `php artisan migrate --force`. Si no usas migrate, ejecuta `../db-mysql/04-laravel-sanctum.sql` en MySQL.
5. `php artisan serve`

---

## Notas técnicas

- La capa de negocio en BD usa **stored procedures** (`StoredProcedureService`); los errores controlados del SP se devuelven como **422** con `{ "message": "..." }`.
- Los roles en BD son `cliente`, `proveedor` y `admin`; la API actual expone flujos principalmente para **cliente** y **proveedor**.
