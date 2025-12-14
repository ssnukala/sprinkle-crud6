<?php

/**
 * Validate Test Records Script
 * 
 * This script queries the database to verify that test records with specific IDs exist
 * before running integration tests. It helps diagnose whether 404 errors are due to
 * missing data or code issues.
 * 
 * Usage: php validate-test-records.php <config_file>
 * 
 * The config file should be a JSON file containing:
 * - models: Array of model configurations with model name, table name, primary key, and test IDs
 * 
 * Example config:
 * {
 *   "models": [
 *     {
 *       "model": "users",
 *       "table": "users",
 *       "primary_key": "id",
 *       "test_ids": [100, 101, 102]
 *     }
 *   ]
 * }
 */

declare(strict_types=1);

// Bootstrap UserFrosting
require_once 'app/bootstrap.php';

use UserFrosting\Sprinkle\Core\Core;

// Get config file from command line argument
$configFile = $argv[1] ?? null;

if (!$configFile || !file_exists($configFile)) {
    echo "❌ ERROR: Config file not provided or doesn't exist\n";
    echo "Usage: php validate-test-records.php <config_file>\n";
    exit(1);
}

// Load configuration
$config = json_decode(file_get_contents($configFile), true);

if (!$config || !isset($config['models'])) {
    echo "❌ ERROR: Invalid config file format\n";
    exit(1);
}

// Get database connection
$app = Core::create();
$db = $app->db;

echo "========================================\n";
echo "Validating Test Records\n";
echo "========================================\n";
echo "\n";

$totalModels = 0;
$totalIds = 0;
$foundIds = 0;
$missingIds = 0;
$errors = [];

// Process each model
foreach ($config['models'] as $modelConfig) {
    $model = $modelConfig['model'] ?? 'unknown';
    $table = $modelConfig['table'] ?? $model;
    $primaryKey = $modelConfig['primary_key'] ?? 'id';
    $testIds = $modelConfig['test_ids'] ?? [];
    
    if (empty($testIds)) {
        echo "⚠️  No test IDs configured for model: {$model}\n";
        continue;
    }
    
    $totalModels++;
    
    echo "Checking {$model} (table: {$table}, key: {$primaryKey})\n";
    echo "  Test IDs: " . implode(', ', $testIds) . "\n";
    
    // Query for each test ID
    foreach ($testIds as $testId) {
        $totalIds++;
        
        try {
            $record = $db->table($table)->where($primaryKey, $testId)->first();
            
            if ($record) {
                $foundIds++;
                echo "  ✅ Found: {$primaryKey}={$testId}\n";
                
                // Log some record details for debugging
                $recordArray = (array) $record;
                $displayFields = array_slice($recordArray, 0, 3);
                echo "     Data: " . json_encode($displayFields) . "\n";
            } else {
                $missingIds++;
                echo "  ❌ MISSING: {$primaryKey}={$testId}\n";
                $errors[] = "Missing record: {$model}.{$primaryKey}={$testId}";
            }
        } catch (\Exception $e) {
            $missingIds++;
            echo "  ❌ ERROR querying {$primaryKey}={$testId}: " . $e->getMessage() . "\n";
            $errors[] = "Error querying {$model}.{$primaryKey}={$testId}: " . $e->getMessage();
        }
    }
    
    echo "\n";
}

// Summary
echo "========================================\n";
echo "Validation Summary\n";
echo "========================================\n";
echo "Total models checked: {$totalModels}\n";
echo "Total IDs checked: {$totalIds}\n";
echo "✅ Found: {$foundIds}\n";
echo "❌ Missing: {$missingIds}\n";
echo "\n";

if ($missingIds > 0) {
    echo "ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    echo "⚠️  WARNING: Some test records are missing!\n";
    echo "This may cause 404 errors in integration tests.\n";
    echo "\n";
    exit(0); // Don't fail the workflow, just warn
} else {
    echo "✅ All test records found!\n";
    exit(0);
}
