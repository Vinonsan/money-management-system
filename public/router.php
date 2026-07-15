<?php
/**
 * PHP built-in server router.
 * Mimics .htaccess rewrite: serve existing files, route everything else to index.php.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve existing files directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    return false;
}

// Route everything else to the front controller
$_GET['route'] = ltrim($uri, '/');
$_SERVER['REQUEST_URI'] = '/index.php?route=' . urlencode($_GET['route']);

require __DIR__ . '/index.php';
