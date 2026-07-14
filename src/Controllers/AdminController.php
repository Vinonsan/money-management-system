<?php
namespace Controllers;

use Models\Location;
use Models\Payment;
use Models\Setting;
use Models\User;
use Models\Ward;

class AdminController
{
    public function index(): void
    {
        $db = \Models\Database::connect();
        $today = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        // Total members (active, with monthly_amount > 0)
        $totalMembers = $db->fetch('SELECT COUNT(*) AS cnt FROM users WHERE is_active = 1 AND monthly_amount > 0')['cnt'] ?? 0;

        // Monthly target = total members * average monthly amount (or sum of all monthly amounts)
        $monthlyTarget = $db->fetch('SELECT COALESCE(SUM(monthly_amount), 0) AS total FROM users WHERE is_active = 1 AND monthly_amount > 0')['total'] ?? 0;

        // This month collection
        $thisMonth = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE created_at >= ? AND created_at < ?",
            [$firstOfMonth, date('Y-m-01', strtotime('+1 month'))]
        )['total'] ?? 0;

        // Yearly collection
        $firstOfYear = date('Y-01-01');
        $thisYear = $db->fetch(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE created_at >= ? AND created_at < ?",
            [$firstOfYear, date('Y-01-01', strtotime('+1 year'))]
        )['total'] ?? 0;

        // Yearly target = monthly target * 12
        $yearlyTarget = $monthlyTarget * 12;

        // Total collection overall
        $totalCollected = $db->fetch('SELECT COALESCE(SUM(amount), 0) AS total FROM payments')['total'] ?? 0;

