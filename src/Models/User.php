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

    public static function findById(int $id): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }
}
