# PHPUnit Test Execution Path Fix - December 14, 2025

## Issue
CI workflow failed with error:
```
/home/runner/work/_temp/101c44e7-d27d-4126-9905-84fc5aa41300.sh: line 39: ../../bin/phpunit: No such file or directory
Error: Process completed with exit code 127.
```

**GitHub Actions Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20214724796/job/58025862837

## Root Cause Analysis

The workflow attempted to run PHPUnit from within the sprinkle's vendor directory:
```bash
cd userfrosting/vendor/ssnukala/sprinkle-crud6
../../bin/phpunit --testdox --colors=always
```

This path (`../../bin/phpunit`) was trying to access `userfrosting/vendor/bin/phpunit`, but:
1. The relative path calculation was correct in theory
2. However, this approach doesn't match UserFrosting 6 testing patterns

## UserFrosting 6 Testing Pattern Research

After reviewing the official UserFrosting 6 sprinkles, we found:

### sprinkle-core (https://github.com/userfrosting/sprinkle-core/tree/6.0)
- **No phpunit.xml** in the sprinkle root
- **No CI workflows** (.github directory doesn't exist)
- Tests are in `app/tests/` and extend `UserFrosting\Testing\TestCase`
- Tests require full UserFrosting application context

### sprinkle-account (https://github.com/userfrosting/sprinkle-account/tree/6.0)
- **No phpunit.xml** in the sprinkle root
- **No CI workflows** (.github directory doesn't exist)
- Tests are in `app/tests/` and extend `UserFrosting\Testing\TestCase`
- Tests require full UserFrosting application context

### framework (https://github.com/userfrosting/framework/tree/6.0)
- Contains the base `UserFrosting\Testing\TestCase` class
- TestCase requires:
  - Full UserFrosting application instance
  - A main sprinkle to be specified (`$mainSprinkle` property)
  - UserFrosting's autoloader to be available
  - DI container with all services

## Key Insights

1. **Sprinkles are tested within the application context**, not in isolation
2. **PHPUnit must run from the UserFrosting root**, not from vendor subdirectories
3. **Tests need the full framework autoloader** to access `UserFrosting\Testing\TestCase`
4. **No sprinkles have their own CI workflows** - they're tested when integrated into a UF app

## The Fix

### Before (Incorrect Approach)
```yaml
- name: Run PHPUnit tests from sprinkle
  run: |
    cd userfrosting
    cd vendor/ssnukala/sprinkle-crud6
    ../../bin/phpunit --testdox --colors=always
```

**Problems:**
- Running from wrong directory (vendor subdirectory)
- Assumes sprinkle's phpunit.xml bootstrap path will work
- Doesn't match official UF sprinkle patterns

### After (Correct Approach)
```yaml
- name: Run PHPUnit tests from sprinkle
  run: |
    cd userfrosting
    
    # Check if PHPUnit is installed
    if [ ! -f "vendor/bin/phpunit" ]; then
      echo "❌ PHPUnit not found in vendor/bin/"
      exit 1
    fi
    
    # Run PHPUnit from UserFrosting root using sprinkle's test directory
    vendor/bin/phpunit \
      --bootstrap vendor/autoload.php \
      --testdox \
      --colors=always \
      vendor/ssnukala/sprinkle-crud6/app/tests
```

**Benefits:**
- Runs from UserFrosting root (correct context)
- Uses UserFrosting's vendor/autoload.php (has all dependencies)
- Explicitly specifies test directory path
- Matches the pattern used by official UF sprinkles
- PHPUnit path is simple and direct (`vendor/bin/phpunit`)

## Why This Works

1. **Autoloader Context**: Running from the UserFrosting root ensures the autoloader includes:
   - All UserFrosting framework classes (`UserFrosting\Testing\TestCase`)
   - All sprinkle dependencies (sprinkle-core, sprinkle-account, sprinkle-admin)
   - CRUD6 sprinkle classes (via composer's path repository)

2. **Test Discovery**: PHPUnit can properly discover and run tests that extend `UserFrosting\Testing\TestCase`

3. **DI Container**: Tests can instantiate the full UserFrosting application with the CRUD6 sprinkle loaded

4. **Framework Integration**: Tests can use:
   - `RefreshDatabase` trait (from sprinkle-core)
   - `WithTestUser` trait (from sprinkle-account)
   - Full HTTP request/response testing
   - Database migrations and seeders

## Testing Pattern in CRUD6

Our tests properly follow UF6 patterns:

```php
// app/tests/AdminTestCase.php
namespace UserFrosting\Sprinkle\CRUD6\Tests;

use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Testing\TestCase;

class AdminTestCase extends TestCase
{
    protected string $mainSprinkle = CRUD6::class;
}
```

```php
// app/tests/Integration/SchemaBasedApiTest.php
namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;

class SchemaBasedApiTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    
    // Tests that require full UF application context
}
```

## Files Modified

1. `.github/workflows/integration-test.yml`
   - Updated "Run PHPUnit tests from sprinkle" step
   - Changed from running in vendor subdirectory to UserFrosting root
   - Explicit bootstrap and test directory specification

## Verification

To verify this fix works:

```bash
# In a UserFrosting 6 application with CRUD6 installed
cd userfrosting

# This should now work correctly:
vendor/bin/phpunit \
  --bootstrap vendor/autoload.php \
  --testdox \
  vendor/ssnukala/sprinkle-crud6/app/tests
```

## Related Documentation

- UserFrosting 6 Testing: https://learn.userfrosting.com/testing
- PHPUnit Documentation: https://phpunit.de/documentation.html
- sprinkle-core tests: https://github.com/userfrosting/sprinkle-core/tree/6.0/app/tests
- sprinkle-account tests: https://github.com/userfrosting/sprinkle-account/tree/6.0/app/tests
- framework TestCase: https://github.com/userfrosting/framework/tree/6.0/src/Testing

## Notes

- The sprinkle's `phpunit.xml` is still valid for local development when working within the sprinkle repository
- For CI integration testing, the explicit command-line approach is more reliable
- This pattern should be used for any UserFrosting 6 sprinkle that needs CI testing
