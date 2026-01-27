# Hybrid Schema Testing Guide

## Overview

CRUD6 supports two types of schemas for testing, each with different characteristics and use cases. Tests must adapt their assertions based on which schema type is in use.

## Schema Types

### 1. Static Schemas (Default)

**Source**: Hand-crafted files in `examples/schema/`

**Characteristics**:
- Precise, predictable structure
- Use semantic field types (email, password)
- Exact permission counts (24 from 4 schemas)
- May include both modern (`show_in`) and legacy (`listable`) structures for backward compatibility testing
- Optimized for comprehensive integration testing

**Example**:
```json
{
  "model": "users",
  "fields": {
    "email": {
      "type": "email",           // Semantic type
      "show_in": ["list", "form"], // Modern structure
      "validation": {"email": true, "unique": true}
    }
  }
}
```

**Permission Count**:
- 6 legacy CRUD6 permissions (crud6_*, delete_crud6_field, etc.)
- 18 schema-defined permissions from 4 example schemas (users, roles, groups, permissions)
- **Total: Exactly 24**

### 2. Auto-Generated Schemas

**Source**: Generated via `php bakery crud6:generate` from database tables

**Characteristics**:
- Generated from actual database structure using DatabaseScanner + SchemaGenerator
- Use database types (string for VARCHAR, not email)
- Modern structure only (`show_in` arrays, `details` plural)
- Permission count varies based on tables scanned (may include activities, etc.)
- Optimized for validating schema generation workflow

**Example**:
```json
{
  "model": "users",
  "fields": {
    "email": {
      "type": "string",          // Database type (VARCHAR)
      "show_in": ["list", "form"], // Modern structure
      "validation": {"email": true, "unique": true}
    }
  }
}
```

**Permission Count**:
- 6 legacy CRUD6 permissions
- Schema-defined permissions from all tables scanned
- **Total: At least 24** (exact count depends on tables)

## Enabling Auto-Generated Schemas

Set environment variable before running tests:

```bash
# Use static schemas (default)
vendor/bin/phpunit

# Use auto-generated schemas
GENERATE_TEST_SCHEMAS=1 vendor/bin/phpunit
```

## Writing Tests That Support Both Types

### Using SchemaTestHelper (Recommended)

The `SchemaTestHelper` class provides methods to detect schema type and adapt assertions:

```php
use UserFrosting\Sprinkle\CRUD6\Testing\SchemaTestHelper;

class MyTest extends CRUD6TestCase
{
    public function testPermissions(): void
    {
        $role = Role::where('slug', 'crud6-admin')->first();
        
        // Automatic assertion based on schema type
        SchemaTestHelper::assertPermissionCount(
            $this,
            $role->permissions,
            'crud6-admin should have correct permission count'
        );
    }
    
    public function testEmailField(): void
    {
        $schema = $this->getSchema('users');
        $field = $schema['fields']['email'];
        
        $expected = SchemaTestHelper::getExpectedEmailFieldType();
        
        if (is_array($expected['type'])) {
            // Static: May be 'email' or 'string'
            $this->assertContains($field['type'], $expected['type']);
        } else {
            // Auto-generated: Always 'string'
            $this->assertEquals($expected['type'], $field['type']);
        }
        
        // Both should have email validation
        $this->assertTrue($field['validation']['email'] ?? false);
    }
}
```

### Manual Detection

```php
public function testSchemaStructure(): void
{
    $schema = $this->getSchema('users');
    
    if (SchemaTestHelper::isUsingGeneratedSchemas()) {
        // Auto-generated schemas always use modern structure
        $this->assertArrayHasKey('show_in', $schema['fields']['email']);
        $this->assertArrayHasKey('details', $schema); // Plural
    } else {
        // Static schemas may use either modern or legacy
        $field = $schema['fields']['email'];
        $hasModern = isset($field['show_in']);
        $hasLegacy = isset($field['listable']);
        
        $this->assertTrue($hasModern || $hasLegacy);
    }
}
```

## Key Differences Summary

| Aspect | Static Schemas | Auto-Generated Schemas |
|--------|---------------|------------------------|
| Field Types | Semantic (email, password) | Database (string, integer) |
| Structure | Mixed (modern + legacy) | Modern only (show_in, details) |
| Permission Count | Exactly 24 | At least 24 (varies) |
| Email Field Type | `email` or `string` | Always `string` |
| Password Field Type | `password` | `string` |
| show_in | Present in modern fields | Always present |
| listable/editable | May be present (legacy) | Never present |
| details (plural) | May use `detail` (singular) | Always `details` (plural) |

## SchemaTestHelper API

### Detection Methods

```php
// Check if using auto-generated schemas
SchemaTestHelper::isUsingGeneratedSchemas(): bool

// Check if schema uses modern structure
SchemaTestHelper::usesModernSchemaStructure(array $schema): bool
```

### Expected Values

```php
// Get expected permission count
SchemaTestHelper::getExpectedPermissionCount(): array
// Returns: ['min' => 24, 'exact' => 24|null]

// Get expected email field type
SchemaTestHelper::getExpectedEmailFieldType(): array
// Returns: ['type' => string|array, 'hasValidation' => bool]
```

