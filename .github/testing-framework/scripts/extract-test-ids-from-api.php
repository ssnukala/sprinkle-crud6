<?php

/**
 * Extract Test IDs from API List Responses
 * 
 * This script calls the CRUD6 list API for each model and extracts actual IDs
 * from the response. This ensures integration tests use real, existing IDs.
 * 
 * Strategy:
 * 1. Call GET /api/crud6/{model} for each model (list endpoint)
 * 2. Extract IDs from the response rows
 * 3. Select appropriate ID for detail testing:
 *    - For users/groups: Prefer ID 1 (admin/default)
 *    - For other models: Prefer ID != 1 when multiple exist
 *    - Fallback to first available ID
 * 
 * This guarantees that detail API calls (/api/crud6/{model}/{id}) will work
 * because the ID was retrieved from the list API that shows actual database records.
 * 
 * Usage: php extract-test-ids-from-api.php <models_config_file> <base_url> <cookie_file>
 * 
 * Example: php extract-test-ids-from-api.php config.json http://localhost:8080 /tmp/cookies.txt
 * 
 * Output: JSON file with selected test IDs for each model
 */

declare(strict_types=1);

// Parse command line arguments
$configFile = $argv[1] ?? null;
$baseUrl = $argv[2] ?? 'http://localhost:8080';
$cookieFile = $argv[3] ?? null;

if (!$configFile || !file_exists($configFile)) {
    echo "❌ ERROR: Config file not provided or doesn't exist\n";
    echo "Usage: php extract-test-ids-from-api.php <config_file> <base_url> [cookie_file]\n";
    exit(1);
}

// Load configuration
$config = json_decode(file_get_contents($configFile), true);

if (!$config || !isset($config['models'])) {
    echo "❌ ERROR: Invalid config file format\n";
    exit(1);
}

echo "========================================\n";
echo "Extracting Test IDs from API\n";
echo "========================================\n";
echo "Base URL: {$baseUrl}\n";
echo "Cookie file: " . ($cookieFile ?? 'none (assuming already authenticated)') . "\n";
echo "\n";

$selectedIds = [];
$summary = [];
$failedModels = [];

// Process each model
foreach ($config['models'] as $modelConfig) {
    $model = $modelConfig['model'] ?? 'unknown';
    $primaryKey = $modelConfig['primary_key'] ?? 'id';
    $mustUseOne = $modelConfig['must_use_id_1'] ?? false;
    
    echo "Fetching data for {$model}...\n";
    
    // Build API URL
    $apiUrl = rtrim($baseUrl, '/') . "/api/crud6/{$model}";
    
    // Initialize cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Use cookie file for authentication if provided
    if ($cookieFile && file_exists($cookieFile)) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "  ❌ cURL Error: {$curlError}\n";
        $summary[] = "  ❌ {$model}: cURL error";
        $failedModels[] = $model;
        echo "\n";
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "  ❌ HTTP {$httpCode} - API call failed\n";
        $summary[] = "  ❌ {$model}: HTTP {$httpCode}";
        $failedModels[] = $model;
        echo "\n";
        continue;
    }
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "  ❌ Failed to parse JSON response\n";
        $summary[] = "  ❌ {$model}: Invalid JSON response";
        $failedModels[] = $model;
        echo "\n";
        continue;
    }
    
    // Extract rows from response (Sprunje format)
    $rows = $data['rows'] ?? [];
    
    if (empty($rows)) {
        echo "  ⚠️  No records found in API response\n";
        $summary[] = "  ⚠️  {$model}: No records available";
        echo "\n";
        continue;
    }
    
    // Extract IDs from rows
    $availableIds = array_map(function($row) use ($primaryKey) {
        return $row[$primaryKey] ?? null;
    }, $rows);
    
    // Filter out null IDs
    $availableIds = array_filter($availableIds, fn($id) => $id !== null);
    
    if (empty($availableIds)) {
        echo "  ⚠️  No valid IDs found in response\n";
        $summary[] = "  ⚠️  {$model}: No valid IDs";
        echo "\n";
        continue;
    }
    
    $recordCount = count($availableIds);
    echo "  Found {$recordCount} record(s)\n";
    echo "  Available IDs: " . implode(', ', array_slice($availableIds, 0, 10)) . 
         ($recordCount > 10 ? '...' : '') . "\n";
    
    // Determine which ID to use
    $selectedId = null;
    
    if ($mustUseOne) {
        // For users and groups, MUST use ID 1
        if (in_array(1, $availableIds)) {
            $selectedId = 1;
            echo "  ✅ Selected: ID 1 (required for {$model})\n";
            $summary[] = "  ✅ {$model}: ID 1 (required)";
        } else {
            echo "  ⚠️  ID 1 not found but required for {$model}, using first available\n";
            $selectedId = $availableIds[0];
            $summary[] = "  ⚠️  {$model}: ID {$selectedId} (ID 1 preferred but not found)";
        }
    } else {
        // For other models, prefer ID != 1 when possible
        if ($recordCount > 1) {
            // Multiple records exist, prefer something other than ID 1
            $nonOneIds = array_values(array_filter($availableIds, fn($id) => $id != 1));
            if (!empty($nonOneIds)) {
                $selectedId = $nonOneIds[0];
                echo "  ✅ Selected: ID {$selectedId} (prefer non-1 when multiple exist)\n";
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
        
        // Show preview of the selected record
        $selectedRow = null;
        foreach ($rows as $row) {
            if (isset($row[$primaryKey]) && $row[$primaryKey] == $selectedId) {
                $selectedRow = $row;
                break;
            }
        }
        
        if ($selectedRow) {
            $displayFields = array_slice($selectedRow, 0, 3);
            echo "  Record preview: " . json_encode($displayFields) . "\n";
        }
    }
    
    echo "\n";
}

// Display summary
echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
foreach ($summary as $line) {
    echo $line . "\n";
}
echo "\n";

if (!empty($failedModels)) {
    echo "⚠️  WARNING: Some models failed:\n";
    foreach ($failedModels as $model) {
        echo "  - {$model}\n";
    }
    echo "\n";
}

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

// Also save all available IDs for reference
$allIdsFile = dirname($configFile) . '/test-ids-all-available.json';
$allIdsData = [];
foreach ($config['models'] as $modelConfig) {
    $model = $modelConfig['model'] ?? 'unknown';
    if (isset($selectedIds[$model])) {
        $allIdsData[$model] = [
            'selected_id' => $selectedIds[$model],
            'count' => count($availableIds ?? []),
        ];
    }
}
file_put_contents($allIdsFile, json_encode($allIdsData, JSON_PRETTY_PRINT));
echo "✅ Summary saved to: {$allIdsFile}\n";
echo "\n";

exit(0);
