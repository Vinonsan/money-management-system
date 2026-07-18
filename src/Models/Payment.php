<?php
namespace Models;

class Payment
{
    public static function getAll(int $page = 1, int $perPage = 50, string $status = '', int $memberId = 0): array
    {
        $db = Database::connect();
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $params = [];

        if ($memberId > 0) {
            $conditions[] = 'p.member_id = ?';
            $params[] = $memberId;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return $db->fetchAll(
            "SELECT p.*, m.name AS member_name, m.card_number, m.monthly_amount
             FROM payments p
             LEFT JOIN members m ON m.id = p.member_id
             {$where}
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
    }

    public static function getByMember(int $memberId): array
    {
        return Database::connect()->fetchAll(
            'SELECT * FROM payments WHERE member_id = ? ORDER BY created_at DESC',
            [$memberId]
        );
    }

    public static function getLastPayment(int $memberId): ?array
    {
        return Database::connect()->fetch(
            'SELECT * FROM payments WHERE member_id = ? ORDER BY to_month DESC LIMIT 1',
            [$memberId]
        );
    }

    public static function create(array $data): string
    {
        return Database::connect()->insert(
            'INSERT INTO payments (member_id, amount, months_covered, extra_amount, from_month, to_month, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['member_id'],
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
     * Calculate payment breakdown for a member.
     */
    public static function calculatePayment(int $memberId, float $amount): array
    {
        $member = Database::connect()->fetch(
            'SELECT id, monthly_amount FROM members WHERE id = ?',
            [$memberId]
        );
        if (!$member || (float) $member['monthly_amount'] <= 0) {
            return ['from' => null, 'to' => null, 'months' => 0, 'extra' => $amount];
        }

        $monthly = (float) $member['monthly_amount'];
        $lastPayment = self::getLastPayment($memberId);

        // Determine start month
        if ($lastPayment && $lastPayment['to_month']) {
            $start = date('Y-m-d', strtotime($lastPayment['to_month'] . ' +1 month'));
        } else {
            $start = Setting::getCollectionStartDate();
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

    public static function getMemberPaymentSummary(int $memberId): array
    {
        $member = Database::connect()->fetch(
            'SELECT id, name, card_number, phone, monthly_amount, location_id, ward_id FROM members WHERE id = ?',
            [$memberId]
        );
        if (!$member) return [];

        $lastPay = self::getLastPayment($memberId);
        $startDate = Setting::getCollectionStartDate();

        return [
            'member'          => $member,
            'last_payment'    => $lastPay,
            'next_due_month'  => $lastPay && $lastPay['to_month']
                ? date('Y-m-01', strtotime($lastPay['to_month'] . ' +1 month'))
                : date('Y-m-01', strtotime($startDate)),
            'total_paid'      => Database::connect()->fetch(
                'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE member_id = ?', [$memberId]
            )['total'] ?? 0,
        ];
    }

    /**
     * Get all members with payment status.
     */
    public static function getMembers(string $search = '', int $locationId = 0, int $wardId = 0, string $status = 'unpaid', int $page = 1, int $perPage = 50, ?int $locationFilter = null): array
    {
        $today = date('Y-m-d');
        $conditions = ['(m.monthly_amount > 0)'];
        $params = [];

        // Enforce location isolation
        if ($locationFilter !== null) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationFilter;
        }

        if ($search !== '') {
            $conditions[] = '(m.name LIKE ? OR m.card_number LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($locationId > 0) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locationId;
        }
        if ($wardId > 0) {
            $conditions[] = 'm.ward_id = ?';
            $params[] = $wardId;
        }

        $where = implode(' AND ', $conditions);

        $baseSelect = "SELECT m.id, m.name, m.phone, m.card_number, m.monthly_amount,
                    l.name AS location_name, w.ward_number,
                    (SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.member_id = m.id) AS last_paid_to,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.member_id = m.id) AS total_paid,
                    (SELECT COUNT(*) FROM payments p WHERE p.member_id = m.id) AS payment_count
             FROM members m
             LEFT JOIN locations l ON l.id = m.location_id
             LEFT JOIN wards w ON w.id = m.ward_id
             WHERE {$where}";

        $orderBy = 'm.name ASC';
        if ($status === 'paid') {
            $having = "HAVING last_paid_to >= ? AND last_paid_to != '0000-00-00'";
            $params[] = $today;
            $orderBy = 'last_paid_to DESC, m.name ASC';
        } elseif ($status === 'unpaid') {
            $having = "HAVING (last_paid_to < ? OR last_paid_to = '0000-00-00')";
            $params[] = $today;
            $orderBy = 'last_paid_to ASC, m.name ASC';
        } else {
            $having = '';
        }

        $offset = ($page - 1) * $perPage;
        $countParams = $params;

        $countSql = "SELECT COUNT(*) AS cnt FROM ($baseSelect $having) AS sub";
        $countResult = Database::connect()->fetch($countSql, $countParams);
        $total = (int) ($countResult['cnt'] ?? 0);

        $params[] = $perPage;
        $params[] = $offset;
        $rows = Database::connect()->fetchAll(
            "$baseSelect $having ORDER BY $orderBy LIMIT ? OFFSET ?",
            $params
        );

        return ['rows' => $rows, 'total' => $total];
    }
}
