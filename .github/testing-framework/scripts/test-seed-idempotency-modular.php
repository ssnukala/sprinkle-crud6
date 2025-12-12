#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Seed Idempotency Test Script
 *
 * This script tests that seeds are idempotent (can be run multiple times) based on JSON configuration.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php test-seed-idempotency-modular.php <config_file> [after] [expected_counts]
 * Example: php test-seed-idempotency-modular.php integration-test-seeds.json
 */

// Parse command line arguments
$configFile = $argv[1] ?? null;
$mode = $argv[2] ?? 'before';
$expectedCounts = $argv[3] ?? null;

if (!$configFile) {
    echo "Usage: php test-seed-idempotency-modular.php <config_file> [after] [expected_counts]\n";
    echo "Example: php test-seed-idempotency-modular.php integration-test-seeds.json\n";
    exit(1);
}

// Ensure we're in the right directory
if (!file_exists('vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Run this script from the UserFrosting project root.\n";
    exit(1);
}

// Load configuration
if (!file_exists($configFile)) {
    echo "ERROR: Configuration file not found: {$configFile}\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    echo "ERROR: Failed to parse configuration file\n";
    exit(1);
}

// Check if idempotency testing is enabled
if (!($config['validation']['idempotency']['enabled'] ?? false)) {
    echo "Idempotency testing is not enabled in configuration\n";
    exit(0);
}

// Bootstrap UserFrosting 6 application
require 'vendor/autoload.php';

// Bootstrap the UserFrosting application using Bakery (CLI bootstrap method)
use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);
$container = $bakery->getContainer();

use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;

echo "=========================================\n";
echo "Testing Seed Idempotency (Modular)\n";
echo "=========================================\n";
echo "Config file: {$configFile}\n\n";

// Get test seeds from config
$testSeedSprinkles = $config['validation']['idempotency']['test_seeds'] ?? [];

// Collect all countable items from test seeds
$counts = [];
foreach ($testSeedSprinkles as $sprinkleName) {
    if (!isset($config['seeds'][$sprinkleName])) {
        echo "WARNING: Sprinkle '{$sprinkleName}' not found in seeds configuration\n";
        continue;
    }
    
    $sprinkleConfig = $config['seeds'][$sprinkleName];
    
    foreach ($sprinkleConfig['seeds'] ?? [] as $seedConfig) {
        if (!isset($seedConfig['validation'])) {
            continue;
        }
        
        $validation = $seedConfig['validation'];
        
        switch ($validation['type']) {
            case 'role':
                $slug = $validation['slug'];
                $count = Role::where('slug', $slug)->count();
                $counts[$slug] = $count;
                echo "Role '{$slug}': {$count}\n";
                break;
                
            case 'permissions':
                $slugs = $validation['slugs'] ?? [];
                $count = Permission::whereIn('slug', $slugs)->count();
                $key = 'permissions_' . implode('_', array_slice($slugs, 0, 2));
                $counts[$key] = $count;
                echo "Permissions (first {$count}): " . implode(', ', array_slice($slugs, 0, 3)) . "...\n";
                break;
        }
    }
}

// Serialize counts for comparison
$countsString = json_encode($counts);

if ($mode !== 'after') {
    // Before re-seeding - output counts for workflow to capture
    echo "\n=========================================\n";
    echo "ℹ️  Note: Re-run seeds via bakery seed commands\n";
    echo "ℹ️  This script will be called again to verify counts\n";
    echo "=========================================\n";
    echo "BEFORE:{$countsString}\n";
    exit(0);
}

// After re-seeding - compare counts
echo "\n=========================================\n";
echo "Comparing counts after re-seeding\n";
echo "=========================================\n\n";

if (!$expectedCounts) {
    echo "ERROR: Expected counts not provided\n";
    exit(1);
}

$beforeCounts = json_decode($expectedCounts, true);
if (!$beforeCounts) {
    echo "ERROR: Failed to parse expected counts\n";
    exit(1);
}

$allMatch = true;
foreach ($beforeCounts as $key => $beforeCount) {
    $afterCount = $counts[$key] ?? 0;
    
    if ($beforeCount === $afterCount) {
        echo "✅ {$key}: {$afterCount} (unchanged)\n";
    } else {
        echo "❌ {$key}: Before={$beforeCount}, After={$afterCount}\n";
        $allMatch = false;
    }
}

echo "\n=========================================\n";
if ($allMatch) {
    echo "✅ Seeds are idempotent - no duplicates created\n";
    echo "=========================================\n";
    exit(0);
} else {
    echo "❌ Seed counts changed after re-seeding!\n";
    echo "=========================================\n";
    exit(1);
}
