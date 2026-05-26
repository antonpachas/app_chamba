-- ============================================================
-- Proyecto: Chamba — Pagos / Escrow / Comisiones (MVP)
-- Motor: MySQL 8.0+
-- Archivo: 07-payments-schema.sql
-- Idempotente. Seguro de re-ejecutar.
-- ============================================================

USE jaapsyst_chamba;

SET @stmt := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = 'service_requests'
              AND index_name = 'idx_service_requests_status'
        ),
        'DROP INDEX idx_service_requests_status ON service_requests',
        'SELECT 1'
    )
);
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

ALTER TABLE service_requests
    MODIFY status ENUM(
        'nuevo','contactado','cotizado','aceptado',
        'pagado_pendiente','en_custodia','en_progreso',
        'terminado','confirmado','cancelado','disputado','cerrado'
    ) NOT NULL DEFAULT 'nuevo';

ALTER TABLE service_requests ADD INDEX idx_service_requests_status (status);

-- ============================================================
-- Cotizaciones del proveedor
-- ============================================================
CREATE TABLE IF NOT EXISTS service_quotes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_request_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'PEN',
    estimated_days SMALLINT UNSIGNED NULL,
    notes TEXT NULL,
    status ENUM('pendiente','aceptada','rechazada','expirada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quotes_request
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_quotes_amount CHECK (amount > 0),
    INDEX idx_quotes_request (service_request_id),
    INDEX idx_quotes_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- Configuración de comisiones (global o por categoría)
-- ============================================================
CREATE TABLE IF NOT EXISTS commission_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    rate DECIMAL(5,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_commission_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uk_commission_category UNIQUE (category_id),
    CONSTRAINT chk_commission_rate CHECK (rate >= 0 AND rate <= 100),
    INDEX idx_commission_active (is_active)
) ENGINE=InnoDB;

INSERT IGNORE INTO commission_settings (category_id, rate, is_active)
VALUES (NULL, 15.00, 1);

-- ============================================================
-- Pagos (escrow): cliente paga; admin confirma; sistema libera
-- ============================================================
CREATE TABLE IF NOT EXISTS service_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_request_id BIGINT UNSIGNED NOT NULL,
    service_quote_id BIGINT UNSIGNED NOT NULL,
    client_user_id BIGINT UNSIGNED NOT NULL,
    provider_profile_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'PEN',
    commission_rate DECIMAL(5,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    net_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('yape','plin','transferencia','culqi','mercadopago','otro')
        NOT NULL DEFAULT 'yape',
    payment_reference VARCHAR(100) NULL,
    status ENUM('pendiente_revision','en_custodia','liberado','reembolsado','rechazado')
        NOT NULL DEFAULT 'pendiente_revision',
    paid_at TIMESTAMP NULL,
    confirmed_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_request
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
    CONSTRAINT fk_payments_quote
        FOREIGN KEY (service_quote_id) REFERENCES service_quotes(id),
    CONSTRAINT fk_payments_client
        FOREIGN KEY (client_user_id) REFERENCES users(id),
    CONSTRAINT fk_payments_provider
        FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id),
    CONSTRAINT chk_payments_amount CHECK (amount > 0),
    INDEX idx_payments_status (status),
    INDEX idx_payments_provider_status (provider_profile_id, status),
    INDEX idx_payments_client (client_user_id),
    INDEX idx_payments_quote (service_quote_id),
    INDEX idx_payments_request (service_request_id)
) ENGINE=InnoDB;

-- ============================================================
-- Wallet del proveedor (saldo + datos para retiro)
-- ============================================================
CREATE TABLE IF NOT EXISTS provider_wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_profile_id BIGINT UNSIGNED NOT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_earned DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_withdrawn DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    bank_account_holder VARCHAR(150) NULL,
    yape_phone VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_provider
        FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uk_wallet_provider UNIQUE (provider_profile_id),
    CONSTRAINT chk_wallet_balance CHECK (balance >= 0)
) ENGINE=InnoDB;

-- ============================================================
-- Solicitudes de retiro (proveedor pide, admin paga)
-- ============================================================
CREATE TABLE IF NOT EXISTS wallet_withdrawals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_profile_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payout_method ENUM('yape','plin','transferencia') NOT NULL,
    payout_reference VARCHAR(100) NULL,
    status ENUM('solicitado','pagado','rechazado') NOT NULL DEFAULT 'solicitado',
    notes TEXT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_withdrawals_provider
        FOREIGN KEY (provider_profile_id) REFERENCES provider_profiles(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_withdrawals_amount CHECK (amount > 0),
    INDEX idx_withdrawals_status (status),
    INDEX idx_withdrawals_provider (provider_profile_id, created_at)
) ENGINE=InnoDB;
