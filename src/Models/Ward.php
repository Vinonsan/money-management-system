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
            $where = 'WHERE (w.id IN (SELECT wl.ward_id FROM ward_locations wl JOIN locations l ON l.id = wl.location_id WHERE l.created_by = ?) OR w.id NOT IN (SELECT ward_id FROM ward_locations))';
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
            $where = 'WHERE (w.id IN (SELECT wl.ward_id FROM ward_locations wl JOIN locations l ON l.id = wl.location_id WHERE l.created_by = ?) OR w.id NOT IN (SELECT ward_id FROM ward_locations))';
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

    public static function allActive(?int $locationFilter = null, ?int $createdBy = null): array
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

        if ($createdBy !== null) {
            $sql .= ' AND (w.id IN (SELECT wl.ward_id FROM ward_locations wl JOIN locations l ON l.id = wl.location_id WHERE l.created_by = ?) OR w.id NOT IN (SELECT ward_id FROM ward_locations))';
            $params[] = $createdBy;
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
        $sql = 'UPDATE wards SET ward_number = ?, is_active = ? WHERE id = ?';
        $params = [
            (int) $data['ward_number'],
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ];

        // Scope by created_by if provided (via locations JOIN)
        if (!empty($data['created_by'])) {
            $sql .= ' AND id IN (SELECT ward_id FROM ward_locations wl JOIN locations l ON l.id = wl.location_id WHERE l.created_by = ?)';
            $params[] = (int) $data['created_by'];
        }

        return Database::connect()->execute($sql, $params);
    }

    public static function delete(int $id, ?int $createdBy = null): int
    {
        $sql = 'DELETE FROM wards WHERE id = ?';
        $params = [$id];

        if ($createdBy !== null) {
            $sql .= ' AND id IN (SELECT ward_id FROM ward_locations wl JOIN locations l ON l.id = wl.location_id WHERE l.created_by = ?)';
            $params[] = $createdBy;
        }

        return Database::connect()->execute($sql, $params);
    }

    public static function syncLocations(int $wardId, array $locationIds, ?int $createdBy = null): void
    {
        $db = Database::connect();

        // Verify each location belongs to the admin
        if ($createdBy !== null) {
            $validIds = [];
            foreach (array_unique(array_map('intval', $locationIds)) as $locId) {
                $loc = $db->fetch('SELECT id FROM locations WHERE id = ? AND created_by = ?', [$locId, $createdBy]);
                if ($loc) {
                    $validIds[] = (int) $loc['id'];
                }
            }
            $locationIds = $validIds;
        }

        $db->execute('DELETE FROM ward_locations WHERE ward_id = ?', [$wardId]);

        foreach (array_unique(array_map('intval', $locationIds)) as $locationId) {
            $db->insert(
                'INSERT INTO ward_locations (ward_id, location_id) VALUES (?, ?)',
                [$wardId, $locationId]
            );
        }
    }
}
