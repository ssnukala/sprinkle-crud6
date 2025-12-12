#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Display Roles and Permissions
 *
 * This script displays all roles and permissions from the database using Eloquent.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php display-roles-permissions.php
 */

// Ensure we're in the right directory
if (!file_exists('vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Run this script from the UserFrosting project root.\n";
    exit(1);
}

// Bootstrap UserFrosting 6 application
require 'vendor/autoload.php';

use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;

$bakery = new Bakery(MyApp::class);
$container = $bakery->getContainer();

echo "=========================================\n";
echo "Database Roles and Permissions Display\n";
echo "=========================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get database connection info
    $db = $container->get(\Illuminate\Database\Capsule\Manager::class);
    $connection = $db->getConnection();
    
    echo "📊 Database connection:\n";
    echo "   Driver: " . $connection->getDriverName() . "\n";
    echo "   Database: " . $connection->getDatabaseName() . "\n";
    echo "   Host: " . $connection->getConfig('host') . "\n";
    echo "   Table Prefix: " . $connection->getTablePrefix() . "\n\n";

    // Display Roles
    echo "=========================================\n";
    echo "ROLES TABLE\n";
    echo "=========================================\n";

    $roles = Role::orderBy('id')->get();
    
    if ($roles->isEmpty()) {
        echo "❌ No roles found in database\n";
    } else {
        echo "Found " . $roles->count() . " role(s):\n\n";
        echo sprintf("%-5s %-20s %-30s %s\n", "ID", "Slug", "Name", "Description");
        echo str_repeat("-", 100) . "\n";
        foreach ($roles as $role) {
            echo sprintf(
                "%-5s %-20s %-30s %s\n",
                $role->id,
                $role->slug,
                substr($role->name, 0, 30),
                substr($role->description ?? '', 0, 40)
            );
        }
    }
    echo "\n";

    // Check specifically for crud6-admin role
    echo "=========================================\n";
    echo "CRUD6-ADMIN ROLE CHECK\n";
    echo "=========================================\n";

    $crud6AdminRole = Role::where('slug', 'crud6-admin')->first();
    
    if ($crud6AdminRole === null) {
        echo "❌ crud6-admin role NOT FOUND\n";
    } else {
        echo "✅ crud6-admin role EXISTS:\n";
        echo "   ID: {$crud6AdminRole->id}\n";
        echo "   Slug: {$crud6AdminRole->slug}\n";
        echo "   Name: {$crud6AdminRole->name}\n";
        echo "   Description: {$crud6AdminRole->description}\n";
        $permCount = $crud6AdminRole->permissions()->count();
        echo "   Permissions: {$permCount}\n";
    }
    echo "\n";

    // Display Permissions
    echo "=========================================\n";
    echo "PERMISSIONS TABLE\n";
    echo "=========================================\n";

    $permissions = Permission::orderBy('id')->get();
    
    if ($permissions->isEmpty()) {
        echo "❌ No permissions found in database\n";
    } else {
        echo "Found " . $permissions->count() . " permission(s):\n\n";
        echo sprintf("%-5s %-30s %-40s %s\n", "ID", "Slug", "Name", "Conditions");
        echo str_repeat("-", 120) . "\n";
        foreach ($permissions as $perm) {
            echo sprintf(
                "%-5s %-30s %-40s %s\n",
                $perm->id,
                substr($perm->slug, 0, 30),
                substr($perm->name, 0, 40),
                substr($perm->conditions ?? '', 0, 20)
            );
        }
    }
    echo "\n";

    // Check specifically for CRUD6 permissions
    echo "=========================================\n";
    echo "CRUD6 PERMISSIONS CHECK\n";
    echo "=========================================\n";

    $crud6Permissions = [
        'create_crud6',
        'delete_crud6',
        'update_crud6_field',
        'uri_crud6',
        'uri_crud6_list',
        'view_crud6_field'
    ];

    $foundCount = 0;
    foreach ($crud6Permissions as $permSlug) {
        $perm = Permission::where('slug', $permSlug)->first();
        
        if ($perm !== null) {
            echo "✅ {$permSlug}: {$perm->name}\n";
            $foundCount++;
        } else {
            echo "❌ {$permSlug}: NOT FOUND\n";
        }
    }

    echo "\nSummary: {$foundCount}/" . count($crud6Permissions) . " CRUD6 permissions found\n";
    echo "\n";

    // Display Role-Permission assignments
    echo "=========================================\n";
    echo "ROLE-PERMISSION ASSIGNMENTS\n";
    echo "=========================================\n";

    $allRoles = Role::orderBy('slug')->get();
    
    if ($allRoles->isEmpty()) {
        echo "❌ No role-permission assignments found\n";
    } else {
        echo "Permission counts by role:\n\n";
        echo sprintf("%-20s %s\n", "Role Slug", "Permission Count");
        echo str_repeat("-", 50) . "\n";
        foreach ($allRoles as $role) {
            $permCount = $role->permissions()->count();
            echo sprintf("%-20s %d\n", $role->slug, $permCount);
        }
    }
    echo "\n";

    echo "=========================================\n";
    echo "✅ Database display complete\n";
    echo "=========================================\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
