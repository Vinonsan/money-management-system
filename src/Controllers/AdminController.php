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
        $this->view('Dashboard', 'dashboard.php', [], 'dashboard');
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
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
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
                'phone' => $phone,
                'card_number' => !empty($data['card_number']) ? (int) $data['card_number'] : null,
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

    public function scheduleMessage(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();
        $type = trim((string) ($data['type'] ?? 'pending'));
        $userIds = $data['user_ids'] ?? [];

        if (empty($userIds) || !is_array($userIds)) {
            $this->jsonError('No members selected.');
            return;
        }

        $users = \Models\Database::connect()->fetchAll(
            'SELECT id, name, phone, monthly_amount FROM users WHERE id IN (' . implode(',', array_map('intval', $userIds)) . ')'
        );

        $sent = 0;
        foreach ($users as $u) {
            $phone = preg_replace('/[^0-9]/', '', $u['phone'] ?? '');
            if ($phone === '') continue;

            $msg = $type === 'due'
                ? 'Dear ' . $u['name'] . ', your masjid payment of Rs. ' . number_format((float) ($u['monthly_amount'] ?? 0), 2) . ' is due. Please pay at your earliest convenience. - MasjidPay'
                : 'Dear ' . $u['name'] . ', this is a reminder for your pending masjid payment. Monthly amount: Rs. ' . number_format((float) ($u['monthly_amount'] ?? 0), 2) . '. - MasjidPay';

            try {
                $sms = new \Services\SMSService();
                $sms->send($phone, $msg);
                $sent++;
            } catch (\Exception $e) {
                // Log error but continue
            }
        }

        $this->jsonSuccess("Message sent to {$sent} member(s).");
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
            $this->jsonSuccess('Payment recorded.');
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
