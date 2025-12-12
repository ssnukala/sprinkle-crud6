#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Seed Idempotency Test Script
 *
 * This script tests that seeds are idempotent (can be run multiple times) based on JSON configuration.
 * It uses MySQL CLI directly to avoid UserFrosting bootstrap and CSRF issues.
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

echo "=========================================\n";
echo "Testing Seed Idempotency (Modular)\n";
echo "=========================================\n";
echo "Config file: {$configFile}\n\n";

// Get database credentials from environment
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'userfrosting_test';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root';

/**
 * Execute a MySQL query and return results
 */
function executeQuery(string $query, string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPassword): array
{
    $command = sprintf(
        'mysql -h %s -P %s -u %s %s %s -N -e %s 2>&1',
        escapeshellarg($dbHost),
        escapeshellarg($dbPort),
        escapeshellarg($dbUser),
        !empty($dbPassword) ? '-p' . escapeshellarg($dbPassword) : '',
        escapeshellarg($dbName),
        escapeshellarg($query)
    );
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Filter out MySQL password warning from output
    $output = array_values(array_filter($output, function ($line) {
        return strpos($line, 'Using a password') === false;
    }));
    
    if ($returnCode !== 0) {
        throw new RuntimeException("Query failed: " . implode("\n", $output));
    }
    
    return $output;
}

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
        
        try {
            switch ($validation['type']) {
                case 'role':
                    $slug = $validation['slug'];
                    $query = "SELECT COUNT(*) FROM roles WHERE slug = '{$slug}'";
                    $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                    $count = (int)($result[0] ?? 0);
                    $counts[$slug] = $count;
                    echo "Role '{$slug}': {$count}\n";
                    break;
                    
                case 'permissions':
                    $slugs = $validation['slugs'] ?? [];
                    $slugList = "'" . implode("','", $slugs) . "'";
                    $query = "SELECT COUNT(*) FROM permissions WHERE slug IN ({$slugList})";
                    $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                    $count = (int)($result[0] ?? 0);
                    $key = 'permissions_' . implode('_', array_slice($slugs, 0, 2));
                    $counts[$key] = $count;
                    echo "Permissions (first {$count}): " . implode(', ', array_slice($slugs, 0, 3)) . "...\n";
                    break;
            }
        } catch (Exception $e) {
            echo "ERROR: Failed to count {$validation['type']}: " . $e->getMessage() . "\n";
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
