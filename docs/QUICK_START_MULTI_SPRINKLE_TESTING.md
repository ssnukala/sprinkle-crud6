# Quick Start: Testing Multiple Sprinkles with CRUD6

This quick start guide shows you how to configure CRUD6's testing framework to test schemas from multiple sprinkles in just a few minutes.

## The Problem

You have multiple sprinkles using CRUD6:
- `sprinkle-crud6` (base)
- `sprinkle-inventory` (your custom inventory schemas)
- `sprinkle-crm` (your CRM schemas)

Previously, tests could only discover schemas from the hardcoded `examples/schema` directory.

## The Solution (3 Steps)

### Step 1: Add Environment Variable to phpunit.xml

Open your `phpunit.xml` and add the `TEST_SCHEMA_DIRS` environment variable:

```xml
<phpunit>
    <php>
        <!-- Add this line with your schema paths -->
        <env name="TEST_SCHEMA_DIRS" value="examples/schema,vendor/mycompany/sprinkle-inventory/schema,vendor/mycompany/sprinkle-crm/schema,app/schema/crud6"/>
    </php>
</phpunit>
```

### Step 2: Run Your Tests

That's it! Run your tests normally:

```bash
phpunit
```

The testing framework will automatically discover and test schemas from ALL configured directories.

### Step 3: Verify Schema Discovery

To see which schemas were discovered:

```bash
phpunit --filter schemaProvider --testdox
```

You should see tests for schemas from all your configured directories.

## Alternative: Override in Test Class

If you prefer code over configuration:

```php
<?php
// app/tests/MyTestCase.php

namespace MyApp\Tests;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

class MyTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/mycompany/sprinkle-inventory/schema',
            __DIR__ . '/../../vendor/mycompany/sprinkle-crm/schema',
            __DIR__ . '/../../../vendor/ssnukala/sprinkle-crud6/examples/schema',
        ];
    }
}
```

Then extend `MyTestCase` instead of `CRUD6TestCase` in your tests:

```php
<?php
namespace MyApp\Tests\Integration;

use MyApp\Tests\MyTestCase;  // Use your custom base class

class MyIntegrationTest extends MyTestCase
{
    // Tests will automatically use schemas from all configured directories
}
```

## Path Formats Supported

You can use any of these path formats:

```xml
<!-- Relative to project root -->
<env name="TEST_SCHEMA_DIRS" value="examples/schema,app/schema/crud6"/>

<!-- Absolute paths -->
<env name="TEST_SCHEMA_DIRS" value="/var/www/myapp/schemas,/opt/sprinkles/inventory/schema"/>

<!-- Mixed -->
<env name="TEST_SCHEMA_DIRS" value="examples/schema,/absolute/path/schema"/>
```

## Common Scenarios

### Scenario 1: Development (Test Your App Only)

```bash
export TEST_SCHEMA_DIRS="app/schema/crud6"
phpunit
```

### Scenario 2: Staging (Test App + One Sprinkle)

```bash
export TEST_SCHEMA_DIRS="app/schema/crud6,vendor/mycompany/sprinkle-inventory/schema"
phpunit
```

### Scenario 3: CI/CD (Test Everything)

```yaml
# .github/workflows/test.yml
- name: Run Tests
  env:
    TEST_SCHEMA_DIRS: examples/schema,vendor/mycompany/sprinkle-inventory/schema,vendor/mycompany/sprinkle-crm/schema,app/schema/crud6
  run: vendor/bin/phpunit
```

## Verification

### Check What Directories Are Being Used

Create a simple test:

```php
public function testSchemaDirectoryConfiguration(): void
{
    $dirs = $this->getTestSchemaDirs();
    dump($dirs);  // Will show all configured directories
    
    $this->assertNotEmpty($dirs);
}
```

### List All Discovered Schemas

Run with verbose output:

```bash
phpunit --testdox --filter schemaProvider
```

## Troubleshooting

### No Schemas Found?

1. **Check paths exist:**
   ```bash
   ls -la examples/schema
   ls -la vendor/mycompany/sprinkle-inventory/schema
   ```

2. **Check phpunit.xml syntax:**
   - Paths are comma-separated (no semicolons)
   - No quotes around individual paths
   - Use forward slashes (`/`) not backslashes

3. **Enable debug output:**
   ```bash
   phpunit -v
   ```

### Schema Not Being Tested?

The testing framework only tests schemas for tables that exist. Make sure:
1. The table exists in your test database
2. The table is in the `knownTables` array (users, roles, groups, permissions, activities)
3. Or override `schemaProvider()` to add your custom tables

## Next Steps

- **Full Documentation:** [docs/TESTING_MULTI_SPRINKLE.md](../docs/TESTING_MULTI_SPRINKLE.md)
- **Detailed Examples:** [docs/examples/MULTI_SPRINKLE_TESTING_EXAMPLE.md](../docs/examples/MULTI_SPRINKLE_TESTING_EXAMPLE.md)
- **Implementation Details:** [.archive/MULTI_SPRINKLE_TESTING_IMPLEMENTATION.md](../.archive/MULTI_SPRINKLE_TESTING_IMPLEMENTATION.md)

## Summary

✅ Add `TEST_SCHEMA_DIRS` to phpunit.xml  
✅ Run `phpunit`  
✅ Done!

Your tests will now automatically discover and test schemas from all configured directories across all your sprinkles.
