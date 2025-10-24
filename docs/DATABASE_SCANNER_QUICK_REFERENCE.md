# Database Scanner Quick Reference

A quick reference guide for using the Database Scanner and Schema Generator services.

## Quick Start

### 1. Detect Relationships in a Table

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;

$scanner = $ci->get(DatabaseScanner::class);
$relationships = $scanner->scanTable('orders');
```

### 2. Generate Complete Schema

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaGenerator;

$generator = $ci->get(SchemaGenerator::class);
$schema = $generator->generateSchema('users');
$generator->saveSchemaToFile($schema, 'app/schema/crud6/users.json');
```

### 3. Scan Entire Database

```php
// Detect all relationships
$allRelationships = $scanner->scanDatabase(null, ['migrations', 'cache']);

// Generate all schemas
$allSchemas = $generator->generateAllSchemas(null, ['migrations', 'cache']);
```

## DatabaseScanner

### Configuration

```php
$scanner = $ci->get(DatabaseScanner::class);

// Set custom naming patterns
$scanner->setForeignKeyPatterns(['/_id$/', '/_uuid$/', '/^fk_/']);

// Adjust sample size (default: 100)
$scanner->setSampleSize(200);

// Set validation threshold (default: 0.8)
$scanner->setValidationThreshold(0.9);
```

### Scanning Methods

```php
// Scan single table
$relationships = $scanner->scanTable('orders');

// Scan with specific connection
$relationships = $scanner->scanTable('events', 'analytics');

// Scan entire database
$allRelationships = $scanner->scanDatabase();

// Exclude tables
$allRelationships = $scanner->scanDatabase(null, ['migrations', 'cache', 'sessions']);
```

### Output Format

```php
[
    'user_id' => [
        'table' => 'users',
        'key' => 'id',
        'field' => 'user_id',
        'is_valid' => true,
        'match_rate' => 0.98,
        'sampled_count' => 100,
        'matching_count' => 98,
        'target_table' => 'users',
        'target_key' => 'id',
    ],
]
```

### Generate Schema Relationships

```php
$relationships = $scanner->scanTable('orders');
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

// Output:
[
    'user_id' => [
        'type' => 'belongsTo',
        'related' => 'users',
        'foreign_key' => 'user_id',
        'owner_key' => 'id',
        'confidence' => 0.98,
    ],
]
```

## SchemaGenerator

### Basic Schema Generation

```php
$generator = $ci->get(SchemaGenerator::class);

// Generate schema
$schema = $generator->generateSchema('users');

// Save to file
$generator->saveSchemaToFile($schema, 'app/schema/crud6/users.json');

// Pretty print (default) or compact
$generator->saveSchemaToFile($schema, 'path/to/file.json', true);  // Pretty
$generator->saveSchemaToFile($schema, 'path/to/file.json', false); // Compact
```

### Generation Options

```php
$options = [
    'detect_relationships' => true,     // Use DatabaseScanner
    'include_permissions' => true,      // Add permissions template
    'include_default_sort' => true,     // Add default sort
    'description' => 'Custom description',
];

$schema = $generator->generateSchema('products', null, $options);
```

### Batch Generation

```php
// Generate all schemas
$allSchemas = $generator->generateAllSchemas();

// With exclusions
$allSchemas = $generator->generateAllSchemas(null, ['migrations', 'cache']);

// Save all to files
foreach ($allSchemas as $tableName => $schema) {
    $filePath = "app/schema/crud6/{$tableName}.json";
    if (!file_exists($filePath)) {
        $generator->saveSchemaToFile($schema, $filePath);
    }
}
```

### Schema Output Structure

```php
[
    'model' => 'users',
    'title' => 'Users Management',
    'singular_title' => 'User',
    'description' => 'Manage users',
    'table' => 'users',
    'primary_key' => 'id',
    'timestamps' => true,
    'soft_delete' => false,
    'fields' => [
        'id' => [
            'type' => 'integer',
            'label' => 'ID',
            'readonly' => true,
            'auto_increment' => true,
            // ...
        ],
        // ... more fields
    ],
    'relationships' => [
        'group_id' => [
            'type' => 'belongsTo',
            'related' => 'groups',
            'foreign_key' => 'group_id',
            'owner_key' => 'id',
            'confidence' => 0.95,
        ],
    ],
    'permissions' => [
        'read' => 'uri_users',
        'create' => 'create_user',
        'update' => 'update_user',
        'delete' => 'delete_user',
    ],
    'default_sort' => [
        'name' => 'asc',
    ],
]
```

## SchemaService Integration

### Enrich Existing Schemas

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

$schemaService = $ci->get(SchemaService::class);
$scanner = $ci->get(DatabaseScanner::class);

