# Cupcake Constructor Error Fix

## Issue
GitHub Actions integration test failing with:
```
PHP Fatal error:  Uncaught ArgumentCountError: Too few arguments to function UserFrosting\Cupcake::__construct(), 
0 passed in /home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/.github/crud6-framework/scripts/load-seed-sql.php on line 49 and exactly 1 expected
```

**GitHub Actions Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20147991574/job/57833547790

## Root Cause
The script `load-seed-sql.php` was using an outdated UserFrosting bootstrap pattern:

```php
// ❌ OLD PATTERN - No longer works in UserFrosting 6
use UserFrosting\UserFrosting;
$uf = new UserFrosting();
$db = $uf->getContainer()->get(\Illuminate\Database\Capsule\Manager::class);
```

In UserFrosting 6, the `UserFrosting` class (an alias for `Cupcake`) now requires a sprinkle class parameter in its constructor. The correct pattern for CLI scripts is to use the `Bakery` class.

## Solution
Updated both versions of `load-seed-sql.php` to use the official UserFrosting 6 CLI bootstrap pattern:

```php
// ✅ NEW PATTERN - Official UserFrosting 6 CLI bootstrap
use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);
$container = $bakery->getContainer();
$db = $container->get(\Illuminate\Database\Capsule\Manager::class);
```

## Files Changed
1. `.github/scripts/load-seed-sql.php` - Direct version
2. `.github/testing-framework/scripts/load-seed-sql.php` - Source version that gets copied to `.github/crud6-framework/` during CI

## Pattern Verification
This pattern is confirmed as the official UserFrosting 6 convention because:

1. **Consistent Usage**: ALL other CLI scripts in the repository use this pattern:
   - `check-seeds-modular.php`
   - `test-seed-idempotency-modular.php`
   - `check-seeds.php`
   - `test-seed-idempotency.php`

2. **Explicit Documentation**: Every script includes the comment:
   ```php
   // Bootstrap the UserFrosting application using Bakery (CLI bootstrap method)
   // This follows the same pattern as the bakery CLI tool in UserFrosting 6
   ```

3. **Framework Convention**: This is the standard way to bootstrap UserFrosting in CLI scripts, matching the `bakery` command-line tool itself.

## How CI Works
1. CI workflow copies `.github/testing-framework/*` to `.github/crud6-framework/` 
2. Scripts are then executed from `.github/crud6-framework/scripts/`
3. Our fix in `.github/testing-framework/scripts/load-seed-sql.php` ensures the copied version is correct

## Testing
- ✅ PHP syntax validation passed for both files
- ✅ Pattern matches all other CLI scripts in repository
- ✅ Follows UserFrosting 6 official conventions

## Date
December 11, 2024

## Related
- UserFrosting 6.0.4 beta compatibility
- Bakery CLI tool pattern
- DI container initialization in CLI contexts
