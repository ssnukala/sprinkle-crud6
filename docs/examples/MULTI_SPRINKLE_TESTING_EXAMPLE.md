# Example: Testing CRUD6 Schemas from Multiple Sprinkles

This example demonstrates how other sprinkles using CRUD6 can configure their tests to use custom schema directories.

## Example Scenario

You have an application with:
- `sprinkle-crud6` (base CRUD6 functionality)
- `sprinkle-inventory` (inventory management schemas)
- `sprinkle-crm` (customer relationship management schemas)
- Your main application with custom schemas

## Directory Structure

```
myapp/
├── app/
│   ├── schema/
│   │   └── crud6/
│   │       ├── products.json
│   │       └── orders.json
│   └── tests/
│       ├── MyAppTestCase.php
│       └── Integration/
│           └── ProductApiTest.php
├── vendor/
│   ├── ssnukala/
│   │   └── sprinkle-crud6/
│   │       └── examples/
│   │           └── schema/
│   │               ├── users.json
│   │               ├── roles.json
│   │               └── groups.json
│   ├── mycompany/
│   │   ├── sprinkle-inventory/
│   │   │   └── schema/
│   │   │       ├── warehouses.json
│   │   │       └── stock_items.json
│   │   └── sprinkle-crm/
│   │       └── schema/
│   │           ├── customers.json
│   │           └── contacts.json
└── phpunit.xml
```

## Option 1: Configure in phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="app/tests/bootstrap.php">
    <php>
        <!-- Test all schemas from all sprinkles -->
        <env name="TEST_SCHEMA_DIRS" value="vendor/ssnukala/sprinkle-crud6/examples/schema,vendor/mycompany/sprinkle-inventory/schema,vendor/mycompany/sprinkle-crm/schema,app/schema/crud6"/>
    </php>
    
    <testsuites>
        <testsuite name="Application">
            <directory>app/tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## Option 2: Custom Test Base Class

Create a custom test base class in your application:

```php
<?php
// app/tests/MyAppTestCase.php

namespace MyApp\Tests;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

/**
 * Base test case for MyApp that includes all sprinkle schemas
 */
class MyAppTestCase extends CRUD6TestCase
{
    /**
     * Configure schema directories for all sprinkles in the application
     */
    protected function getTestSchemaDirs(): array
    {
        return [
            // CRUD6 example schemas (users, roles, groups)
            __DIR__ . '/../../vendor/ssnukala/sprinkle-crud6/examples/schema',
            
            // Inventory sprinkle schemas
            __DIR__ . '/../../vendor/mycompany/sprinkle-inventory/schema',
            
            // CRM sprinkle schemas
            __DIR__ . '/../../vendor/mycompany/sprinkle-crm/schema',
            
            // Application-specific schemas
            __DIR__ . '/../schema/crud6',
        ];
    }
}
```

## Option 3: Separate Test Suites per Sprinkle

Create separate test base classes for each sprinkle:

```php
<?php
// app/tests/InventoryTestCase.php

namespace MyApp\Tests;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

class InventoryTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/mycompany/sprinkle-inventory/schema',
        ];
    }
}
```

```php
<?php
// app/tests/CRMTestCase.php

namespace MyApp\Tests;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

class CRMTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/mycompany/sprinkle-crm/schema',
        ];
    }
}
```

Then configure separate test suites in phpunit.xml:

```xml
<phpunit>
    <testsuites>
        <testsuite name="Inventory">
            <directory>app/tests/Inventory</directory>
        </testsuite>
        <testsuite name="CRM">
            <directory>app/tests/CRM</directory>
        </testsuite>
        <testsuite name="Application">
            <directory>app/tests/Application</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Run specific test suites:

```bash
# Test only inventory schemas
phpunit --testsuite Inventory

# Test only CRM schemas
phpunit --testsuite CRM

# Test all
phpunit
```

## Example Test Class

```php
<?php
// app/tests/Integration/ProductApiTest.php

namespace MyApp\Tests\Integration;

use MyApp\Tests\MyAppTestCase;
use UserFrosting\Sprinkle\CRUD6\Tests\Integration\SchemaBasedApiTest;

/**
 * Test Product API endpoints
 * 
 * This test will automatically use all configured schema directories
 * and test the product schema along with all other available schemas.
 */
class ProductApiTest extends SchemaBasedApiTest
{
    // Inherits all test methods from SchemaBasedApiTest
    // Will automatically test all schemas found in configured directories
}
```

## Testing Multiple Sprinkles Together

When you want to test interactions between schemas from different sprinkles:

```php
<?php
// app/tests/Integration/CrossSprinkleTest.php

namespace MyApp\Tests\Integration;

use MyApp\Tests\MyAppTestCase;

class CrossSprinkleTest extends MyAppTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    
    /**
     * Test that inventory products can be linked to CRM customers
     */
    public function testProductCustomerRelationship(): void
    {
        // Setup test data from multiple sprinkles
        $customer = Customer::factory()->create();  // From CRM sprinkle
        $product = Product::factory()->create();    // From Inventory sprinkle
        $order = Order::factory()->create([          // From your app
            'customer_id' => $customer->id,
        ]);
        
        // Test the relationship
        $order->products()->attach($product->id, ['quantity' => 5]);
        
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }
}
```

## CI/CD Configuration

For GitHub Actions or other CI systems:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests with all sprinkle schemas
        env:
          TEST_SCHEMA_DIRS: vendor/ssnukala/sprinkle-crud6/examples/schema,vendor/mycompany/sprinkle-inventory/schema,vendor/mycompany/sprinkle-crm/schema,app/schema/crud6
        run: vendor/bin/phpunit
```

## Environment-Specific Configuration

Different configurations for different environments:

```bash
# Development: Test only your app schemas
export TEST_SCHEMA_DIRS="app/schema/crud6"
phpunit

# Staging: Test app + inventory
export TEST_SCHEMA_DIRS="app/schema/crud6,vendor/mycompany/sprinkle-inventory/schema"
phpunit

# Production/CI: Test everything
export TEST_SCHEMA_DIRS="vendor/ssnukala/sprinkle-crud6/examples/schema,vendor/mycompany/sprinkle-inventory/schema,vendor/mycompany/sprinkle-crm/schema,app/schema/crud6"
phpunit
```

## Best Practices

1. **Use explicit paths**: Be explicit about which schemas to test rather than wildcards
2. **Order matters**: List paths in priority order (first path has priority for duplicate schema names)
3. **Separate concerns**: Use different test base classes for different sprinkles when appropriate
4. **Document dependencies**: Document which schemas your tests depend on
5. **Version compatibility**: Ensure schema compatibility across sprinkles
6. **CI testing**: Always test with all sprinkle schemas in CI to catch integration issues

## Troubleshooting

### Schema Not Found

If tests can't find schemas:

```bash
# Debug: Print configured directories
php -r "
require 'vendor/autoload.php';
\$test = new UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase('test');
print_r(\$test->getTestSchemaDirs());
"
```

### Duplicate Schema Names

If multiple sprinkles have schemas with the same name, the first one found wins. To control this:

1. Rename schemas to be unique across sprinkles (recommended)
2. Control priority by ordering paths in TEST_SCHEMA_DIRS
3. Use separate test suites with different base classes

### Table Not Found

If tests fail with "table not found" errors:

1. Ensure migrations are run for all sprinkles
2. Add tables to `knownTables` filter in `schemaProvider()`
3. Use separate test suites that only test schemas for available tables
