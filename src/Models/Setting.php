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
}
