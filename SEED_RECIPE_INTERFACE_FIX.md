# Seed Recipe Interface Fix - Technical Documentation

## Problem Statement

Integration test was failing with error:
```
Error: ] Class is not a valid seed :                                            
         UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles                
Error: Process completed with exit code 1.
```

**Reference**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18347392709/job/52257698651

Despite:
- ✅ Seed classes implementing `SeedInterface` correctly
- ✅ Seeds registered in `CRUD6::getSeeds()` method  
- ✅ No invalid imports in CRUD6.php
- ✅ All PHP files having valid syntax
- ✅ Seed structure matching Account sprinkle patterns

## Root Cause Analysis

### The Seed Discovery Process

UserFrosting 6 uses `SprinkleSeedsRepository` to discover seeds across all sprinkles:

```php
// From UserFrosting\Sprinkle\Core\Seeder\SprinkleSeedsRepository::all()
foreach ($this->sprinkleManager->getSprinkles() as $sprinkle) {
    if (!$sprinkle instanceof SeedRecipe) {
        continue;  // <-- CRUD6 was being skipped here!
    }
    foreach ($sprinkle->getSeeds() as $commandsClass) {
        // Validate and register seed classes
    }
}
```

### The Issue

The CRUD6 sprinkle class was declared as:
```php
class CRUD6 implements SprinkleRecipe
```

But UserFrosting 6 requires sprinkles with seeds to implement **both**:
1. `SprinkleRecipe` - Base sprinkle interface
2. `SeedRecipe` - Seed discovery interface

Without `SeedRecipe`, the `SprinkleSeedsRepository` skips the sprinkle entirely during seed discovery, causing the bakery seed command to fail with "Class is not a valid seed".

### Comparison with Account Sprinkle

Looking at `userfrosting/sprinkle-account` (6.0 branch):

```php
class Account implements
    SprinkleRecipe,
    MigrationRecipe,
    SeedRecipe,        // <-- Required for seed discovery!
    EventListenerRecipe,
    TwigExtensionRecipe,
    BakeryRecipe
```

The Account sprinkle implements `SeedRecipe` alongside other recipe interfaces.

## Solution

### The Fix

**File**: `app/src/CRUD6.php`

```diff
+ use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
  use UserFrosting\Sprinkle\SprinkleRecipe;
  // ... other imports ...

- class CRUD6 implements SprinkleRecipe
+ class CRUD6 implements SprinkleRecipe, SeedRecipe
```

### Why This Works

1. **Seed Discovery**: `SprinkleSeedsRepository` now recognizes CRUD6 as having seeds
2. **Seed Registration**: The `getSeeds()` method is now called during discovery
3. **Validation Passes**: Seeds are properly instantiated and validated
4. **Bakery Command Success**: Seeds can be executed via `php bakery seed` command

## Verification

### Before Fix
```bash
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
# Error: Class is not a valid seed
```

### After Fix
```bash
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
# ✅ Success - seed runs without error
```

### Integration Test Workflow

The GitHub Actions workflow runs seeds in this order:
1. ✅ `Account::DefaultGroups`
2. ✅ `Account::DefaultPermissions`
3. ✅ `Account::DefaultRoles`
4. ✅ `Account::UpdatePermissions`
5. ✅ `CRUD6::DefaultRoles` (now works!)
6. ✅ `CRUD6::DefaultPermissions` (now works!)

## Technical Details

### SeedRecipe Interface

```php
namespace UserFrosting\Sprinkle\Core\Sprinkle\Recipe;

interface SeedRecipe
{
    /**
     * Return an array of all registered seeds.
     *
     * @return class-string<\UserFrosting\Sprinkle\Core\Seeder\SeedInterface>[]
     */
    public function getSeeds(): array;
}
```

### Recipe Pattern in UserFrosting 6

UserFrosting 6 uses multiple "Recipe" interfaces for different sprinkle capabilities:

- `SprinkleRecipe` - Base sprinkle functionality
- `MigrationRecipe` - Database migrations
- `SeedRecipe` - Database seeding
- `EventListenerRecipe` - Event listeners
- `TwigExtensionRecipe` - Twig extensions
- `BakeryRecipe` - Bakery commands

A sprinkle can implement any combination of these interfaces based on its needs.

## Files Changed

**Modified**:
- `app/src/CRUD6.php` - Added `SeedRecipe` interface implementation

**No Changes Required**:
- `app/src/Database/Seeds/DefaultRoles.php` - Already correct
- `app/src/Database/Seeds/DefaultPermissions.php` - Already correct

## Impact

- **Scope**: Minimal - single interface addition
- **Risk**: None - additive change only
- **Testing**: Fixes integration test failure
- **Backwards Compatibility**: Fully compatible - no breaking changes
- **UserFrosting 6 Standards**: Now follows official patterns from sprinkle-account

## Prevention

When creating UserFrosting 6 sprinkles:

1. **Always implement required recipe interfaces**:
   - Has seeds? → Implement `SeedRecipe`
   - Has migrations? → Implement `MigrationRecipe`
   - Has bakery commands? → Implement `BakeryRecipe`

2. **Reference official sprinkles**:
   - Check `userfrosting/sprinkle-account` for patterns
   - Check `userfrosting/sprinkle-admin` for patterns
   - Check `userfrosting/sprinkle-core` for available interfaces

3. **Test seed discovery**:
   ```bash
   php bakery seed:list  # Should show your seeds
   ```

## References

- GitHub Issue: Integration test failing with "Class is not a valid seed" error
- GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18347392709/job/52257698651
- UserFrosting Account Sprinkle (6.0): https://github.com/userfrosting/sprinkle-account/blob/6.0/app/src/Account.php
- UserFrosting Core SeedRecipe: https://github.com/userfrosting/sprinkle-core/blob/6.0/app/src/Sprinkle/Recipe/SeedRecipe.php
- UserFrosting Core SprinkleSeedsRepository: https://github.com/userfrosting/sprinkle-core/blob/6.0/app/src/Seeder/SprinkleSeedsRepository.php
