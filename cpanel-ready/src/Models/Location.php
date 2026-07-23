<?php
namespace Models;

class Location
{
    public static function getAll(int $page = 1, int $perPage = 10, string $search = '', string $sortField = 'id', string $sortDir = 'asc', string $filterBy = '', ?int $locationFilter = null, ?int $createdBy = null): array
    {
        $db = Database::connect();
        $allowedSort = ['id', 'name', 'city', 'is_active', 'created_at'];
        $sortField = in_array($sortField, $allowedSort) ? $sortField : 'id';
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($locationFilter !== null) {
            $conditions[] = 'id = ?';
            $params[] = $locationFilter;
        }

        if ($createdBy !== null) {
            $conditions[] = 'created_by = ?';
            $params[] = $createdBy;
        }

        if ($search !== '') {
            $allowedFilters = ['id', 'name', 'city', 'address'];
            if ($filterBy !== '' && in_array($filterBy, $allowedFilters)) {
                $conditions[] = $filterBy . ' LIKE ?';
                $params[] = '%' . $search . '%';
            } else {
                $conditions[] = '(name LIKE ? OR city LIKE ? OR address LIKE ?)';
                $like = '%' . $search . '%';
                $params = array_merge($params, [$like, $like, $like]);
            }
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $rows = $db->fetchAll(
            "SELECT * FROM locations {$where} ORDER BY {$sortField} {$sortDir} LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return $rows;
    }

    public static function count(string $search = '', string $filterBy = '', ?int $locationFilter = null, ?int $createdBy = null): int
    {
        $db = Database::connect();

        $conditions = [];
        $params = [];

        if ($locationFilter !== null) {
            $conditions[] = 'id = ?';
            $params[] = $locationFilter;
        }

        if ($createdBy !== null) {
            $conditions[] = 'created_by = ?';
            $params[] = $createdBy;
        }

        if ($search !== '') {
            $allowedFilters = ['id', 'name', 'city', 'address'];
            if ($filterBy !== '' && in_array($filterBy, $allowedFilters)) {
                $conditions[] = $filterBy . ' LIKE ?';
                $params[] = '%' . $search . '%';
            } else {
                $conditions[] = '(name LIKE ? OR city LIKE ? OR address LIKE ?)';
                $like = '%' . $search . '%';
                $params = array_merge($params, [$like, $like, $like]);
            }
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $result = $db->fetch("SELECT COUNT(*) AS cnt FROM locations {$where}", $params);
        return (int) ($result['cnt'] ?? 0);
    }

    public static function getById(int $id): ?array
    {
        return Database::connect()->fetch('SELECT * FROM locations WHERE id = ?', [$id]);
    }

    public static function nameExists(string $name, ?int $excludeId = null, ?int $createdBy = null): bool
    {
        $sql = 'SELECT id FROM locations WHERE name = ?';
        $params = [$name];

        if ($createdBy !== null) {
            $sql .= ' AND created_by = ?';
            $params[] = $createdBy;
        }

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return Database::connect()->fetch($sql, $params) !== null;
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO locations (name, address, city, is_active, created_by) VALUES (?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                !empty($data['is_active']) ? 1 : 0,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        $sql = 'UPDATE locations SET name = ?, address = ?, city = ?, is_active = ? WHERE id = ?';
        $params = [
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ];

        // Scope to created_by if provided
        if (!empty($data['created_by'])) {
            $sql .= ' AND created_by = ?';
            $params[] = (int) $data['created_by'];
        }

        return Database::connect()->execute($sql, $params);
    }

    public static function delete(int $id, ?int $createdBy = null): int
    {
        $sql = 'DELETE FROM locations WHERE id = ?';
        $params = [$id];

        if ($createdBy !== null) {
            $sql .= ' AND created_by = ?';
            $params[] = $createdBy;
        }

        return Database::connect()->execute($sql, $params);
    }

    public static function allActive(?int $locationFilter = null, ?int $createdBy = null): array
    {
        $db = Database::connect();
        $conditions = ['is_active = 1'];
        $params = [];

        if ($locationFilter !== null) {
            $conditions[] = 'id = ?';
            $params[] = $locationFilter;
        }

        if ($createdBy !== null && $db->columnExists('locations', 'created_by')) {
            $conditions[] = '(created_by = ? OR created_by IS NULL)';
            $params[] = $createdBy;
        }

        $where = implode(' AND ', $conditions);
        return $db->fetchAll(
            "SELECT id, name FROM locations WHERE {$where} ORDER BY name ASC",
            $params
        );
    }
}
