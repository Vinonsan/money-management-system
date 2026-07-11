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
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) DEFAULT NULL,
    role ENUM('super_admin', 'admin', 'collector') NOT NULL DEFAULT 'collector',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- ─── Seed admin user ────────────────────────────────────────────────────
INSERT INTO users (name, email, phone, role, is_active)
VALUES ('vinonsan', 'vinonsan.99@gmail.com', '0754476969', 'admin', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    email = VALUES(email),
    role = VALUES(role),
    is_active = 1;
