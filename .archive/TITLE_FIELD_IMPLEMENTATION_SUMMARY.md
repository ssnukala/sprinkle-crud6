# Title Field Implementation Summary

## Problem Statement

When viewing individual records via routes like `/crud6/users/8`, the breadcrumb showed the ID ("8") instead of a human-readable identifier like the username. This made navigation less intuitive, especially for models where the primary key is not meaningful to users.

## Solution

Implemented a configurable `title_field` attribute in JSON schemas that specifies which field should be displayed in breadcrumbs and page titles for individual records.

## Implementation Details

### 1. Backend (PHP)

**File**: `app/src/ServicesProvider/SchemaService.php`

The SchemaService already included support for `title_field` in the detail context:

```php
// Include title_field if present (for displaying record name)
if (isset($schema['title_field'])) {
    $data['title_field'] = $schema['title_field'];
}
```

This code ensures that when a schema is filtered for the "detail" context, the `title_field` attribute is passed to the frontend.

### 2. Frontend (Vue.js)

**File**: `app/assets/views/PageRow.vue`

The frontend already had logic to use `title_field`:

```typescript
// Use title_field from schema, or fall back to ID
const titleField = flattenedSchema.value?.title_field
let recordName = titleField ? (fetchedRow[titleField] || recordId.value) : recordId.value
```

This creates a simple, schema-driven system:
1. Uses the field specified in `title_field` if configured
2. Falls back to the record ID if `title_field` is not specified or the field is empty

### 3. Schema Examples

Added `title_field` to example schemas to demonstrate usage:

| Schema | title_field | Reason |
|--------|-------------|--------|
| users01.json | `user_name` | Shows username instead of ID |
| products.json | `name` | Shows product name |
| orders.json | `order_number` | Shows order number (more meaningful than ID) |
| contacts.json | `last_name` | Shows last name for person identification |
| groups.json | `name` | Shows group name |
| categories.json | `name` | Shows category name |

### 4. Documentation

#### README.md Changes

Added to the example schema:
```json
{
  "model": "users",
  "title": "User Management",
  "description": "Manage system users", 
  "table": "users",
  "title_field": "user_name",  // ← NEW
  ...
}
```

Added comprehensive "Breadcrumb Display Configuration" section:
- Explained the purpose of `title_field`
- Documented fallback behavior
- Provided common examples for different model types

#### examples/schema/README.md Changes

Added complete "Breadcrumb and Page Title Configuration" section with:
- Detailed explanation of `title_field`
- Default behavior documentation
- Usage examples with breadcrumb output
- Table of common patterns for different model types
- Guidelines on when to use/skip `title_field`

### 5. Tests

Added comprehensive test coverage in `SchemaFilteringTest.php`:

**Test 1**: `testTitleFieldIncludedInDetailContext()`
- Verifies `title_field` is included in detail context
- Tests that the value matches the schema configuration
- Validates behavior when `title_field` is not present

**Test 2**: `testTitleFieldWithVariousFieldTypes()`
- Tests `title_field` with different field names (name, sku, order_number)
- Ensures the attribute works with various field types

## Usage Examples

### Basic Usage

```json
{
  "model": "users",
  "title_field": "user_name",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "user_name": { "type": "string", "label": "Username" }
  }
}
```

**Result**: Route `/crud6/users/8` displays breadcrumb:
```
Home > Users > john_doe
```
(Instead of: Home > Users > 8)

### Order Management Example

```json
{
  "model": "orders",
  "title_field": "order_number",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "order_number": { "type": "string", "label": "Order Number" }
  }
}
```

**Result**: Route `/crud6/orders/42` displays breadcrumb:
```
Home > Orders > ORD-2024-001
```
(Instead of: Home > Orders > 42)

### Product Catalog Example

```json
{
  "model": "products",
  "title_field": "name",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "name": { "type": "string", "label": "Product Name" },
    "sku": { "type": "string", "label": "SKU" }
  }
}
```

**Result**: Route `/crud6/products/15` displays breadcrumb:
```
Home > Products > Premium Widget
```
(Instead of: Home > Products > 15)

## Fallback Behavior

If `title_field` is not specified or the field is empty, the system will display the record's ID (primary key).

This ensures breadcrumbs always show a value, with the schema controlling what field is displayed.

## Benefits

1. **Better UX**: Users see meaningful identifiers instead of IDs (when configured)
2. **Schema-Driven**: Fully controlled by schema configuration
3. **No Hardcoded Assumptions**: No assumptions about field names
4. **Easy Configuration**: Just add one line to the schema
5. **Predictable**: Simple fallback to ID when not configured

## Files Changed

1. `examples/schema/users01.json` - Added `title_field`
2. `examples/schema/products.json` - Added `title_field`
3. `examples/schema/groups.json` - Added `title_field`
4. `examples/schema/contacts.json` - Added `title_field`
5. `examples/schema/orders.json` - Added `title_field`
6. `examples/schema/categories.json` - Added `title_field`
7. `README.md` - Added documentation
8. `examples/schema/README.md` - Added comprehensive documentation
9. `app/tests/ServicesProvider/SchemaFilteringTest.php` - Added tests

## Testing

All changes validated:
- ✅ JSON syntax validation for all schema files
- ✅ PHP syntax validation for all source files
- ✅ Unit tests added for `title_field` functionality
- ✅ Documentation review and completeness check

## Related Components

The `title_field` attribute is also used in:
- `app/assets/views/PageMasterDetail.vue` - For master-detail views
- `app/assets/components/CRUD6/Info.vue` - For info display

These components use the same fallback logic to ensure consistency across the application.
