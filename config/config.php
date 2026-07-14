<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Colombo');

// ─── Environment Detection ──────────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal  = in_array($host, ['localhost', '127.0.0.1', '::1'], true);

// PHP built-in server serves from /public — no subfolder needed
$isBuiltInServer = PHP_SAPI === 'cli-server';
// When using XAMPP, the project subfolder matches the htdocs folder name
$projectFolder = basename(dirname(__DIR__)); // money-management-system-1
$subfolder = ($isLocal && !$isBuiltInServer) ? '/' . $projectFolder : '';

define('BASE_URL', rtrim($protocol . $host . $subfolder, '/'));

// ─── App Settings ───────────────────────────────────────────────────────
define('APP_NAME', 'MasjidPay');

// ─── OTP / SMS (SMS API later; fixed OTP for now) ───────────────────────
define('OTP_DEV_CODE', '111111');
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_MAX_REQUESTS', 3);          // 3 OTP requests → lock 1 minute
define('OTP_LOCK_SECONDS', 60);
define('LOGIN_FAIL_MAX', 2);            // 2 failed OTP verifies → next lock
define('LOGIN_FAIL_LOCK_SECONDS', 300); // 5 minutes
define('LOGIN_ESCALATED_LOCK_SECONDS', 3600); // 1 hour

// ─── Deployment Secret (used by deploy/migrate.php) ─────────────────────
// CHANGE THIS to a random string before production deployment.
define('DEPLOY_SECRET', 'change-this-to-a-secure-random-token');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/theme.php';

// ─── Session ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Autoloader ─────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Controllers\\' => __DIR__ . '/../src/Controllers/',
        'Models\\'      => __DIR__ . '/../src/Models/',
        'Services\\'    => __DIR__ . '/../src/Services/',
        'Middleware\\'  => __DIR__ . '/../src/Middleware/',
        'Helpers\\'    => __DIR__ . '/../src/Helpers/',
        'Components\\' => __DIR__ . '/../src/Components/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ─── Global Helpers ─────────────────────────────────────────────────────
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
