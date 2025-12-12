#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Seed Validation Script
 *
 * This script validates that seeds have been run successfully based on a JSON configuration.
 * It uses MySQL CLI directly to avoid UserFrosting bootstrap and CSRF issues.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php check-seeds-modular.php <config_file>
 * Example: php check-seeds-modular.php integration-test-seeds.json
 */

// Parse command line arguments
$configFile = $argv[1] ?? null;

if (!$configFile) {
    echo "Usage: php check-seeds-modular.php <config_file>\n";
    echo "Example: php check-seeds-modular.php integration-test-seeds.json\n";
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

echo "=========================================\n";
echo "Validating Seed Data (Modular)\n";
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
    
    if ($returnCode !== 0) {
        throw new RuntimeException("Query failed: " . implode("\n", $output));
    }
    
    return $output;
}

// DIAGNOSTIC: Display all rows from roles and permissions tables BEFORE validation
echo "=========================================\n";
echo "DIAGNOSTIC: Database State Before Validation\n";
echo "=========================================\n\n";

try {
    echo "📊 All Roles in Database:\n";
    $query = "SELECT id, slug, name FROM roles ORDER BY id";
    $roles = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    echo "   Total count: " . count($roles) . "\n";
    foreach ($roles as $role) {
        echo "   {$role}\n";
    }
    echo "\n";
    
    echo "📊 All Permissions in Database:\n";
    $query = "SELECT id, slug, name FROM permissions ORDER BY id";
    $permissions = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    echo "   Total count: " . count($permissions) . "\n";
    foreach ($permissions as $perm) {
        echo "   {$perm}\n";
    }
    echo "\n";
    
    // Specific check for crud6-admin role
    echo "🔍 Specific Query for crud6-admin role:\n";
    $query = "SELECT COUNT(*) FROM roles WHERE slug = 'crud6-admin'";
    $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    $count = (int)($result[0] ?? 0);
    echo "   Count: {$count}\n";
    
    if ($count > 0) {
        $query = "SELECT id, slug, name, description FROM roles WHERE slug = 'crud6-admin' LIMIT 1";
        $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
        if (!empty($result)) {
            echo "   ✅ Found: {$result[0]}\n";
        }
    } else {
        echo "   ❌ NOT FOUND\n";
    }
    echo "\n";
    
    // Check CRUD6 permissions
    echo "🔍 CRUD6 Permissions Count:\n";
    $crud6Permissions = ['create_crud6', 'delete_crud6', 'update_crud6_field', 'uri_crud6', 'uri_crud6_list', 'view_crud6_field'];
    $slugList = "'" . implode("','", $crud6Permissions) . "'";
    $query = "SELECT COUNT(*) FROM permissions WHERE slug IN ({$slugList})";
    $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    $count = (int)($result[0] ?? 0);
    echo "   Count: {$count}/6\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "⚠️  Error during diagnostic: " . $e->getMessage() . "\n\n";
}

echo "=========================================\n";
echo "Starting Validation Checks\n";
echo "=========================================\n\n";

$totalValidations = 0;
$passedValidations = 0;
$failedValidations = 0;

// Get all seeds from config (ordered)
$allSeeds = [];
foreach ($config['seeds'] ?? [] as $sprinkleName => $sprinkleConfig) {
    foreach ($sprinkleConfig['seeds'] ?? [] as $seedConfig) {
        if (isset($seedConfig['validation'])) {
            $allSeeds[] = [
                'sprinkle' => $sprinkleName,
                'config' => $seedConfig
            ];
        }
    }
}

// Validate each seed
foreach ($allSeeds as $seedInfo) {
    $sprinkleName = $seedInfo['sprinkle'];
    $seedConfig = $seedInfo['config'];
    $validation = $seedConfig['validation'];
    
    echo "Checking: " . $seedConfig['description'] . "\n";
    echo "Sprinkle: {$sprinkleName}\n";
    
    $totalValidations++;
    
    try {
        switch ($validation['type']) {
            case 'role':
                $slug = $validation['slug'];
                $expectedCount = $validation['expected_count'] ?? 1;
                
                $query = "SELECT COUNT(*) FROM roles WHERE slug = '{$slug}'";
                $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                $count = (int)($result[0] ?? 0);
                
                if ($count === $expectedCount) {
                    // Get role details
                    $query = "SELECT name, description FROM roles WHERE slug = '{$slug}' LIMIT 1";
                    $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                    $roleData = $result[0] ?? '';
                    
                    echo "✅ Role '{$slug}' exists (count: {$count})\n";
                    if ($roleData) {
                        $parts = explode("\t", $roleData);
                        echo "   Name: " . ($parts[0] ?? 'N/A') . "\n";
                        echo "   Description: " . ($parts[1] ?? 'N/A') . "\n";
                    }
                    $passedValidations++;
                } else {
                    echo "❌ Role '{$slug}' count mismatch. Expected: {$expectedCount}, Found: {$count}\n";
                    $failedValidations++;
                }
                echo "\n";
                break;
                
            case 'permissions':
                $slugs = $validation['slugs'] ?? [];
                $expectedCount = $validation['expected_count'] ?? count($slugs);
                
                $slugList = "'" . implode("','", $slugs) . "'";
                $query = "SELECT COUNT(*) FROM permissions WHERE slug IN ({$slugList})";
                $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                $count = (int)($result[0] ?? 0);
                
                if ($count === $expectedCount) {
                    echo "✅ Found {$count} permissions (expected {$expectedCount})\n";
                    
                    // Check each permission
                    foreach ($slugs as $permSlug) {
                        $query = "SELECT slug FROM permissions WHERE slug = '{$permSlug}' LIMIT 1";
                        $result = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
                        if (!empty($result)) {
                            echo "   ✅ {$permSlug}\n";
                        } else {
                            echo "   ❌ {$permSlug} NOT FOUND\n";
                            $failedValidations++;
                            $totalValidations++;
                        }
                    }
                    
                    $passedValidations++;
                } else {
                    echo "❌ Permission count mismatch. Expected: {$expectedCount}, Found: {$count}\n";
                    $failedValidations++;
                }
                echo "\n";
                break;
                
            default:
                echo "⚠️  Unknown validation type: " . $validation['type'] . "\n\n";
        }
    } catch (Exception $e) {
        echo "❌ Validation failed: " . $e->getMessage() . "\n\n";
        $failedValidations++;
    }
}

// Print summary
echo "=========================================\n";
echo "Validation Summary\n";
echo "=========================================\n";
echo "Total validations: {$totalValidations}\n";
echo "Passed: {$passedValidations}\n";
echo "Failed: {$failedValidations}\n";
echo "\n";

if ($failedValidations > 0) {
    echo "❌ Some validations failed\n";
    exit(1);
} else {
    echo "✅ All seed data validated successfully\n";
    exit(0);
}
