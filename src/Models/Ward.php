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
        } elseif ($createdBy !== null && $db->columnExists('wards', 'created_by')) {
            $where = 'WHERE (w.created_by = ? OR w.created_by IS NULL)';
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
        } elseif ($createdBy !== null && Database::connect()->columnExists('wards', 'created_by')) {
            $where = 'WHERE (w.created_by = ? OR w.created_by IS NULL)';
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
        $db = Database::connect();
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

        if ($createdBy !== null && $db->columnExists('wards', 'created_by')) {
            $sql .= ' AND (w.created_by = ? OR w.created_by IS NULL)';
            $params[] = $createdBy;
        }

        $sql .= ' GROUP BY w.id ORDER BY w.ward_number ASC';

        return $db->fetchAll($sql, $params);
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

    public static function findByNumber(
        string|int $wardNumber,
        ?int $createdBy = null,
        ?int $locationId = null
    ): ?array {
        $db = Database::connect();
        $wardNumber = self::normalizeNumber($wardNumber);

        if ($createdBy !== null && $db->columnExists('wards', 'created_by')) {
            $wards = $db->fetchAll(
                'SELECT * FROM wards
                 WHERE created_by = ? OR created_by IS NULL
                 ORDER BY created_by IS NULL ASC, id ASC',
                [$createdBy]
            );
            foreach ($wards as $ward) {
                if (self::comparisonKey($ward['ward_number'] ?? '') === self::comparisonKey($wardNumber)) {
                    return $ward;
                }
            }
            return null;
        }

        if ($locationId !== null && $locationId > 0) {
            $wards = $db->fetchAll(
                'SELECT w.* FROM wards w
                 LEFT JOIN ward_locations wl ON wl.ward_id = w.id
                 WHERE wl.location_id = ? OR wl.location_id IS NULL
                 ORDER BY wl.location_id IS NULL ASC, w.id ASC',
                [$locationId]
            );
            foreach ($wards as $ward) {
                if (self::comparisonKey($ward['ward_number'] ?? '') === self::comparisonKey($wardNumber)) {
                    return $ward;
                }
            }
            return null;
        }

        foreach ($db->fetchAll('SELECT * FROM wards ORDER BY id ASC') as $ward) {
            if (self::comparisonKey($ward['ward_number'] ?? '') === self::comparisonKey($wardNumber)) {
                return $ward;
            }
        }
        return null;
    }

    public static function findAndMergeForImport(
        string|int $wardNumber,
        ?int $createdBy = null
    ): ?array {
        $db = Database::connect();
        $params = [];
        $hasCreatedBy = $db->columnExists('wards', 'created_by');
        $ownerWhere = $hasCreatedBy ? 'created_by IS NULL' : '1 = 1';
        if ($hasCreatedBy && $createdBy !== null) {
            $ownerWhere = 'created_by = ?';
            $params[] = $createdBy;
        }

        $matches = [];
        foreach ($db->fetchAll(
            "SELECT * FROM wards WHERE {$ownerWhere} ORDER BY id ASC",
            $params
        ) as $ward) {
            if (self::comparisonKey($ward['ward_number'] ?? '')
                === self::comparisonKey($wardNumber)) {
                $matches[] = $ward;
            }
        }

        if ($matches === []) {
            // Legacy global wards may be reused, but must not be merged into an
            // individual admin because other admins can still reference them.
            return self::findByNumber($wardNumber, $createdBy);
        }

        $keeper = array_shift($matches);
        $keeperId = (int) $keeper['id'];
        $normalized = self::normalizeNumber($wardNumber);
        $db->execute(
            'UPDATE wards SET ward_number = ? WHERE id = ?',
            [$normalized, $keeperId]
        );

        foreach ($matches as $duplicate) {
            $duplicateId = (int) $duplicate['id'];
            $db->execute(
                'INSERT IGNORE INTO ward_locations (ward_id, location_id)
                 SELECT ?, location_id FROM ward_locations WHERE ward_id = ?',
                [$keeperId, $duplicateId]
            );
            $db->execute(
                'UPDATE members SET ward_id = ? WHERE ward_id = ?',
                [$keeperId, $duplicateId]
            );
            $db->execute('DELETE FROM wards WHERE id = ?', [$duplicateId]);
        }

        return self::getById($keeperId);
    }

    public static function numberExists(string|int $wardNumber, ?int $excludeId = null, ?int $createdBy = null): bool
    {
        $db = Database::connect();
        $wardNumber = self::normalizeNumber($wardNumber);
        $sql = 'SELECT w.id, w.ward_number FROM wards w';
        $params = [];
        if ($createdBy !== null && $db->columnExists('wards', 'created_by')) {
            $sql .= ' WHERE (w.created_by = ? OR w.created_by IS NULL)';
            $params[] = $createdBy;
        }
        foreach ($db->fetchAll($sql, $params) as $ward) {
            if (($excludeId === null || (int) $ward['id'] !== $excludeId)
                && self::comparisonKey($ward['ward_number'] ?? '') === self::comparisonKey($wardNumber)) {
                return true;
            }
        }
        return false;
    }

    public static function create(array $data): string
    {
        $db = Database::connect();
        $params = [
            self::normalizeNumber($data['ward_number'] ?? ''),
            !empty($data['is_active']) ? 1 : 0,
        ];

        if ($db->columnExists('wards', 'created_by')) {
            $params[] = $data['created_by'] ?? null;
            return $db->insert(
                'INSERT INTO wards (ward_number, is_active, created_by) VALUES (?, ?, ?)',
                $params
            );
        }

        return $db->insert(
            'INSERT INTO wards (ward_number, is_active) VALUES (?, ?)',
            $params
        );
    }

    public static function update(int $id, array $data): int
    {
        $sql = 'UPDATE wards SET ward_number = ?, is_active = ? WHERE id = ?';
        $params = [
            self::normalizeNumber($data['ward_number'] ?? ''),
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ];

        // Scope by created_by if provided (via locations JOIN)
        $db = Database::connect();
        if (!empty($data['created_by']) && $db->columnExists('wards', 'created_by')) {
            $sql .= ' AND created_by = ?';
            $params[] = (int) $data['created_by'];
        }

        return $db->execute($sql, $params);
    }

    public static function delete(int $id, ?int $createdBy = null): int
    {
        $db = Database::connect();
        $sql = 'DELETE FROM wards WHERE id = ?';
        $params = [$id];

        if ($createdBy !== null && $db->columnExists('wards', 'created_by')) {
            $sql .= ' AND created_by = ?';
            $params[] = $createdBy;
        }

        return $db->execute($sql, $params);
    }

    public static function syncLocations(int $wardId, array $locationIds, ?int $createdBy = null): void
    {
        $db = Database::connect();

        // Verify each location belongs to the admin
        if ($createdBy !== null && $db->columnExists('locations', 'created_by')) {
            $validIds = [];
            foreach (array_unique(array_map('intval', $locationIds)) as $locId) {
                $loc = $db->fetch(
                    'SELECT id FROM locations
                     WHERE id = ? AND (created_by = ? OR created_by IS NULL)',
                    [$locId, $createdBy]
                );
                if ($loc) {
                    $validIds[] = (int) $loc['id'];
                }
            }
            $locationIds = $validIds;
        }

        $locationIds = array_values(array_unique(array_filter(
            array_map('intval', $locationIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($locationIds === []) {
            throw new \RuntimeException('No valid locations were selected.');
        }

        $db->execute('DELETE FROM ward_locations WHERE ward_id = ?', [$wardId]);

        foreach ($locationIds as $locationId) {
            $db->insert(
                'INSERT INTO ward_locations (ward_id, location_id) VALUES (?, ?)',
                [$wardId, $locationId]
            );
        }
    }

    public static function addLocations(int $wardId, array $locationIds, ?int $createdBy = null): void
    {
        $db = Database::connect();
        foreach (array_unique(array_map('intval', $locationIds)) as $locationId) {
            if ($locationId <= 0) {
                continue;
            }
            if ($createdBy !== null && $db->columnExists('locations', 'created_by')) {
                $location = $db->fetch(
                    'SELECT id FROM locations
                     WHERE id = ? AND (created_by = ? OR created_by IS NULL)',
                    [$locationId, $createdBy]
                );
                if (!$location) {
                    continue;
                }
            }
            $db->execute(
                'INSERT IGNORE INTO ward_locations (ward_id, location_id) VALUES (?, ?)',
                [$wardId, $locationId]
            );
        }
    }

    public static function normalizeNumber(string|int $wardNumber): string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $wardNumber)) ?? '';
        $withoutPrefix = preg_replace('/^ward\s*[#:_-]?\s*/iu', '', $value) ?? $value;
        $value = trim($withoutPrefix) !== '' ? trim($withoutPrefix) : $value;

        // Excel and manually typed variants such as 1, 01, 1.0 and Ward1
        // must resolve to the same ward instead of creating separate rows.
        if (preg_match('/^\d+(?:[.,]0+)?$/', $value) === 1) {
            $integerPart = preg_split('/[.,]/', $value, 2)[0] ?? $value;
            $integerPart = ltrim($integerPart, '0');
            return $integerPart === '' ? '0' : $integerPart;
        }

        return $value;
    }

    private static function comparisonKey(string|int $wardNumber): string
    {
        return mb_strtolower(self::normalizeNumber($wardNumber), 'UTF-8');
    }
}
