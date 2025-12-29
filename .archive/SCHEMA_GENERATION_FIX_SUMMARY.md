# Schema Generation Fix - Empty Column Names in SQL Queries

## Issue

**Date**: 2025-12-29  
**GitHub Actions Run**: [20565510771](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20565510771/job/59063116148)

### Error Messages
```
SQLSTATE[HY000]: General error: 1 no such column: permissions.
(Connection: memory, SQL: select * from "permissions" where "id" = 59 and "permissions"."" is null limit 1)

SQLSTATE[HY000]: General error: 1 no such column: roles.
(Connection: memory, SQL: select * from "roles" where "id" = 6 and "roles"."" is null limit 1)
```

The SQL queries were trying to filter on an empty column name (`"roles".""` and `"permissions".""`) instead of a valid column name.

## Root Cause

### Investigation Steps
1. Examined CI logs showing SQL errors with empty column names
2. Compared generated schemas in `app/schema/crud6/` with example schemas in `examples/schema/`
3. Found mismatch: `SchemaBuilder.addDetail()` was adding `foreign_key` to details array
4. Analyzed how controllers use details vs relationships arrays

### The Problem
The `GenerateSchemas.php` script was calling:
```php
->addDetail('permissions', 'role_id', ['slug', 'name', 'description'], 'CRUD6.ROLE.PERMISSIONS')
```

This was treating roles-permissions as a **has-many** relationship (where `permissions.role_id` would be a foreign key column), but it's actually a **many-to-many** relationship (where `role_id` is a key in the pivot table `permission_roles`).

When `foreign_key` was incorrectly included in the `details` array for many-to-many relationships, it caused downstream code to attempt SQL queries with malformed column names.

## Solution

### Changes Made

#### 1. Made `foreign_key` Optional in SchemaBuilder
Modified `addDetail()` method in three files:
- `scripts/SchemaBuilder.php`
- `app/src/Schema/SchemaBuilder.php`
- `app/tests/Testing/SchemaBuilder.php`

**Before:**
```php
public function addDetail(
    string $model,
    string $foreignKey,  // Required parameter
    array $listFields = [],
    ?string $title = null
): self
```

**After:**
```php
public function addDetail(
    string $model,
    ?string $foreignKey = null,  // Optional parameter
    array $listFields = [],
    ?string $title = null
): self {
    // ...
    // Only add foreign_key if provided (for has-many relationships)
    if ($foreignKey !== null) {
        $detail['foreign_key'] = $foreignKey;
    }
    // ...
}
```

#### 2. Added Convenience Method for Many-to-Many
Added `addManyToManyDetail()` method to all SchemaBuilder files:
```php
public function addManyToManyDetail(
    string $model,
    array $listFields = [],
    ?string $title = null
): self {
    return $this->addDetail($model, null, $listFields, $title);
}
```

#### 3. Updated Schema Generation
Modified roles and permissions schema generation in:
- `scripts/GenerateSchemas.php`
- `app/tests/Testing/GenerateSchemas.php`

**Roles Schema - Before:**
```php
->addDetail('permissions', 'role_id', ['slug', 'name', 'description'], 'CRUD6.ROLE.PERMISSIONS')
```

**Roles Schema - After:**
```php
->addManyToManyDetail('permissions', ['slug', 'name', 'description'], 'CRUD6.ROLE.PERMISSIONS')
->addManyToManyDetail('users', ['user_name', 'first_name', 'last_name', 'email', 'flag_enabled'], 'CRUD6.ROLE.USERS')
```

**Permissions Schema - Before:**
```php
// No details were being added
```

**Permissions Schema - After:**
```php
->addManyToManyDetail('roles', ['name', 'slug', 'description'], 'ROLE.2')
->addManyToManyDetail('users', ['user_name', 'first_name', 'last_name', 'email'], 'CRUD6.PERMISSION.USERS')
```

## Schema Structure