// Load existing schema
$schema = $schemaService->getSchema('orders');

// Detect relationships
$relationships = $scanner->scanTable('orders');
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

// Enrich schema (preserves existing relationships)
$enrichedSchema = $schemaService->enrichSchemaWithRelationships(
    $schema,
    $schemaRelationships,
    false  // Don't overwrite
);

// Or force overwrite
$enrichedSchema = $schemaService->enrichSchemaWithRelationships(
    $schema,
    $schemaRelationships,
    true  // Overwrite existing
);
```

## Type Mappings

| Database Type | Schema Type |
|--------------|-------------|
| int, bigint, smallint, tinyint | integer |
| varchar, char | string |
| text, mediumtext, longtext | text |
| decimal, numeric | decimal |
| float, double, real | float |
| boolean, bool, bit | boolean |
| date | date |
| datetime, timestamp | datetime |
| json, jsonb | json |

## Common Patterns

### Pattern 1: Generate Schema for New Table

```php
$generator = $ci->get(SchemaGenerator::class);
$schema = $generator->generateSchema('new_table');
$generator->saveSchemaToFile($schema, 'app/schema/crud6/new_table.json');
```

### Pattern 2: Update Existing Schema with Relationships

```php
$scanner = $ci->get(DatabaseScanner::class);
$schemaService = $ci->get(SchemaService::class);

$schema = $schemaService->getSchema('orders');
$relationships = $scanner->scanTable('orders');
$schemaRels = $scanner->generateSchemaRelationships($relationships);

$updated = $schemaService->enrichSchemaWithRelationships($schema, $schemaRels);
file_put_contents(
    'app/schema/crud6/orders.json',
    json_encode($updated, JSON_PRETTY_PRINT)
);
```

### Pattern 3: Batch Generate Missing Schemas

```php
$generator = $ci->get(SchemaGenerator::class);
$existing = glob('app/schema/crud6/*.json');
$existingTables = array_map(fn($f) => basename($f, '.json'), $existing);

$allSchemas = $generator->generateAllSchemas();

foreach ($allSchemas as $table => $schema) {
    if (!in_array($table, $existingTables)) {
        $generator->saveSchemaToFile($schema, "app/schema/crud6/{$table}.json");
        echo "Created schema for {$table}\n";
    }
}
```

### Pattern 4: Validate Detected Relationships

```php
$scanner = $ci->get(DatabaseScanner::class);
$relationships = $scanner->scanTable('orders');

foreach ($relationships as $field => $info) {
    if ($info['is_valid']) {
        $confidence = round($info['match_rate'] * 100, 1);
        echo "✓ {$field} -> {$info['target_table']} ({$confidence}%)\n";
        
        if ($info['match_rate'] < 0.95) {
            echo "  ⚠️  Warning: Lower confidence - check data integrity\n";
        }
    } else {
        echo "✗ {$field}: {$info['reason']}\n";
    }
}
```

### Pattern 5: Generate Documentation

```php
$generator = $ci->get(SchemaGenerator::class);
$allSchemas = $generator->generateAllSchemas();

$markdown = "# Database Schemas\n\n";
foreach ($allSchemas as $table => $schema) {
    $markdown .= "## {$schema['title']}\n";
    $markdown .= "**Table:** `{$schema['table']}`\n\n";
    
    foreach ($schema['fields'] as $field => $info) {
        $required = ($info['required'] ?? false) ? '✓' : '';
        $markdown .= "- `{$field}` ({$info['type']}) {$required}\n";
    }
    $markdown .= "\n";
}

file_put_contents('docs/DATABASE_SCHEMAS.md', $markdown);
```

## Troubleshooting

### Issue: "Target table does not exist"
**Solution:** Check inferred table name matches actual table name. Adjust naming patterns if needed.

### Issue: Low match rates (<80%)
**Solution:** 
- Check for orphaned records
- Review relationship validity
- Clean up data or adjust threshold

### Issue: No relationships detected
**Solution:**
- Verify naming patterns match your conventions
- Ensure tables have data
- Check database connection

### Issue: Wrong pluralization
**Solution:** The scanner uses simple pluralization. Manually adjust schema after generation.

## Best Practices

1. **Review Generated Schemas**: Always review auto-generated schemas before using in production
2. **Version Control**: Commit generated schemas to version control
3. **Document Exceptions**: When scanner misses relationships, document them manually
4. **Regular Scans**: Re-scan when database structure changes
5. **Combine Approaches**: Use scanner for initial discovery, refine manually
6. **Test Thresholds**: Adjust validation threshold based on your data quality

## See Also

- Full documentation: `docs/DATABASE_SCANNER.md`
- Usage examples: `examples/database-scanner-usage.php`, `examples/schema-generator-usage.php`
- README section on Database Scanner
