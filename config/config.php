<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

// ─── Environment Detection ──────────────────────────────────────────────
// Auto-detect: local dev uses /php-structure, production uses document root
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal  = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
$subfolder = $isLocal ? '/php-structure' : '';

define('BASE_URL', rtrim($protocol . $host . $subfolder, '/'));

// ─── App Settings ───────────────────────────────────────────────────────
define('APP_NAME', 'MyApp');

// ─── Deployment Secret (used by deploy/migrate.php) ─────────────────────
// CHANGE THIS to a random string before production deployment.
define('DEPLOY_SECRET', 'change-this-to-a-secure-random-token');

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