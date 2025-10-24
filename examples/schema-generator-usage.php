<?php

/**
 * Schema Generator Usage Examples
 * 
 * This file demonstrates how to use the SchemaGenerator service to automatically
 * generate complete schema definitions from existing database tables.
 */

use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaGenerator;

// Example 1: Generate schema for a single table
// ==============================================

/** @var SchemaGenerator $generator */
$generator = $ci->get(SchemaGenerator::class);

// Generate complete schema from database table
$schema = $generator->generateSchema('users');

// The generated schema includes:
// - All field definitions with appropriate types
// - Detected relationships (via DatabaseScanner)
// - Primary key detection
// - Timestamp detection
// - Permissions template
// - Default sort configuration

print_r($schema);


// Example 2: Generate schema without relationship detection
// =========================================================

$schema = $generator->generateSchema('orders', null, [
    'detect_relationships' => false,  // Skip relationship detection
]);


// Example 3: Generate schemas for all tables
// ==========================================

// Generate schemas for all tables (excluding common system tables)
$allSchemas = $generator->generateAllSchemas();

foreach ($allSchemas as $tableName => $schema) {
    echo "Generated schema for {$tableName}\n";
    
    // Save to file
    $filePath = "app/schema/crud6/{$tableName}.json";
    $generator->saveSchemaToFile($schema, $filePath);
}


// Example 4: Generate schemas with custom options
// ===============================================

$options = [
    'detect_relationships' => true,     // Detect and include relationships
    'include_permissions' => true,      // Include permissions template
    'include_default_sort' => true,     // Include default sort
    'description' => 'Custom description for this table',
];

$schema = $generator->generateSchema('products', null, $options);


// Example 5: Generate schemas for specific database connection
// ============================================================

// Generate schema from a specific database connection
$schema = $generator->generateSchema('analytics_events', 'analytics');

// Generate all schemas from analytics database
$analyticsSchemas = $generator->generateAllSchemas('analytics', ['_internal']);


// Example 6: Save generated schemas to files
// ==========================================

$schema = $generator->generateSchema('products');

// Save with pretty formatting (default)
$generator->saveSchemaToFile($schema, 'app/schema/crud6/products.json');

// Save without pretty formatting (compact)
$generator->saveSchemaToFile($schema, 'app/schema/crud6/products.json', false);


// Example 7: Batch generate and save all schemas
// ==============================================

$excludeTables = [
    'migrations',
    'cache',
    'sessions',
    'jobs',
    'failed_jobs',
    'password_resets',
];

$allSchemas = $generator->generateAllSchemas(null, $excludeTables);

foreach ($allSchemas as $tableName => $schema) {
    $filePath = "app/schema/crud6/{$tableName}.json";
    
    // Skip if file already exists (don't overwrite manual schemas)
    if (file_exists($filePath)) {
        echo "Skipping {$tableName} - schema file already exists\n";
        continue;
    }
    
    $generator->saveSchemaToFile($schema, $filePath);
    echo "Created schema for {$tableName}\n";
}


// Example 8: Review and customize generated schema
// ================================================

$schema = $generator->generateSchema('products');

// Review the generated schema
echo "Model: {$schema['model']}\n";
echo "Table: {$schema['table']}\n";
echo "Primary Key: {$schema['primary_key']}\n";
echo "Has Timestamps: " . ($schema['timestamps'] ? 'Yes' : 'No') . "\n";
echo "\nFields:\n";

foreach ($schema['fields'] as $fieldName => $fieldInfo) {
    echo "  - {$fieldName} ({$fieldInfo['type']})\n";
    if ($fieldInfo['required'] ?? false) {
        echo "    Required: Yes\n";
    }
}

if (isset($schema['relationships'])) {
    echo "\nDetected Relationships:\n";
    foreach ($schema['relationships'] as $field => $rel) {
        echo "  - {$field} -> {$rel['related']}.{$rel['owner_key']}\n";
        echo "    Confidence: " . round($rel['confidence'] * 100, 1) . "%\n";
    }
}

// Customize before saving
$schema['description'] = 'Updated description';
$schema['permissions']['custom'] = 'custom_permission';

$generator->saveSchemaToFile($schema, 'app/schema/crud6/products.json');


// Example 9: Generate documentation from schemas
// ==============================================

$allSchemas = $generator->generateAllSchemas();

// Create markdown documentation
$markdown = "# Database Schema Documentation\n\n";

