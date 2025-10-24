<?php

/**
 * Database Scanner Usage Examples
 * 
 * This file demonstrates how to use the DatabaseScanner service to detect
 * foreign key relationships in databases that don't have explicit constraints.
 */

use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use Illuminate\Database\DatabaseManager;

// Example 1: Basic table scanning
// ===============================

/** @var DatabaseManager $db */
$db = $ci->get(DatabaseManager::class);
$scanner = new DatabaseScanner($db);

// Scan a single table for relationships
$relationships = $scanner->scanTable('orders');

// Output detected relationships
print_r($relationships);
// Example output:
// [
//     'user_id' => [
//         'table' => 'users',
//         'key' => 'id',
//         'field' => 'user_id',
//         'is_valid' => true,
//         'match_rate' => 0.98,
//         'sampled_count' => 100,
//         'matching_count' => 98,
//         'target_table' => 'users',
//         'target_key' => 'id',
//     ],
//     'product_id' => [
//         'table' => 'products',
//         'key' => 'id',
//         'field' => 'product_id',
//         'is_valid' => true,
//         'match_rate' => 1.0,
//         'sampled_count' => 100,
//         'matching_count' => 100,
//         'target_table' => 'products',
//         'target_key' => 'id',
//     ],
// ]


// Example 2: Scan entire database
// ================================

$scanner = new DatabaseScanner($db);

// Scan all tables (excluding migrations and other system tables)
$databaseRelationships = $scanner->scanDatabase(null, ['migrations', 'cache', 'sessions']);

foreach ($databaseRelationships as $table => $relationships) {
    echo "Table: {$table}\n";
    foreach ($relationships as $field => $info) {
        echo "  - {$field} -> {$info['target_table']}.{$info['target_key']} ";
        echo "(confidence: {$info['match_rate']})\n";
    }
}


// Example 3: Configure scanner behavior
// ======================================

$scanner = new DatabaseScanner($db);

// Set custom naming patterns
$scanner->setForeignKeyPatterns([
    '/_id$/',           // Standard: user_id, product_id
    '/_uuid$/',         // UUID references: user_uuid
    '/^ref_/',          // Prefixed: ref_user, ref_product
    '/_key$/',          // Custom: user_key, product_key
]);

// Adjust sample size (default: 100)
$scanner->setSampleSize(200);

// Adjust validation threshold (default: 0.8 = 80%)
// Only consider relationships where 90% of sampled data matches
$scanner->setValidationThreshold(0.9);

$relationships = $scanner->scanTable('orders');


// Example 4: Generate schema relationships
// =========================================

$scanner = new DatabaseScanner($db);
$relationships = $scanner->scanTable('orders');

// Convert to schema-compatible format
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

// Output for schema definition
print_r($schemaRelationships);
// [
//     'user_id' => [
//         'type' => 'belongsTo',
//         'related' => 'users',
//         'foreign_key' => 'user_id',
//         'owner_key' => 'id',
//         'confidence' => 0.98,
//     ],
//     'product_id' => [
//         'type' => 'belongsTo',
//         'related' => 'products',
//         'foreign_key' => 'product_id',
//         'owner_key' => 'id',
//         'confidence' => 1.0,
//     ],
// ]


// Example 5: Integrate with SchemaService
// ========================================

/** @var SchemaService $schemaService */
$schemaService = $ci->get(SchemaService::class);
$scanner = new DatabaseScanner($db);

// Load existing schema
$schema = $schemaService->getSchema('orders');

// Detect relationships
$relationships = $scanner->scanTable('orders');
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

// Enrich schema with detected relationships
$enrichedSchema = $schemaService->enrichSchemaWithRelationships(
    $schema,
    $schemaRelationships,
    false  // Don't overwrite existing relationships
);

// Now the schema has both manually defined and auto-detected relationships


// Example 6: Multi-database support
// ==================================

$scanner = new DatabaseScanner($db);

// Scan table in specific database connection
$relationships = $scanner->scanTable('analytics_events', 'analytics');

// Scan entire alternate database
$analyticsRelationships = $scanner->scanDatabase('analytics', ['_internal']);


// Example 7: Handling validation results
// =======================================

$scanner = new DatabaseScanner($db);
$relationships = $scanner->scanTable('orders');

foreach ($relationships as $field => $info) {
    if ($info['is_valid']) {
        $confidence = round($info['match_rate'] * 100, 1);
        echo "✓ {$field} -> {$info['target_table']}.{$info['target_key']} ";
        echo "({$confidence}% confidence)\n";
        
        if ($info['match_rate'] < 0.95) {
            echo "  Warning: Lower confidence detected. Check data integrity.\n";
        }
    }
}


// Example 8: Export scan results for documentation
// =================================================

$scanner = new DatabaseScanner($db);
$allRelationships = $scanner->scanDatabase();

// Generate a report
$report = [];
foreach ($allRelationships as $table => $relationships) {
    $report[$table] = [];
    foreach ($relationships as $field => $info) {
        $report[$table][] = [
            'field' => $field,
            'references' => "{$info['target_table']}.{$info['target_key']}",
            'confidence' => round($info['match_rate'] * 100, 1) . '%',
            'samples' => $info['sampled_count'],
        ];
    }
}

// Save as JSON for documentation
file_put_contents('database_relationships.json', json_encode($report, JSON_PRETTY_PRINT));


// Example 9: Create schema files from scan results
// =================================================

$scanner = new DatabaseScanner($db);
$schemaService = $ci->get(SchemaService::class);

// Scan all tables
$allRelationships = $scanner->scanDatabase(null, ['migrations']);

foreach ($allRelationships as $table => $relationships) {
    // Skip if schema already exists
    try {
        $existingSchema = $schemaService->getSchema($table);
        echo "Schema for {$table} already exists, skipping...\n";
        continue;
    } catch (\Exception $e) {
        // Schema doesn't exist, we can create it
    }
    
    // Generate schema relationships
    $schemaRelationships = $scanner->generateSchemaRelationships($relationships);
    
    // Create basic schema structure
    $schema = [
        'model' => $table,
        'table' => $table,
        'fields' => [], // Would need to be filled in manually or with column introspection
        'relationships' => $schemaRelationships,
    ];
    
    // Save schema file
    $schemaPath = "app/schema/crud6/{$table}.json";
    file_put_contents($schemaPath, json_encode($schema, JSON_PRETTY_PRINT));
    
    echo "Created schema for {$table}\n";
}
