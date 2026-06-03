-- ============================================================
-- sp_search_provider_services — versión mejorada
-- Busca en: título, descripción, categoría, nombre del negocio,
--           dirección del anuncio, dirección del perfil y nombre del distrito.
-- ============================================================

USE jaapsyst_chamba;

DROP PROCEDURE IF EXISTS sp_search_provider_services;

DELIMITER $$

CREATE PROCEDURE sp_search_provider_services(
  IN p_category_id BIGINT UNSIGNED,
  IN p_district_id BIGINT UNSIGNED,
  IN p_keyword     VARCHAR(120),
  IN p_user_lat    DECIMAL(10,7),
  IN p_user_lng    DECIMAL(10,7),
  IN p_radius_km   DECIMAL(10,2)
)
BEGIN
  SELECT
    ps.id                                       AS service_id,
    ps.title,
    ps.description,
    ps.base_price,
    ps.price_type,
    ps.address_text,
    c.id                                        AS category_id,
    c.name                                      AS category_name,
    pp.id                                       AS provider_profile_id,
    COALESCE(pp.business_name, u.full_name)     AS provider_name,
    pp.whatsapp,
    pp.contact_phone,
    pp.address_text                             AS provider_address_text,
    pp.avg_rating,
    pp.total_reviews,
    d.id                                        AS district_id,
    d.name                                      AS district_name,
    pr.id                                       AS province_id,
    pr.name                                     AS province_name,
    dep.id                                      AS department_id,
    dep.name                                    AS department_name,
    COALESCE(ps.latitude,  d.latitude)          AS provider_latitude,
    COALESCE(ps.longitude, d.longitude)         AS provider_longitude,
    fn_distance_km(
      p_user_lat, p_user_lng,
      COALESCE(ps.latitude,  d.latitude),
      COALESCE(ps.longitude, d.longitude)
    )                                           AS distance_km
  FROM provider_services ps
  JOIN categories      c   ON c.id   = ps.category_id
  JOIN provider_profiles pp ON pp.id  = ps.provider_profile_id
  JOIN users           u   ON u.id   = pp.user_id
  JOIN districts       d   ON d.id   = COALESCE(ps.district_id, pp.district_id)
  JOIN provinces       pr  ON pr.id  = d.province_id
  JOIN departments     dep ON dep.id = pr.department_id
  WHERE ps.is_active  = 1
    AND ps.admin_hidden = 0
    AND c.is_active   = 1
    AND u.status      = 'activo'
    AND (p_category_id IS NULL OR ps.category_id = p_category_id)
    AND (p_district_id IS NULL OR d.id            = p_district_id)
    AND (
      p_keyword IS NULL
      OR ps.title                                   LIKE CONCAT('%', p_keyword, '%')
      OR ps.description                             LIKE CONCAT('%', p_keyword, '%')
      OR c.name                                     LIKE CONCAT('%', p_keyword, '%')
      OR COALESCE(pp.business_name, u.full_name)    LIKE CONCAT('%', p_keyword, '%')
      OR ps.address_text                            LIKE CONCAT('%', p_keyword, '%')
      OR pp.address_text                            LIKE CONCAT('%', p_keyword, '%')
      OR d.name                                     LIKE CONCAT('%', p_keyword, '%')
    )
    AND (
      CASE
        WHEN p_user_lat IS NULL OR p_user_lng IS NULL OR p_radius_km IS NULL THEN TRUE
        ELSE fn_distance_km(
               p_user_lat, p_user_lng,
               COALESCE(ps.latitude,  d.latitude),
               COALESCE(ps.longitude, d.longitude)
             ) <= p_radius_km
      END
    )
  ORDER BY
    CASE
      WHEN p_user_lat IS NULL OR p_user_lng IS NULL THEN 0
      ELSE fn_distance_km(
             p_user_lat, p_user_lng,
             COALESCE(ps.latitude,  d.latitude),
             COALESCE(ps.longitude, d.longitude)
           )
    END ASC,
    pp.avg_rating    DESC,
    pp.total_reviews DESC,
    ps.published_at  DESC,
    ps.created_at    DESC;
END$$

DELIMITER ;
