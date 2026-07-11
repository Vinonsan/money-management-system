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

        require_once __DIR__ . '/../Views/layouts/auth_layout.php';
        renderAuthLayout('Staff Login', __DIR__ . '/../Views/auth/login.php', [
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'step' => $_SESSION['otp_step'] ?? 'phone',
            'phone' => $_SESSION['otp_phone'] ?? '',
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
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
            $this->redirectLogin();
        }

        $rateMsg = LoginRateLimit::recordOtpRequest($phone);
        if ($rateMsg !== null) {
            $this->flashError($rateMsg);
            $_SESSION['otp_step'] = 'phone';
            unset($_SESSION['otp_phone']);
            $this->redirectLogin();
        }

        $sms = new SMSService();
        $otp = $sms->generateOtp();
        OtpCode::create($phone, $otp);
        $sms->sendOtp($phone, $otp);

        $_SESSION['otp_step'] = 'otp';
        $_SESSION['otp_phone'] = $phone;
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
            unset($_SESSION['otp_phone']);
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
            // If locked after this fail, reset to phone step
            if (LoginRateLimit::isLocked($phone) !== null) {
                $_SESSION['otp_step'] = 'phone';
                unset($_SESSION['otp_phone']);
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
        unset($_SESSION['otp_step'], $_SESSION['otp_phone'], $_SESSION['flash_error'], $_SESSION['flash_success']);

        header('Location: ' . BASE_URL . '/admin');
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
}