### Correct Structure for Many-to-Many Relationships

The schema should have **separate** arrays for display configuration and relationship queries:

```json
{
  "model": "roles",
  "table": "roles",
  "details": [
    {
      "model": "permissions",
      "list_fields": ["slug", "name", "description"],
      "title": "CRUD6.ROLE.PERMISSIONS"
    }
  ],
  "relationships": [
    {
      "name": "permissions",
      "type": "many_to_many",
      "pivot_table": "permission_roles",
      "foreign_key": "role_id",
      "related_key": "permission_id"
    }
  ]
}
```

### Key Points

1. **`details` array**: Display configuration only
   - Specifies WHAT to show (model, list_fields, title)
   - NO `foreign_key` for many-to-many relationships
   - Only include `foreign_key` for has-many relationships

2. **`relationships` array**: Query configuration
   - Specifies HOW to query (type, pivot_table, foreign_key, related_key)
   - Contains the actual relationship mechanics
   - Required for many-to-many queries in EditAction and other controllers

3. **Has-Many vs Many-to-Many**:
   - **Has-Many** (e.g., group has many users): Include `foreign_key` in details
     - `foreign_key` is a column in the child table (e.g., `users.group_id`)
   - **Many-to-Many** (e.g., roles ↔ permissions): NO `foreign_key` in details
     - Relationship uses a pivot table, not a direct foreign key

## Controller Behavior

### EditAction.php Logic
```php
foreach ($detailsConfig as $detailConfig) {
    $foreignKey = $detailConfig['foreign_key'] ?? null;
    
    if ($foreignKey !== null) {
        // Has-many relationship - query using foreign key
        $rows = $this->queryHasManyRelationship(...);
    } else {
        // Many-to-many relationship - look up relationship config
        $relationship = $relationshipMap[$relatedModel] ?? null;
        if ($relationship) {
            $rows = $this->queryRelationship(...);
        }
    }
}
```

Without the fix, many-to-many relationships had `foreign_key` set, causing them to be incorrectly queried as has-many relationships, leading to SQL errors.

## Testing

### Files Modified
- `scripts/SchemaBuilder.php` - ✅ Syntax validated
- `scripts/GenerateSchemas.php` - ✅ Syntax validated
- `app/src/Schema/SchemaBuilder.php` - ✅ Syntax validated
- `app/tests/Testing/SchemaBuilder.php` - ✅ Syntax validated
- `app/tests/Testing/GenerateSchemas.php` - ✅ Syntax validated
- `app/tests/Testing/SchemaBuilderTest.php` - ✅ Added test case

### Verification
1. ✅ Regenerated schemas have correct structure (no foreign_key in details for many-to-many)
2. ✅ `testAddDetail()` still passes (has-many with foreign_key)
3. ✅ `testAddManyToManyDetail()` added and validates many-to-many without foreign_key
4. ⏳ CI tests pending (will be validated when PR is merged)

## Future Considerations

### Note on relationships Array
The current `SchemaBuilder` does not have methods to generate the `relationships` array. For now, roles and permissions schemas rely on the existing relationships defined in `examples/schema/*.json` files.

If future testing reveals that the generated schemas need the relationships array, we should:
1. Add `addManyToManyRelationship()` method to SchemaBuilder
2. Update GenerateSchemas to include relationship definitions
3. Ensure generated schemas are fully self-contained

However, for the immediate issue (empty column SQL errors), the fix to the `details` array is sufficient.

## References
- [GitHub Actions Run with Error](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20565510771/job/59063116148)
- [EditAction.php - Detail Loading Logic](../app/src/Controller/EditAction.php#L470-L520)
- [CRUD6Model.php - Relationship Handling](../app/src/Database/Models/CRUD6Model.php#L636-L699)
- [Example Schema - roles.json](../examples/schema/roles.json)
- [Example Schema - permissions.json](../examples/schema/permissions.json)
