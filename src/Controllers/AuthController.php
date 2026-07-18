<?php
namespace Controllers;

use Models\LoginRateLimit;
use Models\OtpCode;
use Models\User;
use Services\SMSService;

class AuthController
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        if (isset($_GET['reset'])) {
            unset($_SESSION['otp_step'], $_SESSION['otp_phone'], $_SESSION['flash_success']);
        }

        // Check lock status for pre-filled phone
        $lockSeconds = 0;
        if (!empty($_SESSION['otp_phone'])) {
            $lockSeconds = LoginRateLimit::getLockRemainingSeconds($_SESSION['otp_phone']);
        }

        require_once __DIR__ . '/../Views/layouts/auth_layout.php';
        renderAuthLayout('Staff Login', __DIR__ . '/../Views/auth/login.php', [
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'step' => $_SESSION['otp_step'] ?? 'phone',
            'phone' => $_SESSION['otp_phone'] ?? '',
            'otp_sent_at' => $_SESSION['otp_sent_at'] ?? 0,
            'lock_seconds' => $lockSeconds,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function showSuperAdminLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            if (($_SESSION['user_role'] ?? '') === 'super_admin') {
                header('Location: ' . BASE_URL . '/super-admin');
            } else {
                header('Location: ' . BASE_URL . '/admin');
            }
            exit;
        }

        if (isset($_GET['reset'])) {
            unset($_SESSION['sa_otp_step'], $_SESSION['sa_otp_phone'], $_SESSION['flash_success']);
        }

        // Check lock status for pre-filled phone
        $lockSeconds = 0;
        if (!empty($_SESSION['sa_otp_phone'])) {
            $lockSeconds = LoginRateLimit::getLockRemainingSeconds($_SESSION['sa_otp_phone']);
        }

        require_once __DIR__ . '/../Views/layouts/super_admin_auth_layout.php';
        renderSuperAdminAuthLayout('Super Admin Login', __DIR__ . '/../Views/auth/super_admin_login.php', [
            'error'   => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'step'    => $_SESSION['sa_otp_step'] ?? 'phone',
            'phone'   => $_SESSION['sa_otp_phone'] ?? '',
            'otp_sent_at' => $_SESSION['sa_otp_sent_at'] ?? 0,
            'lock_seconds' => $lockSeconds,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function requestSuperAdminOtp(): void
    {
        $this->assertPost();
        $this->assertCsrf();

        $phone = $this->normalizePhone($_POST['phone'] ?? '');

        if ($phone === '' || !$this->isValidPhone($phone)) {
            $this->flashError('Enter a valid phone number (e.g. 0754476969).');
            $this->redirectSuperAdminLogin();
        }

        $lockMsg = LoginRateLimit::isLocked($phone);
        if ($lockMsg !== null) {
            $this->flashError($lockMsg);
            $_SESSION['sa_otp_step'] = 'phone';
            $_SESSION['sa_otp_phone'] = $phone;
            $this->redirectSuperAdminLogin();
        }

        $user = User::findByPhone($phone);
        if (!$user) {
            $this->flashError('If this number is registered, an OTP will be sent.');
            $_SESSION['sa_otp_step'] = 'phone';
            $_SESSION['sa_otp_phone'] = $phone;
            $this->redirectSuperAdminLogin();
        }

        // Only super_admin role allowed
        if ($user['role'] !== 'super_admin') {
            $this->flashError('This portal is only for Super Administrators.');
            $_SESSION['sa_otp_step'] = 'phone';
            $_SESSION['sa_otp_phone'] = $phone;
            $this->redirectSuperAdminLogin();
        }

        $rateMsg = LoginRateLimit::recordOtpRequest($phone);
        if ($rateMsg !== null) {
            $this->flashError($rateMsg);
            $_SESSION['sa_otp_step'] = 'phone';
            $_SESSION['sa_otp_phone'] = $phone;
            unset($_SESSION['sa_otp_sent_at']);
            $this->redirectSuperAdminLogin();
        }

        $sms = new SMSService();
        $otp = $sms->generateOtp();
        OtpCode::create($phone, $otp);
        $sent = $sms->sendOtp($phone, $otp);

        if (!$sent && $sms->isConfigured()) {
            $this->flashError('Failed to send SMS. Please check SMS configuration (Sender ID) in Super Admin panel.');
            $_SESSION['sa_otp_step'] = 'phone';
            unset($_SESSION['sa_otp_phone']);
            $this->redirectSuperAdminLogin();
        }

        $_SESSION['sa_otp_step'] = 'otp';
        $_SESSION['sa_otp_phone'] = $phone;
        $_SESSION['sa_otp_sent_at'] = time();
        $_SESSION['flash_success'] = 'OTP sent successfully. Check your phone.';
        $this->redirectSuperAdminLogin();
    }

    public function verifySuperAdminOtp(): void
    {
        $this->assertPost();
        $this->assertCsrf();

        $phone = $this->normalizePhone($_POST['phone'] ?? ($_SESSION['sa_otp_phone'] ?? ''));
        $otp = trim((string) ($_POST['otp'] ?? ''));

        if ($phone === '' || !$this->isValidPhone($phone)) {
            $this->flashError('Session expired. Enter your phone number again.');
            $_SESSION['sa_otp_step'] = 'phone';
            unset($_SESSION['sa_otp_phone'], $_SESSION['sa_otp_sent_at']);
            $this->redirectSuperAdminLogin();
        }

        $lockMsg = LoginRateLimit::isLocked($phone);
        if ($lockMsg !== null) {
            $this->flashError($lockMsg);
            $_SESSION['sa_otp_step'] = 'phone';
            $_SESSION['sa_otp_phone'] = $phone;
            $this->redirectSuperAdminLogin();
        }

        if ($otp === '' || !preg_match('/^\d{6}$/', $otp)) {
            $this->flashError('Enter the 6-digit OTP.');
            $_SESSION['sa_otp_step'] = 'otp';
            $_SESSION['sa_otp_phone'] = $phone;
            $this->redirectSuperAdminLogin();
        }

        $user = User::findByPhone($phone);
        if (!$user || !OtpCode::verify($phone, $otp)) {
            $msg = LoginRateLimit::recordFailedLogin($phone);
            $this->flashError($msg);
            if (LoginRateLimit::isLocked($phone) !== null) {
                $_SESSION['sa_otp_step'] = 'phone';
                $_SESSION['sa_otp_phone'] = $phone;
                unset($_SESSION['sa_otp_sent_at']);
            } else {
                $_SESSION['sa_otp_step'] = 'otp';
                $_SESSION['sa_otp_phone'] = $phone;
            }
            $this->redirectSuperAdminLogin();
        }

        LoginRateLimit::clearOnSuccess($phone);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_location_id'] = $user['location_id'] ? (int) $user['location_id'] : null;
        unset($_SESSION['sa_otp_step'], $_SESSION['sa_otp_phone'], $_SESSION['flash_error'], $_SESSION['flash_success']);

        header('Location: ' . BASE_URL . '/super-admin');
        exit;
    }

    public function requestOtp(): void
    {
        $this->assertPost();
        $this->assertCsrf();

        $phone = $this->normalizePhone($_POST['phone'] ?? '');

        if ($phone === '' || !$this->isValidPhone($phone)) {
            $this->flashError('Enter a valid phone number (e.g. 0754476969).');
            $this->redirectLogin();
        }

        $lockMsg = LoginRateLimit::isLocked($phone);
        if ($lockMsg !== null) {
            $this->flashError($lockMsg);
            $_SESSION['otp_step'] = 'phone';
            $_SESSION['otp_phone'] = $phone;
            $this->redirectLogin();
        }

        $user = User::findByPhone($phone);
        if (!$user) {
            // Same message to avoid user enumeration
            $this->flashError('If this number is registered, an OTP will be sent.');
            $_SESSION['otp_step'] = 'phone';
            $this->redirectLogin();
        }

        // Only admin-side roles for now (super_admin reserved for later)
        if (!in_array($user['role'], ['super_admin', 'admin', 'collector'], true)) {
            $this->flashError('You are not allowed to access admin login.');
            $_SESSION['otp_step'] = 'phone';
            $_SESSION['otp_phone'] = $phone;
            $this->redirectLogin();
        }

        $rateMsg = LoginRateLimit::recordOtpRequest($phone);
        if ($rateMsg !== null) {
            $this->flashError($rateMsg);
            $_SESSION['otp_step'] = 'phone';
            $_SESSION['otp_phone'] = $phone;
            unset($_SESSION['otp_sent_at']);
            $this->redirectLogin();
        }

        $sms = new SMSService();
        $otp = $sms->generateOtp();
        OtpCode::create($phone, $otp);
        $sent = $sms->sendOtp($phone, $otp);

        if (!$sent && $sms->isConfigured()) {
            $this->flashError('Failed to send SMS. Please check SMS configuration (Sender ID) in Super Admin panel.');
            $_SESSION['otp_step'] = 'phone';
            unset($_SESSION['otp_phone']);
            $this->redirectLogin();
        }

        $_SESSION['otp_step'] = 'otp';
        $_SESSION['otp_phone'] = $phone;
        $_SESSION['otp_sent_at'] = time();
        $_SESSION['flash_success'] = 'OTP sent successfully. Check your phone.';
        $this->redirectLogin();
    }

    public function verifyOtp(): void
    {
        $this->assertPost();
        $this->assertCsrf();

        $phone = $this->normalizePhone($_POST['phone'] ?? ($_SESSION['otp_phone'] ?? ''));
        $otp = trim((string) ($_POST['otp'] ?? ''));

        if ($phone === '' || !$this->isValidPhone($phone)) {
            $this->flashError('Session expired. Enter your phone number again.');
            $_SESSION['otp_step'] = 'phone';
            unset($_SESSION['otp_phone']);
            $this->redirectLogin();
        }

        $lockMsg = LoginRateLimit::isLocked($phone);
        if ($lockMsg !== null) {
            $this->flashError($lockMsg);
            $_SESSION['otp_step'] = 'phone';
            $_SESSION['otp_phone'] = $phone;
            $this->redirectLogin();
        }

        if ($otp === '' || !preg_match('/^\d{6}$/', $otp)) {
            $this->flashError('Enter the 6-digit OTP.');
            $_SESSION['otp_step'] = 'otp';
            $_SESSION['otp_phone'] = $phone;
            $this->redirectLogin();
        }

        $user = User::findByPhone($phone);
        if (!$user || !OtpCode::verify($phone, $otp)) {
            $msg = LoginRateLimit::recordFailedLogin($phone);
            $this->flashError($msg);
            if (LoginRateLimit::isLocked($phone) !== null) {
                $_SESSION['otp_step'] = 'phone';
                $_SESSION['otp_phone'] = $phone;
                unset($_SESSION['otp_sent_at']);
            } else {
                $_SESSION['otp_step'] = 'otp';
                $_SESSION['otp_phone'] = $phone;
            }
            $this->redirectLogin();
        }

        LoginRateLimit::clearOnSuccess($phone);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_location_id'] = $user['location_id'] ? (int) $user['location_id'] : null;
        unset($_SESSION['otp_step'], $_SESSION['otp_phone'], $_SESSION['flash_error'], $_SESSION['flash_success']);

        // Redirect super_admin to super admin portal
        $redirect = ($user['role'] === 'super_admin') ? '/super-admin' : '/admin';
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';
        $phone = preg_replace('/^\+94/', '0', $phone) ?? $phone;
        $phone = preg_replace('/^94/', '0', $phone) ?? $phone;
        return $phone;
    }

    private function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^0\d{9}$/', $phone);
    }

    private function assertPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }
    }

    private function assertCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->flashError('Invalid session. Please try again.');
            $this->redirectLogin();
        }
    }

    private function flashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    private function redirectLogin(): void
    {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    private function redirectSuperAdminLogin(): void
    {
        header('Location: ' . BASE_URL . '/super-admin/login');
        exit;
    }
}
