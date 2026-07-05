<?php
date_default_timezone_set('UTC');

// Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$subfolder = '/php-structure';
define('BASE_URL', $protocol . $host . $subfolder);

// App settings
define('APP_NAME', 'MyApp');

// Session
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_lifetime' => 0, 'cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefixes = [
        'Controllers\\' => __DIR__ . '/../src/Controllers/',
        'Models\\' => __DIR__ . '/../src/Models/',
        'Services\\' => __DIR__ . '/../src/Services/',
        'Middleware\\' => __DIR__ . '/../src/Middleware/',
        'Helpers\\' => __DIR__ . '/../src/Helpers/',
        'Components\\' => __DIR__ . '/../src/Components/'
    ];
    foreach ($prefixes as $prefix => $base_dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $base_dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}