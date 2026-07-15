<?php
namespace Models;

class Location
{
    public static function getAll(int $page = 1, int $perPage = 10, string $search = '', string $sortField = 'id', string $sortDir = 'asc', string $filterBy = ''): array
    {
        $db = Database::connect();
        $allowedSort = ['id', 'name', 'city', 'is_active', 'created_at'];
        $sortField = in_array($sortField, $allowedSort) ? $sortField : 'id';
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search !== '') {
            $allowedFilters = ['id', 'name', 'city', 'address'];
            if ($filterBy !== '' && in_array($filterBy, $allowedFilters)) {
                $where = 'WHERE ' . $filterBy . ' LIKE ?';
                $params = ['%' . $search . '%'];
            } else {
                $where = 'WHERE (name LIKE ? OR city LIKE ? OR address LIKE ?)';
                $like = '%' . $search . '%';
                $params = [$like, $like, $like];
            }
        }

        $rows = $db->fetchAll(
            "SELECT * FROM locations {$where} ORDER BY {$sortField} {$sortDir} LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return $rows;
    }

    public static function count(string $search = '', string $filterBy = ''): int
    {
        $db = Database::connect();

        $where = '';
        $params = [];
        if ($search !== '') {
            $allowedFilters = ['id', 'name', 'city', 'address'];
            if ($filterBy !== '' && in_array($filterBy, $allowedFilters)) {
                $where = 'WHERE ' . $filterBy . ' LIKE ?';
                $params = ['%' . $search . '%'];
            } else {
                $where = 'WHERE (name LIKE ? OR city LIKE ? OR address LIKE ?)';
                $like = '%' . $search . '%';
                $params = [$like, $like, $like];
            }
        }

        $result = $db->fetch("SELECT COUNT(*) AS cnt FROM locations {$where}", $params);
        return (int) ($result['cnt'] ?? 0);
    }

    public static function getById(int $id): ?array
    {
        return Database::connect()->fetch('SELECT * FROM locations WHERE id = ?', [$id]);
    }

    public static function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM locations WHERE name = ?';
        $params = [$name];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return Database::connect()->fetch($sql, $params) !== null;
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO locations (name, address, city, is_active) VALUES (?, ?, ?, ?)',
            [
                $data['name'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                !empty($data['is_active']) ? 1 : 0,
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        return Database::connect()->execute(
            'UPDATE locations SET name = ?, address = ?, city = ?, is_active = ? WHERE id = ?',
            [
                $data['name'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                !empty($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): int
    {
        return Database::connect()->execute('DELETE FROM locations WHERE id = ?', [$id]);
    }

    public static function allActive(): array
    {
        return Database::connect()->fetchAll(
            'SELECT id, name FROM locations WHERE is_active = 1 ORDER BY name ASC'
        );
    }
}
