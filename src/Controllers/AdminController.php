<?php
namespace Controllers;

use Models\Location;
use Models\Member;
use Models\Payment;
use Models\Setting;
use Models\User;
use Models\Ward;

class AdminController
{
    private ?array $_jsonCache = null;

    /**
     * Get the current admin's location ID for data isolation.
     * Super admin sees all (null = no filter).
     */
    private function getLocationFilter(): ?int
    {
        $role = $_SESSION['user_role'] ?? '';
        if ($role === 'super_admin') {
            return null; // super admin sees all
        }
        $locId = $_SESSION['user_location_id'] ?? 0;
        // If admin has no location assigned, use -1 so queries return empty
        return $locId > 0 ? (int) $locId : -1;
    }

    public function index(): void
    {
        $db = \Models\Database::connect();
        $today = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');
        $locFilter = $this->getLocationFilter();
        $locJoin = $locFilter ? ' AND m.location_id = ' . (int) $locFilter : '';
        $locJoinSimple = $locFilter ? ' AND location_id = ' . (int) $locFilter : '';
        $locPayJoin = $locFilter ? ' AND m2.location_id = ' . (int) $locFilter : '';

        // Total members
        $totalMembers = $db->fetch("SELECT COUNT(*) AS cnt FROM members WHERE is_active = 1 AND monthly_amount > 0{$locJoinSimple}")['cnt'] ?? 0;

        // Monthly target
        $monthlyTarget = $db->fetch("SELECT COALESCE(SUM(monthly_amount), 0) AS total FROM members WHERE is_active = 1 AND monthly_amount > 0{$locJoinSimple}")['total'] ?? 0;

        // This month collection - filter by admin's location members
        $thisMonth = $db->fetch(
            "SELECT COALESCE(SUM(p.amount), 0) AS total FROM payments p
             JOIN members m ON m.id = p.member_id
             WHERE p.created_at >= ? AND p.created_at < ?{$locJoin}",
            [$firstOfMonth, date('Y-m-01', strtotime('+1 month'))]
        )['total'] ?? 0;

        // Yearly collection
        $firstOfYear = date('Y-01-01');
        $thisYear = $db->fetch(
            "SELECT COALESCE(SUM(p.amount), 0) AS total FROM payments p
             JOIN members m ON m.id = p.member_id
             WHERE p.created_at >= ? AND p.created_at < ?{$locJoin}",
            [$firstOfYear, date('Y-01-01', strtotime('+1 year'))]
        )['total'] ?? 0;

        $yearlyTarget = $monthlyTarget * 12;

        // Total collection (admin's location only)
        $totalCollected = $db->fetch("SELECT COALESCE(SUM(p.amount), 0) AS total FROM payments p JOIN members m ON m.id = p.member_id WHERE 1=1{$locJoin}")['total'] ?? 0;

        // Unpaid members count
        $unpaidCount = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM members m WHERE m.is_active = 1 AND m.monthly_amount > 0{$locJoin}
             AND ((SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.member_id = m.id) < ?
                  OR (SELECT COUNT(*) FROM payments p WHERE p.member_id = m.id) = 0)",
            [$today]
        )['cnt'] ?? 0;

