# Schema-Driven Code Review Summary

## Problem Statement

Review the code to check for any hard coding for users, roles etc. All the code and testing should be driven by the JSON schema files. Examples folder will have the JSON files and any SQL scripts etc., but the frontend and backend code should have code that is driven by the JSON schema.

## Solution Overview

The codebase has been reviewed and updated to ensure it follows a schema-driven approach throughout. The key principle is: **All models, fields, relationships, and permissions are defined in JSON schema files, not hardcoded in the application code.**

## Key Findings

### ✅ Already Schema-Driven

These components were already correctly schema-driven:

1. **DefaultPermissions.php** (lines 98-167)
   - Uses `loadPermissionsFromSchemas()` method
   - Dynamically loads permissions from all schema files in `app/schema/crud6/`
   - Creates Permission objects based on schema definitions
   - No hardcoded permission lists needed

2. **Frontend fixtures.ts**
   - Uses `getAvailableModels()` to load from config
   - Loads schemas dynamically from examples/schema/
   - No hardcoded model lists

3. **Controllers and Routes**
   - Use generic `{model}` parameter
   - Work with `CRUD6Model` dynamically configured from schemas
   - No model-specific code

## Changes Made

### 1. SchemaBasedApiTest.php - Dynamic Schema Discovery

**Before**: Hardcoded list of 4 test models
```php
$testSchemas = [
    'users',       
    'roles',       
    'groups',      
    'permissions', 
];
```

**After**: Dynamic discovery from schema directory
```php
$schemaDir = __DIR__ . '/../../../examples/schema';
$schemaFiles = glob($schemaDir . '/*.json');
$availableSchemas = array_map(fn($file) => basename($file, '.json'), $schemaFiles);
// Filters to known tables with proper validation
```

**Result**: Tests now discover 11+ schemas automatically instead of 4 hardcoded ones.

### 2. Model Class Resolution

**Before**: Hardcoded map with arbitrary fallback
```php
$modelMap = [/* hardcoded mappings */];
return $modelMap[$modelName] ?? User::class; // Fallback to User!
```

**After**: Schema-driven with generic CRUD6Model
```php
$userFrostingModels = [/* only for models with factories */];
return $userFrostingModels[$modelName] ?? CRUD6Model::class; // Generic model!
```

**Why UserFrosting models still listed?**: These models (User, Role, Group, Permission, Activity) have factories needed for test data generation. For all other models, the generic CRUD6Model is used.

### 3. Related Model Resolution

**Before**: Only fallback map
```php
$fallbackMap = [/* hardcoded mappings */];
return $fallbackMap[$name] ?? null;
```

**After**: Schema-first approach
```php
try {
    $schema = $schemaService->getSchema($pluralName);
    if ($schema) {
        return $this->getModelClass($schema);  // Schema-driven!
    }
} catch (\Exception $e) {
    // Only then fallback to known UserFrosting models
}
```

### 4. Documentation Updates

Added comprehensive "Schema-Driven Philosophy" section to README explaining:
- No hardcoded models principle
- How to add new models (schema file + database table only)
- What gets automatically created
- What NOT to change
- Which components are schema-driven

### 5. Code Clarifications

- **DefaultRoles.php**: Added comment explaining it's for sprinkle-specific roles only (not user models)
- **DefaultPermissions.php**: Added comment highlighting schema-driven permission loading
- **SQL files**: Added "DO NOT EDIT MANUALLY" warnings and regeneration instructions

## Architecture Validation

### Schema-Driven Components ✓

| Component | Status | How It Works |
|-----------|--------|--------------|
| **Controllers** | ✓ Schema-Driven | Use generic `{model}` param + CRUD6Model |
| **Routes** | ✓ Schema-Driven | Handle any `{model}` dynamically |
| **Permissions** | ✓ Schema-Driven | Loaded from all schemas via `loadPermissionsFromSchemas()` |
| **Tests** | ✓ NOW Schema-Driven | Auto-discover schemas from directory |
| **Frontend** | ✓ Schema-Driven | Load schemas dynamically via SchemaService API |
| **Validation** | ✓ Schema-Driven | Based on field definitions in schema |
| **Relationships** | ✓ Schema-Driven | Defined in schema relationship arrays |

### What CAN Be Hardcoded ✓

Only these are acceptable to hardcode:

1. **Sprinkle-specific roles** (DefaultRoles.php)
   - `crud6-admin` role is specific to CRUD6 sprinkle itself
   - NOT for user-defined models

2. **UserFrosting core models with factories** (in tests)
   - User, Role, Group, Permission, Activity
   - Needed because they have factories for test data
   - Used as fallback when schema-based lookup fails

3. **Test examples** (in frontend tests)
   - Using 'users', 'products' as examples in unit tests
   - Testing the fixture loader mechanism itself

## Verification

### Schema Discovery Test
```bash
$ php -r "... test schema discovery ..."
Available schemas after filtering:
  - activities
  - categories
  - contacts
  - groups
  - order_details
  - orders
  - permissions
  - product_categories
  - products
  - roles
  - users
```

✅ **11 schemas discovered automatically!**

### Syntax Check
```bash
$ find app/src app/tests -name "*.php" -exec php -l {} \;
# Result: No syntax errors detected
```

✅ **All files valid!**

## Adding a New Model - The Schema-Driven Way

### Before This Review

To add a new "products" model:
1. Create products.json schema ✓
2. Create database table ✓
3. Update SchemaBasedApiTest.php with 'products' in hardcoded list ✗
4. Update model class map ✗
5. Update test fixtures ✗

### After This Review

To add a new "products" model:
1. Create products.json schema ✓
2. Create database table ✓
3. **Done! Everything else is automatic.**

- API endpoints: ✅ Automatic (generic routes)
- Tests: ✅ Automatic (schema discovery)
- Permissions: ✅ Automatic (loadPermissionsFromSchemas)
- Frontend: ✅ Automatic (schema API)
- Validation: ✅ Automatic (from schema fields)

## Files Modified

1. `app/tests/Integration/SchemaBasedApiTest.php` - Dynamic schema discovery
2. `app/src/Database/Seeds/DefaultRoles.php` - Clarified purpose
3. `app/src/Database/Seeds/DefaultPermissions.php` - Documented schema-driven approach
4. `app/sql/seeds/crud6-test-data.sql` - Added generation warning
5. `app/sql/migrations/crud6-tables.sql` - Added generation warning
6. `README.md` - Added "Schema-Driven Philosophy" section

## Conclusion

✅ **CRUD6 is now truly schema-driven throughout**

- No hardcoded model lists in tests
- No hardcoded model mappings (except UserFrosting core models with factories)
- Automatic discovery of new models from schema directory
- Clear documentation of the schema-driven approach
- Appropriate comments explaining any necessary hardcoding

The codebase now follows the principle that **"all the code and testing should be driven by the JSON schema files"** as specified in the problem statement.
