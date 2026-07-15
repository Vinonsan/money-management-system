<?php
namespace Models;

class Payment
{
    public static function getAll(int $page = 1, int $perPage = 50, string $status = '', int $userId = 0): array
    {
        $db = Database::connect();
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if ($userId > 0) {
            $conditions[] = 'p.user_id = ?';
            $params[] = $userId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return $db->fetchAll(
            "SELECT p.*, u.name AS user_name, u.card_number, u.monthly_amount
             FROM payments p
             LEFT JOIN users u ON u.id = p.user_id
             {$where}
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    }

    public static function getByUser(int $userId): array
    {
        return Database::connect()->fetchAll(
            'SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function getLastPayment(int $userId): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM payments WHERE user_id = ? ORDER BY to_month DESC LIMIT 1',
            [$userId]
        );
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO payments (user_id, amount, months_covered, extra_amount, from_month, to_month, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['amount'],
                $data['months_covered'],
                $data['extra_amount'] ?? 0,
                $data['from_month'],
                $data['to_month'],
                $data['notes'] ?? null,
            ]
        );
    }

    /**
     * Calculate payment breakdown for a user.
     * Returns: [from_month, to_month, months_covered, extra_amount]
     */
    public static function calculatePayment(int $userId, float $amount): array
    {
        $user = Database::connect()->fetch(
            'SELECT id, monthly_amount FROM users WHERE id = ?',
            [$userId]
        );
        if (!$user || (float) $user['monthly_amount'] <= 0) {
            return ['from' => null, 'to' => null, 'months' => 0, 'extra' => $amount];
        }

        $monthly = (float) $user['monthly_amount'];
        $lastPayment = self::getLastPayment($userId);

        // Determine start month
        if ($lastPayment && $lastPayment['to_month']) {
            $start = date('Y-m-d', strtotime($lastPayment['to_month'] . ' +1 month'));
        } else {
            $start = Setting::get('collection_start_date', date('Y-m-d'));
            // Always start from 1st of the month
            $start = date('Y-m-01', strtotime($start));
        }

        $monthsCovered = floor($amount / $monthly);
        $extraAmount = $amount - ($monthsCovered * $monthly);

        if ($monthsCovered <= 0) {
            // Amount is less than one month — treat as partial
            return ['from' => $start, 'to' => $start, 'months' => 0, 'extra' => $amount];
        }

        $to = date('Y-m-d', strtotime($start . ' +' . ($monthsCovered - 1) . ' months'));
        // Set to last day of that month
        $to = date('Y-m-t', strtotime($to));

        return [
            'from'   => $start,
            'to'     => $to,
            'months' => (int) $monthsCovered,
            'extra'  => round($extraAmount, 2),
        ];
    }

    public static function getUserPaymentSummary(int $userId): array
    {
        $user = Database::connect()->fetch(
            'SELECT id, name, card_number, phone, monthly_amount, location_id, ward_id FROM users WHERE id = ?',
            [$userId]
        );
        if (!$user) return [];

        $lastPay = self::getLastPayment($userId);
        $startDate = Setting::get('collection_start_date', date('Y-m-d'));

        return [
            'user'             => $user,
            'last_payment'     => $lastPay,
            'next_due_month'   => $lastPay && $lastPay['to_month']
                ? date('Y-m-01', strtotime($lastPay['to_month'] . ' +1 month'))
                : date('Y-m-01', strtotime($startDate)),
            'total_paid'       => Database::connect()->fetch(
                'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = ?', [$userId]
            )['total'] ?? 0,
        ];
    }

    /**
     * Get all members with payment status. Default shows unpaid/due members.
     * status: 'all' | 'paid' | 'unpaid'
     */
    public static function getMembers(string $search = '', int $locationId = 0, int $wardId = 0, string $status = 'unpaid', int $page = 1, int $perPage = 50): array
    {
        $today = date('Y-m-d');
        $conditions = ['(u.monthly_amount > 0)'];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.card_number LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($locationId > 0) {
            $conditions[] = 'u.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'u.ward_id = ?';
            $params[] = $wardId;
        }

        $where = implode(' AND ', $conditions);

        $baseSelect = "SELECT u.id, u.name, u.phone, u.card_number, u.monthly_amount,
                    l.name AS location_name, w.ward_number,
                    (SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.user_id = u.id) AS last_paid_to,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.user_id = u.id) AS total_paid,
                    (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id) AS payment_count
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             LEFT JOIN wards w ON w.id = u.ward_id
             WHERE {$where}";

        $orderBy = 'u.name ASC';
        if ($status === 'paid') {
            $having = "HAVING last_paid_to >= ? AND last_paid_to != '0000-00-00'";
            $params[] = $today;
            $orderBy = 'last_paid_to DESC, u.name ASC';
        } elseif ($status === 'unpaid') {
            $having = "HAVING (last_paid_to < ? OR last_paid_to = '0000-00-00')";
            $params[] = $today;
            $orderBy = 'last_paid_to ASC, u.name ASC';
        } else {
            $having = '';
        }

        $offset = ($page - 1) * $perPage;
        $countParams = $params;

        // Count total
        $countSql = "SELECT COUNT(*) AS cnt FROM ($baseSelect $having) AS sub";
        $countResult = Database::connect()->fetch($countSql, $countParams);
        $total = (int) ($countResult['cnt'] ?? 0);

        // Fetch page
        $params[] = $perPage;
        $params[] = $offset;
        $rows = Database::connect()->fetchAll(
            "$baseSelect $having ORDER BY $orderBy LIMIT ? OFFSET ?",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }
}