### Assertion Helpers

```php
// Assert permission count (adapts automatically)
SchemaTestHelper::assertPermissionCount(
    TestCase $test,
    Collection $permissions,
    string $message = ''
): void

// Assert schema structure (modern or legacy)
SchemaTestHelper::assertSchemaStructure(
    TestCase $test,
    array $schema,
    string $fieldName
): void
```

## Best Practices

### 1. Use SchemaTestHelper for Adaptive Assertions

✅ **Do**:
```php
SchemaTestHelper::assertPermissionCount($this, $role->permissions);
```

❌ **Don't**:
```php
$this->assertCount(24, $role->permissions); // Fails with auto-generated
```

### 2. Test Both Schema Types in CI

Add to your CI workflow:

```yaml
- name: Run tests with static schemas
  run: vendor/bin/phpunit

- name: Run tests with auto-generated schemas
  run: GENERATE_TEST_SCHEMAS=1 vendor/bin/phpunit
```

### 3. Document Schema-Dependent Tests

Add comments explaining why assertions differ:

```php
/**
 * Test permission count.
 * 
 * Static schemas: Exactly 24 permissions from 4 example schemas
 * Auto-generated: At least 24, may include additional tables
 */
public function testPermissionCount(): void
{
    SchemaTestHelper::assertPermissionCount($this, $role->permissions);
}
```

### 4. Prefer Flexible Assertions

When possible, write tests that work with both types:

✅ **Good**:
```php
// Works with both types
$this->assertArrayHasKey('email', $schema['fields']);
$this->assertTrue($schema['fields']['email']['validation']['email']);
```

❌ **Bad**:
```php
// Only works with static schemas
$this->assertEquals('email', $schema['fields']['email']['type']);
```

## Testing Strategy

### Integration Tests (Default)

Use **static schemas** for:
- Precise schema structure validation
- Exact permission counts
- Regression testing
- CI/CD pipeline stability

```bash
vendor/bin/phpunit app/tests/Integration/
```

### Bakery Tests (Optional)

Use **auto-generated schemas** for:
- Validating schema generation workflow
- Testing DatabaseScanner functionality
- Testing SchemaGenerator functionality
- End-to-end workflow validation

```bash
GENERATE_TEST_SCHEMAS=1 vendor/bin/phpunit app/tests/Bakery/
```

## Migration Guide

If you have existing tests that fail with auto-generated schemas:

### Step 1: Identify Failing Assertions

Common failures:
- `assertCount(24, ...)` → Use `SchemaTestHelper::assertPermissionCount()`
- `assertEquals('email', ...)` → Use `SchemaTestHelper::getExpectedEmailFieldType()`
- `assertArrayHasKey('listable', ...)` → Use `SchemaTestHelper::assertSchemaStructure()`

### Step 2: Update Test Code

```php
// Before
$this->assertCount(24, $role->permissions);

// After
SchemaTestHelper::assertPermissionCount($this, $role->permissions);
```

### Step 3: Verify Both Schema Types

```bash
# Test with static schemas
vendor/bin/phpunit

# Test with auto-generated schemas
GENERATE_TEST_SCHEMAS=1 vendor/bin/phpunit
```

## Example: Complete Test Class

```php
<?php

namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\SchemaTestHelper;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class HybridSchemaTest extends CRUD6TestCase
{
    use RefreshDatabase;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->seedDatabase(); // Uses static or auto-generated based on env var
    }
    
    /**
     * Test permission count - works with both schema types.
     */
    public function testPermissionCount(): void
    {
        $role = Role::where('slug', 'crud6-admin')->first();
        
        // Automatically adapts to schema type
        SchemaTestHelper::assertPermissionCount(
            $this,
            $role->permissions,
            'crud6-admin permissions'
        );
    }
    
    /**
     * Test email field - adapts to schema type.
     */
    public function testEmailField(): void
    {
        $schema = $this->ci->get(SchemaService::class)->getSchema('users');
        $field = $schema['fields']['email'];
        
        $expected = SchemaTestHelper::getExpectedEmailFieldType();
        
        // Type assertion adapts to schema type
        if (is_array($expected['type'])) {
            $this->assertContains($field['type'], $expected['type']);
        } else {
            $this->assertEquals($expected['type'], $field['type']);
        }
        
        // Email validation should always be present
        $this->assertTrue($field['validation']['email']);
    }
    
    /**
     * Test schema structure - adapts to modern vs legacy.
     */
    public function testSchemaStructure(): void
    {
        $schema = $this->ci->get(SchemaService::class)->getSchema('users');
        
        // Helper automatically checks for modern or legacy structure
        SchemaTestHelper::assertSchemaStructure($this, $schema, 'email');
    }
}
```

## Conclusion

The hybrid schema testing approach allows CRUD6 to:

1. **Maintain stable integration tests** using precise static schemas
2. **Validate schema generation** using auto-generated schemas
3. **Test both workflows** without maintaining separate test suites
4. **Adapt assertions** automatically based on schema type

Use `SchemaTestHelper` methods to write tests that work seamlessly with both schema types!
