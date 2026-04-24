-- ============================================================
-- Proyecto: Aplicacion movil de servicios locales (MVP)
-- Archivo: 02-routines-mvp.sql
-- Requiere: 01-schema-mvp.sql
-- ============================================================

USE jaapsyst_chamba;

DROP FUNCTION IF EXISTS fn_distance_km;
DROP PROCEDURE IF EXISTS sp_create_service_request;
DROP PROCEDURE IF EXISTS sp_create_review;
DROP PROCEDURE IF EXISTS sp_toggle_favorite;
DROP PROCEDURE IF EXISTS sp_search_provider_services;
DROP PROCEDURE IF EXISTS sp_register_user;
DROP PROCEDURE IF EXISTS sp_create_provider_profile;
DROP PROCEDURE IF EXISTS sp_update_provider_profile;
DROP PROCEDURE IF EXISTS sp_create_provider_service;
DROP PROCEDURE IF EXISTS sp_update_provider_service;
DROP PROCEDURE IF EXISTS sp_set_service_status;
DROP PROCEDURE IF EXISTS sp_close_service_request;
DROP PROCEDURE IF EXISTS sp_get_provider_dashboard;
DROP PROCEDURE IF EXISTS sp_list_user_favorites;

DELIMITER $$

-- Distancia en kilometros usando Haversine (lat/lng en grados)
CREATE FUNCTION fn_distance_km(
  p_lat1 DECIMAL(10,7),
  p_lng1 DECIMAL(10,7),
  p_lat2 DECIMAL(10,7),
  p_lng2 DECIMAL(10,7)
)
RETURNS DECIMAL(10,3)
DETERMINISTIC
BEGIN
  DECLARE v_earth_radius_km DECIMAL(10,3) DEFAULT 6371.000;
  DECLARE v_dlat DOUBLE;
  DECLARE v_dlng DOUBLE;
  DECLARE v_a DOUBLE;
  DECLARE v_c DOUBLE;

  SET v_dlat = RADIANS(p_lat2 - p_lat1);
  SET v_dlng = RADIANS(p_lng2 - p_lng1);

  SET v_a = SIN(v_dlat / 2) * SIN(v_dlat / 2)
          + COS(RADIANS(p_lat1)) * COS(RADIANS(p_lat2))
          * SIN(v_dlng / 2) * SIN(v_dlng / 2);

  SET v_c = 2 * ATAN2(SQRT(v_a), SQRT(1 - v_a));

  RETURN ROUND(v_earth_radius_km * v_c, 3);
END$$

-- Registro de usuario con validaciones base
CREATE PROCEDURE sp_register_user(
  IN p_full_name VARCHAR(150),
  IN p_email VARCHAR(150),
  IN p_phone VARCHAR(20),
  IN p_password_hash VARCHAR(255),
  IN p_role VARCHAR(20)
)
BEGIN
  DECLARE v_exists_email INT DEFAULT 0;

  IF p_role NOT IN ('cliente', 'proveedor') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Rol invalido para registro';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_email
  FROM users
  WHERE email = p_email;

  IF v_exists_email > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El email ya se encuentra registrado';
  END IF;

  INSERT INTO users (
    full_name, email, phone, password_hash, role, status
  )
  VALUES (
    p_full_name, p_email, p_phone, p_password_hash, p_role, 'activo'
  );

  SELECT LAST_INSERT_ID() AS user_id;
END$$

