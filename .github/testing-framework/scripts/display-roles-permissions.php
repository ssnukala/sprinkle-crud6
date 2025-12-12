#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Display Roles and Permissions
 *
 * This script displays all roles and permissions from the database.
 * It uses MySQL CLI directly to avoid UserFrosting bootstrap issues.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php display-roles-permissions.php
 */

// Ensure we're in the right directory
if (!file_exists('vendor/autoload.php')) {
    echo "ERROR: vendor/autoload.php not found. Run this script from the UserFrosting project root.\n";
    exit(1);
}

echo "=========================================\n";
echo "Database Roles and Permissions Display\n";
echo "=========================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Get database credentials from environment
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'userfrosting_test';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root';

echo "📊 Database connection:\n";
echo "   Host: {$dbHost}:{$dbPort}\n";
echo "   Database: {$dbName}\n";
echo "   User: {$dbUser}\n\n";

/**
 * Execute a MySQL query and return results
 */
function executeQuery(string $query, string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPassword): array
{
    $command = sprintf(
        'mysql -h %s -P %s -u %s %s %s -e %s 2>&1',
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
        // Filter out password warning
        $filteredOutput = array_filter($output, function($line) {
            return strpos($line, 'Using a password') === false;
        });
        if (!empty($filteredOutput)) {
            throw new RuntimeException("Query failed: " . implode("\n", $filteredOutput));
        }
    }
    
    return $output;
}

try {
    // Display Roles
    echo "=========================================\n";
    echo "ROLES TABLE\n";
    echo "=========================================\n";
    
    $query = "SELECT id, slug, name, description FROM roles ORDER BY id";
    $output = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    
    if (count($output) <= 1) {
        echo "❌ No roles found in database\n";
    } else {
        echo "Found " . (count($output) - 1) . " role(s):\n\n";
        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
    echo "\n";
    
    // Check specifically for crud6-admin role
    echo "=========================================\n";
    echo "CRUD6-ADMIN ROLE CHECK\n";
    echo "=========================================\n";
    
    $query = "SELECT * FROM roles WHERE slug = 'crud6-admin'";
    $output = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    
    if (count($output) <= 1) {
        echo "❌ crud6-admin role NOT FOUND\n";
    } else {
        echo "✅ crud6-admin role EXISTS:\n";
        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
    echo "\n";
    
    // Display Permissions
    echo "=========================================\n";
    echo "PERMISSIONS TABLE\n";
    echo "=========================================\n";
    
    $query = "SELECT id, slug, name, conditions FROM permissions ORDER BY id";
    $output = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    
    if (count($output) <= 1) {
        echo "❌ No permissions found in database\n";
    } else {
        echo "Found " . (count($output) - 1) . " permission(s):\n\n";
        foreach ($output as $line) {
            echo $line . "\n";
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
        $query = "SELECT slug, name FROM permissions WHERE slug = '{$permSlug}'";
        $output = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
        
        if (count($output) > 1) {
            echo "✅ {$permSlug}: " . ($output[1] ?? '') . "\n";
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
    
    $query = "SELECT r.slug as role_slug, COUNT(pr.permission_id) as permission_count 
              FROM roles r 
              LEFT JOIN permission_role pr ON r.id = pr.role_id 
              GROUP BY r.id, r.slug 
              ORDER BY r.slug";
    $output = executeQuery($query, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword);
    
    if (count($output) <= 1) {
        echo "❌ No role-permission assignments found\n";
    } else {
        echo "Permission counts by role:\n\n";
        foreach ($output as $line) {
            echo $line . "\n";
        }
    }
    echo "\n";
    
    echo "=========================================\n";
    echo "✅ Database display complete\n";
    echo "=========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
