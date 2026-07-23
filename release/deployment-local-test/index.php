<?php
declare(strict_types=1);

// Root front controller for shared hosting and LiteSpeed/cPanel.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? trim($path, '/') : '';

$_GET['route'] = $path;

require __DIR__ . '/public/index.php';
