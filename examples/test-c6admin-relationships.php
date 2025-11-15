#!/usr/bin/env php
<?php
/**
 * Test script to validate c6admin schema relationships
 * 
 * This script validates that all relationship configurations in the c6admin schemas
 * match the migration table structures from sprinkle-account v400.
 */

echo "=== C6Admin Schema Relationship Validation ===\n\n";

// Define expected relationships based on migrations
$expectedRelationships = [
    'users' => [
        'roles' => [
            'type' => 'many_to_many',
            'pivot_table' => 'role_users',
            'foreign_key' => 'user_id',
            'related_key' => 'role_id',
        ],
        'permissions' => [
            'type' => 'belongs_to_many_through',
            'through' => 'roles',
            'first_pivot_table' => 'role_users',
            'first_foreign_key' => 'user_id',
            'first_related_key' => 'role_id',
            'second_pivot_table' => 'permission_roles',
            'second_foreign_key' => 'role_id',
            'second_related_key' => 'permission_id',
        ],
    ],
    'roles' => [
        'permissions' => [
            'type' => 'many_to_many',
            'pivot_table' => 'permission_roles',
            'foreign_key' => 'role_id',
            'related_key' => 'permission_id',
        ],
        'users' => [
            'type' => 'many_to_many',
            'pivot_table' => 'role_users',
            'foreign_key' => 'role_id',
            'related_key' => 'user_id',
        ],
    ],
    'permissions' => [
        'roles' => [
            'type' => 'many_to_many',
            'pivot_table' => 'permission_roles',
            'foreign_key' => 'permission_id',
            'related_key' => 'role_id',
        ],
        'users' => [
            'type' => 'belongs_to_many_through',
            'through' => 'roles',
            'first_pivot_table' => 'permission_roles',
            'first_foreign_key' => 'permission_id',
            'first_related_key' => 'role_id',
            'second_pivot_table' => 'role_users',
            'second_foreign_key' => 'role_id',
            'second_related_key' => 'user_id',
        ],
    ],
];

$allPassed = true;

// Test each schema
foreach ($expectedRelationships as $model => $expectedRels) {
    echo "Testing: c6admin-{$model}.json\n";
    echo str_repeat('-', 50) . "\n";
    
    $schemaPath = __DIR__ . "/examples/schema/c6admin-{$model}.json";
    
    if (!file_exists($schemaPath)) {
        echo "❌ FAIL: Schema file not found\n\n";
        $allPassed = false;
        continue;
    }
    
    $schema = json_decode(file_get_contents($schemaPath), true);
    
    if (!$schema) {
        echo "❌ FAIL: Invalid JSON\n\n";
        $allPassed = false;
        continue;
    }
    
    echo "✓ Schema loaded successfully\n";
    echo "  Model: {$schema['model']}\n";
    echo "  Table: {$schema['table']}\n\n";
    
    // Check relationships
    $actualRels = $schema['relationships'] ?? [];
    
    foreach ($expectedRels as $relName => $expectedConfig) {
        echo "  Relationship: {$relName}\n";
        
        // Find the relationship in actual schema
        $found = false;
        $actualConfig = null;
        
        foreach ($actualRels as $rel) {
            if ($rel['name'] === $relName) {
                $found = true;
                $actualConfig = $rel;
                break;
            }
        }
        
        if (!$found) {
            echo "    ❌ FAIL: Relationship not found in schema\n";
            $allPassed = false;
            continue;
        }
        
        echo "    ✓ Relationship found\n";
        
        // Validate configuration
        $configValid = true;
        
        foreach ($expectedConfig as $key => $expectedValue) {
            if (!isset($actualConfig[$key])) {
                echo "    ❌ FAIL: Missing key '{$key}'\n";
                $configValid = false;
                $allPassed = false;
                continue;
            }
            
            if ($actualConfig[$key] !== $expectedValue) {
                echo "    ❌ FAIL: Key '{$key}' has value '{$actualConfig[$key]}', expected '{$expectedValue}'\n";
                $configValid = false;
                $allPassed = false;
            }
        }
        
        if ($configValid) {
            echo "    ✓ Configuration correct\n";
            
            // Display relationship details
            if ($expectedConfig['type'] === 'many_to_many') {
                echo "      Type: Many-to-Many\n";
                echo "      Pivot: {$expectedConfig['pivot_table']}\n";
                echo "      Keys: {$expectedConfig['foreign_key']} ↔ {$expectedConfig['related_key']}\n";
            } elseif ($expectedConfig['type'] === 'belongs_to_many_through') {
                echo "      Type: Belongs-to-Many-Through\n";
                echo "      Through: {$expectedConfig['through']}\n";
                echo "      First pivot: {$expectedConfig['first_pivot_table']}\n";
                echo "      First keys: {$expectedConfig['first_foreign_key']} ↔ {$expectedConfig['first_related_key']}\n";
                echo "      Second pivot: {$expectedConfig['second_pivot_table']}\n";
                echo "      Second keys: {$expectedConfig['second_foreign_key']} ↔ {$expectedConfig['second_related_key']}\n";
            }
        }
        
        echo "\n";
    }
    
    echo "\n";
}

// Summary
echo str_repeat('=', 50) . "\n";
if ($allPassed) {
    echo "✅ ALL TESTS PASSED\n";
    echo "\nAll c6admin schemas have correct relationship configurations!\n";
    echo "They match the migration table structures from sprinkle-account v400.\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "\nPlease review the errors above and fix the schema configurations.\n";
    exit(1);
}
