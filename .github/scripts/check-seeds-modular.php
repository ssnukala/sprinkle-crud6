#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Seed Validation Script
 *
 * This script validates that seeds have been run successfully based on a JSON configuration.
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

// Bootstrap UserFrosting 6 application
require 'vendor/autoload.php';

// Bootstrap the UserFrosting application using Bakery (CLI bootstrap method)
// This follows the same pattern as the bakery CLI tool in UserFrosting 6
use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);
$container = $bakery->getContainer();

use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;

echo "=========================================\n";
echo "Validating Seed Data (Modular)\n";
echo "=========================================\n";
echo "Config file: {$configFile}\n\n";

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
    
    switch ($validation['type']) {
        case 'role':
            $slug = $validation['slug'];
            $expectedCount = $validation['expected_count'] ?? 1;
            
            $count = Role::where('slug', $slug)->count();
            
            if ($count === $expectedCount) {
                $role = Role::where('slug', $slug)->first();
                echo "✅ Role '{$slug}' exists (count: {$count})\n";
                echo "   Name: " . $role->name . "\n";
                echo "   Description: " . $role->description . "\n";
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
            
            $count = Permission::whereIn('slug', $slugs)->count();
            
            if ($count === $expectedCount) {
                echo "✅ Found {$count} permissions (expected {$expectedCount})\n";
                
                foreach ($slugs as $permSlug) {
                    $perm = Permission::where('slug', $permSlug)->first();
                    if ($perm) {
                        echo "   ✅ {$permSlug}\n";
                    } else {
                        echo "   ❌ {$permSlug} NOT FOUND\n";
                        $failedValidations++;
                        $totalValidations++;
                    }
                }
                
                // Validate role assignments if specified
                if (isset($validation['role_assignments'])) {
                    echo "\n   Checking role assignments...\n";
                    
                    foreach ($validation['role_assignments'] as $roleSlug => $expectedPermCount) {
                        $role = Role::where('slug', $roleSlug)->first();
                        
                        if (!$role) {
                            echo "   ⚠️  Role '{$roleSlug}' not found (may be expected in some setups)\n";
                            continue;
                        }
                        
                        $assignedPerms = $role->permissions()->whereIn('slug', $slugs)->count();
                        
                        if ($assignedPerms >= $expectedPermCount) {
                            echo "   ✅ Role '{$roleSlug}' has {$assignedPerms} permissions (expected >= {$expectedPermCount})\n";
                        } else {
                            echo "   ❌ Role '{$roleSlug}' has {$assignedPerms} permissions (expected >= {$expectedPermCount})\n";
                            $failedValidations++;
                            $totalValidations++;
                        }
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
