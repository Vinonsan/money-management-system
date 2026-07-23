<?php
namespace Models;

class Setting
{
    public static function get(string $key, string $default = ''): string
    {
        $row = Database::connect()->fetch(
            'SELECT value FROM settings WHERE key_name = ?',
            [$key]
        );
        return $row['value'] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        Database::connect()->execute(
            'INSERT INTO settings (key_name, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, $value]
        );
    }

    public static function getAll(): array
    {
        return Database::connect()->fetchAll(
            'SELECT key_name, value, updated_at FROM settings ORDER BY key_name ASC'
        );
    }

    /**
     * Get the effective collection start date for a user.
     * Priority: user's own setting > global setting > today.
     */
    public static function getCollectionStartDate(?int $userId = null): string
    {
        // Check per-user setting first
        if ($userId !== null && $userId > 0) {
            $user = Database::connect()->fetch(
                'SELECT collection_start_date FROM users WHERE id = ?',
                [$userId]
            );
            if (!empty($user['collection_start_date'])) {
                return $user['collection_start_date'];
            }
        }
        // Fallback to global setting
        $global = self::get('collection_start_date', '');
        if ($global !== '') {
            return $global;
        }
        // Final fallback to today
        return date('Y-m-d');
    }
}