-- Crea perfil de proveedor (una sola vez por usuario)
CREATE PROCEDURE sp_create_provider_profile(
  IN p_user_id BIGINT UNSIGNED,
  IN p_business_name VARCHAR(150),
  IN p_description TEXT,
  IN p_whatsapp VARCHAR(20),
  IN p_contact_phone VARCHAR(20),
  IN p_address_text VARCHAR(255),
  IN p_district_id BIGINT UNSIGNED
)
BEGIN
  DECLARE v_valid_user INT DEFAULT 0;
  DECLARE v_exists_profile INT DEFAULT 0;
  DECLARE v_exists_district INT DEFAULT 0;

  SELECT COUNT(*)
    INTO v_valid_user
  FROM users
  WHERE id = p_user_id
    AND role = 'proveedor'
    AND status = 'activo';

  IF v_valid_user = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario no es proveedor activo';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_profile
  FROM provider_profiles
  WHERE user_id = p_user_id;

  IF v_exists_profile > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El proveedor ya tiene perfil registrado';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_district
  FROM districts
  WHERE id = p_district_id;

  IF v_exists_district = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Distrito no existe';
  END IF;

  INSERT INTO provider_profiles (
    user_id, business_name, description, whatsapp, contact_phone, address_text, district_id
  )
  VALUES (
    p_user_id, p_business_name, p_description, p_whatsapp, p_contact_phone, p_address_text, p_district_id
  );

  SELECT LAST_INSERT_ID() AS provider_profile_id;
END$$

-- Actualiza perfil de proveedor
CREATE PROCEDURE sp_update_provider_profile(
  IN p_provider_profile_id BIGINT UNSIGNED,
  IN p_business_name VARCHAR(150),
  IN p_description TEXT,
  IN p_whatsapp VARCHAR(20),
  IN p_contact_phone VARCHAR(20),
  IN p_address_text VARCHAR(255),
  IN p_district_id BIGINT UNSIGNED
)
BEGIN
  DECLARE v_exists_profile INT DEFAULT 0;
  DECLARE v_exists_district INT DEFAULT 0;

  SELECT COUNT(*)
    INTO v_exists_profile
  FROM provider_profiles
  WHERE id = p_provider_profile_id;

  IF v_exists_profile = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Perfil de proveedor no existe';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_district
  FROM districts
  WHERE id = p_district_id;

  IF v_exists_district = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Distrito no existe';
  END IF;

  UPDATE provider_profiles
  SET business_name = p_business_name,
      description = p_description,
      whatsapp = p_whatsapp,
      contact_phone = p_contact_phone,
      address_text = p_address_text,
      district_id = p_district_id
  WHERE id = p_provider_profile_id;

  SELECT p_provider_profile_id AS provider_profile_id;
END$$

-- Crea publicacion de servicio
CREATE PROCEDURE sp_create_provider_service(
  IN p_provider_profile_id BIGINT UNSIGNED,
  IN p_category_id BIGINT UNSIGNED,
  IN p_title VARCHAR(180),
  IN p_description TEXT,
  IN p_base_price DECIMAL(10,2),
  IN p_price_type VARCHAR(20)
)
BEGIN
  DECLARE v_exists_profile INT DEFAULT 0;
  DECLARE v_exists_category INT DEFAULT 0;

  IF p_price_type NOT IN ('fijo', 'desde', 'cotizar') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Tipo de precio invalido';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_profile
  FROM provider_profiles
  WHERE id = p_provider_profile_id;

  IF v_exists_profile = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Perfil de proveedor no existe';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_category
  FROM categories
  WHERE id = p_category_id
    AND is_active = 1;

  IF v_exists_category = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Categoria no existe o esta inactiva';
  END IF;

  INSERT INTO provider_services (
    provider_profile_id, category_id, title, description, base_price, price_type, is_active
  )
  VALUES (
    p_provider_profile_id, p_category_id, p_title, p_description, p_base_price, p_price_type, 1
  );

  SELECT LAST_INSERT_ID() AS service_id;
END$$

