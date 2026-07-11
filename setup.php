<?php
require_once __DIR__ . '/config/database.php';
echo '<h2>MasjidPay Setup</h2>';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    echo "<p style='color:green'>Database '" . DB_NAME . "' ready.</p>";
    echo "<p>Admin seeded: <strong>vinonsan</strong> / phone <strong>0754476969</strong></p>";
    echo "<p>Login OTP (dev): <strong>111111</strong></p>";
    echo '<p><a href="/masjidpay/login">Go to Login →</a></p>';
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . '</p>';
}
