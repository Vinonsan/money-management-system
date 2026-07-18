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

        // SMS data (per-admin balance)
        $adminId = (int) ($_SESSION['user_id'] ?? 0);
        $smsBalance    = \Services\SMSService::getBalance($adminId);
        $smsCost       = Setting::get('sms_cost_per_message', '0.62');
        $remainingSms  = (float)$smsCost > 0 ? floor($smsBalance / (float)$smsCost) : 0;
        $smsConfigured = $smsBalance > 0;

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

        // Enforce location isolation: non-super-admin only sees locations they created
        $role = $_SESSION['user_role'] ?? '';
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
        $locFilter = null; // Not filtering by assigned location_id
        $locations = Location::getAll($page, $perPage, $search, $sortField, $sortDir, $filterBy, $locFilter, $createdBy);
        $total     = Location::count($search, $filterBy, $locFilter, $createdBy);

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
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['user_role'] ?? '';
        $createdByCheck = $role !== 'super_admin' ? $userId : null;
        if (Location::nameExists($name, null, $createdByCheck)) {
            $this->jsonError('A location with this name already exists.');
            return;
        }

        try {
            Location::create([
                'name'       => $name,
                'address'    => trim($data['address'] ?? ''),
                'city'       => trim($data['city'] ?? ''),
                'is_active'  => !empty($data['is_active']) ? 1 : 0,
                'created_by' => $role !== 'super_admin' ? $userId : null,
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
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $role = $_SESSION['user_role'] ?? '';
        $createdByCheck = $role !== 'super_admin' ? $userId : null;
        if (Location::nameExists($name, $id, $createdByCheck)) {
            $this->jsonError('A location with this name already exists.');
            return;
        }

        try {
            Location::update($id, [
                'name'      => $name,
                'address'   => trim($data['address'] ?? ''),
                'city'      => trim($data['city'] ?? ''),
                'is_active' => !empty($data['is_active']) ? 1 : 0,
                'created_by' => $createdByCheck,
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

        $role = $_SESSION['user_role'] ?? '';
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;

        try {
            Location::delete($id, $createdBy);
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
$role = $_SESSION['user_role'] ?? '';
    $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
    $locFilter = null; // don't filter by single location_id

    $wards = Ward::getAll($page, $perPage, $locFilter, $createdBy);
    $total = Ward::count($locFilter, $createdBy);
    $locations = Location::allActive(null, $createdBy);
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
        $role = $_SESSION['user_role'] ?? '';
        $createdByCheck = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
        if (Ward::numberExists((int) $data['ward_number'], null, $createdByCheck)) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            $wardId = Ward::create([
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
            ]);
            Ward::syncLocations((int) $wardId, $locationIds, $createdByCheck);
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
        $role = $_SESSION['user_role'] ?? '';
        $createdByCheck = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
        if (Ward::numberExists((int) $data['ward_number'], $id, $createdByCheck)) {
            $this->jsonError('This ward number already exists.');
            return;
        }

        try {
            Ward::update($id, [
                'ward_number' => (int) $data['ward_number'],
                'is_active'   => !empty($data['is_active']) ? 1 : 0,
                'created_by'  => $createdByCheck,
            ]);
            Ward::syncLocations($id, $locationIds, $createdByCheck);
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

        $role = $_SESSION['user_role'] ?? '';
        $createdByCheck = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;

        try {
            Ward::delete($id, $createdByCheck);
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
        $filterBy = trim((string) ($_GET['filter'] ?? ''));
        $locationId = max(0, (int) ($_GET['location_id'] ?? 0));
        $wardId = max(0, (int) ($_GET['ward_id'] ?? 0));
        $sortField = (string) ($_GET['sort'] ?? 'created_at');
        $sortDir = (string) ($_GET['dir'] ?? 'desc');
        $role = $_SESSION['user_role'] ?? '';

        // Data isolation: non-super-admin sees only members in their own locations
        $createdLocationIds = null;
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $createdLocationIds = array_column($locRows, 'id');
            } else {
                // No locations created — force empty result
                $createdLocationIds = [-1];
            }
        }

        $total = Member::count($search, $locationId, $wardId, $createdLocationIds);

        // Filter locations/wards by creator for isolation in member form
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;

        $this->view('Members', 'users.php', [
            'members' => Member::getAll($page, $perPage, $search, $sortField, $sortDir, $locationId, $wardId, $createdLocationIds),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'locationId' => $locationId,
            'wardId' => $wardId,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'locations' => Location::allActive(null, $createdBy),
            'wards' => Ward::allActive(null, $createdBy),
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
            $locationId = !empty($data['location_id']) ? (int) $data['location_id'] : null;

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
                'location_id' => !empty($data['location_id']) ? (int) $data['location_id'] : null,
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
        $role = $_SESSION['user_role'] ?? '';
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
        $this->view('Payments', 'payments.php', [
            'locations' => Location::allActive(null, $createdBy),
            'wards'     => Ward::allActive(null, $createdBy),
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
        $role = $_SESSION['user_role'] ?? '';

        // Data isolation: non-super-admin sees only members in their own locations
        $createdLocationIds = null;
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $createdLocationIds = array_column($locRows, 'id');
            } else {
                $createdLocationIds = [-1];
            }
        }

        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;
        $result = Payment::getMembers($search, $locationId, $wardId, $status, $page, $perPage, $createdLocationIds);

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
            'locations'  => Location::allActive(null, $createdBy),
            'wards'     => Ward::allActive(null, $createdBy),
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
                "SELECT sm.id, sm.member_id, sm.message, sm.scheduled_date, sm.scheduled_time, m.phone
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
                    $sms = new \Services\SMSService((int) ($_SESSION['user_id'] ?? 0));
                    $sent = $sms->send($phone, $m['message']);
                    if ($sent) {
                        $db->execute(
                            "UPDATE scheduled_messages SET status = 'sent', sent_at = NOW() WHERE id = ?",
                            [(int) $m['id']]
                        );

                        // Auto-schedule for next month (recurring)
                        $nextDate = date('Y-m-d', strtotime($m['scheduled_date'] . ' +1 month'));
                        $memberInfo = $db->fetch('SELECT name, monthly_amount FROM members WHERE id = ?', [(int) $m['member_id']]);
                        if ($memberInfo) {
                            $dueMonth = date('F Y', strtotime($nextDate));
                            $template = \Models\Setting::get('msg_due', 'Dear [Name], your contribution of Rs.[Amount] for [Month] is due. Pay before [Date].');
                            $newMsg = str_replace(
                                ['[Name]', '[Amount]', '[MonthlyAmount]', '[Date]', '[Month]'],
                                [$memberInfo['name'], number_format((float) ($memberInfo['monthly_amount'] ?? 0), 2), number_format((float) ($memberInfo['monthly_amount'] ?? 0), 2), $nextDate, $dueMonth],
                                $template
                            );
                            $db->execute(
                                'INSERT INTO scheduled_messages (member_id, user_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                                [(int) $m['member_id'], (int) ($_SESSION['user_id'] ?? 0), $nextDate, $m['scheduled_time'] ?? null, $newMsg, 'due', 'pending']
                            );
                        }
                    } else {
                        $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                    }
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
        $startDate = \Models\Setting::getCollectionStartDate((int) ($_SESSION['user_id'] ?? 0));

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

            // Calculate unpaid months since last payment
            $lastPaid = $db->fetch(
                "SELECT COALESCE(MAX(to_month), '0000-00-00') AS last_to FROM payments WHERE member_id = ?",
                [(int) $u['id']]
            );
            $lastPaidDate = $lastPaid['last_to'] ?? '0000-00-00';
            $dueMonthStart = date('Y-m-01', strtotime($scheduleDate));
            
            if ($lastPaidDate !== '0000-00-00') {
                $unpaidStart = date('Y-m-01', strtotime($lastPaidDate . ' +1 month'));
            } else {
                $startSetting = \Models\Setting::getCollectionStartDate((int) ($_SESSION['user_id'] ?? 0));
                $unpaidStart = date('Y-m-01', strtotime($startSetting));
            }

            // Calculate number of unpaid months
            $unpaidMonths = 0;
            $totalDue = 0;
            $monthlyAmount = (float) ($u['monthly_amount'] ?? 0);
            if ($monthlyAmount > 0 && $unpaidStart <= $dueMonthStart) {
                $diff = (strtotime($dueMonthStart) - strtotime($unpaidStart)) / (60 * 60 * 24 * 30.44);
                $unpaidMonths = max(0, (int) ceil($diff));
                $totalDue = $unpaidMonths * $monthlyAmount;
            }

            $dueMonth = date('F Y', strtotime($scheduleDate));

            // Add unpaid months info to message
            $dueInfo = '';
            if ($unpaidMonths > 1) {
                $dueInfo = sprintf('%d months (Rs. %s) ', $unpaidMonths, number_format($totalDue, 2));
            }

            $msg = str_replace(
                ['[Name]', '[Amount]', '[MonthlyAmount]', '[Date]', '[Month]', '[DueMonths]', '[TotalDue]'],
                [$u['name'], number_format($monthlyAmount, 2), number_format($monthlyAmount, 2), $scheduleDate, $dueMonth, $unpaidMonths, number_format($totalDue, 2)],
                $template
            );

            // Remove old pending schedules for this member, keep new one
            $db->execute(
                "DELETE FROM scheduled_messages WHERE member_id = ? AND status = 'pending' AND type = 'due'",
                [(int) $u['id']]
            );

            $db->execute(
                'INSERT INTO scheduled_messages (member_id, user_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [(int) $u['id'], (int) ($_SESSION['user_id'] ?? 0), $scheduleDate, $scheduleTime !== '' ? $scheduleTime : null, $msg, 'due', 'pending']
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
                $sms = new \Services\SMSService((int) ($_SESSION['user_id'] ?? 0));
                $smsSent = $sms->send($phone, $m['message']);
                if ($smsSent) {
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
                                'INSERT INTO scheduled_messages (member_id, user_id, scheduled_date, scheduled_time, message, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                                [(int) $m['member_id'], (int) ($_SESSION['user_id'] ?? 0), $nextDate, $m['scheduled_time'] ?? null, $newMsg, 'due', 'pending']
                            );
                            $autoScheduled++;
                        }
                    }
                } else {
                    $db->execute("UPDATE scheduled_messages SET status = 'failed' WHERE id = ?", [(int) $m['id']]);
                    $failed++;
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

        $role = $_SESSION['user_role'] ?? '';
        $params = ['%' . $query . '%', '%' . $query . '%'];

        // Data isolation: non-super-admin searches only in their own locations
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $locIds = array_column($locRows, 'id');
                $placeholders = implode(',', array_fill(0, count($locIds), '?'));
                $locJoin = " AND m.location_id IN ({$placeholders})";
                $params = array_merge($params, $locIds);
            } else {
                $locJoin = ' AND 1=0'; // No locations → no results
            }
        } else {
            $locJoin = '';
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
        $role = $_SESSION['user_role'] ?? '';
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $locIds = array_column($locRows, 'id');
                $placeholders = implode(',', array_fill(0, count($locIds), '?'));
                $member = \Models\Database::connect()->fetch(
                    "SELECT id FROM members WHERE id = ? AND location_id IN ({$placeholders})",
                    array_merge([$memberId], $locIds)
                );
            } else {
                $member = null;
            }
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
        $role = $_SESSION['user_role'] ?? '';
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $locIds = array_column($locRows, 'id');
                $placeholders = implode(',', array_fill(0, count($locIds), '?'));
                $member = \Models\Database::connect()->fetch(
                    "SELECT id FROM members WHERE id = ? AND location_id IN ({$placeholders})",
                    array_merge([$memberId], $locIds)
                );
            } else {
                $member = null;
            }
            if (!$member) {
                $this->jsonError('Member not found.');
                return;
            }
        }

        $calc = Payment::calculatePayment($memberId, $amount);
        $calc['extra'] = round((float) ($calc['extra'] ?? 0), 2);
        $calc['months'] = (int) ($calc['months'] ?? 0);

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
        $role = $_SESSION['user_role'] ?? '';
        if ($role !== 'super_admin') {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            $locRows = \Models\Database::connect()->fetchAll(
                'SELECT id FROM locations WHERE created_by = ?',
                [$userId]
            );
            if (!empty($locRows)) {
                $locIds = array_column($locRows, 'id');
                $placeholders = implode(',', array_fill(0, count($locIds), '?'));
                $member = \Models\Database::connect()->fetch(
                    "SELECT id FROM members WHERE id = ? AND location_id IN ({$placeholders})",
                    array_merge([$memberId], $locIds)
                );
            } else {
                $member = null;
            }
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
                'amount'         => round($amount, 2),
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

                    // Calculate remaining/outstanding amount
                    $monthlyAmount = (float) $user['monthly_amount'];
                    $lastPay = \Models\Database::connect()->fetch(
                        "SELECT COALESCE(MAX(to_month), '0000-00-00') AS last_to FROM payments WHERE member_id = ?",
                        [$memberId]
                    );
                    $lastPaidTo = $lastPay['last_to'] ?? '0000-00-00';
                    $today = date('Y-m-d');
                    $remainingAmount = 0;
                    if ($monthlyAmount > 0 && $lastPaidTo !== '0000-00-00') {
                        $unpaidStart = date('Y-m-01', strtotime($lastPaidTo . ' +1 month'));
                        if ($unpaidStart <= $today) {
                            $diff = (strtotime($today) - strtotime($unpaidStart)) / (60 * 60 * 24 * 30.44);
                            $unpaidMonths = max(0, (int) ceil($diff));
                            $remainingAmount = $unpaidMonths * $monthlyAmount;
                        }
                    }

                    $template = Setting::get('msg_confirmation', 'Dear [Name], Rs.[Amount] paid for [Period]. Next due: [NextDue]. Outstanding: Rs.[Remaining]. Thank you!');
                    $msg = str_replace(
                        ['[Name]', '[Amount]', '[Period]', '[MonthlyAmount]', '[PaidUpTo]', '[NextDue]', '[TotalPaid]', '[ExtraAmount]', '[MonthsCovered]', '[Remaining]'],
                        [
                            $user['name'],
                            number_format($amount, 2),
                            $period,
                            number_format($monthlyAmount, 2),
                            $period ?: '-',
                            $nextDue,
                            number_format(0, 2),
                            number_format((float) ($calc['extra'] ?? 0), 2),
                            (string) ($calc['months'] ?? 0),
                            number_format($remainingAmount, 2),
                        ],
                        $template
                    );
                    try {
                        $sms = new \Services\SMSService((int) ($_SESSION['user_id'] ?? 0));
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
        // Read per-admin collection start date, fallback to global setting
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $user = User::findById($userId);
        $startDate = $user['collection_start_date'] ?? Setting::get('collection_start_date', date('Y-m-d'));

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
            // Save per-admin collection start date to users table
            $userId = (int) ($_SESSION['user_id'] ?? 0);
            \Models\Database::connect()->execute(
                'UPDATE users SET collection_start_date = ? WHERE id = ?',
                [$startDate, $userId]
            );
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

        $smsBalance = \Services\SMSService::getBalance((int) ($_SESSION['user_id'] ?? 0));
        $smsCost = \Models\Setting::get('sms_cost_per_message', '0.62');
        $remainingSms = (float)$smsCost > 0 ? floor($smsBalance / (float)$smsCost) : 0;

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

    // ─── Admin Transfer ───────────────────────────

    public function transfer(): void
    {
        $this->view('Transfer Ownership', 'transfer.php', [], 'profile.transfer');
    }

    public function transferSendOldOtp(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $phone = trim($data['phone'] ?? '');

        if ($phone === '') {
            $this->jsonError('Phone number is required.');
            return;
        }

        // Find old admin
        $oldAdmin = \Models\Database::connect()->fetch(
            'SELECT id, name, phone FROM users WHERE phone = ? AND role = ?',
            [$phone, 'admin']
        );
        if (!$oldAdmin) {
            $this->jsonError('No admin found with this phone number.');
            return;
        }

        // Store old admin in session
        $_SESSION['transfer_old_admin_id'] = (int) $oldAdmin['id'];
        $_SESSION['transfer_old_admin_name'] = $oldAdmin['name'];
        $_SESSION['transfer_old_admin_phone'] = $oldAdmin['phone'];

        // Generate and send OTP
        $otp = (new \Services\SMSService())->generateOtp();
        \Models\OtpCode::create($phone, $otp);
        $sent = (new \Services\SMSService())->sendOtp($phone, $otp);

        if ($sent || !(new \Services\SMSService())->isConfigured()) {
            $_SESSION['transfer_old_otp'] = $otp;
            $this->jsonSuccess('OTP sent to old admin.');
        } else {
            $this->jsonError('Failed to send OTP.');
        }
    }

    public function transferVerifyOldOtp(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $otp = trim($data['otp'] ?? '');

        $savedOtp = $_SESSION['transfer_old_otp'] ?? '';
        $phone = $_SESSION['transfer_old_admin_phone'] ?? '';

        if ($otp === '' || $savedOtp === '') {
            $this->jsonError('Invalid OTP.');
            return;
        }

        if (\Models\OtpCode::verify($phone, $otp)) {
            $_SESSION['transfer_old_verified'] = true;
            $this->jsonSuccess('Old admin verified. Now enter new admin details.');
        } else {
            $this->jsonError('Invalid OTP.');
        }
    }

    public function transferSendNewOtp(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone are required.');
            return;
        }

        // Check if phone already in use
        $existing = \Models\Database::connect()->fetch(
            'SELECT id FROM users WHERE phone = ?',
            [$phone]
        );
        if ($existing) {
            $this->jsonError('Phone number already in use by another user.');
            return;
        }

        // Store new admin details in session
        $_SESSION['transfer_new_name'] = $name;
        $_SESSION['transfer_new_phone'] = $phone;
        $_SESSION['transfer_new_email'] = $email;

        // Generate and send OTP to new admin
        $otp = (new \Services\SMSService())->generateOtp();
        \Models\OtpCode::create($phone, $otp);
        $sent = (new \Services\SMSService())->sendOtp($phone, $otp);

        if ($sent || !(new \Services\SMSService())->isConfigured()) {
            $_SESSION['transfer_new_otp'] = $otp;
            $this->jsonSuccess('OTP sent to new admin.');
        } else {
            $this->jsonError('Failed to send OTP.');
        }
    }

    public function transferVerifyNewOtp(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $otp = trim($data['otp'] ?? '');

        $savedOtp = $_SESSION['transfer_new_otp'] ?? '';
        $phone = $_SESSION['transfer_new_phone'] ?? '';

        if ($otp === '' || $savedOtp === '') {
            $this->jsonError('Invalid OTP.');
            return;
        }

        if (\Models\OtpCode::verify($phone, $otp)) {
            // Complete the transfer
            $this->transferComplete();
        } else {
            $this->jsonError('Invalid OTP.');
        }
    }

    // ──────────────────────────────────────────────
    //  Reports
    // ──────────────────────────────────────────────

    public function reports(): void
    {
        $locFilter = $this->getLocationFilter();
        $role = $_SESSION['user_role'] ?? '';
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;

        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? 0);
        $locationId = (int) ($_GET['location_id'] ?? 0);
        $wardId = (int) ($_GET['ward_id'] ?? 0);

        $db = \Models\Database::connect();

        // ── Monthly breakdown for selected year ──────────────
        $monthlyParams = [$year];
        $monthlyWhere = 'YEAR(p.created_at) = ?';

        if ($locFilter !== null) {
            $monthlyWhere .= ' AND m.location_id = ?';
            $monthlyParams[] = $locFilter;
        }
        if ($locationId > 0) {
            $monthlyWhere .= ' AND m.location_id = ?';
            $monthlyParams[] = $locationId;
        }
        if ($wardId > 0) {
            $monthlyWhere .= ' AND m.ward_id = ?';
            $monthlyParams[] = $wardId;
        }

        $monthlyData = $db->fetchAll(
            "SELECT
                MONTH(p.created_at) AS m,
                COUNT(*) AS txns,
                COALESCE(SUM(p.amount), 0) AS total
             FROM payments p
             JOIN members m ON m.id = p.member_id
             WHERE {$monthlyWhere}
             GROUP BY MONTH(p.created_at)
             ORDER BY m ASC",
            $monthlyParams
        );

        // Build full 12-month grid
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $found = 0;
            $txns = 0;
            foreach ($monthlyData as $md) {
                if ((int) $md['m'] === $i) {
                    $found = (float) $md['total'];
                    $txns = (int) $md['txns'];
                    break;
                }
            }
            $months[$i] = ['total' => $found, 'txns' => $txns];
        }

        // ── Yearly summary ──────────────────────────────────
        $yearlyParams = [];
        $yearlyWhere = '1=1';
        if ($locFilter !== null) {
            $yearlyWhere .= ' AND m.location_id = ?';
            $yearlyParams[] = $locFilter;
        }

        $yearlyData = $db->fetchAll(
            "SELECT
                YEAR(p.created_at) AS y,
                COUNT(*) AS txns,
                COALESCE(SUM(p.amount), 0) AS total
             FROM payments p
             JOIN members m ON m.id = p.member_id
             WHERE {$yearlyWhere}
             GROUP BY YEAR(p.created_at)
             ORDER BY y DESC
             LIMIT 10",
            $yearlyParams
        );

        // ── Detail transactions for selected month/year ─────
        $detailWhere = '1=1';
        $detailParams = [];

        if ($year > 0) {
            $detailWhere .= ' AND YEAR(p.created_at) = ?';
            $detailParams[] = $year;
        }
        if ($month > 0) {
            $detailWhere .= ' AND MONTH(p.created_at) = ?';
            $detailParams[] = $month;
        }
        if ($locFilter !== null) {
            $detailWhere .= ' AND m.location_id = ?';
            $detailParams[] = $locFilter;
        }
        if ($locationId > 0) {
            $detailWhere .= ' AND m.location_id = ?';
            $detailParams[] = $locationId;
        }
        if ($wardId > 0) {
            $detailWhere .= ' AND m.ward_id = ?';
            $detailParams[] = $wardId;
        }

        $details = $db->fetchAll(
            "SELECT
                p.id, p.amount, p.months_covered, p.extra_amount, p.from_month, p.to_month, p.created_at,
                m.name AS member_name, m.phone, m.card_number,
                l.name AS location_name, w.ward_number
             FROM payments p
             JOIN members m ON m.id = p.member_id
             LEFT JOIN locations l ON l.id = m.location_id
             LEFT JOIN wards w ON w.id = m.ward_id
             WHERE {$detailWhere}
             ORDER BY p.created_at DESC
             LIMIT 500",
            $detailParams
        );

        // ── Year to date totals ─────────────────────────────
        $ytdParams = [date('Y')];
        $ytdWhere = 'YEAR(p.created_at) = ?';
        if ($locFilter !== null) {
            $ytdWhere .= ' AND m.location_id = ?';
            $ytdParams[] = $locFilter;
        }
        $ytdTotal = $db->fetch(
            "SELECT COALESCE(SUM(p.amount), 0) AS total FROM payments p
             JOIN members m ON m.id = p.member_id
             WHERE {$ytdWhere}",
            $ytdParams
        )['total'] ?? 0;

        // ── Available years for filter ──────────────────────
        $startYear = (int) date('Y', strtotime(Setting::getCollectionStartDate((int) ($_SESSION['user_id'] ?? 0))));
        $currentYear = (int) date('Y');
        $availYears = $db->fetchAll(
            "SELECT DISTINCT y FROM (
                SELECT YEAR(p.created_at) AS y FROM payments p
                UNION
                SELECT ? AS y
                UNION
                SELECT ? AS y
            ) AS yr ORDER BY y DESC",
            [$startYear, $currentYear]
        );

        $this->view('Reports', 'reports.php', [
            'year'       => $year,
            'month'      => $month,
            'months'     => $months,
            'yearlyData' => $yearlyData,
            'details'    => $details,
            'ytdTotal'   => $ytdTotal,
            'availYears' => $availYears,
            'locations'  => Location::allActive(null, $createdBy),
            'wards'      => Ward::allActive(null, $createdBy),
            'locationId' => $locationId,
            'wardId'     => $wardId,
        ], 'reports');
    }

    // ──────────────────────────────────────────────
    //  Bulk Import (CSV)
    // ──────────────────────────────────────────────

    public function bulkImport(): void
    {
        $role = $_SESSION['user_role'] ?? '';
        $createdBy = $role !== 'super_admin' ? (int) ($_SESSION['user_id'] ?? 0) : null;

        $this->view('Bulk Import', 'bulk_import.php', [
            'locations' => Location::allActive(null, $createdBy),
            'wards'     => Ward::allActive(null, $createdBy),
        ], 'locations.bulk-import');
    }

    public function downloadTemplate(): void
    {
        $type = trim((string) ($_GET['type'] ?? 'members'));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_template.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        switch ($type) {
            case 'locations':
                fputcsv($output, ['name']);
                break;

            case 'wards':
                fputcsv($output, ['ward_number', 'location_names']);
                break;

            case 'members':
            default:
                fputcsv($output, ['name', 'phone', 'monthly_amount', 'card_number', 'ward_number', 'location_name']);
                break;
        }

        fclose($output);
        exit;
    }

    public function processBulkImport(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $importType = trim((string) ($data['import_type'] ?? ''));
        $rows = $data['rows'] ?? [];

        if ($importType !== 'members') {
            $this->jsonError('Invalid import type.');
            return;
        }

        if (empty($rows) || !is_array($rows)) {
            $this->jsonError('No data rows to import.');
            return;
        }

        $db = \Models\Database::connect();
        $imported = 0;
        $errors = [];
        $role = $_SESSION['user_role'] ?? '';
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // +2 because row 1 is header
            try {
                $result = $this->importMemberRow($row, $role, $userId);

                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = 'Row ' . $rowNum . ': ' . $result['error'];
                }
            } catch (\Exception $e) {
                $errors[] = 'Row ' . $rowNum . ': ' . $e->getMessage();
            }
        }

        if ($imported === 0 && !empty($errors)) {
            $this->jsonError('Import failed. ' . implode(' | ', $errors));
            return;
        }

        $msg = "Successfully imported {$imported} {$importType}.";
        if (!empty($errors)) {
            $msg .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $msg .= ' (and ' . (count($errors) - 10) . ' more errors)';
            }
        }

        $this->jsonSuccess($msg);
    }

    private function importLocationRow(array $row, int $userId, string $role): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return ['success' => false, 'error' => 'Location name is required.'];
        }

        $createdBy = $role !== 'super_admin' ? $userId : null;

        if (Location::nameExists($name, null, $createdBy)) {
            return ['success' => false, 'error' => "Location '{$name}' already exists."];
        }

        // Default to active (1) if is_active is not provided or empty
        $isActive = 1;
        if (isset($row['is_active']) && $row['is_active'] !== '') {
            $isActive = $row['is_active'] === '0' ? 0 : 1;
        }

        Location::create([
            'name'       => $name,
            'address'    => trim((string) ($row['address'] ?? '')),
            'city'       => trim((string) ($row['city'] ?? '')),
            'is_active'  => $isActive,
            'created_by' => $createdBy,
        ]);

        return ['success' => true];
    }

    private function importWardRow(array $row, string $role, int $userId): array
    {
        $wardNumber = trim((string) ($row['ward_number'] ?? ''));
        // Handle Excel-exported numbers like "1.0" or "1,0" (European locale)
        if ($wardNumber === '' || !is_numeric(str_replace(',', '.', $wardNumber))) {
            return ['success' => false, 'error' => 'Valid ward_number is required (positive integer).'];
        }
        // Cast through float to handle decimals (e.g. "1.0" → (int)1.0 → 1)
        $wardNumber = (int) (float) str_replace(',', '.', $wardNumber);

        $createdBy = $role !== 'super_admin' ? $userId : null;

        if (Ward::numberExists($wardNumber, null, $createdBy)) {
            return ['success' => false, 'error' => "Ward number {$wardNumber} already exists."];
        }

        // Default to active (1) if is_active is not provided or empty
        $isActive = 1;
        if (isset($row['is_active']) && $row['is_active'] !== '') {
            $isActive = $row['is_active'] === '0' ? 0 : 1;
        }

        $wardId = Ward::create([
            'ward_number' => $wardNumber,
            'is_active'   => $isActive,
        ]);

        // Parse location_names (comma-separated). If empty, assign to admin's active locations.
        $locationIds = [];
        $locationNames = trim((string) ($row['location_names'] ?? ''));
        if ($locationNames !== '') {
            $names = explode(',', $locationNames);

            foreach ($names as $locName) {
                $locName = trim($locName);
                if ($locName === '') continue;

                // Check if location exists (scoped to admin's locations)
                $loc = \Models\Database::connect()->fetch(
                    'SELECT id FROM locations WHERE name = ? LIMIT 1',
                    [$locName]
                );

                if ($loc) {
                    $locationIds[] = (int) $loc['id'];
                } else {
                    // Auto-create the location if it doesn't exist
                    $newLocId = Location::create([
                        'name'       => $locName,
                        'address'    => '',
                        'city'       => '',
                        'is_active'  => 1,
                        'created_by' => $createdBy,
                    ]);
                    $locationIds[] = (int) $newLocId;
                }
            }
        }

        if (!empty($locationIds)) {
            Ward::syncLocations((int) $wardId, $locationIds, $createdBy);
        } else {
            // No locations specified - assign to admin's own active locations only
            $locations = Location::allActive(null, $createdBy);
            if (!empty($locations)) {
                Ward::syncLocations((int) $wardId, array_column($locations, 'id'), $createdBy);
            }
        }

        return ['success' => true];
    }

    private function importMemberRow(array $row, string $role, int $userId): array
    {
        $name = trim((string) ($row['name'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Member name is required.'];
        }
        if ($phone === '' || !preg_match('/^[+0-9][+0-9()\- ]{6,19}$/', $phone)) {
            return ['success' => false, 'error' => "Valid phone number is required for '{$name}'."];
        }
        if (Member::phoneExists($phone)) {
            return ['success' => false, 'error' => "Phone {$phone} already exists for '{$name}'."];
        }

        // Resolve location_name — auto-create if missing
        $locationId = null;
        $locationName = trim((string) ($row['location_name'] ?? ''));
        if ($locationName !== '') {
            $loc = \Models\Database::connect()->fetch(
                'SELECT id FROM locations WHERE name = ? LIMIT 1',
                [$locationName]
            );
            if ($loc) {
                $locationId = (int) $loc['id'];
            } else {
                $createdBy = $role !== 'super_admin' ? $userId : null;
                $newLocId = Location::create([
                    'name'       => $locationName,
                    'address'    => '',
                    'city'       => '',
                    'is_active'  => 1,
                    'created_by' => $createdBy,
                ]);
                $locationId = (int) $newLocId;
            }
        }

        // Enforce admin location isolation for non-super-admin
        if ($role !== 'super_admin') {
            $userLocId = $_SESSION['user_location_id'] ?? 0;
            if ($userLocId > 0) {
                $locationId = (int) $userLocId;
            }
        }

        // Resolve ward_number — auto-create if missing
        $wardId = null;
        $wardNumber = trim((string) ($row['ward_number'] ?? ''));
        if ($wardNumber !== '') {
            // Handle Excel-style decimals e.g. "1.0"
            $cleanWard = preg_replace('/[^0-9]/', '', $wardNumber);
            if ($cleanWard !== '') {
                $cleanWard = (int) $cleanWard;
                $wd = \Models\Database::connect()->fetch(
                    'SELECT id FROM wards WHERE ward_number = ? LIMIT 1',
                    [$cleanWard]
                );
                if ($wd) {
                    $wardId = (int) $wd['id'];
                } else {
                    // Auto-create ward
                    $newWardId = Ward::create([
                        'ward_number' => $cleanWard,
                        'is_active'   => 1,
                    ]);
                    $wardId = (int) $newWardId;

                    // Link ward to member's location if available
                    if ($locationId !== null) {
                        Ward::syncLocations($wardId, [$locationId]);
                    }
                }
            }
        }

        Member::create([
            'name'           => $name,
            'phone'          => $phone,
            'card_number'    => trim((string) ($row['card_number'] ?? '')),
            'monthly_amount' => (float) ($row['monthly_amount'] ?? 0),
            'location_id'    => $locationId,
            'ward_id'        => $wardId,
        ]);

        return ['success' => true];
    }

    private function transferComplete(): void
    {
        $db = \Models\Database::connect();
        $oldId = (int) ($_SESSION['transfer_old_admin_id'] ?? 0);
        $oldName = $_SESSION['transfer_old_admin_name'] ?? '';
        $oldPhone = $_SESSION['transfer_old_admin_phone'] ?? '';
        $newName = $_SESSION['transfer_new_name'] ?? '';
        $newPhone = $_SESSION['transfer_new_phone'] ?? '';
        $newEmail = $_SESSION['transfer_new_email'] ?? '';

        if ($oldId <= 0 || $newName === '' || $newPhone === '') {
            $this->jsonError('Transfer session expired. Please start again.');
            return;
        }

        try {
            // Update old admin: set inactive
            $db->execute(
                'UPDATE users SET name = ?, phone = ?, email = ?, is_active = 0 WHERE id = ?',
                [$newName, $newPhone, $newEmail, $oldId]
            );

            // Update old admin's members to reassign
            $db->execute(
                'UPDATE members SET location_id = NULL WHERE location_id = (SELECT location_id FROM users WHERE id = ?)',
                [$oldId]
            );

            // Send notifications
            $loginUrl = BASE_URL . '/login';
            try {
                $sms = new \Services\SMSService();

                // To super admin
                $superAdmins = $db->fetchAll(
                    'SELECT phone FROM users WHERE role = ? AND is_active = 1',
                    ['super_admin']
                );
                foreach ($superAdmins as $sa) {
                    $saPhone = preg_replace('/[^0-9]/', '', $sa['phone'] ?? '');
                    if ($saPhone !== '') {
                        $sms->send($saPhone, "Admin transfer: {$oldName} ({$oldPhone}) transferred to {$newName} ({$newPhone}).");
                    }
                }

                // To old admin
                $sms->send($oldPhone, "Your MasjidPay admin account has been transferred to {$newName} ({$newPhone}).");

                // To new admin
                $sms->send($newPhone, "Welcome {$newName}! You are now the admin for MasjidPay. Login at: {$loginUrl} - OTP will be sent to your phone.");
            } catch (\Exception $e) {
                // SMS failure shouldn't block transfer
            }

            // Clear session
            unset(
                $_SESSION['transfer_old_admin_id'],
                $_SESSION['transfer_old_admin_name'],
                $_SESSION['transfer_old_admin_phone'],
                $_SESSION['transfer_old_otp'],
                $_SESSION['transfer_old_verified'],
                $_SESSION['transfer_new_name'],
                $_SESSION['transfer_new_phone'],
                $_SESSION['transfer_new_email'],
                $_SESSION['transfer_new_otp']
            );

            $this->jsonSuccess('Admin ownership transferred successfully!');
        } catch (\Exception $e) {
            $this->jsonError('Transfer failed: ' . $e->getMessage());
        }
    }
}
