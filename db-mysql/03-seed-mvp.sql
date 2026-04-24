-- ============================================================
-- Proyecto: Aplicacion movil de servicios locales (MVP)
-- Archivo: 03-seed-mvp.sql
-- Requiere: 01-schema-mvp.sql + 02-routines-mvp.sql
-- ============================================================

USE jaapsyst_chamba;

-- Limpieza de datos de prueba (sin borrar estructura)
DELETE FROM favorites;
DELETE FROM service_images;
DELETE FROM reviews;
DELETE FROM service_requests;
DELETE FROM provider_services;
DELETE FROM categories;
DELETE FROM provider_profiles;
DELETE FROM users;
DELETE FROM districts;
DELETE FROM provinces;
DELETE FROM departments;

-- =========================
-- Ubicaciones (Peru - muestra)
-- =========================
INSERT INTO departments (name, latitude, longitude) VALUES
('Lima', -12.0463740, -77.0427930),
('Arequipa', -16.4090470, -71.5374510),
('La Libertad', -8.1117630, -79.0286860);

INSERT INTO provinces (department_id, name, latitude, longitude)
SELECT d.id, 'Lima', -12.0463740, -77.0427930 FROM departments d WHERE d.name = 'Lima'
UNION ALL
SELECT d.id, 'Callao', -12.0621065, -77.1320760 FROM departments d WHERE d.name = 'Lima'
UNION ALL
SELECT d.id, 'Arequipa', -16.4090470, -71.5374510 FROM departments d WHERE d.name = 'Arequipa'
UNION ALL
SELECT d.id, 'Trujillo', -8.1117630, -79.0286860 FROM departments d WHERE d.name = 'La Libertad';

INSERT INTO districts (province_id, name, latitude, longitude)
SELECT p.id, 'Miraflores', -12.1211500, -77.0297800 FROM provinces p WHERE p.name = 'Lima'
UNION ALL
SELECT p.id, 'San Isidro', -12.0971500, -77.0364900 FROM provinces p WHERE p.name = 'Lima'
UNION ALL
SELECT p.id, 'Cercado de Lima', -12.0463740, -77.0427930 FROM provinces p WHERE p.name = 'Lima'
UNION ALL
SELECT p.id, 'Bellavista', -12.0603000, -77.1296000 FROM provinces p WHERE p.name = 'Callao'
UNION ALL
SELECT p.id, 'Yanahuara', -16.3959000, -71.5455000 FROM provinces p WHERE p.name = 'Arequipa'
UNION ALL
SELECT p.id, 'Cercado de Arequipa', -16.3989000, -71.5350000 FROM provinces p WHERE p.name = 'Arequipa'
UNION ALL
SELECT p.id, 'Trujillo', -8.1117630, -79.0286860 FROM provinces p WHERE p.name = 'Trujillo'
UNION ALL
SELECT p.id, 'Victor Larco Herrera', -8.1416000, -79.0419000 FROM provinces p WHERE p.name = 'Trujillo';

-- =========================
-- Usuarios
-- Nota: password_hash de prueba (no usar en prod)
-- =========================
INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES
('Juan Perez', 'juan.cliente@example.com', '999111111', '$2y$10$demo_hash_cliente_01', 'cliente', 'activo'),
('Maria Soto', 'maria.cliente@example.com', '999111112', '$2y$10$demo_hash_cliente_02', 'cliente', 'activo'),
('Carlos Llantero', 'carlos.prov@example.com', '999222221', '$2y$10$demo_hash_proveedor_01', 'proveedor', 'activo'),
('Ana Carpintera', 'ana.prov@example.com', '999222222', '$2y$10$demo_hash_proveedor_02', 'proveedor', 'activo'),
('Luis Electricista', 'luis.prov@example.com', '999222223', '$2y$10$demo_hash_proveedor_03', 'proveedor', 'activo');

-- =========================
-- Perfiles de proveedor
-- =========================
INSERT INTO provider_profiles (
  user_id, business_name, description, whatsapp, contact_phone, address_text, district_id, is_verified
)
SELECT
  u.id,
  'Llantera Rapida 24/7',
  'Servicio de llantas, parchado y cambio de neumáticos a domicilio.',
  '51999222221',
  '999222221',
  'Av. Principal 123',
  d.id,
  1
FROM users u
JOIN districts d ON d.name = 'Miraflores'
WHERE u.email = 'carlos.prov@example.com';

INSERT INTO provider_profiles (
  user_id, business_name, description, whatsapp, contact_phone, address_text, district_id, is_verified
)
SELECT
  u.id,
  'Madera Fina SAC',
  'Trabajos de carpinteria en melamina, closets y cocinas.',
  '51999222222',
  '999222222',
  'Calle Los Robles 450',
  d.id,
  0
FROM users u
JOIN districts d ON d.name = 'San Isidro'
WHERE u.email = 'ana.prov@example.com';

