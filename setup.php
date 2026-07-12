<?php
require_once __DIR__ . '/config/database.php';
echo '<h2>MasjidPay Setup</h2>';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Upgrade installations created before wards had a separate ward number.
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . DB_NAME . '`');
    $usersTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if ($usersTableExists && !(bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'road_number'")->fetchColumn()) {
        $pdo->exec('ALTER TABLE users ADD COLUMN road_number VARCHAR(100) DEFAULT NULL AFTER phone');
    }
    if ($usersTableExists && !(bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'street'")->fetchColumn()) {
        $pdo->exec('ALTER TABLE users ADD COLUMN street VARCHAR(255) DEFAULT NULL AFTER road_number');
    }
    $wardsTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'wards'")->fetchColumn();
    $wardNumberExists = $wardsTableExists && (bool) $pdo->query("SHOW COLUMNS FROM wards LIKE 'ward_number'")->fetchColumn();
    if ($wardsTableExists && !$wardNumberExists) {
        $pdo->exec('ALTER TABLE wards ADD COLUMN ward_number INT UNSIGNED NULL AFTER location_id');
        $pdo->exec('UPDATE wards SET ward_number = id WHERE ward_number IS NULL');
        $pdo->exec('ALTER TABLE wards MODIFY ward_number INT UNSIGNED NOT NULL');
        $pdo->exec('ALTER TABLE wards ADD UNIQUE KEY uq_ward_location_number (location_id, ward_number)');
    }

    // Upgrade the former one-location-per-ward structure to a many-to-many relation.
    $wardLocationsExists = (bool) $pdo->query("SHOW TABLES LIKE 'ward_locations'")->fetchColumn();
    if ($wardsTableExists && !$wardLocationsExists) {
        $pdo->exec('CREATE TABLE ward_locations (
            ward_id INT UNSIGNED NOT NULL,
            location_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (ward_id, location_id),
            INDEX idx_ward_locations_location (location_id),
            CONSTRAINT fk_ward_locations_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE CASCADE,
            CONSTRAINT fk_ward_locations_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('INSERT IGNORE INTO ward_locations (ward_id, location_id) SELECT id, location_id FROM wards');
        $pdo->exec('ALTER TABLE wards DROP FOREIGN KEY fk_ward_location');
        $pdo->exec('ALTER TABLE wards DROP INDEX idx_ward_location');
        $pdo->exec('ALTER TABLE wards DROP INDEX uq_ward_location_number');
        $pdo->exec('ALTER TABLE wards DROP COLUMN location_id, DROP COLUMN name, DROP COLUMN description');
    }

    $wardNumberIndexExists = $wardsTableExists && (bool) $pdo->query("SHOW INDEX FROM wards WHERE Key_name = 'uq_ward_number'")->fetchColumn();
    if ($wardsTableExists && !$wardNumberIndexExists) {
        // If legacy locations used the same number, merge them into one ward
        // before making ward numbers globally unique.
        $pdo->exec('INSERT IGNORE INTO ward_locations (ward_id, location_id)
            SELECT canonical.id, wl.location_id
            FROM ward_locations wl
            INNER JOIN wards source ON source.id = wl.ward_id
            INNER JOIN (SELECT ward_number, MIN(id) AS id FROM wards GROUP BY ward_number) canonical
                ON canonical.ward_number = source.ward_number');
        $pdo->exec('DELETE duplicate FROM wards duplicate
            INNER JOIN wards canonical
                ON duplicate.ward_number = canonical.ward_number AND duplicate.id > canonical.id');
        $pdo->exec('ALTER TABLE wards ADD UNIQUE KEY uq_ward_number (ward_number)');
    }

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    echo "<p style='color:green'>Database '" . DB_NAME . "' ready.</p>";
    echo "<p>Admin seeded: <strong>vinonsan</strong> / phone <strong>0754476969</strong></p>";
    echo "<p>Login OTP (dev): <strong>111111</strong></p>";
    echo '<p><a href="/masjidpay/login">Go to Login →</a></p>';
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . '</p>';
}
