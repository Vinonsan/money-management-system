<?php
/**
 * Web-Accessible Migration Trigger
 *
 * Usage (after deploying from release-live branch):
 *   https://masterbrain.site/migrate.php?token=YOUR_DEPLOY_SECRET
 *
 * This thin entry point sits inside the document root (public/) and
 * delegates to the actual migration logic in deploy/migrate.php,
 * which is above the document root and not directly accessible.
 *
 * The migration runner:
 *   - Reads deploy/migrations.sql
 *   - Computes a SHA-256 hash
 *   - Checks the _schema_migrations tracking table
 *   - Applies only new/changed migrations
 *   - Records the hash for idempotent re-runs
 */

require_once __DIR__ . '/../deploy/migrate.php';
