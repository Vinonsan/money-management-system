<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? $path : '/';

if (str_starts_with($path, '/assets/')) {
    $asset = __DIR__ . '/public' . $path;
    if (is_file($asset)) {
        $type = mime_content_type($asset) ?: 'application/octet-stream';
        header('Content-Type: ' . $type);
        readfile($asset);
        return true;
    }
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
