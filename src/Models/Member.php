<?php
namespace Models;

class Member
{
    public static function getAll(int $page = 1, int $perPage = 10, string $search = '', string $sortField = 'created_at', string $sortDir = 'desc', int $locationId = 0, int $wardId = 0, ?int $locationFilter = null): array
    {
        $allowedSort = ['name', 'phone', 'monthly_amount', 'location_id', 'ward_id', 'is_active', 'created_at'];
        $sortField = in_array($sortField, $allowedSort, true) ? $sortField : 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        // Enforce location isolation
        if ($locationFilter !== null) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationFilter;
        }

        if ($search !== '') {
            $conditions[] = '(m.name LIKE ? OR m.phone LIKE ? OR m.card_number LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($locationId > 0) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'm.ward_id = ?';
            $params[] = $wardId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return Database::connect()->fetchAll(
            "SELECT m.id, m.name, m.phone, m.card_number, m.monthly_amount, m.is_active, m.created_at,
                    l.name AS location_name, w.ward_number
             FROM members m
             LEFT JOIN locations l ON l.id = m.location_id
             LEFT JOIN wards w ON w.id = m.ward_id
             {$where}
             ORDER BY {$sortField} {$sortDir}
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    }

    public static function count(string $search = '', int $locationId = 0, int $wardId = 0, ?int $locationFilter = null): int
    {
        $conditions = [];
        $params = [];

        // Enforce location isolation
        if ($locationFilter !== null) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationFilter;
        }

        if ($search !== '') {
            $conditions[] = '(m.name LIKE ? OR m.phone LIKE ? OR m.card_number LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($locationId > 0) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'm.ward_id = ?';
            $params[] = $wardId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $row = Database::connect()->fetch("SELECT COUNT(*) AS cnt FROM members m {$where}", $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO members (name, email, phone, card_number, road_number, street, location_id, ward_id, monthly_amount, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $data['name'],
                $data['email'] ?? null,
                $data['phone'],
                !empty($data['card_number']) ? (int) $data['card_number'] : null,
                $data['road_number'] ?? null,
                $data['street'] ?? null,
                $data['location_id'] ?? null,
                $data['ward_id'] ?? null,
                $data['monthly_amount'] ?? 0,
            ]
        );
    }

    public static function findByPhone(string $phone): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM members WHERE phone = ? AND is_active = 1 LIMIT 1',
            [$phone]
        );
    }

    public static function phoneExists(string $phone): bool
    {
        return Database::connect()->fetch('SELECT id FROM members WHERE phone = ? LIMIT 1', [$phone]) !== null;
    }

    public static function phoneExistsExclude(string $phone, int $excludeId): bool
    {
        return Database::connect()->fetch(
            'SELECT id FROM members WHERE phone = ? AND id != ? LIMIT 1',
            [$phone, $excludeId]
        ) !== null;
    }

    public static function update(int $id, array $data): int
    {
        return Database::connect()->execute(
            'UPDATE members SET name = ?, email = ?, phone = ?, card_number = ?, road_number = ?, street = ?, location_id = ?, ward_id = ?, monthly_amount = ? WHERE id = ?',
            [
                $data['name'],
                $data['email'] ?? null,
                $data['phone'],
                !empty($data['card_number']) ? (int) $data['card_number'] : null,
                $data['road_number'] ?? null,
                $data['street'] ?? null,
                $data['location_id'] ?? null,
                $data['ward_id'] ?? null,
                $data['monthly_amount'] ?? 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): int
    {
        return Database::connect()->execute('DELETE FROM members WHERE id = ?', [$id]);
    }

    public static function findById(int $id): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM members WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }

    public static function getByLocation(int $locationId): array
    {
        return Database::connect()->fetchAll(
            'SELECT id, name, phone, card_number FROM members WHERE location_id = ? AND is_active = 1 ORDER BY name',
            [$locationId]
        );
    }
}
