-- Tabla para enlaces de recuperación de contraseña (Laravel).
-- Si usas `php artisan migrate`, no hace falta ejecutar esto.
-- ============================================================

SET NAMES utf8mb4;
USE jaapsyst_chamba;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
