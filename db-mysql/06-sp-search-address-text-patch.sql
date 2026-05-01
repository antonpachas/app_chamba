-- ============================================================
-- Parche: recrear sp_search_provider_services (incluye address_text
-- y filtro por radio seguro cuando no se envían lat/lng).
--
-- IMPORTANTE: ejecútalo en un cliente que acepte DELIMITER (MySQL CLI,
-- Workbench en modo script). NO pegues solo el CREATE sin DELIMITER:
-- el primer ";" del SELECT terminaría el CREATE y daría error de sintaxis.
--
-- Si falla por columna desconocida, antes ejecuta (una vez):
--   ALTER TABLE provider_profiles
--     ADD COLUMN address_text VARCHAR(255) NULL AFTER contact_phone;
-- (si ya existe, MySQL devolverá error 1060; ignóralo o comprueba con SHOW COLUMNS.)
-- ============================================================

USE jaapsyst_chamba;

-- Ajusta el nombre de la base si no es jaapsyst_chamba.
-- Si address_text no existe en provider_profiles, descomenta y ejecuta una vez:
-- ALTER TABLE provider_profiles
--   ADD COLUMN address_text VARCHAR(255) NULL AFTER contact_phone;

DROP PROCEDURE IF EXISTS sp_search_provider_services;

DELIMITER $$

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
    pp.address_text,
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
      CASE
        WHEN p_user_lat IS NULL OR p_user_lng IS NULL OR p_radius_km IS NULL THEN TRUE
        ELSE fn_distance_km(p_user_lat, p_user_lng, d.latitude, d.longitude) <= p_radius_km
      END
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

DELIMITER ;
