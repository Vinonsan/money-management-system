<?php
namespace Models;

class Ward
{
    public static function getAll(int $page = 1, int $perPage = 50, ?int $locationFilter = null, ?int $createdBy = null): array
    {
        $db = Database::connect();
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [];

        if ($locationFilter !== null) {
            $where = 'WHERE wl.location_id = ?';
            $params[] = $locationFilter;
        } elseif ($createdBy !== null) {
            $where = 'WHERE l.created_by = ?';
            $params[] = $createdBy;
        }

        return $db->fetchAll(
            "SELECT w.*, 
                    GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ', ') AS location_names,
                    GROUP_CONCAT(DISTINCT wl.location_id ORDER BY wl.location_id) AS location_ids
             FROM wards w
             LEFT JOIN ward_locations wl ON wl.ward_id = w.id
             LEFT JOIN locations l ON l.id = wl.location_id
             {$where}
             GROUP BY w.id
             ORDER BY w.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    }

    public static function count(?int $locationFilter = null, ?int $createdBy = null): int
    {
        $where = '';
        $params = [];
        if ($locationFilter !== null) {
            $where = 'INNER JOIN ward_locations wl ON wl.ward_id = w.id AND wl.location_id = ?';
            $params[] = $locationFilter;
        } elseif ($createdBy !== null) {
            $where = 'INNER JOIN ward_locations wl ON wl.ward_id = w.id INNER JOIN locations l ON l.id = wl.location_id AND l.created_by = ?';
            $params[] = $createdBy;
        }
        $result = Database::connect()->fetch("SELECT COUNT(*) AS cnt FROM wards w {$where}", $params);
        return (int) ($result['cnt'] ?? 0);
    }

    public static function getById(int $id): ?array
    {
        return Database::connect()->fetch(
            'SELECT w.*, 
                    GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ", ") AS location_names,
                    GROUP_CONCAT(DISTINCT wl.location_id ORDER BY wl.location_id) AS location_ids
             FROM wards w
             LEFT JOIN ward_locations wl ON wl.ward_id = w.id
             LEFT JOIN locations l ON l.id = wl.location_id
             WHERE w.id = ?
             GROUP BY w.id',
            [$id]
        );
    }

    public static function allActive(?int $locationFilter = null): array
    {
        $sql = 'SELECT w.*, 
                    GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ", ") AS location_names,
                    GROUP_CONCAT(DISTINCT wl.location_id ORDER BY wl.location_id) AS location_ids
             FROM wards w
             LEFT JOIN ward_locations wl ON wl.ward_id = w.id
             LEFT JOIN locations l ON l.id = wl.location_id
             WHERE w.is_active = 1';
        $params = [];

        if ($locationFilter !== null) {
            $sql .= ' AND wl.location_id = ?';
            $params[] = $locationFilter;
        }

        $sql .= ' GROUP BY w.id ORDER BY w.ward_number ASC';

        return Database::connect()->fetchAll($sql, $params);
    }

    public static function getByLocation(int $locationId): array
    {
        return Database::connect()->fetchAll(
            'SELECT w.* FROM wards w
             INNER JOIN ward_locations wl ON wl.ward_id = w.id
             WHERE wl.location_id = ? ORDER BY w.ward_number ASC',
            [$locationId]
        );
    }

    public static function numberExists(int $wardNumber, ?int $excludeId = null, ?int $createdBy = null): bool
    {
        $sql = 'SELECT w.id FROM wards w';
        $params = [];

        if ($createdBy !== null) {
            $sql .= ' INNER JOIN ward_locations wl ON wl.ward_id = w.id INNER JOIN locations l ON l.id = wl.location_id AND l.created_by = ?';
            $params[] = $createdBy;
        }

        $sql .= ' WHERE w.ward_number = ?';
        $params[] = $wardNumber;

        if ($excludeId !== null) {
            $sql .= ' AND w.id != ?';
            $params[] = $excludeId;
        }

        return Database::connect()->fetch($sql, $params) !== null;
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO wards (ward_number, is_active) VALUES (?, ?)',
            [
                (int) $data['ward_number'],
                !empty($data['is_active']) ? 1 : 0,
            ]
        );
    }

    public static function update(int $id, array $data): int
    {
        return Database::connect()->execute(
            'UPDATE wards SET ward_number = ?, is_active = ? WHERE id = ?',
            [
                (int) $data['ward_number'],
                !empty($data['is_active']) ? 1 : 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): int
    {
        return Database::connect()->execute('DELETE FROM wards WHERE id = ?', [$id]);
    }

    public static function syncLocations(int $wardId, array $locationIds): void
    {
        $db = Database::connect();
        $db->execute('DELETE FROM ward_locations WHERE ward_id = ?', [$wardId]);

        foreach (array_unique(array_map('intval', $locationIds)) as $locationId) {
            $db->insert(
                'INSERT INTO ward_locations (ward_id, location_id) VALUES (?, ?)',
                [$wardId, $locationId]
            );
        }
    }
}
