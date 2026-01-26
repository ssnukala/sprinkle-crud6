# Test Failures Analysis - Auto-Generated vs Static Schemas

## Date
2026-01-26

## CI Run
https://github.com/ssnukala/sprinkle-crud6/actions/runs/21374262716/job/61526558138?pr=372

## Summary of Issues

The integration tests are now using **auto-generated schemas** (from DatabaseScanner/SchemaGenerator) instead of **static hand-crafted schemas**. This causes test failures because:

1. Auto-generated schemas use **modern CRUD6 structure**
2. Some tests expect **old schema structure** 
3. Auto-generated schemas have **more permissions** than expected
4. Auto-generated schemas use **`string` type** for email fields (not `email` type)

## Failure Details

### Failure 1: Permission Count Mismatch
**Test**: `DefaultSeedsTest::testDefaultPermissionsSeed()`
**Location**: `app/tests/Database/Seeds/DefaultSeedsTest.php:109`

```
Expected: 24 permissions (6 legacy + 18 schema-defined)
Actual: 31 permissions
```

**Root Cause**: Auto-generated schemas from UserFrosting tables (users, groups, roles, permissions, activities) create more permissions than the 4 hand-crafted example schemas (users, groups, roles, permissions).

**Analysis**:
- Hand-crafted schemas: 4 schemas × ~4-5 permissions each = ~18-20 permissions
- Auto-generated schemas: 5 tables × ~6 permissions each = ~30 permissions
- The auto-generated permissions are CORRECT - they match what DatabaseScanner finds
- The test expectation is OUTDATED - it expects static schema count

### Failure 2: Missing 'listable' Key
**Test**: `GenerateSchemaCommandTest::testGenerateCommandBasic()`
**Location**: `app/tests/Bakery/GenerateSchemaCommandTest.php:135`

```
Failed asserting that an array has the key 'listable'
```

**Root Cause**: Modern CRUD6 schemas use `show_in: ["list", "form", "detail"]` instead of individual boolean flags like `listable: true`.

**Old Format** (what test expects):
```json
{
  "name": {
    "type": "string",
    "listable": true,
    "editable": true,
    "searchable": true
  }
}
```

**New Format** (what SchemaGenerator produces):
```json
{
  "name": {
    "type": "string",
    "show_in": ["list", "form", "detail"],
    "sortable": true,
    "filterable": true,
    "searchable": true
  }
}
```

### Failure 3: Missing 'detail' Key (Singular)
**Test**: `GenerateSchemaCommandTest::testGenerateCommandWithRelationships()`
**Location**: `app/tests/Bakery/GenerateSchemaCommandTest.php:445`

```
Failed asserting that an array has the key 'detail'
```

**Root Cause**: Modern CRUD6 schemas use `details` (plural array) instead of `detail` (singular object).

**Old Format** (what test expects):
```json
{
  "detail": {
    "model": "orders",
    "foreign_key": "product_id"
  }
}
```

**New Format** (what SchemaGenerator produces):
```json
{
  "details": [
    {
      "model": "orders",
      "foreign_key": "product_id",
      "list_fields": ["id", "order_date", "amount"]
    }
  ]
}
```

### Failure 4: Type Mismatch - 'string' vs 'email'
**Test**: Some test expecting email type
**Location**: Unknown (need to investigate)

```
Expected: 'email'
Actual: 'string'
```

**Root Cause**: DatabaseScanner detects field types from database metadata, not column names:
- Database column type: `VARCHAR(255)` → SchemaGenerator produces `type: "string"`
- Static schemas: Manually set `type: "email"` based on column name pattern

**This is EXPECTED behavior** - auto-generated schemas use database types, not semantic types.

## Recommendations

### Option 1: Use Static Schemas for These Tests (RECOMMENDED)
Keep using hand-crafted static schemas in `examples/schema/` for tests that validate:
- Specific schema structure
- Specific permission counts
- Specific field types (email, password, etc.)

**Pros**:
- Tests validate exact expected structure
- No brittleness from schema generation changes
- Clear separation: bakery tests vs integration tests

**Cons**:
- Requires maintaining two sets of schemas

### Option 2: Update Tests to Match Auto-Generated Schemas
Update tests to:
- Expect modern `show_in` arrays instead of `listable` 
- Expect `details` (plural) instead of `detail` (singular)
- Expect `string` type instead of `email` type for auto-generated schemas
- Expect actual permission counts (31 not 24)

**Pros**:
- Tests validate real schema generation
- End-to-end workflow validation

**Cons**:
- Tests become dependent on database structure
- Permission counts can change
- Less precise validation

### Option 3: Hybrid Approach (BEST)
1. **Integration tests** use static schemas from `examples/schema/`
2. **Bakery tests** validate schema generation (already separate)
3. **Keep both** for comprehensive coverage

## Files That Need Changes

### If choosing Option 1 (Static Schemas):
1. `app/tests/Testing/GenerateSchemas.php` - Revert to use static schemas OR make conditional
2. `app/src/Testing/WithDatabaseSeeds.php` - Don't call generateFromDatabase() 

### If choosing Option 2 (Update Tests):
1. `app/tests/Database/Seeds/DefaultSeedsTest.php:109` - Change expected count from 24 to actual
2. `app/tests/Bakery/GenerateSchemaCommandTest.php:135` - Check `show_in` instead of `listable`
3. `app/tests/Bakery/GenerateSchemaCommandTest.php:445` - Check `details` instead of `detail`
4. All tests expecting `email` type - Accept `string` type for auto-generated schemas

### If choosing Option 3 (Hybrid):
1. Keep `GenerateSchemas` but make it optional via flag
2. Use static schemas by default for integration tests
3. Use auto-generated schemas only in dedicated bakery tests

## User's Perspective

From the comment:
> "This is probably because we are using auto generated schemas not the static schemas we have for testing - for auto generated schemas we can pass this test, as changing from string to email is more of a customization. We can still use the static schemas also for testing this comprehensively."

**User wants HYBRID APPROACH** - use both static and auto-generated schemas for comprehensive testing.

## Proposed Solution

Make `GenerateSchemas` conditional with an environment variable or flag:
```php
// In WithDatabaseSeeds.php
if (env('USE_STATIC_SCHEMAS', true)) {
    // Use static schemas from examples/schema/
} else {
    // Use auto-generated schemas
    GenerateSchemas::generateFromDatabase($this->ci);
}
```

This allows:
- ✅ Integration tests use static schemas (precise validation)
- ✅ Bakery tests use auto-generated schemas (real-world validation)
- ✅ Both test scenarios are covered
- ✅ No test failures from schema format differences
