# Testing CRUD6 with Multiple Sprinkles

This guide explains how to configure CRUD6's testing framework to test schemas from multiple sprinkles.

## Overview

When you have multiple sprinkles that use CRUD6, each sprinkle may have its own schema files that need to be tested. The CRUD6 testing framework supports testing schemas from multiple directories simultaneously.

## Configuration Methods

There are three ways to configure test schema directories, listed in priority order:

### 1. Environment Variable (Recommended for CI/CD)

Set the `TEST_SCHEMA_DIRS` environment variable with comma-separated paths:

```bash
export TEST_SCHEMA_DIRS="examples/schema,vendor/mysprinkle/schema,app/schema/crud6"
phpunit
```

In your `phpunit.xml`:

```xml
<phpunit>
    <php>
        <env name="TEST_SCHEMA_DIRS" value="examples/schema,vendor/mysprinkle/schema,app/schema/crud6"/>
    </php>
</phpunit>
```

### 2. Override in Test Class (Recommended for Custom Test Suites)

Create a custom test case that extends `CRUD6TestCase` and override the `getTestSchemaDirs()` method:

```php
<?php

namespace MyApp\Tests;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

class MyAppTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../schema/myapp',
            __DIR__ . '/../../../vendor/anothersprinkle/schema',
            __DIR__ . '/../../../examples/schema',
        ];
    }
}
```

Then extend your custom test case in your tests:

```php
<?php

namespace MyApp\Tests\Integration;

use MyApp\Tests\MyAppTestCase;

class MyModelTest extends MyAppTestCase
{
    // Tests will automatically use schemas from configured directories
}
```

### 3. Default Behavior

If no configuration is provided, the framework defaults to the `examples/schema` directory in the CRUD6 sprinkle.

## Path Resolution

The framework supports multiple path formats:

- **Absolute paths**: `/var/www/myapp/schema`
- **Relative to project root**: `examples/schema` or `vendor/mysprinkle/schema`
- **Relative to test file**: `__DIR__ . '/../../schema'`

Relative paths are automatically resolved to absolute paths relative to the project root.

## Schema Discovery

The testing framework:

1. **Scans all configured directories** for `.json` schema files
2. **Deduplicates schemas** if the same schema name exists in multiple directories (first found wins)
3. **Filters schemas** to only test those with corresponding database tables
4. **Automatically generates tests** for each discovered schema

## Example Use Cases

### Single Sprinkle with Custom Schemas

Your application has custom schemas in `app/schema/crud6/`:

```xml
<env name="TEST_SCHEMA_DIRS" value="examples/schema,app/schema/crud6"/>
```

### Multiple Sprinkles

Your application uses multiple sprinkles, each with their own schemas:

```xml
<env name="TEST_SCHEMA_DIRS" value="examples/schema,vendor/sprinkle-inventory/schema,vendor/sprinkle-crm/schema,app/schema/crud6"/>
```

### Different Configurations for Different Test Suites

You can create different test base classes for different sprinkles:

```php
// InventoryTestCase.php
class InventoryTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/sprinkle-inventory/schema',
            __DIR__ . '/../../../examples/schema',
        ];
    }
}

// CRMTestCase.php
class CRMTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/sprinkle-crm/schema',
            __DIR__ . '/../../../examples/schema',
        ];
    }
}
```

## Testing Your Configuration

To verify your schema directories are configured correctly, run:

```bash
phpunit --filter schemaProvider
```

This will show which schemas were discovered from your configured directories.

## Troubleshooting

### No Schemas Found

If the test framework reports no schemas found:

1. **Check paths are correct**: Ensure the directories exist and contain `.json` files
2. **Check path format**: Use forward slashes (`/`) not backslashes (`\`)
3. **Check permissions**: Ensure the test process can read the directories
4. **Enable debug output**: Run phpunit with `-v` flag for verbose output

### Duplicate Schema Names

If you have schemas with the same name in multiple directories, the first one found will be used. To avoid confusion:

- Use unique schema names across sprinkles
- Or ensure the priority order in `TEST_SCHEMA_DIRS` reflects your preferences (first directory has highest priority)

### Schema Not Being Tested

If a schema file exists but isn't being tested:

1. **Check table exists**: The schema's table must exist in the test database
2. **Check schema is in knownTables**: Currently only schemas for UserFrosting tables (users, roles, groups, permissions, activities) are tested by default
3. **Add table to knownTables**: Extend `schemaProvider()` in your test class to add custom tables

## Advanced: Custom Schema Filtering

You can override `schemaProvider()` to implement custom filtering logic:

```php
public static function schemaProvider(): array
{
    $testCase = new static('schemaProvider');
    $schemaDirs = $testCase->getTestSchemaDirs();
    
    $availableSchemas = [];
    foreach ($schemaDirs as $schemaDir) {
        if (!is_dir($schemaDir)) {
            continue;
        }
        
        $schemaFiles = glob($schemaDir . '/*.json');
        foreach ($schemaFiles as $file) {
            $schemaName = basename($file, '.json');
            $availableSchemas[$schemaName] = true;
        }
    }
    
    // Custom filtering logic here
    $myCustomTables = ['products', 'orders', 'customers'];
    $testSchemas = array_filter(array_keys($availableSchemas), function($schema) use ($myCustomTables) {
        return in_array($schema, $myCustomTables);
    });
    
    sort($testSchemas);
    return array_map(fn($schema) => [$schema], $testSchemas);
}
```

## Migration Guide

### From Hardcoded examples/schema

**Before:**
```php
// Tests only worked with examples/schema directory
```

**After:**
```xml
<!-- phpunit.xml -->
<env name="TEST_SCHEMA_DIRS" value="examples/schema,app/schema/crud6"/>
```

No code changes needed - existing tests will automatically use the new configuration!

## See Also

- [CRUD6TestCase.php](../app/tests/CRUD6TestCase.php) - Base test case implementation
- [SchemaBasedApiTest.php](../app/tests/Integration/SchemaBasedApiTest.php) - Integration test example
- [phpunit.xml](../phpunit.xml) - PHPUnit configuration example