        // Unpaid members count
        $unpaidCount = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM users u WHERE u.is_active = 1 AND u.monthly_amount > 0
             AND ((SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.user_id = u.id) < ?
                  OR (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id) = 0)",
            [$today]
        )['cnt'] ?? 0;

        // SMS balance — sync live from SMSlenz API
        $smsBalance = (float) (\Models\Setting::get('sms_balance', '0'));
        $smsCost = (float) (\Models\Setting::get('sms_cost_per_message', '0.62'));
        $smsConfigured = false;
        try {
            $sms = new \Services\SMSService();
            if ($sms->isConfigured()) {
                $smsConfigured = true;
                $status = $sms->getAccountStatus();
                if ($status['success']) {
                    $smsBalance = $status['balance'];
                    \Models\Setting::set('sms_balance', (string) $smsBalance);
                }
            }
        } catch (\Exception $e) {
            // Use local balance if live sync fails
        }
        $remainingSms = $smsCost > 0 ? floor($smsBalance / $smsCost) : 0;

        $this->view('Dashboard', 'dashboard.php', [
            'totalMembers'   => (int) $totalMembers,
            'monthlyTarget'  => (float) $monthlyTarget,
            'yearlyTarget'   => (float) $yearlyTarget,
            'thisMonth'      => (float) $thisMonth,
            'thisYear'       => (float) $thisYear,
            'totalCollected' => (float) $totalCollected,
            'unpaidCount'    => (int) $unpaidCount,
            'smsBalance'     => $smsBalance,
            'smsCost'        => $smsCost,
            'remainingSms'   => (int) $remainingSms,
            'smsConfigured'  => $smsConfigured,
        ], 'dashboard');
    }

    public function profile(): void
    {
        $user = User::findById((int) ($_SESSION['user_id'] ?? 0));
        $this->view('Profile', 'profile.php', ['user' => $user ?? []], 'profile');
    }

    // ──────────────────────────────────────────────
    //  Location Management
    // ──────────────────────────────────────────────

    public function locations(): void
    {
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $search   = trim((string) ($_GET['search'] ?? ''));
        $filterBy = trim((string) ($_GET['filter'] ?? ''));
        $sortField = $_GET['sort'] ?? 'id';
        $sortDir  = $_GET['dir'] ?? 'asc';

        $locations = Location::getAll($page, $perPage, $search, $sortField, $sortDir, $filterBy);
        $total     = Location::count($search, $filterBy);

        $this->view('Location', 'locations.php', [
            'locations'  => $locations,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'search'     => $search,
            'filterBy'   => $filterBy,
            'sortField'  => $sortField,
            'sortDir'    => $sortDir,
        ], 'locations.location');
    }

    public function createLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Location name is required.');
            return;
        }
        if (Location::nameExists($name)) {
            $this->jsonError('A location with this name already exists.');
            return;
        }

        try {
            Location::create([
                'name'      => $name,
                'address'   => trim($data['address'] ?? ''),
                'city'      => trim($data['city'] ?? ''),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]);
            $this->jsonSuccess('Location created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create location: ' . $e->getMessage());
        }
    }

    public function updateLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid location ID.');
            return;
        }

        $name = trim($data['name'] ?? '');
        if ($name === '') {
            $this->jsonError('Location name is required.');
            return;
        }
        if (Location::nameExists($name, $id)) {
            $this->jsonError('A location with this name already exists.');
            return;
        }

        try {
            Location::update($id, [
                'name'      => $name,
                'address'   => trim($data['address'] ?? ''),
                'city'      => trim($data['city'] ?? ''),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
            ]);
            $this->jsonSuccess('Location updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update location: ' . $e->getMessage());
        }
    }

    public function deleteLocation(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid location ID.');
            return;
        }

        try {
            Location::delete($id);
            $this->jsonSuccess('Location deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete location: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Wards
    // ──────────────────────────────────────────────

    public function wards(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;

        $wards = Ward::getAll($page, $perPage);
        $total = Ward::count();
        $locations = Location::allActive();

        $this->view('Ward', 'wards.php', [
            'wards'     => $wards,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'locations' => $locations,
        ], 'locations.ward');
    }

    public function createWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        if (!isset($data['ward_number']) || filter_var($data['ward_number'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->jsonError('Ward number must be a positive number.');
            return;
        }
        $locationIds = $this->locationIds($data['location_ids'] ?? []);
        if (empty($locationIds)) {
            $this->jsonError('Select at least one location.');
            return;
        }
        if (Ward::numberExists((int) $data['ward_number'])) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            $wardId = Ward::create([
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ]);
            Ward::syncLocations((int) $wardId, $locationIds);
            $this->jsonSuccess('Ward created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create ward: ' . $e->getMessage());
        }
    }

    public function updateWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid ward ID.');
            return;
        }
        if (!isset($data['ward_number']) || filter_var($data['ward_number'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->jsonError('Ward number must be a positive number.');
            return;
        }
        $locationIds = $this->locationIds($data['location_ids'] ?? []);
        if (empty($locationIds)) {
            $this->jsonError('Select at least one location.');
            return;
        }
        if (Ward::numberExists((int) $data['ward_number'], $id)) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            Ward::update($id, [
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ]);
            Ward::syncLocations($id, $locationIds);
            $this->jsonSuccess('Ward updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update ward: ' . $e->getMessage());
        }
    }

    public function deleteWard(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid ward ID.');
            return;
        }

        try {
            Ward::delete($id);
            $this->jsonSuccess('Ward deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete ward: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Users
    // ──────────────────────────────────────────────

    public function usersList(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $locationId = max(0, (int) ($_GET['location_id'] ?? 0));
        $wardId = max(0, (int) ($_GET['ward_id'] ?? 0));
        $sortField = (string) ($_GET['sort'] ?? 'created_at');
        $sortDir = (string) ($_GET['dir'] ?? 'desc');
        $total = User::count($search, $locationId, $wardId);

        $this->view('Users', 'users.php', [
            'users' => User::getAll($page, $perPage, $search, $sortField, $sortDir, $locationId, $wardId),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'locationId' => $locationId,
            'wardId' => $wardId,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'locations' => Location::allActive(),
            'wards' => Ward::allActive(),
        ], 'users.list');
    }

    public function createUser(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone number are required.');
            return;
        }
        if (!preg_match('/^[+0-9][+0-9()\\- ]{6,19}$/', $phone)) {
            $this->jsonError('Enter a valid phone number.');
            return;
        }
        if (User::phoneExists($phone)) {
            $this->jsonError('This phone number is already in use.');
            return;
        }

        try {
            User::create([
                'name' => $name,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
                'road_number' => !empty($data['road_number']) ? trim($data['road_number']) : null,
                'street' => !empty($data['street']) ? trim($data['street']) : null,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'ward_id' => !empty($data['ward_id']) ? (int) $data['ward_id'] : null,
                'monthly_amount' => !empty($data['monthly_amount']) ? (float) $data['monthly_amount'] : 0,
            ]);
            $this->jsonSuccess('User created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create user: ' . $e->getMessage());
        }
    }

    public function updateUser(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid user ID.');
            return;
        }

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone number are required.');
            return;
        }
        if (!preg_match('/^[+0-9][+0-9()\- ]{6,19}$/', $phone)) {
            $this->jsonError('Enter a valid phone number.');
            return;
        }
        if (User::phoneExistsExclude($phone, $id)) {
            $this->jsonError('This phone number is already in use by another user.');
            return;
        }

        try {
            User::update($id, [
                'name' => $name,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
                'road_number' => !empty($data['road_number']) ? trim($data['road_number']) : null,
                'street' => !empty($data['street']) ? trim($data['street']) : null,
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
                'ward_id' => !empty($data['ward_id']) ? (int) $data['ward_id'] : null,
                'monthly_amount' => !empty($data['monthly_amount']) ? (float) $data['monthly_amount'] : 0,
            ]);
            $this->jsonSuccess('User updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update user: ' . $e->getMessage());
        }
    }

    public function deleteUser(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid user ID.');
            return;
        }

        try {
            User::delete($id);
            $this->jsonSuccess('User deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete user: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Payments
    // ──────────────────────────────────────────────

    public function paymentUpdate(): void
    {
        $this->view('Payments', 'payments.php', [
            'locations' => Location::allActive(),
            'wards'     => Ward::allActive(),
        ], 'payments.update');
    }

    public function paymentMembers(): void
    {
        // Auto-process any due scheduled messages
        $this->processDueMessages();

        $search = trim((string) ($_GET['search'] ?? ''));
        $locationId = max(0, (int) ($_GET['location_id'] ?? 0));
        $wardId = max(0, (int) ($_GET['ward_id'] ?? 0));
        $status = trim((string) ($_GET['status'] ?? 'unpaid'));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 50)));
        $sortField = (string) ($_GET['sort'] ?? 'name');
        $sortDir = (string) ($_GET['dir'] ?? 'asc');
        $result = Payment::getMembers($search, $locationId, $wardId, $status, $page, $perPage);

        $this->view('Members', 'payments_members.php', [
            'members'    => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'perPage'    => $perPage,
            'search'     => $search,
            'locationId' => $locationId,
            'wardId'     => $wardId,
            'status'     => $status,
            'sortField'  => $sortField,
            'sortDir'    => $sortDir,
            'locations'  => Location::allActive(),
            'wards'     => Ward::allActive(),
        ], 'payments.members');
    }

    private function processDueMessages(): void
    {
        try {
            $db = \Models\Database::connect();
            $dueMessages = $db->fetchAll(
                "SELECT sm.id, sm.user_id, sm.message, u.phone
                 FROM scheduled_messages sm
                 JOIN users u ON u.id = sm.user_id
                 WHERE sm.status = 'pending' AND sm.scheduled_date <= CURDATE()"
            );

            foreach ($dueMessages as $m) {
                $phone = preg_replace('/[^0-9]/', '', $m['phone'] ?? '');
                if ($phone === '') {
                    $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                    continue;
                }

                try {
                    $sms = new \Services\SMSService();
                    $sms->send($phone, $m['message']);
                    $db->execute(
                        "UPDATE scheduled_messages SET status = 'sent', sent_at = NOW() WHERE id = ?",
                        [(int) $m['id']]
                    );
                } catch (\Exception $e) {
                    $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                }
            }
        } catch (\Exception $e) {
            // Silently handle - don't block page load
        }
    }

    public function scheduleMessage(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $type = trim((string) ($data['type'] ?? 'due'));
        $scheduleDate = trim((string) ($data['schedule_date'] ?? date('Y-m-d')));
        $scheduleTime = trim((string) ($data['schedule_time'] ?? ''));
        // Validate time format HH:MM (24-hour)
        if ($scheduleTime !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $scheduleTime)) {
            $scheduleTime = '';
        }
        $status = trim((string) ($data['status'] ?? 'unpaid'));
        $search = trim((string) ($data['search'] ?? ''));
        $locationId = max(0, (int) ($data['location_id'] ?? 0));
        $wardId = max(0, (int) ($data['ward_id'] ?? 0));

        // Build query to get all matching unpaid members
        $db = \Models\Database::connect();
        $today = date('Y-m-d');
        $startDate = \Models\Setting::get('collection_start_date', $today);

        $conditions = ['(u.monthly_amount > 0)'];
        $params = [];

        if ($search !== '') {
            $conditions[] = '(u.name LIKE ? OR u.card_number LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
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

        // Unpaid = last_paid_to < today OR never paid
        $users = $db->fetchAll(
            "SELECT u.id, u.name, u.phone, u.monthly_amount
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             LEFT JOIN wards w ON w.id = u.ward_id
             WHERE {$where}
               AND (
                   (SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.user_id = u.id) < ?
                   OR (SELECT COUNT(*) FROM payments p WHERE p.user_id = u.id) = 0
               )
             ORDER BY u.name",
            array_merge($params, [$today])
        );

        if (empty($users)) {
            $this->jsonError('No unpaid members found matching the current filters.');
            return;
        }

        $scheduled = 0;
        $template = Setting::get('msg_due', 'Dear [Name], your contribution of Rs.[Amount] for [Month] is due. Pay before [Date].');

        foreach ($users as $u) {
            $phone = preg_replace('/[^0-9]/', '', $u['phone'] ?? '');
            if ($phone === '') continue;

            // Determine due month from schedule date
            $dueMonth = date('F Y', strtotime($scheduleDate));

            $msg = str_replace(
                ['[Name]', '[Amount]', '[MonthlyAmount]', '[Date]', '[Month]'],
                [$u['name'], number_format((float) ($u['monthly_amount'] ?? 0), 2), number_format((float) ($u['monthly_amount'] ?? 0), 2), $scheduleDate, $dueMonth],
                $template
            );

            $db->execute(
                'INSERT INTO scheduled_messages (user_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?)',
                [(int) $u['id'], $scheduleDate, $scheduleTime !== '' ? $scheduleTime : null, $msg, 'due', 'pending']
            );
            $scheduled++;
        }

        $timeLabel = $scheduleTime !== '' ? " at {$scheduleTime}" : '';
        $this->jsonSuccess("Message scheduled for {$scheduled} member(s) on {$scheduleDate}{$timeLabel}.");
    }

    public function processScheduledMessages(): void
    {
        $this->requireJson();
        $db = \Models\Database::connect();

        // Fetch pending messages where:
        //   - scheduled_date < CURDATE(), OR
        //   - scheduled_date = CURDATE() AND (scheduled_time IS NULL OR scheduled_time <= CURTIME())
        $dueMessages = $db->fetchAll(
            "SELECT sm.id, sm.user_id, sm.message, sm.scheduled_date, sm.scheduled_time, u.phone
             FROM scheduled_messages sm
             JOIN users u ON u.id = sm.user_id
             WHERE sm.status = 'pending'
               AND (
                   sm.scheduled_date < CURDATE()
                   OR (sm.scheduled_date = CURDATE() AND (sm.scheduled_time IS NULL OR sm.scheduled_time <= CURTIME()))
               )"
        );

        $sent = 0;
        $failed = 0;
        foreach ($dueMessages as $m) {
            $phone = preg_replace('/[^0-9]/', '', $m['phone'] ?? '');
            if ($phone === '') {
                $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                $failed++;
                continue;
            }

            try {
                $sms = new \Services\SMSService();
                $sms->send($phone, $m['message']);
                $db->execute(
                    "UPDATE scheduled_messages SET status = 'sent', sent_at = NOW() WHERE id = ?",
                    [(int) $m['id']]
                );
                $sent++;
            } catch (\Exception $e) {
                $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                $failed++;
            }
        }

        $this->jsonSuccess("Processed: {$sent} sent, {$failed} failed.");
    }

    public function searchUser(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $query = trim((string) ($data['query'] ?? ''));

        if ($query === '') {
            $this->jsonError('Enter a name or card number.');
            return;
        }

        $users = \Models\Database::connect()->fetchAll(
            "SELECT u.id, u.name, u.phone, u.card_number, u.monthly_amount, u.is_active,
                    l.name AS location_name, w.ward_number
             FROM users u
             LEFT JOIN locations l ON l.id = u.location_id
             LEFT JOIN wards w ON w.id = u.ward_id
             WHERE u.name LIKE ? OR u.card_number LIKE ?
             LIMIT 20",
            ['%' . $query . '%', '%' . $query . '%']
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    public function getUserPaymentInfo(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $userId = (int) ($data['user_id'] ?? 0);

        if ($userId <= 0) {
            $this->jsonError('Invalid user.');
            return;
        }

        $summary = Payment::getUserPaymentSummary($userId);
        $history = Payment::getByUser($userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $summary, 'history' => $history]);
        exit;
    }

    public function calculatePayment(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($userId <= 0 || $amount <= 0) {
            $this->jsonError('Invalid user or amount.');
            return;
        }

        $calc = Payment::calculatePayment($userId, $amount);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'calculation' => $calc]);
        exit;
    }

    public function createPayment(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $userId = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($userId <= 0 || $amount <= 0) {
            $this->jsonError('Invalid user or amount.');
            return;
        }

        $calc = Payment::calculatePayment($userId, $amount);
        if (!$calc['from']) {
            $this->jsonError('Cannot calculate payment months. Check user monthly amount.');
            return;
        }

        try {
            Payment::create([
                'user_id'        => $userId,
                'amount'         => $amount,
                'months_covered' => $calc['months'],
                'extra_amount'   => $calc['extra'],
                'from_month'     => $calc['from'],
                'to_month'       => $calc['to'],
                'notes'          => trim((string) ($data['notes'] ?? '')),
            ]);

            // Send confirmation SMS
            $user = \Models\Database::connect()->fetch(
                'SELECT id, name, phone, monthly_amount FROM users WHERE id = ?',
                [$userId]
            );
            if ($user && !empty($user['phone'])) {
                $phone = preg_replace('/[^0-9]/', '', $user['phone']);
                if ($phone !== '') {
                    // Build period label (e.g. "Jan 2026 - Mar 2026")
                    $period = '';
                    if ($calc['from'] && $calc['months'] > 0) {
                        $from = date('M Y', strtotime($calc['from']));
                        $to   = date('M Y', strtotime($calc['to']));
                        $period = ($from === $to) ? $from : "{$from} - {$to}";
                    }
                    // Next due month
                    $nextDue = $calc['to']
                        ? date('M Y', strtotime($calc['to'] . ' +1 month'))
                        : date('M Y');
                    // Total paid so far
                    $totalPaid = \Models\Database::connect()->fetch(
                        'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = ?',
                        [$userId]
                    )['total'] ?? 0;

                    $template = Setting::get('msg_confirmation', 'Dear [Name], Rs.[Amount] paid for [Period]. Next due: [NextDue]. Total paid: Rs.[TotalPaid]. Thank you!');
                    $msg = str_replace(
                        ['[Name]', '[Amount]', '[Period]', '[MonthlyAmount]', '[PaidUpTo]', '[NextDue]', '[TotalPaid]', '[ExtraAmount]', '[MonthsCovered]'],
                        [
                            $user['name'],
                            number_format($amount, 2),
                            $period,
                            number_format((float) $user['monthly_amount'], 2),
                            $period ?: '-',
                            $nextDue,
                            number_format((float) $totalPaid, 2),
                            number_format((float) ($calc['extra'] ?? 0), 2),
                            (string) ($calc['months'] ?? 0),
                        ],
                        $template
                    );
                    try {
                        $sms = new \Services\SMSService();
                        $sms->send($phone, $msg);
                        $smsSent = true;
                    } catch (\Exception $e) {
                        $smsSent = false;
                    }
                }
            }

            $this->jsonSuccessWithSms('Payment recorded.', $smsSent ?? false);
        } catch (\Exception $e) {
            $this->jsonError('Failed to record payment: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  System Configuration
    // ──────────────────────────────────────────────

    public function systemConfig(): void
    {
        $startDate = Setting::get('collection_start_date', date('Y-m-d'));

        // SMSlenz configuration
        $smsUserId   = Setting::get('smslenz_user_id', '');
        $smsApiKey   = Setting::get('smslenz_api_key', '');
        $smsSenderId = Setting::get('smslenz_sender_id', 'SMSlenzDEMO');
        $smsCost     = Setting::get('sms_cost_per_message', '0.62');
        $smsBalance  = Setting::get('sms_balance', '0');

        // Live balance from SMSlenz API
        $liveBalance = null;
        $livePlan    = null;
        try {
            $sms = new \Services\SMSService();
            if ($sms->isConfigured()) {
                $status = $sms->getAccountStatus();
                if ($status['success']) {
                    $liveBalance = $status['balance'];
                    $livePlan    = $status['plan'];
                    // Update local balance from live
                    Setting::set('sms_balance', (string) $liveBalance);
                    $smsBalance = (string) $liveBalance;
                }
            }
        } catch (\Exception $e) {
            // Ignore live sync errors
        }

        $this->view('System Config', 'system_config.php', [
            'startDate'    => $startDate,
            'smsUserId'    => $smsUserId,
            'smsApiKey'    => $smsApiKey,
            'smsSenderId'  => $smsSenderId,
            'smsCost'      => $smsCost,
            'smsBalance'   => $smsBalance,
            'liveBalance'  => $liveBalance,
            'livePlan'     => $livePlan,
            'smsConfigured' => $smsUserId !== '' && $smsApiKey !== '',
        ], 'system_config.settings');
    }

    public function saveSystemConfig(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $startDate = trim((string) ($data['collection_start_date'] ?? ''));
        if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $this->jsonError('Valid start date (YYYY-MM-DD) is required.');
            return;
        }

        try {
            Setting::set('collection_start_date', $startDate);
            $this->jsonSuccess('Configuration saved.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Save SMSlenz API configuration.
     */
    public function saveSmsConfig(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $userId   = trim((string) ($data['smslenz_user_id'] ?? ''));
        $apiKey   = trim((string) ($data['smslenz_api_key'] ?? ''));
        $senderId = trim((string) ($data['smslenz_sender_id'] ?? 'SMSlenzDEMO'));
        $cost     = trim((string) ($data['sms_cost_per_message'] ?? '0.62'));

        if ($userId === '') {
            $this->jsonError('SMSlenz User ID is required.');
            return;
        }
        if ($apiKey === '') {
            $this->jsonError('SMSlenz API Key is required.');
            return;
        }
        if ($senderId === '') {
            $this->jsonError('Sender ID is required.');
            return;
        }
        if ($cost === '' || (float) $cost <= 0) {
            $this->jsonError('Valid cost per message is required.');
            return;
        }

        try {
            Setting::set('smslenz_user_id', $userId);
            Setting::set('smslenz_api_key', $apiKey);
            Setting::set('smslenz_sender_id', $senderId);
            Setting::set('sms_cost_per_message', $cost);

            // Sync live balance after saving
            try {
                $sms = new \Services\SMSService();
                $status = $sms->getAccountStatus();
                if ($status['success']) {
                    Setting::set('sms_balance', (string) $status['balance']);
                }
            } catch (\Exception $e) {
                // Ignore
            }

            $this->jsonSuccess('SMS configuration saved successfully.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to save SMS config: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Message Configuration
    // ──────────────────────────────────────────────

    public function systemMessages(): void
    {
        $msgConfirm = Setting::get('msg_confirmation', 'Dear [Name], Rs.[Amount] paid for [Period]. Next due: [NextDue]. Total paid: Rs.[TotalPaid]. Thank you!');
        $msgDue = Setting::get('msg_due', 'Dear [Name], your monthly contribution of Rs.[MonthlyAmount] for [Month] is due. Kindly pay Rs.[Amount] before [Date].');

        $this->view('Message Config', 'messages.php', [
            'msgConfirm' => $msgConfirm,
            'msgDue' => $msgDue,
        ], 'system_config.messages');
    }

    public function saveSystemMessages(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $msgConfirm = trim((string) ($data['msg_confirmation'] ?? ''));
        $msgDue = trim((string) ($data['msg_due'] ?? ''));

        if ($msgConfirm === '' || $msgDue === '') {
            $this->jsonError('Both message templates are required.');
            return;
        }

        if (mb_strlen($msgConfirm) > 150) {
            $this->jsonError('Confirmation message must be 150 characters or less.');
            return;
        }
        if (mb_strlen($msgDue) > 150) {
            $this->jsonError('Due reminder message must be 150 characters or less.');
            return;
        }

        try {
            Setting::set('msg_confirmation', $msgConfirm);
            Setting::set('msg_due', $msgDue);
            $this->jsonSuccess('Message templates saved.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to save: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Internal helpers
    // ──────────────────────────────────────────────

    private function placeholder(string $title, string $activeNav, string $hint = ''): void
    {
        $this->view($title, 'placeholder.php', [
            'pageTitle' => $title,
            'pageHint'  => $hint !== '' ? $hint : 'Module UI coming next.',
        ], $activeNav);
    }

    private function view(string $title, string $file, array $data, string $activeNav): void
    {
        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout($title, __DIR__ . '/../Views/admin/' . $file, $data, $activeNav);
    }

    private function requireJson(): void
    {
        if (strtolower($_SERVER['REQUEST_METHOD'] ?? '') !== 'post') {
            http_response_code(405);
            $this->jsonError('Method not allowed.');
            exit;
        }
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(string $message): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }

    private function jsonSuccessWithSms(string $message, bool $smsSent = false): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message, 'sms_sent' => $smsSent]);
        exit;
    }

    private function locationIds(mixed $locationIds): array
    {
        if (!is_array($locationIds)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $locationIds),
            static fn (int $id): bool => $id > 0
        )));
        $activeIds = array_map('intval', array_column(Location::allActive(), 'id'));

        return count($ids) === count(array_intersect($ids, $activeIds)) ? $ids : [];
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
