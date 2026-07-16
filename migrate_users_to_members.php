<?php
/**
 * Migration Script: users → members restructuring
 *
 * Run this ONCE after deploying the new code.
 * 1. Upload to server via FTP/File Manager
 * 2. Access: https://masterbrain.site/migrate_users_to_members.php
 * 3. Delete this file after migration
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/plain');
$db = \Models\Database::connect();

echo "=== MasjidPay DB Migration: users → members ===\n\n";

// ─── 1. Create members table ─────────────────────────────────────────
$db->execute("CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    card_number INT UNSIGNED DEFAULT NULL,
    road_number VARCHAR(100) DEFAULT NULL,
    street VARCHAR(255) DEFAULT NULL,
    location_id INT UNSIGNED DEFAULT NULL,
    ward_id INT UNSIGNED DEFAULT NULL,
    monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_location (location_id),
    INDEX idx_member_ward (ward_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "✓ members table created/verified.\n";

// ─── 2. Add member_id to payments ────────────────────────────────────
$payCols = array_column($db->fetchAll("SHOW COLUMNS FROM payments"), 'Field');
if (!in_array('member_id', $payCols)) {
    $db->execute("ALTER TABLE payments ADD COLUMN member_id INT UNSIGNED DEFAULT NULL AFTER id, ADD INDEX idx_payment_member (member_id)");
    echo "✓ member_id column added to payments.\n";
} else {
    echo "✓ member_id column already exists in payments.\n";
}

// ─── 3. Copy collectors → members ────────────────────────────────────
$collectors = $db->fetchAll("SELECT * FROM users WHERE role = 'collector'");
$migrated = 0;
foreach ($collectors as $c) {
    $existing = $db->fetch("SELECT id FROM members WHERE phone = ?", [$c['phone']]);
    if (!$existing) {
        $db->insert("INSERT INTO members (name, email, phone, card_number, road_number, street, location_id, ward_id, monthly_amount, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $c['name'], $c['email'], $c['phone'], $c['card_number'],
            $c['road_number'], $c['street'], $c['location_id'], $c['ward_id'],
            $c['monthly_amount'], $c['is_active'], $c['created_at'], $c['updated_at']
        ]);
        $migrated++;
    }
}
echo "✓ $migrated collectors migrated to members.\n";

// ─── 4. Link payments to members ─────────────────────────────────────
$unlinked = $db->fetchAll("SELECT p.id AS pid, p.user_id FROM payments p WHERE p.member_id IS NULL OR p.member_id = 0");
$updated = 0;
foreach ($unlinked as $p) {
    $user = $db->fetch("SELECT phone FROM users WHERE id = ?", [$p['user_id']]);
    if ($user) {
        $member = $db->fetch("SELECT id FROM members WHERE phone = ?", [$user['phone']]);
        if ($member) {
            $db->execute("UPDATE payments SET member_id = ? WHERE id = ?", [$member['id'], $p['pid']]);
            $updated++;
        }
    }
}
echo "✓ $updated payments linked to members.\n";

// ─── 5. Link scheduled_messages to members ───────────────────────────
$smCols = array_column($db->fetchAll("SHOW COLUMNS FROM scheduled_messages"), 'Field');
if (!in_array('member_id', $smCols)) {
    $db->execute("ALTER TABLE scheduled_messages ADD COLUMN member_id INT UNSIGNED DEFAULT NULL AFTER id");
    echo "✓ member_id column added to scheduled_messages.\n";
}

$smUnlinked = $db->fetchAll("SELECT sm.id, sm.user_id FROM scheduled_messages sm WHERE sm.member_id IS NULL OR sm.member_id = 0");
$smUpdated = 0;
foreach ($smUnlinked as $sm) {
    $user = $db->fetch("SELECT phone FROM users WHERE id = ?", [$sm['user_id']]);
    if ($user) {
        $member = $db->fetch("SELECT id FROM members WHERE phone = ?", [$user['phone']]);
        if ($member) {
            $db->execute("UPDATE scheduled_messages SET member_id = ? WHERE id = ?", [$member['id'], $sm['id']]);
            $smUpdated++;
        }
    }
}
echo "✓ $smUpdated scheduled_messages linked to members.\n";

// ─── 6. Cleanup old user_id columns ─────────────────────────────────
echo "\n✓ Removing old collector accounts from users table...\n";
$db->execute("DELETE FROM users WHERE role = 'collector'");
echo "✓ Collector users removed.\n";

// Optionally drop old user_id columns
echo "\nNote: To drop old user_id columns, run manually:\n";
echo "  ALTER TABLE payments DROP COLUMN user_id;\n";
echo "  ALTER TABLE scheduled_messages DROP COLUMN user_id;\n";

echo "\n=== Migration Complete! 🎯 ===\n";
echo "Check your data, then DELETE this file for security.\n";
