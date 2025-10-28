# CRUD6 Sprinkle Migration Setup Fix

## Problem Statement

The CRUD6 sprinkle and migrations were not set up properly according to UserFrosting 6 patterns. The issues identified were:

1. CRUD6.php did not implement the `MigrationRecipe` interface
2. No `getMigrations()` method was defined to register migration classes
3. Migrations existed but were not discoverable by the UserFrosting 6 migration system
4. RolePermSeed.php in v600 folder was problematic (incorrect namespace, no-op migration)

## Reference Implementation

The fix was based on the official UserFrosting 6 sprinkle-account implementation:
- Repository: https://github.com/userfrosting/sprinkle-account/tree/6.0
- Main file: app/src/Account.php

## Changes Implemented

### 1. CRUD6.php - Implement MigrationRecipe Interface

**Before:**
```php
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
// ... other imports

class CRUD6 implements SprinkleRecipe, SeedRecipe
```

**After:**
```php
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\CreateCategoriesTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\CreateOrdersTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\CreateProductsTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\CreateOrderDetailsTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\CreateProductCategoriesTable;

class CRUD6 implements SprinkleRecipe, MigrationRecipe, SeedRecipe
```

### 2. Added getMigrations() Method

Added the required `getMigrations()` method to register all migration classes:

```php
/**
 * {@inheritDoc}
 */
public function getMigrations(): array
{
    return [
        CreateCategoriesTable::class,
        CreateProductsTable::class,
        CreateOrdersTable::class,
        CreateOrderDetailsTable::class,
        CreateProductCategoriesTable::class,
    ];
}
```

### 3. Removed Problematic Files

- **Deleted**: `app/src/Database/Migrations/v600/RolePermSeed.php`
  - Empty no-op migration with incorrect copyright header
  - Claimed to be from Account sprinkle instead of CRUD6
  - Migrations should not seed data - that's what seed classes are for
  
- **Removed**: `app/src/Database/Migrations/v600/` directory
  - Empty directory after RolePermSeed.php removal

### 4. Added Test Coverage

Created `app/tests/CRUD6Test.php` to verify the sprinkle configuration:

**Tests Added:**
- `testImplementsSprinkleRecipe()` - Verifies SprinkleRecipe interface
- `testImplementsMigrationRecipe()` - Verifies MigrationRecipe interface
- `testImplementsSeedRecipe()` - Verifies SeedRecipe interface
- `testGetMigrationsReturnsExpectedClasses()` - Verifies all migrations are registered
- `testMigrationClassesExist()` - Verifies migration classes exist
- `testGetName()` - Verifies sprinkle name
- `testGetPath()` - Verifies sprinkle path

## Migration Structure

### Migration Order

The migrations are registered in dependency order:

1. **CreateCategoriesTable** - Independent (no dependencies)
2. **CreateProductsTable** - Independent (no foreign key on category_id)
3. **CreateOrdersTable** - Independent (no dependencies)
4. **CreateOrderDetailsTable** - Depends on CreateOrdersTable
5. **CreateProductCategoriesTable** - Depends on CreateProductsTable and CreateCategoriesTable

### Migration Dependencies

Each migration declares its dependencies using the `$dependencies` static property:

**CreateCategoriesTable.php:**
```php
public static array $dependencies = [];
```

**CreateProductsTable.php:**
```php
public static array $dependencies = [];
```

**CreateOrdersTable.php:**
```php
public static array $dependencies = [];
```

**CreateOrderDetailsTable.php:**
```php
public static array $dependencies = [
    CreateOrdersTable::class,
];
```

**CreateProductCategoriesTable.php:**
```php
public static array $dependencies = [
    CreateProductsTable::class,
    CreateCategoriesTable::class,
];
```

## How UserFrosting 6 Discovers Migrations

1. **Sprinkle Registration**: The CRUD6 sprinkle is added to the application's sprinkle list
2. **Interface Detection**: UserFrosting 6 detects that CRUD6 implements `MigrationRecipe`
3. **Migration Discovery**: Framework calls `getMigrations()` to get the list of migration classes
4. **Dependency Resolution**: Framework uses `$dependencies` property on each migration to determine execution order
5. **Migration Execution**: Migrations are run in the correct order via `php bakery migrate`

## Benefits of This Fix

1. ✅ **Proper Interface Implementation**: CRUD6 now follows UserFrosting 6 patterns
2. ✅ **Migration Discovery**: Migrations are discoverable by the migration system
3. ✅ **Correct Execution Order**: Dependencies ensure migrations run in the right sequence
4. ✅ **Clean Code**: Removed confusing/non-functional migration file
5. ✅ **Test Coverage**: Added tests to prevent regression
6. ✅ **Consistency**: Matches patterns from official UserFrosting sprinkles

## Validation

All changes have been validated:

- ✅ PHP syntax check: All files pass `php -l`
- ✅ Migration dependencies: Correctly defined with foreign key constraints
- ✅ Interface implementation: Implements SprinkleRecipe, MigrationRecipe, SeedRecipe
- ✅ Test suite: Comprehensive tests for sprinkle configuration
- ✅ Pattern matching: Follows sprinkle-account reference implementation

## Commands to Run Migrations

After these changes, migrations can be run using standard UserFrosting 6 commands:

```bash
# Run all pending migrations
php bakery migrate

# Run seeds (after migrations)
php bakery seed

# Rollback last migration batch
php bakery migrate:rollback

# Check migration status
php bakery migrate:status
```

## References

- **UserFrosting 6 Documentation**: https://learn.userfrosting.com/6.0
- **Sprinkle Account Reference**: https://github.com/userfrosting/sprinkle-account/tree/6.0
- **MigrationRecipe Interface**: Part of userfrosting/sprinkle-core
- **Migration Base Class**: UserFrosting\Sprinkle\Core\Database\Migration

## Files Modified

1. `app/src/CRUD6.php` - Added MigrationRecipe implementation
2. `app/src/Database/Migrations/v600/RolePermSeed.php` - Removed (deleted)
3. `app/tests/CRUD6Test.php` - Added comprehensive tests

## Commits

1. **f6876eb**: Implement MigrationRecipe and register all migrations in CRUD6 sprinkle
2. **e4f24fb**: Add CRUD6 sprinkle test to verify MigrationRecipe implementation
