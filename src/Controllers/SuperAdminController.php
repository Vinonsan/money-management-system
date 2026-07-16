<?php
namespace Controllers;

use Models\Setting;
use Models\User;
use Models\Database;

class SuperAdminController
{
    private ?array $_jsonCache = null;
    public function index(): void
    {
        $db = Database::connect();

        $totalAdmins = $db->fetch("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'")['cnt'] ?? 0;

        // SMS stats
        $smsBalance = Setting::get('sms_balance', '0');
        $smsCost = Setting::get('sms_cost_per_message', '0.62');
        $remainingSms = (float)$smsCost > 0 ? floor((float)$smsBalance / (float)$smsCost) : 0;

        $this->view('Super Admin Dashboard', 'super_admin/dashboard.php', [
            'totalAdmins' => (int) $totalAdmins,
            'smsBalance' => $smsBalance,
            'smsCost' => $smsCost,
            'remainingSms' => (int) $remainingSms,
        ], 'super_admin.dashboard');
    }

    // ─── Admin Management ───────────────────────────────

    public function admins(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $admins = Database::connect()->fetchAll(
            "SELECT id, name, business_name, email, phone, is_active, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );
        $total = Database::connect()->fetch("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'")['cnt'] ?? 0;

        $this->view('Manage Admins', 'super_admin/admins.php', [
            'admins' => $admins,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ], 'super_admin.admins');
    }

    public function createAdmin(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $name = trim($data['name'] ?? '');
        $businessName = trim($data['business_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone are required.');
            return;
        }
        if (User::phoneExists($phone)) {
            $this->jsonError('Phone already in use.');
            return;
        }

        $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

        $db = Database::connect();
        $adminId = (int) $db->insert(
            'INSERT INTO users (name, business_name, phone, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)',
            [$name, $businessName, $phone, $email, $hash, 'admin']
        );

        // Auto-create a location for this admin (isolation)
        $locName = $businessName ?: $name . "'s Location";
        $locationId = (int) $db->insert(
            'INSERT INTO locations (name, is_active) VALUES (?, 1)',
            [$locName]
        );

        // Link admin to their location
        $db->execute('UPDATE users SET location_id = ? WHERE id = ?', [$locationId, $adminId]);

        // Send welcome SMS with login details
        try {
            $sms = new \Services\SMSService();
            $loginUrl = BASE_URL . '/login';
            $message = "Dear {$name}, your MasjidPay admin account is ready. Phone: {$phone}. Login at: {$loginUrl} - OTP will be sent to your phone.";
            $sms->send($phone, $message);
        } catch (\Exception $e) {
            // SMS failure shouldn't block account creation
        }

        $this->jsonSuccess('Admin created successfully. Login details sent via SMS.');
    }

    public function updateAdmin(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid admin ID.');
            return;
        }

        $name = trim($data['name'] ?? '');
        $businessName = trim($data['business_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($name === '' || $phone === '') {
            $this->jsonError('Name and phone are required.');
            return;
        }
        if (User::phoneExistsExclude($phone, $id)) {
            $this->jsonError('Phone already in use by another user.');
            return;
        }

        $db = Database::connect();
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->execute(
                'UPDATE users SET name = ?, business_name = ?, phone = ?, email = ?, password = ? WHERE id = ? AND role = ?',
                [$name, $businessName, $phone, $email, $hash, $id, 'admin']
            );
        } else {
            $db->execute(
                'UPDATE users SET name = ?, business_name = ?, phone = ?, email = ? WHERE id = ? AND role = ?',
                [$name, $businessName, $phone, $email, $id, 'admin']
            );
        }

        $this->jsonSuccess('Admin updated successfully.');
    }

    public function deleteAdmin(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Invalid admin ID.');
            return;
        }

