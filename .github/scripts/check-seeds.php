#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Seed Validation Script
 *
 * This script validates that CRUD6 seeds have been run successfully.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php check-seeds.php
 */

// Ensure we're in the right directory
if (!file_exists('vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Run this script from the UserFrosting project root.\n";
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
echo "Validating CRUD6 Seed Data\n";
echo "=========================================\n\n";

// Validate crud6-admin role was created
echo "Checking crud6-admin role...\n";
$role = Role::where('slug', 'crud6-admin')->first();
if (!$role) {
    echo "ERROR: crud6-admin role not found\n";
    exit(1);
}
echo "✅ crud6-admin role exists\n";
echo "   Name: " . $role->name . "\n";
echo "   Description: " . $role->description . "\n\n";

// Validate CRUD6 permissions were created
echo "Checking CRUD6 permissions...\n";
$expectedPermissions = ['create_crud6', 'delete_crud6', 'update_crud6_field', 'uri_crud6', 'uri_crud6_list', 'view_crud6_field'];
$allFound = true;
foreach ($expectedPermissions as $slug) {
    $perm = Permission::where('slug', $slug)->first();
    if (!$perm) {
        echo "ERROR: Permission {$slug} not found\n";
        $allFound = false;
    } else {
        echo "✅ {$slug} permission exists\n";
    }
}
if (!$allFound) {
    exit(1);
}

// Validate permissions are assigned to crud6-admin role
echo "\nChecking permission assignments to crud6-admin role...\n";
$role = Role::where('slug', 'crud6-admin')->first();
if (!$role) {
    echo "ERROR: crud6-admin role not found\n";
    exit(1);
}
$permCount = $role->permissions()->count();
if ($permCount < 6) {
    echo "ERROR: crud6-admin role should have at least 6 permissions, found {$permCount}\n";
    exit(1);
}
echo "✅ crud6-admin role has {$permCount} permissions assigned\n";

// Validate permissions are assigned to site-admin role  
echo "\nChecking permission assignments to site-admin role...\n";
$siteAdminRole = Role::where('slug', 'site-admin')->first();
if (!$siteAdminRole) {
    echo "WARNING: site-admin role not found (expected in Account sprinkle)\n";
} else {
    $crud6Perms = $siteAdminRole->permissions()->whereIn('slug', $expectedPermissions)->count();
    if ($crud6Perms < 6) {
        echo "ERROR: site-admin role should have CRUD6 permissions, found {$crud6Perms}\n";
        exit(1);
    }
    echo "✅ site-admin role has CRUD6 permissions ({$crud6Perms} permissions)\n";
}

echo "\n=========================================\n";
echo "✅ All CRUD6 seed data validated successfully\n";
echo "=========================================\n";

exit(0);
