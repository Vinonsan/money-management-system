<?php
require_once __DIR__ . '/config/database.php';
echo '<h2>MasjidPay Setup</h2>';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if not exists
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');

    // ─── Run schema.sql (handles both fresh installs and upgrades via IF NOT EXISTS / ON DUPLICATE KEY) ───
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);

    echo "<p style='color:green'>Database '" . DB_NAME . "' ready.</p>";
    echo "<p>Admin seeded: <strong>vinonsan</strong> / phone <strong>0754476969</strong></p>";
    echo "<p>Super Admin seeded: <strong>vinonsan</strong> / phone <strong>0758311995</strong></p>";
    echo "<p>Login OTP: <strong>Secure 6-digit random OTP via SMS</strong></p>";
    echo '<p><a href="/masjidpay/login">Staff Login →</a> | <a href="/masjidpay/super-admin/login">Super Admin Login →</a></p>';
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . '</p>';
}
