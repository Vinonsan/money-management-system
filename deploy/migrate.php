<?php
/**
 * Database Migration Runner
 *
 * Usage (triggered from the web-accessible entry point):
 *   https://masterbrain.site/migrate.php?token=YOUR_SECRET
 *
 * This script is loaded by public/migrate.php (which sits inside the
 * document root). It lives above the document root for security and
 * is never accessed directly by a web request.
 *
 * How it works:
 *   1. Reads deploy/migrations.sql
 *   2. Computes a SHA-256 hash of the file
 *   3. Checks _schema_migrations tracking table
 *   4. Applies only new/changed SQL statements (IF NOT EXISTS guards)
 *   5. Records the hash for idempotent re-runs
 *
 * Security:
 *   - Protected by a secret token defined in config.php (DEPLOY_SECRET)
 *   - Only applies migrations that haven't been applied yet
 *   - Safe to run multiple times (idempotent — no data loss)
 */

require_once __DIR__ . '/../config/config.php';

// ─── Token Authentication ───────────────────────────────────────────────
$providedToken = $_GET['token'] ?? '';
if (empty($providedToken) || $providedToken !== DEPLOY_SECRET) {
    http_response_code(403);
    die("Forbidden: Invalid or missing deployment token.");
}

// ─── Only allow POST or explicit token in query string ──────────────────
// Token in URL is acceptable for non-SSH environments.
// For extra security, restrict by IP or use a one-time token.

// ─── Connect to Database ────────────────────────────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}

// ─── Ensure migration tracking table exists ─────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS _schema_migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration_hash VARCHAR(64) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ─── Compute hash of current migration file ─────────────────────────────
$migrationFile = __DIR__ . '/migrations.sql';

if (!file_exists($migrationFile)) {
    die("No migration file found at: deploy/migrations.sql");
}

$currentSql = file_get_contents($migrationFile);
$currentHash = hash('sha256', $currentSql);

// ─── Check if this migration has already been applied ───────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM _schema_migrations WHERE migration_hash = ?");
$stmt->execute([$currentHash]);
$row = $stmt->fetch();

if ($row['cnt'] > 0) {
    echo "Schema is up to date. No migrations to apply.\n";
    exit(0);
}

// ─── Parse and Apply Migrations ─────────────────────────────────────────
// Split SQL file into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $currentSql)),
    fn($line) => !empty($line) && !str_starts_with($line, '--') && !str_starts_with($line, '#')
);

$applied = 0;
$errors  = [];

foreach ($statements as $sql) {
    // Skip pure comment lines and empty blocks
    if (empty($sql) || preg_match('/^\s*(--|#)/', $sql)) {
        continue;
    }

    try {
        $pdo->exec($sql);
        $applied++;
    } catch (PDOException $e) {
        // Check for "already exists" / "duplicate" errors to allow idempotent runs
        $code = $e->getCode();
        $msg  = $e->getMessage();

        // MySQL error 1050: Table already exists
        // MySQL error 1060: Duplicate column name
        // MySQL error 1061: Duplicate key name
        if (in_array($code, ['42S21', '42S22', '42000'], true) ||
            str_contains($msg, 'already exists') ||
            str_contains($msg, 'Duplicate column') ||
            str_contains($msg, 'Duplicate key')) {
            $applied++;
            continue;
        }

        $errors[] = $msg;
    }
}

// ─── Record migration hash ──────────────────────────────────────────────
if (empty($errors)) {
    $stmt = $pdo->prepare("INSERT INTO _schema_migrations (migration_hash) VALUES (?)");
    $stmt->execute([$currentHash]);
    echo "Migration completed successfully. {$applied} statement(s) applied.\n";
} else {
    http_response_code(500);
    echo "Migration completed with errors:\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
    echo "\n{$applied} statement(s) applied successfully before errors.\n";
    exit(1);
}