        // SMS data
        $smsBalance    = Setting::get('sms_balance', '0');
        $smsCost       = Setting::get('sms_cost_per_message', '0.62');
        $remainingSms  = (float)$smsCost > 0 ? floor((float)$smsBalance / (float)$smsCost) : 0;
        $smsConfigured = (float)$smsBalance > 0;

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
        $this->view('Profile', 'profile.php', [
            'user' => $user ?? [],
        ], 'profile');
    }

    public function uploadAvatar(): void
    {
        // Avatar upload disabled - users table simplified
        $_SESSION['upload_error'] = 'Profile image upload is not available.';
        header('Location: ' . BASE_URL . '/admin/profile');
        exit;
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

        // Enforce location isolation: non-super-admin only sees their own location
        $locFilter = $this->getLocationFilter();
        $locations = Location::getAll($page, $perPage, $search, $sortField, $sortDir, $filterBy, $locFilter);
        $total     = Location::count($search, $filterBy, $locFilter);

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
        $locFilter = $this->getLocationFilter();

        $wards = Ward::getAll($page, $perPage, $locFilter);
        $total = Ward::count($locFilter);
        $locations = Location::allActive($locFilter);

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
    //  Members
    // ──────────────────────────────────────────────

    public function membersList(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
        $search = trim((string) ($_GET['search'] ?? ''));
        $locationId = max(0, (int) ($_GET['location_id'] ?? 0));
        $wardId = max(0, (int) ($_GET['ward_id'] ?? 0));
        $sortField = (string) ($_GET['sort'] ?? 'created_at');
        $sortDir = (string) ($_GET['dir'] ?? 'desc');
        $locFilter = $this->getLocationFilter();
        $total = Member::count($search, $locationId, $wardId, $locFilter);

        $this->view('Members', 'users.php', [
            'members' => Member::getAll($page, $perPage, $search, $sortField, $sortDir, $locationId, $wardId, $locFilter),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'locationId' => $locationId,
            'wardId' => $wardId,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'locations' => Location::allActive($locFilter),
            'wards' => Ward::allActive($locFilter),
        ], 'users.list');
    }

    public function createMember(): void
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
        if (Member::phoneExists($phone)) {
            $this->jsonError('This phone number is already in use.');
            return;
        }

        try {
            // Enforce location isolation: use admin's location if not super_admin
            $locFilter = $this->getLocationFilter();
            $locationId = $locFilter !== null
                ? $locFilter
                : (!empty($data['location_id']) ? (int) $data['location_id'] : null);

            Member::create([
                'name' => $name,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
                'road_number' => !empty($data['road_number']) ? trim($data['road_number']) : null,
                'street' => !empty($data['street']) ? trim($data['street']) : null,
                'location_id' => $locationId,
                'ward_id' => !empty($data['ward_id']) ? (int) $data['ward_id'] : null,
                'monthly_amount' => !empty($data['monthly_amount']) ? (float) $data['monthly_amount'] : 0,
            ]);
            $this->jsonSuccess('Member created.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to create member: ' . $e->getMessage());
        }
    }

    public function updateMember(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid member ID.');
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
        if (Member::phoneExistsExclude($phone, $id)) {
            $this->jsonError('This phone number is already in use by another user.');
            return;
        }

        try {
            // Enforce location isolation: verify member belongs to admin's location
            $locFilter = $this->getLocationFilter();
            if ($locFilter !== null) {
                $existing = Member::findById($id);
                if (!$existing || (int) ($existing['location_id'] ?? 0) !== $locFilter) {
                    $this->jsonError('Member not found or access denied.');
                    return;
                }
            }

            Member::update($id, [
                'name' => $name,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
                'road_number' => !empty($data['road_number']) ? trim($data['road_number']) : null,
                'street' => !empty($data['street']) ? trim($data['street']) : null,
                'location_id' => $locFilter ?? (!empty($data['location_id']) ? (int) $data['location_id'] : null),
                'ward_id' => !empty($data['ward_id']) ? (int) $data['ward_id'] : null,
                'monthly_amount' => !empty($data['monthly_amount']) ? (float) $data['monthly_amount'] : 0,
            ]);
            $this->jsonSuccess('Member updated.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to update member: ' . $e->getMessage());
        }
    }

    public function deleteMember(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid member ID.');
            return;
        }

        // Enforce location isolation
        $locFilter = $this->getLocationFilter();
        if ($locFilter !== null) {
            $existing = Member::findById($id);
            if (!$existing || (int) ($existing['location_id'] ?? 0) !== $locFilter) {
                $this->jsonError('Member not found or access denied.');
                return;
            }
        }

        try {
            Member::delete($id);
            $this->jsonSuccess('Member deleted.');
        } catch (\Exception $e) {
            $this->jsonError('Failed to delete user: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    //  Payments
    // ──────────────────────────────────────────────

    public function paymentUpdate(): void
    {
        $locFilter = $this->getLocationFilter();
        $this->view('Payments', 'payments.php', [
            'locations' => Location::allActive($locFilter),
            'wards'     => Ward::allActive($locFilter),
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
        $locFilter = $this->getLocationFilter();
        $result = Payment::getMembers($search, $locationId, $wardId, $status, $page, $perPage, $locFilter);

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
            'locations'  => Location::allActive($locFilter),
            'wards'     => Ward::allActive($locFilter),
        ], 'payments.members');
    }

    private function processDueMessages(): void
    {
        try {
            $db = \Models\Database::connect();
            $locFilter = $this->getLocationFilter();
            $locJoin = $locFilter !== null ? ' AND m.location_id = ?' : '';
            $params = $locFilter !== null ? [$locFilter] : [];
            $dueMessages = $db->fetchAll(
                "SELECT sm.id, sm.member_id, sm.message, m.phone
                 FROM scheduled_messages sm
                 JOIN members m ON m.id = sm.member_id
                 WHERE sm.status = 'pending' AND sm.scheduled_date <= CURDATE(){$locJoin}",
                $params
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

        $conditions = ['(m.monthly_amount > 0)'];
        $params = [];

        // Enforce location isolation for non-super-admin
        $locFilter = $this->getLocationFilter();
        if ($locFilter !== null) {
            $conditions[] = 'm.location_id = ?';
            $params[] = $locFilter;
        }

        if ($search !== '') {
            $conditions[] = '(m.name LIKE ? OR m.card_number LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
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

        // Unpaid = last_paid_to < today OR never paid
        $users = $db->fetchAll(
            "SELECT m.id, m.name, m.phone, m.monthly_amount
             FROM members m
             LEFT JOIN locations l ON l.id = m.location_id
             LEFT JOIN wards w ON w.id = m.ward_id
             WHERE {$where}
               AND (
                   (SELECT COALESCE(MAX(p.to_month), '0000-00-00') FROM payments p WHERE p.member_id = m.id) < ?
                   OR (SELECT COUNT(*) FROM payments p WHERE p.member_id = m.id) = 0
               )
             ORDER BY m.name",
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
                'INSERT INTO scheduled_messages (member_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?)',
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
        $locFilter = $this->getLocationFilter();
        $locJoin = $locFilter !== null ? ' AND m.location_id = ?' : '';
        $locParams = $locFilter !== null ? [$locFilter] : [];

        // Fetch pending messages where:
        //   - scheduled_date < CURDATE(), OR
        //   - scheduled_date = CURDATE() AND (scheduled_time IS NULL OR scheduled_time <= CURTIME())
        $dueMessages = $db->fetchAll(
            "SELECT sm.id, sm.member_id, sm.message, sm.scheduled_date, sm.scheduled_time, m.phone
             FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE sm.status = 'pending'
               AND (
                   sm.scheduled_date < CURDATE()
                   OR (sm.scheduled_date = CURDATE() AND (sm.scheduled_time IS NULL OR sm.scheduled_time <= CURTIME()))
               ){$locJoin}",
            $locParams
        );

        $sent = 0;
        $failed = 0;
        $autoScheduled = 0;
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

                // Auto-schedule for next month (recurring)
                $nextDate = date('Y-m-d', strtotime($m['scheduled_date'] . ' +1 month'));
                $member = $db->fetch('SELECT id, monthly_amount FROM members WHERE id = ?', [(int) $m['member_id']]);
                if ($member) {
                    $dueMonth = date('F Y', strtotime($nextDate));
                    $template = Setting::get('msg_due', 'Dear [Name], your contribution of Rs.[Amount] for [Month] is due. Pay before [Date].');
                    $memberInfo = $db->fetch('SELECT name, monthly_amount FROM members WHERE id = ?', [(int) $m['member_id']]);
                    if ($memberInfo) {
                        $newMsg = str_replace(
                            ['[Name]', '[Amount]', '[MonthlyAmount]', '[Date]', '[Month]'],
                            [$memberInfo['name'], number_format((float) ($memberInfo['monthly_amount'] ?? 0), 2), number_format((float) ($memberInfo['monthly_amount'] ?? 0), 2), $nextDate, $dueMonth],
                            $template
                        );
                        $db->execute(
                            'INSERT INTO scheduled_messages (member_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?)',
                            [(int) $m['member_id'], $nextDate, $m['scheduled_time'] ?? null, $newMsg, 'due', 'pending']
                        );
                        $autoScheduled++;
                    }
                }
            } catch (\Exception $e) {
                $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                $failed++;
            }
        }

        $this->jsonSuccess("Sent: {$sent}, Failed: {$failed}, Auto-scheduled for next month: {$autoScheduled}.");
    }

    // ─── Schedule Report ──────────────────────────

    public function scheduleReport(): void
    {
        $db = \Models\Database::connect();
        $locFilter = $this->getLocationFilter();

        // Stats
        $totalScheduled = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE 1=1" . ($locFilter !== null ? ' AND m.location_id = ?' : ''),
            $locFilter !== null ? [$locFilter] : []
        )['cnt'] ?? 0;

        $sentToday = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE DATE(sm.sent_at) = CURDATE()" . ($locFilter !== null ? ' AND m.location_id = ?' : ''),
            $locFilter !== null ? [$locFilter] : []
        )['cnt'] ?? 0;

        $pendingCount = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE sm.status = 'pending'" . ($locFilter !== null ? ' AND m.location_id = ?' : ''),
            $locFilter !== null ? [$locFilter] : []
        )['cnt'] ?? 0;

        $failedCount = $db->fetch(
            "SELECT COUNT(*) AS cnt FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE sm.status = 'failed'" . ($locFilter !== null ? ' AND m.location_id = ?' : ''),
            $locFilter !== null ? [$locFilter] : []
        )['cnt'] ?? 0;

        // Recent activity
        $recentMessages = $db->fetchAll(
            "SELECT sm.*, m.name AS member_name, m.phone AS member_phone
             FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE 1=1" . ($locFilter !== null ? ' AND m.location_id = ?' : '') . "
             ORDER BY sm.created_at DESC LIMIT 50",
            $locFilter !== null ? [$locFilter] : []
        );

        // Upcoming schedules
        $upcomingMessages = $db->fetchAll(
            "SELECT sm.*, m.name AS member_name, m.phone AS member_phone
             FROM scheduled_messages sm
             JOIN members m ON m.id = sm.member_id
             WHERE sm.status = 'pending' AND sm.scheduled_date >= CURDATE()" . ($locFilter !== null ? ' AND m.location_id = ?' : '') . "
             ORDER BY sm.scheduled_date ASC LIMIT 20",
            $locFilter !== null ? [$locFilter] : []
        );

        $this->view('Schedule Report', 'schedule_report.php', [
            'totalScheduled'  => $totalScheduled,
            'sentToday'       => $sentToday,
            'pendingCount'    => $pendingCount,
            'failedCount'     => $failedCount,
            'recentMessages'  => $recentMessages,
            'upcomingMessages' => $upcomingMessages,
        ], 'payments.schedule');
    }

    public function searchMember(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $query = trim((string) ($data['query'] ?? ''));

        if ($query === '') {
            $this->jsonError('Enter a name or card number.');
            return;
        }

        $locFilter = $this->getLocationFilter();
        $locJoin = $locFilter !== null ? ' AND m.location_id = ?' : '';
        $params = ['%' . $query . '%', '%' . $query . '%'];
        if ($locFilter !== null) {
            $params[] = $locFilter;
        }

        $users = \Models\Database::connect()->fetchAll(
            "SELECT m.id, m.name, m.phone, m.card_number, m.monthly_amount, m.is_active,
                    l.name AS location_name, w.ward_number
             FROM members m
             LEFT JOIN locations l ON l.id = m.location_id
             LEFT JOIN wards w ON w.id = m.ward_id
             WHERE (m.name LIKE ? OR m.card_number LIKE ?){$locJoin}
             LIMIT 20",
            $params
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'members' => $users]);
        exit;
    }

    public function getMemberPaymentInfo(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $memberId = (int) ($data['member_id'] ?? 0);

        if ($memberId <= 0) {
            $this->jsonError('Invalid member.');
            return;
        }

        // Enforce location isolation
        $locFilter = $this->getLocationFilter();
        if ($locFilter !== null) {
            $member = \Models\Database::connect()->fetch(
                'SELECT id FROM members WHERE id = ? AND location_id = ?',
                [$memberId, $locFilter]
            );
            if (!$member) {
                $this->jsonError('Member not found.');
                return;
            }
        }

        $summary = Payment::getMemberPaymentSummary($memberId);
        $history = Payment::getByMember($memberId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $summary, 'history' => $history]);
        exit;
    }

    public function calculatePayment(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $memberId = (int) ($data['member_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($memberId <= 0 || $amount <= 0) {
            $this->jsonError('Invalid member or amount.');
            return;
        }

        // Enforce location isolation
        $locFilter = $this->getLocationFilter();
        if ($locFilter !== null) {
            $member = \Models\Database::connect()->fetch(
                'SELECT id FROM members WHERE id = ? AND location_id = ?',
                [$memberId, $locFilter]
            );
            if (!$member) {
                $this->jsonError('Member not found.');
                return;
            }
        }

        $calc = Payment::calculatePayment($memberId, $amount);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'calculation' => $calc]);
        exit;
    }

    public function createPayment(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $memberId = (int) ($data['member_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);

        if ($memberId <= 0 || $amount <= 0) {
            $this->jsonError('Invalid member or amount.');
            return;
        }

        // Enforce location isolation
        $locFilter = $this->getLocationFilter();
        if ($locFilter !== null) {
            $member = \Models\Database::connect()->fetch(
                'SELECT id FROM members WHERE id = ? AND location_id = ?',
                [$memberId, $locFilter]
            );
            if (!$member) {
                $this->jsonError('Member not found.');
                return;
            }
        }

        $calc = Payment::calculatePayment($memberId, $amount);
        if (!$calc['from']) {
            $this->jsonError('Cannot calculate payment months. Check member monthly amount.');
            return;
        }

        try {
            Payment::create([
                'member_id'        => $memberId,
                'amount'         => $amount,
                'months_covered' => $calc['months'],
                'extra_amount'   => $calc['extra'],
                'from_month'     => $calc['from'],
                'to_month'       => $calc['to'],
                'notes'          => trim((string) ($data['notes'] ?? '')),
            ]);

            // Send confirmation SMS
            $user = \Models\Database::connect()->fetch(
                'SELECT id, name, phone, monthly_amount FROM members WHERE id = ?',
                [$memberId]
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
                        'SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE member_id = ?',
                        [$memberId]
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

        $this->view('System Config', 'system_config.php', [
            'startDate' => $startDate,
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

    // ──────────────────────────────────────────────
    //  SMS Manager (Admin)
    // ──────────────────────────────────────────────

    public function smsManager(): void
    {
        $db = \Models\Database::connect();

        // Use phone-based lookup for reliable user ID
        $adminId = 0;
        $phone = $_SESSION['user_phone'] ?? '';
        if ($phone) {
            $user = \Models\User::findByPhone($phone);
            if ($user) {
                $adminId = (int) $user['id'];
            }
        }
        if ($adminId <= 0) {
            $adminId = (int) ($_SESSION['member_id'] ?? 0);
        }

        $smsBalance = \Models\Setting::get('sms_balance', '0');
        $smsCost = \Models\Setting::get('sms_cost_per_message', '0.62');
        $remainingSms = (float)$smsCost > 0 ? floor((float)$smsBalance / (float)$smsCost) : 0;

        $requests = $db->fetchAll(
            'SELECT * FROM refill_requests WHERE admin_id = ? ORDER BY created_at DESC LIMIT 20',
            [$adminId]
        );

        $this->view('SMS Manager', 'sms_manager.php', [
            'smsBalance'    => $smsBalance,
            'smsCost'       => $smsCost,
            'remainingSms'  => (int) $remainingSms,
            'requests'      => $requests,
            'requestError'  => $_SESSION['refill_error'] ?? null,
            'requestSuccess' => $_SESSION['refill_success'] ?? null,
        ], 'sms');
        unset($_SESSION['refill_error'], $_SESSION['refill_success']);
    }

    public function requestRefill(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }

        // CSRF validation for form POST
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $_SESSION['refill_error'] = 'Invalid request. Please try again.';
            header('Location: ' . BASE_URL . '/admin/sms');
            exit;
        }

        // Use phone-based lookup for reliable user ID
        $adminId = 0;
        $phone = $_SESSION['user_phone'] ?? '';
        if ($phone) {
            $user = \Models\User::findByPhone($phone);
            if ($user) {
                $adminId = (int) $user['id'];
            }
        }
        if ($adminId <= 0) {
            $adminId = (int) ($_SESSION['member_id'] ?? 0);
        }

        $amount  = (float) ($_POST['amount'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($amount <= 0) {
            $_SESSION['refill_error'] = 'Enter a valid amount.';
            header('Location: ' . BASE_URL . '/admin/sms');
            exit;
        }

        \Models\Database::connect()->insert(
            'INSERT INTO refill_requests (admin_id, amount, message) VALUES (?, ?, ?)',
            [$adminId, $amount, $message]
        );

        $_SESSION['refill_success'] = 'Refill request submitted. Waiting for Super Admin approval.';
        header('Location: ' . BASE_URL . '/admin/sms');
        exit;
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

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
        // Parse body once and cache it
        $this->_jsonCache = $this->parseJsonBody();
        // CSRF validation for JSON endpoints
        $token = $this->_jsonCache['_csrf'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            $this->jsonError('Invalid or missing CSRF token.');
            exit;
        }
    }

    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function jsonBody(): array
    {
        // Return cached body (populated by requireJson)
        if ($this->_jsonCache !== null) {
            return $this->_jsonCache;
        }
        return $this->parseJsonBody();
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

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }

    private function locationIds(mixed $locationIds): array
    {
        if (is_array($locationIds)) {
            return array_map('intval', $locationIds);
        }
        if (is_string($locationIds)) {
            return array_map('intval', explode(',', $locationIds));
        }
        return [];
    }
}
