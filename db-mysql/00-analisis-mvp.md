# Analisis MVP - Base de Datos (MySQL)

## Objetivo de esta fase

Disenar una base de datos escalable para conectar clientes y proveedores de servicios locales, cubriendo primero el MVP:

- Registro e inicio de sesion
- Perfiles de cliente y proveedor
- Publicacion de servicios
- Busqueda por categoria , ubicacion(tambien usar gps) y palabras claves
- Contacto directo
- Calificaciones y comentarios

---

## Propuesta de modulos de datos

1. **Identidad y usuarios**
   - Usuarios base con rol (`cliente`, `proveedor`, `admin` futuro).
   - Autenticacion y estado de cuenta.

2. **Ubicaciones**
   - Catalogos normalizados: departamento, provincia, distrito.
   - Reutilizables para filtros y para direccion del proveedor.
   - Cada nivel tendra coordenadas (lat/lng) para habilitar filtros geograficos a futuro.

3. **Servicios y categorias**
   - Categorias de oficios (llantero, carpintero, etc.).
   - Publicaciones de servicio por proveedor.

4. **Interaccion cliente-proveedor**
   - Contactos/solicitudes.
   - Calificaciones y comentarios.

---

## Entidades MVP sugeridas

## 1) users

Cuenta principal del sistema.

Campos clave:

- id (PK)
- full_name
- email (UNIQUE)
- phone
- password_hash
- role (`cliente` | `proveedor`)
- status (`activo` | `suspendido` | `por aprobar`)
- created_at, updated_at

## 2) provider_profiles

Perfil profesional (1:1 con `users` proveedor).

Campos clave:

- id (PK)
- user_id (FK -> users.id, UNIQUE)
- business_name (nullable)
- description
- whatsapp
- contact_phone
- address_text
- district_id (FK -> districts.id)
- is_verified (fase futura, default false)
- avg_rating (denormalizado, default 0)
- total_reviews (denormalizado, default 0)
- created_at, updated_at

## 3) categories

Catalogo de rubros/oficios.

Campos clave:

- id (PK)
- name (UNIQUE)
- slug (UNIQUE)
- is_active
- created_at, updated_at

## 4) provider_services

Servicios publicados por proveedores.

Campos clave:

- id (PK)
- provider_profile_id (FK -> provider_profiles.id)
- category_id (FK -> categories.id)
- title
- description
- base_price (nullable)
- price_type (`fijo` | `desde` | `cotizar`)
- is_active
- created_at, updated_at

## 5) departments

Catalogo geografico nivel 1.

Campos clave:

- id (PK)
- name (UNIQUE)
- latitude (centroide)
- longitude (centroide)

## 6) provinces

Catalogo geografico nivel 2.

Campos clave:

- id (PK)
- department_id (FK -> departments.id)
- name
- latitude (centroide)
- longitude (centroide)

Indice recomendado:

- UNIQUE(department_id, name)
- INDEX(latitude, longitude)

## 7) districts

Catalogo geografico nivel 3.

Campos clave:

- id (PK)
- province_id (FK -> provinces.id)
- name
- latitude (centroide)
- longitude (centroide)

Indice recomendado:

- UNIQUE(province_id, name)
- INDEX(latitude, longitude)

## 8) service_requests

Registro de contactos/solicitudes del cliente al proveedor (MVP sin chat interno).

Campos clave:

- id (PK)
- client_user_id (FK -> users.id)
- provider_service_id (FK -> provider_services.id)
- message
- contact_channel (`telefono` | `whatsapp` | `app`)
- status (`nuevo` | `contactado` | `cerrado`)
- created_at, updated_at

## 9) reviews

Calificacion del cliente al proveedor tras atencion.

Campos clave:

- id (PK)
- service_request_id (FK -> service_requests.id, UNIQUE)
- client_user_id (FK -> users.id)
- provider_profile_id (FK -> provider_profiles.id)
- rating (1..5)
- comment
- created_at, updated_at

Regla:

- Un contacto/solicitud puede generar maximo una resena.

---

## Relaciones principales

- `users` 1:1 `provider_profiles` (solo proveedores)
- `provider_profiles` 1:N `provider_services`
- `categories` 1:N `provider_services`
- `departments` 1:N `provinces` 1:N `districts`
- `users (cliente)` 1:N `service_requests`
- `provider_services` 1:N `service_requests`
- `service_requests` 1:1 `reviews`
- `provider_profiles` 1:N `reviews`

---

## Indices recomendados para rendimiento MVP

- `users(email)` unique
- `users(role, status)`
- `provider_profiles(district_id)`
- `provider_services(category_id, is_active)`
- `provider_services(provider_profile_id, is_active)`
- `service_requests(client_user_id, created_at)`
- `reviews(provider_profile_id, rating)`
- `departments(latitude, longitude)`
- `provinces(latitude, longitude)`
- `districts(latitude, longitude)`

---

## Consideraciones para Laravel + Flutter

- Usar `BIGINT UNSIGNED` para PK/FK (estandar en Laravel).
- Mantener `created_at` y `updated_at` en todas las tablas de negocio.
- Empezar con `utf8mb4` y `utf8mb4_unicode_ci`.
- Evitar sobre-normalizar en MVP; mantener campos denormalizados (`avg_rating`, `total_reviews`) para consultas rapidas.
- Para coordenadas, usar `DECIMAL(10,7)` en `latitude` y `longitude` (precision suficiente para geofiltros).
- En una fase posterior se puede migrar/duplicar a columna `POINT` con indice espacial para consultas de distancia mas eficientes.

---

## Siguiente paso propuesto

1. Validar esta estructura contigo.
2. Generar `01-schema-mvp.sql` con DDL completo (tablas, FKs, indices, checks compatibles).
3. Generar `02-seed-categorias.sql` y (opcional) seed basico de ubicaciones.
