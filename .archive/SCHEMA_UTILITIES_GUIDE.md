# CRUD6 Schema Utilities Guide

**Date:** 2025-12-18  
**Purpose:** Guide for using CRUD6 schema generation utilities in other sprinkles

## Overview

CRUD6 now provides reusable schema building and generation utilities that other sprinkles (like c6admin) can use programmatically. These utilities allow sprinkles to:

1. Build schemas programmatically using a fluent API
2. Generate schema JSON files and translations
3. Use a Bakery command for convenience

## Utilities Provided

### 1. SchemaBuilder (`UserFrosting\Sprinkle\CRUD6\Schema\SchemaBuilder`)

A fluent API for building CRUD6 JSON schemas programmatically.

**Features:**
- Fluent builder pattern
- Support for all CRUD6 field types
- Automatic translation key generation
- Validation rules configuration
- Relationship definitions

**Example Usage:**
```php
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaBuilder;

$schema = SchemaBuilder::create('products', 'products')
    ->setTitleField('name')
    ->setPrimaryKey('id')
    ->addPermissions([
        'read' => 'uri_products',
        'create' => 'create_product',
        'update' => 'update_product_field',
        'delete' => 'delete_product',
    ])
    ->addIntegerField('id', autoIncrement: true, readonly: true)
    ->addStringField('sku', required: true, unique: true, listable: true)
    ->addStringField('name', required: true, sortable: true, filterable: true, listable: true)
    ->addTextField('description', filterable: true)
    ->addCustomField('price', [
        'type' => 'decimal',
        'label' => 'CRUD6.PRODUCTS.PRICE',
        'required' => true,
        'sortable' => true,
        'show_in' => ['list', 'form', 'detail'],
        'validation' => [
            'required' => true,
            'numeric' => true,
            'min' => 0,
        ],
    ])
    ->addIntegerField('quantity', sortable: true, listable: true)
    ->addBooleanField('active', listable: true, default: true)
    ->addDateTimeField('created_at', readonly: true)
    ->addDateTimeField('updated_at', readonly: true)
    ->build();

// Save to file
file_put_contents('app/schema/crud6/products.json', 
    json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
```

**Available Field Methods:**
- `addStringField()` - String/varchar fields
- `addIntegerField()` - Integer fields
- `addBooleanField()` - Boolean/tinyint fields
- `addDateField()` - Date fields
- `addDateTimeField()` - DateTime/timestamp fields
- `addTextField()` - Text/longtext fields
- `addDecimalField()` - Decimal/numeric fields
- `addFloatField()` - Float/double fields
- `addEmailField()` - Email validation string fields
- `addPasswordField()` - Password fields
- `addJsonField()` - JSON fields
- `addCustomField()` - Full custom configuration

**Helper Methods:**
```php
SchemaBuilder::userSchema()      // Pre-built user schema
SchemaBuilder::groupSchema()     // Pre-built group schema
SchemaBuilder::productSchema()   // Pre-built product schema
```

### 2. SchemaGenerator (`UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator`)

Generates schema JSON files and corresponding locale translations.

**Features:**
- Generate multiple schemas at once
- Merge with existing base translations
- Custom output paths
- Automatic directory creation

**Example Usage:**
```php
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator;

// Generate to default paths (app/schema/crud6 and app/locale/en_US)
SchemaGenerator::generate();

// Generate to custom paths (useful for other sprinkles)
SchemaGenerator::generateToPath(
    'vendor/mycompany/my-sprinkle/app/schema/crud6',
    'vendor/mycompany/my-sprinkle/app/locale/en_US'
);
```

**Translation Merging:**
- Loads existing `messages.php` if present
- Preserves base CRUD6 translations (CREATE, DELETE, UPDATE, etc.)
- Adds/updates schema-specific model translations (USERS, GROUPS, etc.)
- Never overwrites manually maintained translations

### 3. Bakery Command

For convenience, CRUD6 provides a Bakery command for schema generation.

**Command:** `crud6:generate-schema`

**Options:**
- `--schema-dir, -s` - Directory for schema files (default: app/schema/crud6)
- `--locale-dir, -l` - Directory for locale files (default: app/locale/en_US)
- `--force, -f` - Force regeneration even if files exist

**Example Usage:**
```bash
# Generate to default paths
php bakery crud6:generate-schema

# Generate to custom paths
php bakery crud6:generate-schema \
    --schema-dir=custom/path/schemas \
    --locale-dir=custom/path/locale

# Force regeneration
php bakery crud6:generate-schema --force
```

## Use Cases

### Use Case 1: Testing (Current CRUD6 Usage)

CRUD6 generates test schemas dynamically during CI:

```yaml
# .github/workflows/unit-tests.yml
- name: Generate CRUD6 Schema Files
  run: php scripts/generate-test-schemas.php
```