foreach ($allSchemas as $tableName => $schema) {
    $markdown .= "## {$schema['title']}\n\n";
    $markdown .= "**Table:** `{$schema['table']}`\n\n";
    $markdown .= "**Description:** {$schema['description']}\n\n";
    
    $markdown .= "### Fields\n\n";
    $markdown .= "| Field | Type | Required | Description |\n";
    $markdown .= "|-------|------|----------|-------------|\n";
    
    foreach ($schema['fields'] as $fieldName => $fieldInfo) {
        $required = ($fieldInfo['required'] ?? false) ? 'Yes' : 'No';
        $label = $fieldInfo['label'] ?? ucfirst($fieldName);
        $markdown .= "| `{$fieldName}` | {$fieldInfo['type']} | {$required} | {$label} |\n";
    }
    
    if (isset($schema['relationships']) && !empty($schema['relationships'])) {
        $markdown .= "\n### Relationships\n\n";
        foreach ($schema['relationships'] as $field => $rel) {
            $markdown .= "- **{$field}**: References `{$rel['related']}.{$rel['owner_key']}`\n";
        }
    }
    
    $markdown .= "\n---\n\n";
}

file_put_contents('docs/DATABASE_SCHEMA.md', $markdown);


// Example 10: Validate generated schemas
// ======================================

$schema = $generator->generateSchema('users');

// Check required fields are present
$requiredFields = ['model', 'table', 'fields'];
foreach ($requiredFields as $field) {
    if (!isset($schema[$field])) {
        echo "Warning: Schema missing required field: {$field}\n";
    }
}

// Check field definitions
foreach ($schema['fields'] as $fieldName => $fieldInfo) {
    if (!isset($fieldInfo['type'])) {
        echo "Warning: Field {$fieldName} missing type\n";
    }
    if (!isset($fieldInfo['label'])) {
        echo "Warning: Field {$fieldName} missing label\n";
    }
}

// Validate relationships have proper structure
if (isset($schema['relationships'])) {
    foreach ($schema['relationships'] as $field => $rel) {
        if (!isset($rel['type'], $rel['related'], $rel['foreign_key'])) {
            echo "Warning: Relationship {$field} has invalid structure\n";
        }
    }
}


// Example 11: Generate schema with custom type mapping
// ====================================================

// Note: To add custom type mappings, you would need to extend SchemaGenerator
// This example shows the concept

class CustomSchemaGenerator extends SchemaGenerator
{
    protected array $typeMapping = [
        // Add custom mappings here
        'custom_type' => 'string',
        // ... existing mappings from parent
    ];
}

// Use custom generator
$customGenerator = new CustomSchemaGenerator($db, $scanner);
$schema = $customGenerator->generateSchema('custom_table');


// Example 12: Incremental schema generation
// =========================================

// Get list of existing schema files
$existingSchemas = glob('app/schema/crud6/*.json');
$existingTables = array_map(function($file) {
    return basename($file, '.json');
}, $existingSchemas);

// Get all database tables
$allSchemas = $generator->generateAllSchemas();

// Only generate schemas for new tables
foreach ($allSchemas as $tableName => $schema) {
    if (!in_array($tableName, $existingTables)) {
        $filePath = "app/schema/crud6/{$tableName}.json";
        $generator->saveSchemaToFile($schema, $filePath);
        echo "Created new schema for {$tableName}\n";
    }
}


// Example 13: Generate migration-ready schemas
// ============================================

$schema = $generator->generateSchema('new_table');

// Add migration-specific metadata
$schema['migration'] = [
    'create' => true,
    'drop_existing' => false,
    'indexes' => [],
];

// Identify fields that should have indexes
foreach ($schema['fields'] as $fieldName => $fieldInfo) {
    if (str_ends_with($fieldName, '_id')) {
        $schema['migration']['indexes'][] = $fieldName;
    }
}

$generator->saveSchemaToFile($schema, 'app/schema/crud6/new_table.json');


// Example 14: Compare generated schema with existing
// ==================================================

$tableName = 'users';
$existingSchemaPath = "app/schema/crud6/{$tableName}.json";

if (file_exists($existingSchemaPath)) {
    $existingSchema = json_decode(file_get_contents($existingSchemaPath), true);
    $generatedSchema = $generator->generateSchema($tableName);
    
    // Compare fields
    $existingFields = array_keys($existingSchema['fields']);
    $generatedFields = array_keys($generatedSchema['fields']);
    
    $newFields = array_diff($generatedFields, $existingFields);
    $removedFields = array_diff($existingFields, $generatedFields);
    
    if (!empty($newFields)) {
        echo "New fields detected: " . implode(', ', $newFields) . "\n";
    }
    
    if (!empty($removedFields)) {
        echo "Fields no longer in database: " . implode(', ', $removedFields) . "\n";
    }
}


// Example 15: Generate schema with custom field labels
// ====================================================

$schema = $generator->generateSchema('products');

// Customize field labels
$customLabels = [
    'sku' => 'Product SKU',
    'msrp' => 'Manufacturer Suggested Retail Price',
    'qty' => 'Quantity',
];

foreach ($customLabels as $field => $label) {
    if (isset($schema['fields'][$field])) {
        $schema['fields'][$field]['label'] = $label;
    }
}

$generator->saveSchemaToFile($schema, 'app/schema/crud6/products.json');
