<?php
namespace Models;

class User
{
    public static function getAll(int $page = 1, int $perPage = 10, string $search = '', string $sortField = 'created_at', string $sortDir = 'desc', int $locationId = 0, int $wardId = 0): array
    {
        $allowedSort = ['name', 'phone', 'monthly_amount', 'location_id', 'ward_id', 'is_active', 'created_at'];
        $sortField = in_array($sortField, $allowedSort, true) ? $sortField : 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like]);
        }
        if ($locationId > 0) {
            $conditions[] = 'u.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'u.ward_id = ?';
            $params[] = $wardId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return Database::connect()->fetchAll(
            "SELECT u.id, u.name, u.phone, u.monthly_amount, u.is_active, u.created_at,
                    l.name AS location_name, w.ward_number
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             LEFT JOIN wards w ON w.id = u.ward_id
             {$where}
             ORDER BY {$sortField} {$sortDir}
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    }

    public static function count(string $search = '', int $locationId = 0, int $wardId = 0): int
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like]);
        }
        if ($locationId > 0) {
            $conditions[] = 'u.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'u.ward_id = ?';
            $params[] = $wardId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $row = Database::connect()->fetch("SELECT COUNT(*) AS cnt FROM users u {$where}", $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO users (name, phone, location_id, ward_id, monthly_amount, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)',
            [
                $data['name'],
                $data['phone'],
                $data['location_id'] ?? null,
                $data['ward_id'] ?? null,
                $data['monthly_amount'] ?? 0,
                'collector',
            ]
        );
    }

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

    public static function findById(int $id): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }
}
