<?php

/**
 * Determine Test IDs Script
 * 
 * This script queries the database to find appropriate record IDs for integration testing.
 * Instead of hardcoding ID 100, it dynamically selects IDs based on actual data.
 * 
 * Strategy:
 * - For users and groups: MUST use ID 1 (admin user and default group)
 * - For other models: Prefer ID != 1 when multiple records exist
 * - Fallback to first available ID if no alternatives exist
 * 
 * Usage: php determine-test-ids.php <models_config_file>
 * 
 * Output: JSON file with selected test IDs for each model
 * 
 * Example output:
 * {
 *   "users": 1,
 *   "groups": 1,
 *   "roles": 2,
 *   "permissions": 3,
 *   "activities": 100
 * }
 */

declare(strict_types=1);

// Bootstrap UserFrosting
// This script assumes it's run from the userfrosting directory (where app/bootstrap.php exists)
$bootstrapPath = 'app/bootstrap.php';
if (!file_exists($bootstrapPath)) {
    echo "❌ ERROR: Cannot find UserFrosting bootstrap file at {$bootstrapPath}\n";
    echo "Please run this script from the userfrosting project root directory.\n";
    exit(1);
}

require_once $bootstrapPath;

use UserFrosting\Sprinkle\Core\Core;

// Get config file from command line argument
$configFile = $argv[1] ?? null;

if (!$configFile || !file_exists($configFile)) {
    echo "❌ ERROR: Config file not provided or doesn't exist\n";
    echo "Usage: php determine-test-ids.php <config_file>\n";
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
echo "Determining Test IDs\n";
echo "========================================\n";
echo "\n";

$selectedIds = [];
$summary = [];

// Process each model
foreach ($config['models'] as $modelConfig) {
    $model = $modelConfig['model'] ?? 'unknown';
    $table = $modelConfig['table'] ?? $model;
    $primaryKey = $modelConfig['primary_key'] ?? 'id';
    $mustUseOne = $modelConfig['must_use_id_1'] ?? false;
    
    echo "Analyzing {$model} (table: {$table})\n";
    
    try {
        // Get all IDs from the table, ordered by primary key
        $records = $db->table($table)
            ->select($primaryKey)
            ->orderBy($primaryKey, 'asc')
            ->limit(10) // Get first 10 records for analysis
            ->get();
        
        if ($records->isEmpty()) {
            echo "  ⚠️  No records found - skipping\n";
            $summary[] = "  ⚠️  {$model}: No records available";
            echo "\n";
            continue;
        }
        
        $availableIds = $records->pluck($primaryKey)->toArray();
        $recordCount = $db->table($table)->count();
        
        echo "  Found {$recordCount} record(s)\n";
        echo "  Available IDs: " . implode(', ', $availableIds) . "\n";
        
        // Determine which ID to use
        $selectedId = null;
        
        if ($mustUseOne) {
            // For users and groups, MUST use ID 1
            if (in_array(1, $availableIds)) {
                $selectedId = 1;
                echo "  ✅ Selected: ID 1 (required for {$model})\n";
                $summary[] = "  ✅ {$model}: ID 1 (required)";
            } else {
                echo "  ❌ ERROR: ID 1 not found but required for {$model}\n";
                $summary[] = "  ❌ {$model}: ID 1 required but not found";
            }
        } else {
            // For other models, prefer ID != 1 when possible
            if ($recordCount > 1) {
                // Multiple records exist, prefer something other than ID 1
                $nonOneIds = array_filter($availableIds, fn($id) => $id != 1);
                if (!empty($nonOneIds)) {
                    $selectedId = reset($nonOneIds);
                    echo "  ✅ Selected: ID {$selectedId} (prefer non-1 when multiple records exist)\n";
                    $summary[] = "  ✅ {$model}: ID {$selectedId} (non-1 preferred)";
                } else {
                    // All IDs are 1 (unlikely), use first available
                    $selectedId = $availableIds[0];
                    echo "  ✅ Selected: ID {$selectedId} (only ID available)\n";
                    $summary[] = "  ✅ {$model}: ID {$selectedId} (only option)";
                }
            } else {
                // Only one record exists, use it
                $selectedId = $availableIds[0];
                echo "  ✅ Selected: ID {$selectedId} (only record available)\n";
                $summary[] = "  ✅ {$model}: ID {$selectedId} (single record)";
            }
        }
        
        if ($selectedId !== null) {
            $selectedIds[$model] = $selectedId;
            
            // Query the record to show some context
            $record = $db->table($table)->where($primaryKey, $selectedId)->first();
            if ($record) {
                $recordArray = (array) $record;
                $displayFields = array_slice($recordArray, 0, 3);
                echo "  Record preview: " . json_encode($displayFields) . "\n";
            }
        }
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        $summary[] = "  ❌ {$model}: Error - " . $e->getMessage();
        echo "\n";
    }
}

// Display summary
echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
foreach ($summary as $line) {
    echo $line . "\n";
}
echo "\n";

// Output JSON result
echo "========================================\n";
echo "Selected Test IDs (JSON)\n";
echo "========================================\n";
echo json_encode($selectedIds, JSON_PRETTY_PRINT) . "\n";
echo "\n";

// Save to file
$outputFile = dirname($configFile) . '/test-ids-selected.json';
file_put_contents($outputFile, json_encode($selectedIds, JSON_PRETTY_PRINT));
echo "✅ Saved to: {$outputFile}\n";
echo "\n";

exit(0);
