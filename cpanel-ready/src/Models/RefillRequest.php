<?php
namespace Models;

class RefillRequest
{
    private static bool $tableReady = false;

    public static function ensureTable(): void
    {
        if (self::$tableReady) {
            return;
        }

        Database::connect()->execute(
            "CREATE TABLE IF NOT EXISTS refill_requests (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NOT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                message TEXT DEFAULT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                approved_by INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_refill_admin (admin_id),
                INDEX idx_refill_status (status),
                INDEX idx_refill_approved_by (approved_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        self::$tableReady = true;
    }

    public static function create(int $adminId, float $amount, string $message = ''): int
    {
        self::ensureTable();
        return (int) Database::connect()->insert(
            'INSERT INTO refill_requests (admin_id, amount, message, status) VALUES (?, ?, ?, ?)',
            [$adminId, $amount, $message, 'pending']
        );
    }

    public static function forAdmin(int $adminId, int $limit = 20): array
    {
        self::ensureTable();
        return Database::connect()->fetchAll(
            'SELECT * FROM refill_requests WHERE admin_id = ? ORDER BY created_at DESC LIMIT ?',
            [$adminId, $limit]
        );
    }

    public static function all(int $limit = 50): array
    {
        self::ensureTable();
        return Database::connect()->fetchAll(
            "SELECT r.*, u.name AS admin_name, u.phone AS admin_phone
             FROM refill_requests r
             LEFT JOIN users u ON u.id = r.admin_id
             ORDER BY r.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public static function findPending(int $id): ?array
    {
        self::ensureTable();
        return Database::connect()->fetch(
            'SELECT * FROM refill_requests WHERE id = ? AND status = ?',
            [$id, 'pending']
        );
    }

    public static function setStatus(int $id, string $status, int $approvedBy): int
    {
        self::ensureTable();
        return Database::connect()->execute(
            'UPDATE refill_requests SET status = ?, approved_by = ? WHERE id = ? AND status = ?',
            [$status, $approvedBy, $id, 'pending']
        );
    }
}