-- Actualiza publicacion de servicio
CREATE PROCEDURE sp_update_provider_service(
  IN p_service_id BIGINT UNSIGNED,
  IN p_category_id BIGINT UNSIGNED,
  IN p_title VARCHAR(180),
  IN p_description TEXT,
  IN p_base_price DECIMAL(10,2),
  IN p_price_type VARCHAR(20)
)
BEGIN
  DECLARE v_exists_service INT DEFAULT 0;
  DECLARE v_exists_category INT DEFAULT 0;

  IF p_price_type NOT IN ('fijo', 'desde', 'cotizar') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Tipo de precio invalido';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_service
  FROM provider_services
  WHERE id = p_service_id;

  IF v_exists_service = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Servicio no existe';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_category
  FROM categories
  WHERE id = p_category_id
    AND is_active = 1;

  IF v_exists_category = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Categoria no existe o esta inactiva';
  END IF;

  UPDATE provider_services
  SET category_id = p_category_id,
      title = p_title,
      description = p_description,
      base_price = p_base_price,
      price_type = p_price_type
  WHERE id = p_service_id;

  SELECT p_service_id AS service_id;
END$$

-- Activa o inactiva un servicio
CREATE PROCEDURE sp_set_service_status(
  IN p_service_id BIGINT UNSIGNED,
  IN p_is_active TINYINT
)
BEGIN
  DECLARE v_exists_service INT DEFAULT 0;

  IF p_is_active NOT IN (0, 1) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Estado invalido, usar 0 o 1';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_service
  FROM provider_services
  WHERE id = p_service_id;

  IF v_exists_service = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Servicio no existe';
  END IF;

  UPDATE provider_services
  SET is_active = p_is_active
  WHERE id = p_service_id;

  SELECT p_service_id AS service_id, p_is_active AS is_active;
END$$

-- Crea solicitud de contacto validando reglas minimas del dominio
CREATE PROCEDURE sp_create_service_request(
  IN p_client_user_id BIGINT UNSIGNED,
  IN p_provider_service_id BIGINT UNSIGNED,
  IN p_message TEXT,
  IN p_contact_channel VARCHAR(20)
)
BEGIN
  DECLARE v_client_count INT DEFAULT 0;
  DECLARE v_service_count INT DEFAULT 0;

  IF p_contact_channel NOT IN ('telefono', 'whatsapp', 'app') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Canal de contacto invalido';
  END IF;

  SELECT COUNT(*)
    INTO v_client_count
  FROM users
  WHERE id = p_client_user_id
    AND role = 'cliente'
    AND status = 'activo';

  IF v_client_count = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El usuario cliente no existe o no esta activo';
  END IF;

  SELECT COUNT(*)
    INTO v_service_count
  FROM provider_services ps
  JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
  JOIN users u ON u.id = pp.user_id
  WHERE ps.id = p_provider_service_id
    AND ps.is_active = 1
    AND u.status = 'activo';

  IF v_service_count = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El servicio no existe o no esta disponible';
  END IF;

  INSERT INTO service_requests (
    client_user_id, provider_service_id, message, contact_channel, status
  )
  VALUES (
    p_client_user_id, p_provider_service_id, p_message, p_contact_channel, 'nuevo'
  );

  SELECT LAST_INSERT_ID() AS service_request_id;
END$$

-- Cierra una solicitud de servicio
CREATE PROCEDURE sp_close_service_request(
  IN p_service_request_id BIGINT UNSIGNED
)
BEGIN
  DECLARE v_exists_request INT DEFAULT 0;

  SELECT COUNT(*)
    INTO v_exists_request
  FROM service_requests
  WHERE id = p_service_request_id;

  IF v_exists_request = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Solicitud no existe';
  END IF;

  UPDATE service_requests
  SET status = 'cerrado'
  WHERE id = p_service_request_id;

  SELECT p_service_request_id AS service_request_id, 'cerrado' AS status;
END$$