        // Prevent deleting self
        if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            $this->jsonError('You cannot delete your own account.');
            return;
        }

        Database::connect()->execute('DELETE FROM users WHERE id = ? AND role = ?', [$id, 'admin']);
        $this->jsonSuccess('Admin deleted.');
    }

    // ─── SMS Management ────────────────────────────────

    public function smsConfig(): void
    {
        $smsBalance = Setting::get('sms_balance', '0');
        $smsCost = Setting::get('sms_cost_per_message', '0.62');
        $smsSenderId = Setting::get('smslenz_sender_id', 'ExGenX9920');
        $smsUserId  = Setting::get('smslenz_user_id', '');
        $smsApiKey  = Setting::get('smslenz_api_key', '');

        // Transaction history from settings (simple log)
        $history = Database::connect()->fetchAll(
            "SELECT key_name, value, updated_at FROM settings WHERE key_name LIKE 'sms_txn_%' ORDER BY updated_at DESC LIMIT 50"
        );

        $this->view('SMS Management', 'super_admin/sms.php', [
            'smsBalance'  => $smsBalance,
            'smsCost'     => $smsCost,
            'smsSenderId' => $smsSenderId,
            'smsUserId'   => $smsUserId,
            'smsApiKey'   => $smsApiKey,
            'history'     => $history,
        ], 'super_admin.sms');
    }

    public function saveSmsConfig(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $smsCost     = trim($data['sms_cost_per_message'] ?? '0.62');
        $smsSenderId = trim($data['smslenz_sender_id'] ?? 'ExGenX9920');
        $smsUserId   = trim($data['smslenz_user_id'] ?? '');
        $smsApiKey   = trim($data['smslenz_api_key'] ?? '');

        if ($smsCost === '' || (float) $smsCost <= 0) {
            $this->jsonError('Valid cost is required.');
            return;
        }

        Setting::set('sms_cost_per_message', $smsCost);
        Setting::set('smslenz_sender_id', $smsSenderId);
        Setting::set('smslenz_user_id', $smsUserId);
        Setting::set('smslenz_api_key', $smsApiKey);
        $this->jsonSuccess('SMS configuration saved.');
    }

    public function refillSms(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            $this->jsonError('Invalid refill amount.');
            return;
        }

        $current = (float) Setting::get('sms_balance', '0');
        $newBalance = $current + $amount;
        Setting::set('sms_balance', (string) $newBalance);

        // Log transaction
        $txnId = 'sms_txn_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        Setting::set($txnId, 'Refill: +' . number_format($amount, 2) . ' | Balance: ' . number_format($newBalance, 2));

        $this->jsonSuccess('SMS balance refilled. New balance: Rs. ' . number_format($newBalance, 2));
    }

    // ─── Refill Requests ─────────────────────────────

    public function refillRequests(): void
    {
        $db = Database::connect();
        $requests = $db->fetchAll(
            "SELECT r.*, u.name AS admin_name, u.phone AS admin_phone
             FROM refill_requests r
             JOIN users u ON u.id = r.admin_id
             ORDER BY r.created_at DESC
             LIMIT 50"
        );

        require_once __DIR__ . '/../Views/layouts/app_layout.php';
        renderAppLayout('Refill Requests', __DIR__ . '/../Views/admin/super_admin/refill_requests.php', [
            'requests' => $requests,
        ], 'super_admin.refill_requests');
    }

    public function approveRefill(): void
    {
        $this->requireJson();
        $data = $this->jsonBody();

        $id = (int) ($data['id'] ?? 0);
        $action = $data['action'] ?? ''; // 'approve' or 'reject'
        $superAdminId = (int) ($_SESSION['user_id'] ?? 0);

        if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            $this->jsonError('Invalid request.');
            return;
        }

        $db = Database::connect();
        $req = $db->fetch('SELECT * FROM refill_requests WHERE id = ? AND status = ?', [$id, 'pending']);
        if (!$req) {
            $this->jsonError('Request not found or already processed.');
            return;
        }

        if ($action === 'approve') {
            $amount = (float) ($req['amount'] ?? 0);
            $current = (float) Setting::get('sms_balance', '0');
            Setting::set('sms_balance', (string) ($current + $amount));

            $txnId = 'sms_txn_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
            Setting::set($txnId, 'Refill (request #' . $id . '): +' . number_format($amount, 2) . ' | Balance: ' . number_format($current + $amount, 2));

            $db->execute(
                'UPDATE refill_requests SET status = ?, approved_by = ? WHERE id = ?',
                ['approved', $superAdminId, $id]
            );
            $this->jsonSuccess('Request #' . $id . ' approved. Rs. ' . number_format($amount, 2) . ' added to balance.');
        } else {
            $db->execute(
                'UPDATE refill_requests SET status = ?, approved_by = ? WHERE id = ?',
                ['rejected', $superAdminId, $id]
            );
            $this->jsonSuccess('Request #' . $id . ' rejected.');
        }
    }

    // ─── Helpers ───────────────────────────────────────

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

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
