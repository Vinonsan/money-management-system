<?php
namespace Models;

class User
{
    public static function findByPhone(string $phone): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM users WHERE phone = ? AND is_active = 1 LIMIT 1',
            [$phone]
        );
    }

    public static function phoneExists(string $phone): bool
    {
        return Database::connect()->fetch('SELECT id FROM users WHERE phone = ? LIMIT 1', [$phone]) !== null;
    }

    public static function phoneExistsExclude(string $phone, int $excludeId): bool
    {
        return Database::connect()->fetch(
            'SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1',
            [$phone, $excludeId]
        ) !== null;
    }

    public static function updateAvatar(int $id, string $avatarPath): int
    {
        return Database::connect()->execute(
            'UPDATE users SET avatar = ? WHERE id = ?',
            [$avatarPath, $id]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }

    public static function getAdminsByLocation(int $locationId): array
    {
        return Database::connect()->fetchAll(
            "SELECT id, name, email, phone FROM users WHERE location_id = ? AND role = 'admin' AND is_active = 1 ORDER BY name",
            [$locationId]
        );
    }
}
