-- ======================================================================
-- MasjidPay — Reset Data (keeps table structure, clears all records)
-- Run this in phpMyAdmin after deployment to start fresh.
-- ======================================================================

USE riversid_masjidpay;

-- ─── Disable foreign key checks ─────────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;
SET SESSION UNIQUE_CHECKS = 0;

-- ─── Clear all data (DELETE instead of TRUNCATE to avoid FK issues) ────
DELETE FROM scheduled_messages;
DELETE FROM refill_requests;
DELETE FROM ward_locations;
DELETE FROM payments;
DELETE FROM members;
DELETE FROM otp_codes;
DELETE FROM login_rate_limits;
DELETE FROM wards;
DELETE FROM locations;
DELETE FROM users WHERE role = 'admin';
DELETE FROM users WHERE role = 'super_admin' AND phone != '0758311995';
DELETE FROM settings;
DELETE FROM _schema_migrations;

-- ─── Reset auto-increment counters ─────────────────────────────────────
ALTER TABLE scheduled_messages AUTO_INCREMENT = 1;
ALTER TABLE refill_requests AUTO_INCREMENT = 1;
ALTER TABLE ward_locations AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE members AUTO_INCREMENT = 1;
ALTER TABLE otp_codes AUTO_INCREMENT = 1;
ALTER TABLE login_rate_limits AUTO_INCREMENT = 1;
ALTER TABLE wards AUTO_INCREMENT = 1;
ALTER TABLE locations AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE settings AUTO_INCREMENT = 1;

-- ─── Re-enable foreign key checks ──────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 1;
SET SESSION UNIQUE_CHECKS = 1;

-- ─── Seed: Super Admin only ─────────────────────────────────────────────
INSERT INTO users (name, business_name, phone, email, role, is_active)
VALUES ('vinonsan', 'MasjidPay Owner', '0754476969', 'vinonsan.99@gmail.com', 'super_admin', 1);

-- ─── Default SMS settings ───────────────────────────────────────────────
INSERT INTO settings (key_name, value) VALUES ('collection_start_date', '2026-01-01');
INSERT INTO settings (key_name, value) VALUES ('smslenz_user_id', '2127');
INSERT INTO settings (key_name, value) VALUES ('smslenz_api_key', 'bf7a3a89-0a35-4054-b69f-1c5d6faf94bd');
INSERT INTO settings (key_name, value) VALUES ('smslenz_sender_id', 'ExGenX9920');
INSERT INTO settings (key_name, value) VALUES ('sms_balance', '5.00');
INSERT INTO settings (key_name, value) VALUES ('sms_cost_per_message', '0.60');