-- Crea una resena y recalcula rating agregado del proveedor
CREATE PROCEDURE sp_create_review(
  IN p_service_request_id BIGINT UNSIGNED,
  IN p_client_user_id BIGINT UNSIGNED,
  IN p_rating TINYINT UNSIGNED,
  IN p_comment TEXT
)
BEGIN
  DECLARE v_exists_request INT DEFAULT 0;
  DECLARE v_exists_review INT DEFAULT 0;
  DECLARE v_provider_profile_id BIGINT UNSIGNED;

  IF p_rating < 1 OR p_rating > 5 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El rating debe estar entre 1 y 5';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_request
  FROM service_requests sr
  WHERE sr.id = p_service_request_id
    AND sr.client_user_id = p_client_user_id;

  IF v_exists_request = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La solicitud no existe o no pertenece al cliente';
  END IF;

  SELECT COUNT(*)
    INTO v_exists_review
  FROM reviews
  WHERE service_request_id = p_service_request_id;

  IF v_exists_review > 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'La solicitud ya tiene una resena registrada';
  END IF;

  SELECT ps.provider_profile_id
    INTO v_provider_profile_id
  FROM service_requests sr
  JOIN provider_services ps ON ps.id = sr.provider_service_id
  WHERE sr.id = p_service_request_id;

  INSERT INTO reviews (
    service_request_id, client_user_id, provider_profile_id, rating, comment
  )
  VALUES (
    p_service_request_id, p_client_user_id, v_provider_profile_id, p_rating, p_comment
  );

  UPDATE provider_profiles pp
  JOIN (
    SELECT provider_profile_id,
           ROUND(AVG(rating), 2) AS avg_rating,
           COUNT(*) AS total_reviews
    FROM reviews
    WHERE provider_profile_id = v_provider_profile_id
    GROUP BY provider_profile_id
  ) t ON t.provider_profile_id = pp.id
  SET pp.avg_rating = t.avg_rating,
      pp.total_reviews = t.total_reviews
  WHERE pp.id = v_provider_profile_id;

  SELECT v_provider_profile_id AS provider_profile_id;
END$$

-- Inserta o elimina favorito (toggle)
CREATE PROCEDURE sp_toggle_favorite(
  IN p_user_id BIGINT UNSIGNED,
  IN p_provider_profile_id BIGINT UNSIGNED
)
BEGIN
  DECLARE v_exists_fav INT DEFAULT 0;

  SELECT COUNT(*)
    INTO v_exists_fav
  FROM favorites
  WHERE user_id = p_user_id
    AND provider_profile_id = p_provider_profile_id;

  IF v_exists_fav = 0 THEN
    INSERT INTO favorites (user_id, provider_profile_id)
    VALUES (p_user_id, p_provider_profile_id);

    SELECT 'added' AS action_result;
  ELSE
    DELETE FROM favorites
    WHERE user_id = p_user_id
      AND provider_profile_id = p_provider_profile_id;

    SELECT 'removed' AS action_result;
  END IF;
END$$

