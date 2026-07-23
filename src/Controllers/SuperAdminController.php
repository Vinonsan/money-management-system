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
        $smsBalance = \Services\SMSService::getBalance((int) ($_SESSION['user_id'] ?? 0));
        $smsCost = Setting::get('sms_cost_per_message', '0.62');
        $remainingSms = (float)$smsCost > 0 ? floor($smsBalance / (float)$smsCost) : 0;

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
if ($name === '' || $phone === '') {
        $this->jsonError('Name and phone are required.');
        return;
    }
    if (User::phoneExists($phone)) {
        $this->jsonError('Phone already in use.');
        return;
    }

    $db = Database::connect();
    $adminId = (int) $db->insert(
        'INSERT INTO users (name, business_name, phone, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)',
        [$name, $businessName, $phone, $email, 'admin']
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
            $sms = new \Services\SMSService((int) ($_SESSION['user_id'] ?? 0));
            $loginUrl = BASE_URL . '/admin/login';
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
if ($name === '' || $phone === '') {
        $this->jsonError('Name and phone are required.');
        return;
    }
    if (User::phoneExistsExclude($phone, $id)) {
        $this->jsonError('Phone already in use by another user.');
        return;
    }

    $db = Database::connect();
    $db->execute(
        'UPDATE users SET name = ?, business_name = ?, phone = ?, email = ? WHERE id = ? AND role = ?',
        [$name, $businessName, $phone, $email, $id, 'admin']
    );

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
        $smsBalance = \Services\SMSService::getBalance((int) ($_SESSION['user_id'] ?? 0));
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

        $smsCost     = number_format((float) trim((string) ($data['sms_cost_per_message'] ?? '0.62')), 2, '.', '');
        $smsSenderId = trim((string) ($data['smslenz_sender_id'] ?? ''));
        $smsUserId   = trim((string) ($data['smslenz_user_id'] ?? ''));
        $smsApiKey   = trim((string) ($data['smslenz_api_key'] ?? ''));

        if ((float) $smsCost <= 0) {
            $this->jsonError('Valid cost is required.');
            return;
        }

        try {
            Setting::setMany([
                'sms_cost_per_message' => $smsCost,
                'smslenz_sender_id'     => $smsSenderId,
                'smslenz_user_id'       => $smsUserId,
                'smslenz_api_key'       => $smsApiKey,
            ]);

            // Do not report success unless the persisted values can be read back.
            $saved = [
                'sms_cost_per_message' => Setting::get('sms_cost_per_message'),
                'smslenz_sender_id'     => Setting::get('smslenz_sender_id'),
                'smslenz_user_id'       => Setting::get('smslenz_user_id'),
                'smslenz_api_key'       => Setting::get('smslenz_api_key'),
            ];
            if ($saved !== [
                'sms_cost_per_message' => $smsCost,
                'smslenz_sender_id'     => $smsSenderId,
                'smslenz_user_id'       => $smsUserId,
                'smslenz_api_key'       => $smsApiKey,
            ]) {
                throw new \RuntimeException('Saved values could not be verified.');
            }

            $this->jsonSuccess('SMS configuration saved.');
        } catch (\Throwable $e) {
            error_log('SMS configuration save failed: ' . $e->getMessage());
            $this->jsonError('Failed to save SMS configuration to the database.');
        }
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

        $adminId = (int) ($_SESSION['user_id'] ?? 0);
        $current = \Services\SMSService::getBalance($adminId);
        $newBalance = $current + $amount;
        \Services\SMSService::setBalance($newBalance, $adminId);

        // Log transaction
        $txnId = 'sms_txn_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        \Models\Setting::set($txnId, 'Refill: +' . number_format($amount, 2) . ' | Balance: ' . number_format($newBalance, 2));

        $this->jsonSuccess('SMS balance refilled. New balance: Rs. ' . number_format($newBalance, 2));
    }

    // ─── Refill Requests ─────────────────────────────

    public function refillRequests(): void
    {
        $requests = \Models\RefillRequest::all();

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
        $req = \Models\RefillRequest::findPending($id);
        if (!$req) {
            $this->jsonError('Request not found or already processed.');
            return;
        }

        if ($action === 'approve') {
            $amount = (float) ($req['amount'] ?? 0);
            $reqAdminId = (int) ($req['admin_id'] ?? 0);
            // Add to the requesting admin's balance
            $current = \Services\SMSService::getBalance($reqAdminId);
            $newBalance = $current + $amount;
            \Services\SMSService::setBalance($newBalance, $reqAdminId);

            $txnId = 'sms_txn_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
            \Models\Setting::set($txnId, 'Refill (request #' . $id . ' - admin ' . $reqAdminId . '): +' . number_format($amount, 2) . ' | Balance: ' . number_format($newBalance, 2));

            \Models\RefillRequest::setStatus($id, 'approved', $superAdminId);
            $this->jsonSuccess('Request #' . $id . ' approved. Rs. ' . number_format($amount, 2) . ' added to balance.');
        } else {
            \Models\RefillRequest::setStatus($id, 'rejected', $superAdminId);
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
