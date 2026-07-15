-- ======================================================================
-- MasjidPay — Database Schema
-- Cash collection system for masjid donations / collections
-- ======================================================================

CREATE DATABASE IF NOT EXISTS masjidpay
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE masjidpay;

-- ─── Schema Migration Tracker ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS _schema_migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration_hash VARCHAR(64) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Users (super_admin later; admin / collector now) ───────────────────
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    card_number INT UNSIGNED DEFAULT NULL,
    road_number VARCHAR(100) DEFAULT NULL,
    street VARCHAR(255) DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    ward_id INT UNSIGNED DEFAULT NULL,
    monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    password VARCHAR(255) DEFAULT NULL,
    role ENUM('super_admin', 'admin', 'collector') NOT NULL DEFAULT 'collector',
    avatar VARCHAR(255) DEFAULT NULL COMMENT 'Profile image path',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_location (location_id),
    INDEX idx_user_ward (ward_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── OTP codes (login) ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_phone (phone),
    INDEX idx_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Login rate limits / lockouts (per phone) ───────────────────────────
CREATE TABLE IF NOT EXISTS login_rate_limits (
    phone VARCHAR(20) NOT NULL PRIMARY KEY,
    otp_request_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
    lock_tier TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Locations ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Wards ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ward_number INT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ward_number (ward_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A ward can be assigned to multiple locations, and a location can have multiple wards.
CREATE TABLE IF NOT EXISTS ward_locations (
    ward_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (ward_id, location_id),
    INDEX idx_ward_locations_location (location_id),
    CONSTRAINT fk_ward_locations_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
    CONSTRAINT fk_ward_locations_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Payments ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    months_covered INT UNSIGNED NOT NULL DEFAULT 0,
    extra_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    from_month DATE DEFAULT NULL,
    to_month DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_user (user_id),
    INDEX idx_payment_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── System Settings (key-value store) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    key_name VARCHAR(100) NOT NULL PRIMARY KEY,
    value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (key_name, value) VALUES ('collection_start_date', '2026-01-01')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ─── Scheduled Messages ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scheduled_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(10) UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME DEFAULT NULL COMMENT 'Optional time of day to send',
    message TEXT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'due',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seed data ──────────────────────────────────────────────────────────
INSERT INTO users (name, email, phone, role, is_active)
VALUES ('vinonsan', 'vinonsan.99@gmail.com', '0754476969', 'admin', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email),
    role = VALUES(role),
    is_active = 1;

INSERT INTO users (name, email, phone, role, is_active)
VALUES ('vinonsan', 'vinonsan.superadmin@gmail.com', '0758311995', 'super_admin', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email),
    role = VALUES(role),
    is_active = 1;
