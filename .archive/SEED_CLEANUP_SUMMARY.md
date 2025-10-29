# CRUD6 Seed Cleanup Summary

## Issue
The UserFrosting 6 bakery seed command was failing with:
```
Error: Class is not a valid seed: UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles
Error: Process completed with exit code 1.
```

Additionally, the repository contained testing seeds and schemas that should not be in the main repository.

## Root Cause
1. Commerce-related seeds (ProductSeeder, CategorySeeder, etc.) were registered in CRUD6.php but were testing data, not core functionality
2. Multiple commerce-related migrations were registered but not needed for core CRUD6 functionality
3. Test schema files were in `app/schema/crud6/` instead of in test cases
4. DefaultPermissions seed didn't follow the UserFrosting 6 pattern of calling DefaultRoles in its run method

## Solution
Cleaned up the repository to only include core CRUD6 seeds that are needed for the sprinkle to function.

## Changes Made

### Removed Seeds (Testing Data)
All commerce-related seeders were removed as they were test data:
- `PurchaseOrderSeeder.php`
- `PurchaseOrderLinesSeeder.php`
- `ProductSeeder.php`
- `SalesOrderSeeder.php`
- `ProductCatalogSeeder.php`
- `CommerceSeeder.php`
- `SalesOrderLinesSeeder.php`
- `CategorySeeder.php`
- `CatalogSeeder.php`

### Removed Migrations (Testing Tables)
All commerce-related migrations were removed:
- `CatalogTable.php`
- `CategoryTable.php`
- `CommerceRolesTable.php`
- `ProductCatalogTable.php`
- `ProductTable.php`
- `PurchaseOrderLinesTable.php`
- `PurchaseOrderTable.php`
- `SalesOrderLinesTable.php`
- `SalesOrderTable.php`

### Removed Schemas (Testing Data)
All test schema files were removed from `app/schema/crud6/`:
- `sales_order_lines.json`
- `category.json`
- `catalog.json`
- `sales_order.json`
- `purchase_order.json`
- `product_catalog.json`
- `purchase_order_lines.json`
- `product.json`
- `db1/users.json`

### Updated Files

#### app/src/CRUD6.php
- `getMigrations()` now returns empty array (no test migrations)
- `getSeeds()` now only returns `DefaultRoles` and `DefaultPermissions`
- Removed all imports for commerce seeders and migrations

#### app/src/Database/Seeds/DefaultPermissions.php
- Added call to `(new DefaultRoles())->run()` at the beginning of `run()` method
- Now follows the exact UserFrosting 6 pattern from sprinkle-account

### Kept Files (Core Functionality)

#### Seeds (Required for CRUD6)
- `DefaultRoles.php` - Creates the `crud6-admin` role
- `DefaultPermissions.php` - Creates CRUD6 permissions and syncs them with roles

#### Tests
- `app/tests/Database/Seeds/DefaultSeedsTest.php` - Tests for the seed functionality

#### Examples (Documentation)
All example schema files remain in `examples/` directory:
- `products.json`
- `categories.json`
- `orders.json`
- `product_categories.json`
- etc.

These are documentation/examples for users, not test data.

## UserFrosting 6 Compatibility

Both remaining seeds now follow the official UserFrosting 6 pattern:

1. ✅ Implement `SeedInterface`
2. ✅ Have a `run(): void` method
3. ✅ DefaultPermissions calls DefaultRoles in its run method (like UF6 Account sprinkle)
4. ✅ Use proper Eloquent model access patterns
5. ✅ Check for existing records before creating (idempotent)
6. ✅ Properly sync permissions with roles

## Integration Test
The `.github/workflows/integration-test.yml` already only seeds the correct data:
```bash
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force
```

No changes needed to the workflow file.

## Verification
- ✅ All PHP files have valid syntax
- ✅ No references to removed seeds/migrations remain
- ✅ Seeds follow UserFrosting 6 patterns
- ✅ Integration test workflow is correct
- ✅ Example files remain in examples/ directory for documentation

## Testing Strategy
Test data and schemas should be:
1. Created in test cases when needed
2. Stored in the `examples/` directory as documentation
3. NOT included as migrations or seeds in the main sprinkle class

This keeps the repository clean and focused on core functionality.

## Date
October 29, 2024
