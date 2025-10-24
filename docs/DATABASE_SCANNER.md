# Database Scanner Module

The Database Scanner module provides intelligent analysis of database structures to detect foreign key relationships based on naming conventions and data sampling. This is particularly useful for databases that implement relationships at the application layer without explicit foreign key constraints.

## Overview

The `DatabaseScanner` service analyzes database tables to:
- Detect potential foreign keys by field naming patterns (e.g., `user_id`, `category_id`)
- Validate relationships through data sampling
- Generate relationship metadata compatible with CRUD6 schema definitions
- Support multiple database connections and drivers

## Features

### Naming Convention Detection
The scanner recognizes common naming patterns for foreign keys:
- `*_id` suffix (e.g., `user_id`, `group_id`, `category_id`)
- `*_uuid` suffix (e.g., `user_uuid`, `category_uuid`)
- `fk_*` prefix (e.g., `fk_user`, `fk_category`)

### Data Sampling Validation
After detecting potential foreign keys, the scanner validates relationships by:
1. Sampling up to 100 rows (configurable) from the source table
2. Checking how many values exist in the target table
3. Calculating a match rate (percentage of valid references)
4. Only reporting relationships above the validation threshold (default: 80%)

### Multi-Database Support
The scanner works with:
- MySQL/MariaDB
- PostgreSQL
- SQLite

## Installation

The Database Scanner is automatically registered when you install the CRUD6 sprinkle. No additional configuration is required.

## Basic Usage

### Scan a Single Table

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;

/** @var DatabaseScanner $scanner */
$scanner = $ci->get(DatabaseScanner::class);

// Scan a table for relationships
$relationships = $scanner->scanTable('orders');

// Example output:
// [
//     'user_id' => [
//         'table' => 'users',
//         'key' => 'id',
//         'field' => 'user_id',
//         'is_valid' => true,
//         'match_rate' => 0.98,
//         'target_table' => 'users',
//         'target_key' => 'id',
//     ],
// ]
```

### Scan Entire Database

```php
// Scan all tables (excluding migrations and system tables)
$allRelationships = $scanner->scanDatabase(null, ['migrations', 'cache', 'sessions']);

foreach ($allRelationships as $table => $relationships) {
    echo "Table: {$table}\n";
    foreach ($relationships as $field => $info) {
        echo "  - {$field} -> {$info['target_table']}.{$info['target_key']} ";
        echo "(confidence: " . round($info['match_rate'] * 100, 1) . "%)\n";
    }
}
```

## Configuration

### Custom Naming Patterns

You can configure custom patterns for foreign key detection:

```php
$scanner->setForeignKeyPatterns([
    '/_id$/',           // Standard: user_id, product_id
    '/_uuid$/',         // UUID references: user_uuid
    '/^ref_/',          // Prefixed: ref_user, ref_product
    '/_key$/',          // Custom: user_key, product_key
]);
```

### Sample Size

Adjust the number of rows to sample for validation:

```php
// Default: 100 rows
$scanner->setSampleSize(200);
```

### Validation Threshold

Set the minimum match rate to consider a relationship valid:

```php
// Default: 0.8 (80% of sampled values must exist in target table)
$scanner->setValidationThreshold(0.9);  // 90% threshold
```

## Integration with SchemaService

The scanner integrates seamlessly with the SchemaService to enrich schema definitions:

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;

/** @var SchemaService $schemaService */
$schemaService = $ci->get(SchemaService::class);

/** @var DatabaseScanner $scanner */
$scanner = $ci->get(DatabaseScanner::class);

// Load existing schema
$schema = $schemaService->getSchema('orders');

// Detect and generate relationships
$relationships = $scanner->scanTable('orders');
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

// Enrich schema (won't overwrite existing relationships)
$enrichedSchema = $schemaService->enrichSchemaWithRelationships(
    $schema,
    $schemaRelationships,
    false  // Preserve manually defined relationships
);
```

## Output Format

### Scan Results

The `scanTable()` method returns an array of detected relationships:

```php
[
    'user_id' => [
        'table' => 'users',              // Inferred target table name
        'key' => 'id',                   // Inferred target key (id or uuid)
        'field' => 'user_id',            // Source field name
        'is_valid' => true,              // Whether relationship is valid
        'match_rate' => 0.98,            // Percentage of matching records (0.0-1.0)
        'sampled_count' => 100,          // Number of rows sampled
        'matching_count' => 98,          // Number of matching records found
        'target_table' => 'users',       // Target table name
        'target_key' => 'id',            // Target primary key
    ],
]
```

### Schema-Compatible Format

The `generateSchemaRelationships()` method converts scan results to schema format:

```php
[
    'user_id' => [
        'type' => 'belongsTo',           // Relationship type
        'related' => 'users',            // Related model/table
        'foreign_key' => 'user_id',      // Foreign key field
        'owner_key' => 'id',             // Owner/primary key
        'confidence' => 0.98,            // Confidence score (match rate)
    ],
]
```

## Use Cases

### 1. Documenting Legacy Databases

Generate documentation for databases without explicit foreign keys:

```php
$allRelationships = $scanner->scanDatabase();

$report = [];
foreach ($allRelationships as $table => $relationships) {
    foreach ($relationships as $field => $info) {
        $report[] = [
            'table' => $table,
            'field' => $field,
            'references' => "{$info['target_table']}.{$info['target_key']}",
            'confidence' => round($info['match_rate'] * 100, 1) . '%',
        ];
    }
}

// Save as JSON documentation
file_put_contents('database_relationships.json', json_encode($report, JSON_PRETTY_PRINT));
```

