#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Seed Runner Script
 *
 * This script runs seeds based on a JSON configuration file.
 * It's designed to be run from the UserFrosting 6 project root directory.
 *
 * Usage: php run-seeds.php <config_file> [sprinkle_name]
 * Example: php run-seeds.php integration-test-seeds.json
 * Example: php run-seeds.php integration-test-seeds.json crud6
 */

// Parse command line arguments
$configFile = $argv[1] ?? null;
$sprinkleFilter = $argv[2] ?? null;

if (!$configFile) {
    echo "Usage: php run-seeds.php <config_file> [sprinkle_name]\n";
    echo "Example: php run-seeds.php integration-test-seeds.json\n";
    echo "Example: php run-seeds.php integration-test-seeds.json crud6\n";
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
echo "Running Seeds from Configuration\n";
echo "=========================================\n";
echo "Config file: {$configFile}\n";
if ($sprinkleFilter) {
    echo "Sprinkle filter: {$sprinkleFilter}\n";
}
echo "\n";

// Collect seeds to run (ordered by sprinkle order)
$seedsToRun = [];
$sprinkles = $config['seeds'] ?? [];

// Sort sprinkles by order
uasort($sprinkles, function($a, $b) {
    return ($a['order'] ?? 999) <=> ($b['order'] ?? 999);
});

foreach ($sprinkles as $sprinkleName => $sprinkleConfig) {
    // Apply sprinkle filter if specified
    if ($sprinkleFilter && $sprinkleName !== $sprinkleFilter) {
        continue;
    }
    
    echo "Sprinkle: {$sprinkleName}\n";
    echo "Description: " . ($sprinkleConfig['description'] ?? 'N/A') . "\n\n";
    
    foreach ($sprinkleConfig['seeds'] ?? [] as $seedConfig) {
        $seedsToRun[] = [
            'sprinkle' => $sprinkleName,
            'class' => $seedConfig['class'],
            'description' => $seedConfig['description'] ?? '',
            'required' => $seedConfig['required'] ?? false
        ];
    }
}

if (empty($seedsToRun)) {
    echo "No seeds to run\n";
    exit(0);
}

echo "Found " . count($seedsToRun) . " seeds to run\n\n";

// Run each seed using bakery
$totalSeeds = 0;
$successSeeds = 0;
$failedSeeds = 0;

foreach ($seedsToRun as $seedInfo) {
    $totalSeeds++;
    
    echo "=========================================\n";
    echo "Running seed {$totalSeeds}/" . count($seedsToRun) . "\n";
    echo "Sprinkle: " . $seedInfo['sprinkle'] . "\n";
    echo "Class: " . $seedInfo['class'] . "\n";
    echo "Description: " . $seedInfo['description'] . "\n";
    echo "Required: " . ($seedInfo['required'] ? 'Yes' : 'No') . "\n";
    echo "=========================================\n";
    
    // Build bakery seed command
    $command = "php bakery seed " . escapeshellarg($seedInfo['class']) . " --force 2>&1";
    
    // Execute command
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Display output
    echo implode("\n", $output) . "\n";
    
    if ($returnCode === 0) {
        echo "✅ Seed completed successfully\n\n";
        $successSeeds++;
    } else {
        echo "❌ Seed failed with return code: {$returnCode}\n\n";
        $failedSeeds++;
        
        // If seed is required and failed, exit
        if ($seedInfo['required']) {
            echo "ERROR: Required seed failed. Exiting.\n";
            exit(1);
        }
    }
}

// Print summary
echo "=========================================\n";
echo "Seed Summary\n";
echo "=========================================\n";
echo "Total seeds: {$totalSeeds}\n";
echo "Successful: {$successSeeds}\n";
echo "Failed: {$failedSeeds}\n";
echo "\n";

if ($failedSeeds > 0) {
    echo "⚠️  Some seeds failed\n";
    exit(1);
}

echo "✅ All seeds completed successfully\n";
echo "\n";

// Create admin user if configured
if (isset($config['admin_user']) && ($config['admin_user']['enabled'] ?? false)) {
    echo "=========================================\n";
    echo "Creating Admin User\n";
    echo "=========================================\n";
    
    $adminConfig = $config['admin_user'];
    $username = $adminConfig['username'] ?? 'admin';
    $password = $adminConfig['password'] ?? 'admin123';
    $email = $adminConfig['email'] ?? 'admin@example.com';
    $firstName = $adminConfig['firstName'] ?? 'Admin';
    $lastName = $adminConfig['lastName'] ?? 'User';
    
    echo "Username: {$username}\n";
    echo "Email: {$email}\n";
    echo "First Name: {$firstName}\n";
    echo "Last Name: {$lastName}\n";
    echo "\n";
    
    // Build create:admin-user command
    // Note: Password is passed via command line for simplicity in CI testing
    // This is acceptable since the password is already in the config file and CI logs
    // For production use, consider using environment variables or interactive input
    $command = sprintf(
        "php bakery create:admin-user --username=%s --password=%s --email=%s --firstName=%s --lastName=%s 2>&1",
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($email),
        escapeshellarg($firstName),
        escapeshellarg($lastName)
    );
    
    // Execute command
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Display output
    echo implode("\n", $output) . "\n";
    
    if ($returnCode === 0) {
        echo "✅ Admin user created successfully\n\n";
    } else {
        // Check if user already exists
        $outputText = implode("\n", $output);
        if (strpos($outputText, 'already exists') !== false || strpos($outputText, 'User with username') !== false) {
            echo "⚠️  Admin user already exists (this is OK)\n\n";
        } else {
            echo "❌ Admin user creation failed with return code: {$returnCode}\n";
            echo "ERROR: Failed to create admin user. Exiting.\n";
            exit(1);
        }
    }
}

exit(0);
