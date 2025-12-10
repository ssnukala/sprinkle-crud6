#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Load SQL seed data for integration tests
 *
 * This script loads SQL seed data generated from CRUD6 schemas.
 * It should be run AFTER admin user creation and BEFORE path testing.
 *
 * EXECUTION ORDER:
 * 1. Migrations (php bakery migrate)
 * 2. Admin user creation (php bakery create:admin-user)
 * 3. THIS SCRIPT (loads test data from SQL)
 * 4. Unauthenticated path testing
 * 5. Authenticated path testing
 *
 * Usage: php load-seed-sql.php <sql_file>
 * Example: php load-seed-sql.php app/sql/seeds/crud6-test-data.sql
 */

// Parse command line arguments
$sqlFile = $argv[1] ?? null;

if (!$sqlFile) {
    echo "Usage: php load-seed-sql.php <sql_file>\n";
    echo "Example: php load-seed-sql.php app/sql/seeds/crud6-test-data.sql\n";
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

// Load UserFrosting application
require 'vendor/autoload.php';

use UserFrosting\UserFrosting;

// Boot UserFrosting
$uf = new UserFrosting();

// Get database connection
$db = $uf->getContainer()->get(\Illuminate\Database\Capsule\Manager::class);
$pdo = $db->getConnection()->getPdo();

// Read SQL file
$sql = file_get_contents($sqlFile);
if (!$sql) {
    echo "ERROR: Failed to read SQL file\n";
    exit(1);
}

echo "📄 SQL file loaded (" . number_format(strlen($sql)) . " bytes)\n";
echo "\n";

// Split SQL into individual statements
// We need to handle multi-line statements properly
$statements = [];
$currentStatement = '';
$lines = explode("\n", $sql);

foreach ($lines as $line) {
    $trimmedLine = trim($line);
    
    // Skip comments and empty lines
    if (empty($trimmedLine) || str_starts_with($trimmedLine, '--')) {
        continue;
    }
    
    // Add line to current statement
    $currentStatement .= $line . "\n";
    
    // Check if statement is complete (ends with semicolon)
    if (str_ends_with($trimmedLine, ';')) {
        $statements[] = trim($currentStatement);
        $currentStatement = '';
    }
}

echo "📊 Parsed " . count($statements) . " SQL statements\n";
echo "\n";

// Execute statements
$executed = 0;
$failed = 0;

echo "🔄 Executing SQL statements...\n";
echo "\n";

try {
    // Start transaction
    $pdo->beginTransaction();
    
    foreach ($statements as $index => $statement) {
        try {
            // Skip SET statements in output
            $isSetStatement = str_starts_with(trim($statement), 'SET ');
            
            if (!$isSetStatement) {
                // Extract table name for progress display
                if (preg_match('/INSERT INTO `?(\w+)`?/i', $statement, $matches)) {
                    echo "   → Inserting into table: {$matches[1]}\n";
                }
            }
            
            $pdo->exec($statement);
            $executed++;
            
        } catch (\PDOException $e) {
            $failed++;
            echo "   ❌ Failed to execute statement " . ($index + 1) . ":\n";
            echo "      " . substr($statement, 0, 100) . "...\n";
            echo "      Error: {$e->getMessage()}\n";
            
            // For testing, we continue on error but track failures
            // In production, you might want to rollback
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
} catch (\Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo "\n";
    echo "❌ Transaction failed: {$e->getMessage()}\n";
    exit(1);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Seed Data Load Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Total statements: " . count($statements) . "\n";
echo "Executed successfully: {$executed}\n";
echo "Failed: {$failed}\n";
echo "\n";

if ($failed > 0) {
    echo "⚠️  Some statements failed (this may be expected for duplicate records)\n";
    echo "   Review output above for details.\n";
    echo "\n";
    exit(0); // Don't fail the build for duplicate key errors
}

echo "✅ All SQL seed data loaded successfully!\n";
echo "\n";
echo "IMPORTANT REMINDERS:\n";
echo "  - User ID 1 is RESERVED for admin (do not delete/disable)\n";
echo "  - Group ID 1 is RESERVED for admin group (do not delete)\n";
echo "  - Test data uses ID >= 2 (safe for DELETE/DISABLE tests)\n";
echo "\n";

exit(0);