**Note:** Generated schemas are NOT committed to the repository. They are ignored via `.gitignore`.

### Use Case 2: Other Sprinkles (e.g., c6admin)

Other sprinkles can use these utilities to generate their own schemas:

**Option A: Programmatic Generation**
```php
// In your sprinkle's setup script or test helper
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaBuilder;
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator;

// Define schemas
$schemas = [
    'orders' => SchemaBuilder::create('orders', 'orders')
        ->addStringField('order_number', required: true, unique: true)
        ->addDecimalField('total', required: true)
        ->build(),
    
    'products' => SchemaBuilder::productSchema(), // Use helper
];

// Save schemas
foreach ($schemas as $name => $schema) {
    file_put_contents(
        "app/schema/crud6/{$name}.json",
        json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

// Generate translations
SchemaGenerator::generateToPath(
    'app/schema/crud6',
    'app/locale/en_US'
);
```

**Option B: Bakery Command**
```bash
# In your sprinkle's README or setup instructions
php bakery crud6:generate-schema --schema-dir=app/schema/crud6
```

**Option C: Extend the Generator**
```php
namespace MyCompany\MySprinkle\Schema;

use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator as BaseGenerator;

class MySchemaGenerator extends BaseGenerator
{
    protected static function getSchemaDefinitions(): array
    {
        return [
            'orders' => fn() => self::ordersSchema(),
            'customers' => fn() => self::customersSchema(),
            // ... your schemas
        ];
    }
    
    private static function ordersSchema(): array
    {
        return SchemaBuilder::create('orders', 'orders')
            // ... field definitions
            ->build();
    }
}
```

## Design Principles

### 1. CRUD6 is Schema-Agnostic
- CRUD6 itself does NOT define or commit schema files
- CRUD6 is a generic CRUD utility
- Consuming sprinkles define their own schemas

### 2. Utilities are Reusable
- `SchemaBuilder` can be used standalone
- `SchemaGenerator` can target any path
- Both are properly namespaced and autoloaded

### 3. CI-Friendly
- Standalone scripts in `scripts/` for CI (no autoloader needed)
- Namespaced utilities in `app/src/Schema/` for production use
- Both versions provide the same functionality

## Files

### Utility Classes (Namespaced, Autoloaded)
- `app/src/Schema/SchemaBuilder.php` - Schema builder utility
- `app/src/Schema/SchemaGenerator.php` - Schema generator utility
- `app/src/Bakery/GenerateSchemaCommand.php` - Bakery command

### Standalone Scripts (CI, No Autoloader)
- `scripts/SchemaBuilder.php` - Standalone version
- `scripts/GenerateSchemas.php` - Standalone version
- `scripts/generate-test-schemas.php` - CLI entry point

### Test Versions (For Internal Testing)
- `app/tests/Testing/SchemaBuilder.php` - Test helper
- `app/tests/Testing/GenerateSchemas.php` - Test helper

## Migration Guide for Existing Sprinkles

If your sprinkle was copying schemas from CRUD6's examples:

**Before:**
```bash
# Manually copying schemas
cp vendor/ssnukala/sprinkle-crud6/examples/schema/*.json app/schema/crud6/
```

**After (Option 1 - Bakery):**
```bash
php bakery crud6:generate-schema
```

**After (Option 2 - Programmatic):**
```php
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator;

SchemaGenerator::generateToPath('app/schema/crud6', 'app/locale/en_US');
```

## Testing

To test schema generation in your sprinkle:

```php
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaBuilder;

class SchemaGenerationTest extends TestCase
{
    public function testGenerateProductSchema(): void
    {
        $schema = SchemaBuilder::create('products', 'products')
            ->addStringField('name', required: true)
            ->build();
        
        $this->assertArrayHasKey('model', $schema);
        $this->assertEquals('products', $schema['model']);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertArrayHasKey('name', $schema['fields']);
    }
}
```

## FAQ

**Q: Should I commit generated schemas to my repository?**  
A: That depends on your sprinkle's purpose:
- For testing: No, generate dynamically (like CRUD6 does)
- For production: Yes, commit them if they're your application's data model

**Q: Can I customize the generated schemas?**  
A: Yes, use `SchemaBuilder` to create exactly the schemas you need, then save them.

**Q: Will schema generation overwrite my translations?**  
A: No, `SchemaGenerator` merges with existing translations. Base translations are preserved.

**Q: Can I use these utilities without the Bakery command?**  
A: Yes, use the namespaced classes directly via composer autoload.

## Related Documentation

- Main CRUD6 README: `/README.md`
- Example schemas: `/examples/schema/`
- Example translations: `/examples/locale/en_US/messages.php`
- CI workflow: `/.github/workflows/unit-tests.yml`