-- Busqueda de servicios por categoria, ubicacion y texto
CREATE PROCEDURE sp_search_provider_services(
  IN p_category_id BIGINT UNSIGNED,
  IN p_district_id BIGINT UNSIGNED,
  IN p_keyword VARCHAR(120),
  IN p_user_lat DECIMAL(10,7),
  IN p_user_lng DECIMAL(10,7),
  IN p_radius_km DECIMAL(10,2)
)
BEGIN
  SELECT
    ps.id AS service_id,
    ps.title,
    ps.description,
    ps.base_price,
    ps.price_type,
    c.id AS category_id,
    c.name AS category_name,
    pp.id AS provider_profile_id,
    COALESCE(pp.business_name, u.full_name) AS provider_name,
    pp.whatsapp,
    pp.contact_phone,
    pp.avg_rating,
    pp.total_reviews,
    d.id AS district_id,
    d.name AS district_name,
    p.id AS province_id,
    p.name AS province_name,
    dep.id AS department_id,
    dep.name AS department_name,
    d.latitude AS provider_latitude,
    d.longitude AS provider_longitude,
    fn_distance_km(p_user_lat, p_user_lng, d.latitude, d.longitude) AS distance_km
  FROM provider_services ps
  JOIN categories c ON c.id = ps.category_id
  JOIN provider_profiles pp ON pp.id = ps.provider_profile_id
  JOIN users u ON u.id = pp.user_id
  JOIN districts d ON d.id = pp.district_id
  JOIN provinces p ON p.id = d.province_id
  JOIN departments dep ON dep.id = p.department_id
  WHERE ps.is_active = 1
    AND c.is_active = 1
    AND u.status = 'activo'
    AND (p_category_id IS NULL OR ps.category_id = p_category_id)
    AND (p_district_id IS NULL OR d.id = p_district_id)
    AND (
      p_keyword IS NULL
      OR ps.title LIKE CONCAT('%', p_keyword, '%')
      OR ps.description LIKE CONCAT('%', p_keyword, '%')
      OR c.name LIKE CONCAT('%', p_keyword, '%')
      OR COALESCE(pp.business_name, u.full_name) LIKE CONCAT('%', p_keyword, '%')
    )
    AND (
      p_user_lat IS NULL
      OR p_user_lng IS NULL
      OR p_radius_km IS NULL
      OR fn_distance_km(p_user_lat, p_user_lng, d.latitude, d.longitude) <= p_radius_km
    )
  ORDER BY
    CASE
      WHEN p_user_lat IS NULL OR p_user_lng IS NULL THEN 0
      ELSE fn_distance_km(p_user_lat, p_user_lng, d.latitude, d.longitude)
    END ASC,
    pp.avg_rating DESC,
    pp.total_reviews DESC,
    ps.created_at DESC;
END$$

-- Resumen para dashboard de proveedor
CREATE PROCEDURE sp_get_provider_dashboard(
  IN p_provider_profile_id BIGINT UNSIGNED
)
BEGIN
  SELECT
    pp.id AS provider_profile_id,
    COALESCE(pp.business_name, u.full_name) AS provider_name,
    pp.avg_rating,
    pp.total_reviews,
    (SELECT COUNT(*) FROM provider_services ps WHERE ps.provider_profile_id = pp.id) AS total_services,
    (SELECT COUNT(*) FROM provider_services ps WHERE ps.provider_profile_id = pp.id AND ps.is_active = 1) AS active_services,
    (SELECT COUNT(*)
       FROM service_requests sr
       JOIN provider_services ps ON ps.id = sr.provider_service_id
      WHERE ps.provider_profile_id = pp.id) AS total_requests,
    (SELECT COUNT(*)
       FROM service_requests sr
       JOIN provider_services ps ON ps.id = sr.provider_service_id
      WHERE ps.provider_profile_id = pp.id
        AND sr.status = 'nuevo') AS pending_requests
  FROM provider_profiles pp
  JOIN users u ON u.id = pp.user_id
  WHERE pp.id = p_provider_profile_id;
END$$

-- Lista favoritos del usuario cliente
CREATE PROCEDURE sp_list_user_favorites(
  IN p_user_id BIGINT UNSIGNED
)
BEGIN
  SELECT
    f.id AS favorite_id,
    f.created_at,
    pp.id AS provider_profile_id,
    COALESCE(pp.business_name, u.full_name) AS provider_name,
    pp.whatsapp,
    pp.contact_phone,
    pp.avg_rating,
    pp.total_reviews,
    d.name AS district_name,
    p.name AS province_name,
    dep.name AS department_name
  FROM favorites f
  JOIN provider_profiles pp ON pp.id = f.provider_profile_id
  JOIN users u ON u.id = pp.user_id
  JOIN districts d ON d.id = pp.district_id
  JOIN provinces p ON p.id = d.province_id
  JOIN departments dep ON dep.id = p.department_id
  WHERE f.user_id = p_user_id
  ORDER BY f.created_at DESC;
END$$

DELIMITER ;
