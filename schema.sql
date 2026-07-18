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
    business_name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    location_id INT UNSIGNED DEFAULT NULL COMMENT 'Admin\'s assigned location for isolation',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Members (collectors / collection targets) ─────────────────────────
CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    card_number INT UNSIGNED DEFAULT NULL,
    road_number VARCHAR(100) DEFAULT NULL,
    street VARCHAR(255) DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    ward_id INT UNSIGNED DEFAULT NULL,
    monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_location (location_id),
    INDEX idx_member_ward (ward_id)
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
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location_created_by (created_by)
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
    member_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    months_covered INT UNSIGNED NOT NULL DEFAULT 0,
    extra_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    from_month DATE DEFAULT NULL,
    to_month DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_member (member_id),
    INDEX idx_payment_date (created_at),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── System Settings (key-value store) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    key_name VARCHAR(100) NOT NULL PRIMARY KEY,
    value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (key_name, value) VALUES ('collection_start_date', '2026-01-01')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ─── SMS Refill Requests (admin → super admin) ────────────────────────
CREATE TABLE IF NOT EXISTS refill_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    message TEXT DEFAULT NULL COMMENT 'Admin\'s note for refill',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Scheduled Messages ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scheduled_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME DEFAULT NULL COMMENT 'Optional time of day to send',
    message TEXT NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'due',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Seed data ──────────────────────────────────────────────────────────
-- Only super admin is seeded. Admins & members must be added via UI.
INSERT INTO users (name, business_name, phone, email, role, is_active)
VALUES ('vinonsan', 'MasjidPay Owner', '0758311995', 'vinonsan.99@gmail.com', 'super_admin', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    business_name = VALUES(business_name),
    email = VALUES(email),
    role = VALUES(role),
    is_active = 1;

-- ─── Default SMS settings ────────────────────────────────────────────
INSERT INTO settings (key_name, value) VALUES ('smslenz_user_id', '2127')
ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (key_name, value) VALUES ('smslenz_api_key', 'bf7a3a89-0a35-4054-b69f-1c5d6faf94bd')
ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (key_name, value) VALUES ('smslenz_sender_id', 'ExGenX9920')
ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (key_name, value) VALUES ('sms_balance', '5.00')
ON DUPLICATE KEY UPDATE value = VALUES(value);
INSERT INTO settings (key_name, value) VALUES ('sms_cost_per_message', '0.62')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- ─── Safe migrations for existing tables (ignore if columns already exist) ─
-- These only run when deploying on a server that already has tables.

-- Locations: add created_by column
SET @db = (SELECT DATABASE());
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'locations' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@col = 0, 'ALTER TABLE locations ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER is_active, ADD INDEX idx_location_created_by (created_by)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Members: add missing columns
SET @db = (SELECT DATABASE());
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND COLUMN_NAME = 'location_id');
SET @sql = IF(@col = 0, 'ALTER TABLE members ADD COLUMN location_id INT UNSIGNED DEFAULT NULL AFTER phone, ADD INDEX idx_member_location (location_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND COLUMN_NAME = 'ward_id');
SET @sql = IF(@col = 0, 'ALTER TABLE members ADD COLUMN ward_id INT UNSIGNED DEFAULT NULL AFTER location_id, ADD INDEX idx_member_ward (ward_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND COLUMN_NAME = 'card_number');
SET @sql = IF(@col = 0, 'ALTER TABLE members ADD COLUMN card_number INT UNSIGNED DEFAULT NULL AFTER phone', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND COLUMN_NAME = 'road_number');
SET @sql = IF(@col = 0, 'ALTER TABLE members ADD COLUMN road_number VARCHAR(100) DEFAULT NULL AFTER card_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'members' AND COLUMN_NAME = 'street');
SET @sql = IF(@col = 0, 'ALTER TABLE members ADD COLUMN street VARCHAR(255) DEFAULT NULL AFTER road_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Users: add missing columns
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'business_name');
SET @sql = IF(@col = 0, 'ALTER TABLE users ADD COLUMN business_name VARCHAR(255) DEFAULT NULL AFTER name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'location_id');
SET @sql = IF(@col = 0, 'ALTER TABLE users ADD COLUMN location_id INT UNSIGNED DEFAULT NULL AFTER role, ADD INDEX idx_user_location (location_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Users: fix role ENUM (remove old 'collector' if present)
SET @enum = (SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @sql = IF(@enum IS NOT NULL AND @enum NOT LIKE '%admin%', 'ALTER TABLE users MODIFY COLUMN role ENUM(\'super_admin\', \'admin\') NOT NULL DEFAULT \'admin\'', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Users: drop UNIQUE on email if exists
SET @idx = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'email' AND NON_UNIQUE = 0);
SET @sql = IF(@idx > 0, 'ALTER TABLE users DROP INDEX email', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Payments: add member_id if missing, drop user_id
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'member_id');
SET @sql = IF(@col = 0, 'ALTER TABLE payments ADD COLUMN member_id INT UNSIGNED DEFAULT NULL AFTER id, ADD INDEX idx_payment_member (member_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@col > 0, 'ALTER TABLE payments DROP COLUMN user_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Payments: make member_id NOT NULL after migration
SET @col = (SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'member_id');
SET @sql = IF(@col = 'YES', 'ALTER TABLE payments MODIFY COLUMN member_id INT UNSIGNED NOT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Scheduled messages: add member_id if missing
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'scheduled_messages' AND COLUMN_NAME = 'member_id');
SET @sql = IF(@col = 0, 'ALTER TABLE scheduled_messages ADD COLUMN member_id INT UNSIGNED DEFAULT NULL AFTER id, ADD INDEX idx_sched_member (member_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Re-create foreign keys if missing
SET @fk = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND CONSTRAINT_TYPE = 'FOREIGN KEY');
SET @sql = IF(@fk = 0, 'ALTER TABLE payments ADD FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
