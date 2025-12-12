#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Load SQL seed data for integration tests using MySQL directly
 *
 * This script loads SQL seed data generated from CRUD6 schemas by running
 * the SQL file directly through MySQL CLI. This bypasses UserFrosting entirely,
 * avoiding any session/CSRF issues and following the intended design of the
 * generate-seed-sql.js script.
 *
 * EXECUTION ORDER:
 * 1. Migrations (php bakery migrate)
 * 2. Admin user creation (php bakery create:admin-user)
 * 3. THIS SCRIPT (loads test data from SQL via MySQL)
 * 4. Unauthenticated path testing
 * 5. Authenticated path testing
 *
 * Usage: php load-seed-sql.php <sql_file>
 * Example: php load-seed-sql.php seed-data.sql
 */

// Parse command line arguments
$sqlFile = $argv[1] ?? null;

if (!$sqlFile) {
    echo "Usage: php load-seed-sql.php <sql_file>\n";
    echo "Example: php load-seed-sql.php seed-data.sql\n";
    exit(1);
}

if (!file_exists($sqlFile)) {
    echo "ERROR: SQL file not found: {$sqlFile}\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Loading CRUD6 SQL Seed Data\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "SQL file: {$sqlFile}\n";
echo "\n";

// Get database credentials from environment (set by .env file)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'userfrosting_test';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root';

echo "📊 Database connection:\n";
echo "   Host: {$dbHost}:{$dbPort}\n";
echo "   Database: {$dbName}\n";
echo "   User: {$dbUser}\n";
echo "\n";

// Build MySQL command
// Use mysql CLI directly as recommended by generate-seed-sql.js
$command = sprintf(
    'mysql -h %s -P %s -u %s %s %s < %s 2>&1',
    escapeshellarg($dbHost),
    escapeshellarg($dbPort),
    escapeshellarg($dbUser),
    !empty($dbPassword) ? '-p' . escapeshellarg($dbPassword) : '',
    escapeshellarg($dbName),
    escapeshellarg($sqlFile)
);

echo "🔄 Executing SQL via MySQL CLI...\n";
echo "\n";

// Execute the command
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Filter out MySQL password warning from output
$output = array_filter($output, function ($line) {
    return strpos($line, 'Using a password') === false;
});

// Display output
if (!empty($output)) {
    foreach ($output as $line) {
        echo "   {$line}\n";
    }
    echo "\n";
}

if ($returnCode !== 0) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "❌ SQL execution failed!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Return code: {$returnCode}\n";
    echo "\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ SQL seed data loaded successfully!\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
echo "IMPORTANT REMINDERS:\n";
echo "  - IDs 1-99 are RESERVED for PHP seed data (roles, permissions, groups, etc.)\n";
echo "  - Test data uses IDs >= 100 (safe for DELETE/DISABLE tests)\n";
echo "  - User ID 1 is the admin user (created by bakery create:admin-user)\n";
echo "\n";

exit(0);
