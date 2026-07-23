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

    /**
     * Persist a group of settings atomically.
     *
     * @param array<string, string> $settings
     */
    public static function setMany(array $settings): void
    {
        $database = Database::connect();
        $connection = $database->getConn();

        $connection->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                self::set((string) $key, (string) $value);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
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
            try {
                $user = Database::connect()->fetch(
                    'SELECT collection_start_date FROM users WHERE id = ?',
                    [$userId]
                );
                if (!empty($user['collection_start_date'])) {
                    return $user['collection_start_date'];
                }
            } catch (\PDOException $e) {
                // Older imported databases may not have this optional column yet.
                // Continue with the global setting so payment screens still render.
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
