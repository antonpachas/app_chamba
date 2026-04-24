-- ============================================================
-- Proyecto: Aplicacion movil de servicios locales (MVP)
-- Motor: MySQL 8.0+
-- Archivo: 01-schema-mvp.sql
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS jaapsyst_chamba
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE jaapsyst_chamba;

-- ============================================================
-- Limpieza (orden inverso por FK)
-- ============================================================
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS service_images;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS provider_services;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS provider_profiles;
DROP TABLE IF EXISTS districts;
DROP TABLE IF EXISTS provinces;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;

-- ============================================================
-- Usuarios
-- ============================================================
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('cliente', 'proveedor', 'admin') NOT NULL DEFAULT 'cliente',
  status ENUM('activo', 'suspendido', 'por_aprobar') NOT NULL DEFAULT 'por_aprobar',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uk_users_email UNIQUE (email),
  INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB;

-- ============================================================
-- Ubicacion (con coordenadas centroides)
-- ============================================================
CREATE TABLE departments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uk_departments_name UNIQUE (name),
  CONSTRAINT chk_departments_lat CHECK (latitude BETWEEN -90.0000000 AND 90.0000000),
  CONSTRAINT chk_departments_lng CHECK (longitude BETWEEN -180.0000000 AND 180.0000000),
  INDEX idx_departments_lat_lng (latitude, longitude)
) ENGINE=InnoDB;

CREATE TABLE provinces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_provinces_department
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT uk_provinces_department_name UNIQUE (department_id, name),
  CONSTRAINT chk_provinces_lat CHECK (latitude BETWEEN -90.0000000 AND 90.0000000),
  CONSTRAINT chk_provinces_lng CHECK (longitude BETWEEN -180.0000000 AND 180.0000000),
  INDEX idx_provinces_lat_lng (latitude, longitude),
  INDEX idx_provinces_department (department_id)
) ENGINE=InnoDB;

CREATE TABLE districts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  province_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_districts_province
    FOREIGN KEY (province_id) REFERENCES provinces(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT uk_districts_province_name UNIQUE (province_id, name),
  CONSTRAINT chk_districts_lat CHECK (latitude BETWEEN -90.0000000 AND 90.0000000),
  CONSTRAINT chk_districts_lng CHECK (longitude BETWEEN -180.0000000 AND 180.0000000),
  INDEX idx_districts_lat_lng (latitude, longitude),
  INDEX idx_districts_province (province_id)
) ENGINE=InnoDB;

-- ============================================================
-- Proveedores y servicios
-- ============================================================
CREATE TABLE provider_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  business_name VARCHAR(150) NULL,
  description TEXT NULL,
  whatsapp VARCHAR(20) NULL,
  contact_phone VARCHAR(20) NULL,
  address_text VARCHAR(255) NULL,
  district_id BIGINT UNSIGNED NOT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  total_reviews INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_provider_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_provider_profiles_district
    FOREIGN KEY (district_id) REFERENCES districts(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT uk_provider_profiles_user UNIQUE (user_id),
  INDEX idx_provider_profiles_district (district_id),
  INDEX idx_provider_profiles_rating (avg_rating, total_reviews)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(130) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uk_categories_name UNIQUE (name),
  CONSTRAINT uk_categories_slug UNIQUE (slug),
  INDEX idx_categories_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE provider_services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_profile_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  base_price DECIMAL(10,2) NULL,
  price_type ENUM('fijo', 'desde', 'cotizar') NOT NULL DEFAULT 'cotizar',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_provider_services_profile
    FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_provider_services_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_provider_services_category_active (category_id, is_active),
  INDEX idx_provider_services_profile_active (provider_profile_id, is_active),
  FULLTEXT INDEX ft_provider_services_text (title, description)
) ENGINE=InnoDB;

CREATE TABLE service_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id BIGINT UNSIGNED NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_service_images_service
    FOREIGN KEY (service_id) REFERENCES provider_services(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_service_images_service (service_id)
) ENGINE=InnoDB;

-- ============================================================
-- Interaccion cliente-proveedor
-- ============================================================
CREATE TABLE service_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_user_id BIGINT UNSIGNED NOT NULL,
  provider_service_id BIGINT UNSIGNED NOT NULL,
  message TEXT NULL,
  contact_channel ENUM('telefono', 'whatsapp', 'app') NOT NULL DEFAULT 'whatsapp',
  status ENUM('nuevo', 'contactado', 'cerrado') NOT NULL DEFAULT 'nuevo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_service_requests_client
    FOREIGN KEY (client_user_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_service_requests_service
    FOREIGN KEY (provider_service_id) REFERENCES provider_services(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  INDEX idx_service_requests_client_created (client_user_id, created_at),
  INDEX idx_service_requests_service_created (provider_service_id, created_at),
  INDEX idx_service_requests_status (status)
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_request_id BIGINT UNSIGNED NOT NULL,
  client_user_id BIGINT UNSIGNED NOT NULL,
  provider_profile_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_service_request
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_client
    FOREIGN KEY (client_user_id) REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_provider
    FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT uk_reviews_service_request UNIQUE (service_request_id),
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  INDEX idx_reviews_provider_rating (provider_profile_id, rating),
  INDEX idx_reviews_client_created (client_user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE favorites (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  provider_profile_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_favorites_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favorites_provider
    FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT uk_favorites_user_provider UNIQUE (user_id, provider_profile_id),
  INDEX idx_favorites_user (user_id),
  INDEX idx_favorites_provider (provider_profile_id)
) ENGINE=InnoDB;