### 2. Auto-Generating Schema Files

Create initial schema files for tables:

```php
$relationships = $scanner->scanTable('orders');
$schemaRelationships = $scanner->generateSchemaRelationships($relationships);

$schema = [
    'model' => 'orders',
    'table' => 'orders',
    'fields' => [],  // Fill in manually
    'relationships' => $schemaRelationships,
];

file_put_contents('app/schema/crud6/orders.json', json_encode($schema, JSON_PRETTY_PRINT));
```

### 3. Validating Data Integrity

Check relationship integrity across your database:

```php
$allRelationships = $scanner->scanDatabase();

foreach ($allRelationships as $table => $relationships) {
    foreach ($relationships as $field => $info) {
        if ($info['is_valid'] && $info['match_rate'] < 0.95) {
            echo "⚠️  Warning: {$table}.{$field} has orphaned records\n";
            echo "   Only " . round($info['match_rate'] * 100, 1) . "% of records have valid references\n";
        }
    }
}
```

### 4. Migration Planning

Identify relationships for adding actual foreign key constraints:

```php
$allRelationships = $scanner->scanDatabase();

foreach ($allRelationships as $table => $relationships) {
    foreach ($relationships as $field => $info) {
        if ($info['is_valid'] && $info['match_rate'] >= 0.99) {
            echo "ALTER TABLE {$table} ADD CONSTRAINT fk_{$table}_{$field}\n";
            echo "  FOREIGN KEY ({$field}) REFERENCES {$info['target_table']}({$info['target_key']});\n";
        }
    }
}
```

## Advanced Features

### Multi-Database Connections

Scan tables in different database connections:

```php
// Scan in the 'analytics' database connection
$relationships = $scanner->scanTable('events', 'analytics');

// Scan entire analytics database
$analyticsRelationships = $scanner->scanDatabase('analytics', ['_internal']);
```

### Custom Table Name Inference

The scanner includes intelligent table name pluralization:

- Regular: `user` → `users`, `product` → `products`
- Y-ending: `category` → `categories`, `entry` → `entries`
- Special: `address` → `addresses`, `box` → `boxes`
- Irregular: `person` → `people`, `child` → `children`

### Handling Invalid Relationships

Not all detected patterns will be valid relationships:

```php
$relationships = $scanner->scanTable('orders');

foreach ($relationships as $field => $info) {
    if (!$info['is_valid']) {
        echo "Skipped {$field}: {$info['reason']}\n";
        // Common reasons:
        // - "Target table does not exist"
        // - "Target key does not exist"
        // - "No non-null values in source field"
        // - Match rate below threshold
    }
}
```

## Performance Considerations

- **Sample Size**: Larger samples are more accurate but slower. Default of 100 is a good balance.
- **Validation Threshold**: Higher thresholds (>0.9) are more strict and may miss valid but imperfect relationships.
- **Excluded Tables**: Always exclude system tables (migrations, cache, sessions) to improve performance.
- **Database Size**: Scanning large databases may take several seconds. Consider caching results.

## Limitations

1. **Naming Convention Dependent**: Only detects relationships following common naming patterns
2. **Pluralization**: Simple pluralization may not work for all languages or custom table names
3. **Composite Keys**: Currently only supports single-column foreign keys
4. **Polymorphic Relations**: Does not detect polymorphic relationships
5. **Data Required**: Needs existing data to validate relationships (empty tables won't validate)

## Best Practices

1. **Review Results**: Always review scanner output before using in production
2. **Document Exceptions**: When scanner misses relationships, document them in schema manually
3. **Version Control**: Store generated schemas in version control
4. **Regular Scans**: Re-scan when database structure changes
5. **Combine with Manual**: Use scanner for initial discovery, refine manually
6. **Test Threshold**: Adjust validation threshold based on your data quality

## Troubleshooting

### "Target table does not exist"
The inferred table name doesn't exist. Check if:
- Table uses different naming convention
- Table name is singular vs. plural
- Field is not actually a foreign key

### Low Match Rates
If match_rate is between 0.5-0.8:
- Check for orphaned records (referential integrity issues)
- Verify the relationship is actually valid
- Consider data cleanup before adding constraints

### No Relationships Detected
If scanner finds nothing:
- Check naming patterns with `setForeignKeyPatterns()`
- Verify tables have data (empty tables won't validate)
- Ensure tables exist in the scanned database/connection

## Examples

See `/examples/database-scanner-usage.php` for comprehensive usage examples.

## API Reference

### DatabaseScanner Methods

#### `scanTable(string $tableName, ?string $connection = null): array`
Scans a single table for relationships.

#### `scanDatabase(?string $connection = null, ?array $excludeTables = null): array`
Scans all tables in a database.

#### `setForeignKeyPatterns(array $patterns): static`
Sets custom regex patterns for foreign key detection.

#### `setSampleSize(int $size): static`
Sets the number of rows to sample (minimum: 1).

#### `setValidationThreshold(float $threshold): static`
Sets the match rate threshold (0.0 to 1.0).

#### `generateSchemaRelationships(array $relationships): array`
Converts scan results to schema-compatible format.

### SchemaService Methods

#### `enrichSchemaWithRelationships(array $schema, array $detectedRelationships, bool $overwrite = false): array`
Enriches a schema with detected relationships.

## Contributing

When contributing to the scanner module:
- Add support for new naming patterns
- Improve pluralization logic
- Add support for new database drivers
- Enhance validation algorithms
- Add more comprehensive tests

## License

MIT License - see LICENSE.md for details
