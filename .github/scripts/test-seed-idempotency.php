#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Seed Idempotency Test Script
 *
 * This script tests that CRUD6 seeds are idempotent (can be run multiple times).
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php .github/scripts/test-seed-idempotency.php
 */

// Ensure we're in the right directory
if (!file_exists('vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Run this script from the UserFrosting project root.\n";
    exit(1);
}

// Bootstrap UserFrosting
require 'vendor/autoload.php';

use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection using environment variables
$capsule = new Capsule();
$capsule->addConnection([
    'driver' => getenv('DB_CONNECTION') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'userfrosting_test',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=========================================\n";
echo "Testing Seed Idempotency\n";
echo "=========================================\n\n";

// Count records before re-seeding
echo "Counting records before re-seeding...\n";
$roleCount = Role::where('slug', 'crud6-admin')->count();
$expectedPermissions = ['create_crud6', 'delete_crud6', 'update_crud6_field', 'uri_crud6', 'uri_crud6_list', 'view_crud6_field'];
$permCount = Permission::whereIn('slug', $expectedPermissions)->count();
echo "crud6-admin roles: {$roleCount}\n";
echo "CRUD6 permissions: {$permCount}\n";

$beforeCounts = "{$roleCount},{$permCount}";

// Note: We can't re-run seeds from this script - that's done by the workflow
// This script just counts before/after

echo "\n=========================================\n";
echo "ℹ️  Note: Re-run seeds via bakery seed commands\n";
echo "ℹ️  This script will be called again to verify counts\n";
echo "=========================================\n";

// Store counts for comparison
if (!isset($argv[1]) || $argv[1] !== 'after') {
    // Before re-seeding - output counts for workflow to capture
    echo "BEFORE:{$beforeCounts}\n";
    exit(0);
}

// After re-seeding - compare counts
echo "\nCounting records after re-seeding...\n";
$afterRoleCount = Role::where('slug', 'crud6-admin')->count();
$afterPermCount = Permission::whereIn('slug', $expectedPermissions)->count();
echo "crud6-admin roles: {$afterRoleCount}\n";
echo "CRUD6 permissions: {$afterPermCount}\n";

$afterCounts = "{$afterRoleCount},{$afterPermCount}";

// Get expected counts from argument
if (!isset($argv[2])) {
    echo "ERROR: Expected counts not provided\n";
    exit(1);
}

$expectedCounts = $argv[2];

if ($expectedCounts !== $afterCounts) {
    echo "\nERROR: Seed counts changed after re-seeding!\n";
    echo "Before: {$expectedCounts}\n";
    echo "After: {$afterCounts}\n";
    exit(1);
}

echo "\n✅ Seeds are idempotent - no duplicates created\n";

echo "\n=========================================\n";
echo "✅ Seed idempotency test passed\n";
echo "=========================================\n";

exit(0);