INSERT INTO provider_profiles (
  user_id, business_name, description, whatsapp, contact_phone, address_text, district_id, is_verified
)
SELECT
  u.id,
  'Electric Solutions',
  'Instalaciones electricas, tableros y mantenimiento residencial.',
  '51999222223',
  '999222223',
  'Jr. Central 780',
  d.id,
  1
FROM users u
JOIN districts d ON d.name = 'Cercado de Lima'
WHERE u.email = 'luis.prov@example.com';

-- =========================
-- Categorias
-- =========================
INSERT INTO categories (name, slug, is_active) VALUES
('Llantero', 'llantero', 1),
('Carpintero', 'carpintero', 1),
('Electricista', 'electricista', 1),
('Soldador', 'soldador', 1),
('Gasfitero', 'gasfitero', 1);

-- =========================
-- Servicios
-- =========================
INSERT INTO provider_services (
  provider_profile_id, category_id, title, description, base_price, price_type, is_active
)
SELECT pp.id, c.id, 'Cambio de llanta a domicilio', 'Atencion inmediata en caso de pinchazo o desgaste.', 45.00, 'desde', 1
FROM provider_profiles pp
JOIN users u ON u.id = pp.user_id
JOIN categories c ON c.slug = 'llantero'
WHERE u.email = 'carlos.prov@example.com';

INSERT INTO provider_services (
  provider_profile_id, category_id, title, description, base_price, price_type, is_active
)
SELECT pp.id, c.id, 'Fabricacion de closet a medida', 'Diseno y armado de closets personalizados.', NULL, 'cotizar', 1
FROM provider_profiles pp
JOIN users u ON u.id = pp.user_id
JOIN categories c ON c.slug = 'carpintero'
WHERE u.email = 'ana.prov@example.com';

INSERT INTO provider_services (
  provider_profile_id, category_id, title, description, base_price, price_type, is_active
)
SELECT pp.id, c.id, 'Instalacion de tablero electrico', 'Implementacion y balanceo de carga para vivienda.', 280.00, 'fijo', 1
FROM provider_profiles pp
JOIN users u ON u.id = pp.user_id
JOIN categories c ON c.slug = 'electricista'
WHERE u.email = 'luis.prov@example.com';

-- =========================
-- Imagenes de servicios
-- =========================
INSERT INTO service_images (service_id, image_url)
SELECT ps.id, 'https://cdn.demo.com/servicios/llantero-01.jpg'
FROM provider_services ps
JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
JOIN users u ON u.id = pp.user_id
WHERE u.email = 'carlos.prov@example.com';

INSERT INTO service_images (service_id, image_url)
SELECT ps.id, 'https://cdn.demo.com/servicios/carpinteria-01.jpg'
FROM provider_services ps
JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
JOIN users u ON u.id = pp.user_id
WHERE u.email = 'ana.prov@example.com';

-- =========================
-- Solicitudes de prueba (usando SP)
-- =========================
CALL sp_create_service_request(
  (SELECT id FROM users WHERE email = 'juan.cliente@example.com'),
  (SELECT ps.id
   FROM provider_services ps
   JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
   JOIN users u ON u.id = pp.user_id
   WHERE u.email = 'carlos.prov@example.com'
   LIMIT 1),
  'Necesito cambio de llanta ahora en Miraflores',
  'whatsapp'
);

CALL sp_create_service_request(
  (SELECT id FROM users WHERE email = 'maria.cliente@example.com'),
  (SELECT ps.id
   FROM provider_services ps
   JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
   JOIN users u ON u.id = pp.user_id
   WHERE u.email = 'luis.prov@example.com'
   LIMIT 1),
  'Cotiza instalacion de tablero para departamento',
  'telefono'
);

-- =========================
-- Resenas de prueba (usando SP)
-- =========================
CALL sp_create_review(
  (SELECT sr.id
   FROM service_requests sr
   JOIN users u ON u.id = sr.client_user_id
   WHERE u.email = 'juan.cliente@example.com'
   ORDER BY sr.id ASC
   LIMIT 1),
  (SELECT id FROM users WHERE email = 'juan.cliente@example.com'),
  5,
  'Muy rapido y puntual, recomendado.'
);

-- =========================
-- Favoritos de prueba
-- =========================
CALL sp_toggle_favorite(
  (SELECT id FROM users WHERE email = 'juan.cliente@example.com'),
  (SELECT pp.id
   FROM provider_profiles pp
   JOIN users u ON u.id = pp.user_id
   WHERE u.email = 'carlos.prov@example.com'
   LIMIT 1)
);

-- =========================
-- Consultas de validacion rapida
-- =========================
SELECT 'users' AS table_name, COUNT(*) AS total FROM users
UNION ALL
SELECT 'provider_profiles', COUNT(*) FROM provider_profiles
UNION ALL
SELECT 'provider_services', COUNT(*) FROM provider_services
UNION ALL
SELECT 'service_requests', COUNT(*) FROM service_requests
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'favorites', COUNT(*) FROM favorites;
