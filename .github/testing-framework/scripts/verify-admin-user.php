#!/usr/bin/env php
<?php

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Verify Admin User Script
 * 
 * This script verifies that the admin user was created successfully in the database.
 * It checks the users table and provides detailed diagnostics.
 * 
 * Usage: php verify-admin-user.php [username]
 * Example: php verify-admin-user.php admin
 */

declare(strict_types=1);

// Load UserFrosting
require_once __DIR__ . '/../../../app/bootstrap.php';

use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Core\Core;

$username = $argv[1] ?? 'admin';

echo "\n";
echo "=========================================\n";
echo "Verifying Admin User in Database\n";
echo "=========================================\n";
echo "Username: {$username}\n";
echo "\n";

try {
    // Boot the application
    $app = Core::getInstance();
    
    // Query for the user
    echo "🔍 Querying database for user '{$username}'...\n";
    $user = User::where('user_name', $username)->first();
    
    if (!$user) {
        echo "❌ ERROR: User '{$username}' not found in database!\n";
        echo "\n";
        echo "Checking all users in database:\n";
        $allUsers = User::all();
        if ($allUsers->isEmpty()) {
            echo "⚠️  No users found in the database at all!\n";
        } else {
            echo "Found {$allUsers->count()} user(s):\n";
            foreach ($allUsers as $u) {
                echo "  - ID: {$u->id}, Username: {$u->user_name}, Email: {$u->email}\n";
            }
        }
        echo "\n";
        exit(1);
    }
    
    // User exists - show details
    echo "✅ User found in database!\n";
    echo "\n";
    echo "User Details:\n";
    echo "  - ID: {$user->id}\n";
    echo "  - Username: {$user->user_name}\n";
    echo "  - Email: {$user->email}\n";
    echo "  - First Name: {$user->first_name}\n";
    echo "  - Last Name: {$user->last_name}\n";
    echo "  - Enabled: " . ($user->flag_enabled ? 'Yes' : 'No') . "\n";
    echo "  - Verified: " . ($user->flag_verified ? 'Yes' : 'No') . "\n";
    echo "\n";
    
    // Check if user has a password
    if (empty($user->password)) {
        echo "❌ ERROR: User has no password set!\n";
        exit(1);
    }
    echo "✅ User has password set (hash: " . substr($user->password, 0, 20) . "...)\n";
    
    // Check if user is enabled
    if (!$user->flag_enabled) {
        echo "⚠️  WARNING: User is not enabled!\n";
    }
    
    // Check if user is verified
    if (!$user->flag_verified) {
        echo "⚠️  WARNING: User is not verified!\n";
    }
    
    // Check user roles
    echo "\n";
    echo "Checking user roles...\n";
    $roles = $user->roles()->get();
    if ($roles->isEmpty()) {
        echo "⚠️  WARNING: User has no roles assigned!\n";
    } else {
        echo "✅ User has {$roles->count()} role(s):\n";
        foreach ($roles as $role) {
            echo "  - {$role->name} (slug: {$role->slug})\n";
        }
    }
    
    // Check for Site Administrator role
    $isSiteAdmin = $roles->contains('slug', 'site-admin');
    if (!$isSiteAdmin) {
        echo "⚠️  WARNING: User is NOT a Site Administrator!\n";
    } else {
        echo "✅ User has Site Administrator role\n";
    }
    
    echo "\n";
    echo "=========================================\n";
    echo "✅ Admin User Verification Complete\n";
    echo "=========================================\n";
    echo "\n";
    
    exit(0);
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}
