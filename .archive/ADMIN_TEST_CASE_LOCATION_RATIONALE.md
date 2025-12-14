# AdminTestCase Location: Why app/src/Testing/ Instead of app/tests/

## The Issue
CI integration tests failed with:
```
Class "UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase" not found
```

## Official UserFrosting Pattern
In official sprinkles (admin, account, core):
- `AdminTestCase` is located in `app/tests/AdminTestCase.php`
- Namespace is `UserFrosting\Sprinkle\Admin\Tests`
- This is in the `autoload-dev` section

Example from sprinkle-admin:
```php
// app/tests/AdminTestCase.php
namespace UserFrosting\Sprinkle\Admin\Tests;

class AdminTestCase extends TestCase {
    protected string $mainSprinkle = Admin::class;
}
```

## Why Official Sprinkles Use app/tests/
Official sprinkles run tests **standalone** in their own repository:
```bash
cd sprinkle-admin
composer install  # Loads autoload-dev
vendor/bin/phpunit  # Uses local phpunit.xml
```

They don't run integration tests from within a parent UserFrosting project.

## CRUD6's Different Requirement
CRUD6's CI workflow runs **integration tests** within a full UserFrosting project context:
```bash
cd userfrosting  # UserFrosting project
composer require ssnukala/sprinkle-crud6:@dev  # Install as dependency
vendor/bin/phpunit vendor/ssnukala/sprinkle-crud6/app/tests  # Run tests
```

When installed as a dependency, `autoload-dev` is NOT included by Composer.

## The Solution
Move `AdminTestCase` to `app/src/Testing/`:
- `app/src/Testing/AdminTestCase.php`
- Namespace `UserFrosting\Sprinkle\CRUD6\Testing`
- Part of main `autoload` (not `autoload-dev`)
- Always available when sprinkle is installed

## Comparison with UserFrosting Testing Utilities

### What Goes in app/src/Testing/ (Main Autoload)
Official examples:
- `UserFrosting\Sprinkle\Account\Testing\WithTestUser` - Test trait for authentication
- `UserFrosting\Sprinkle\Core\Testing\RefreshDatabase` - Database refresh trait
- **CRUD6**: `UserFrosting\Sprinkle\CRUD6\Testing\AdminTestCase` - Base test case
- **CRUD6**: `UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls` - API tracking trait

These are **testing utilities that need to be available when the sprinkle is installed as a dependency**.

### What Goes in app/tests/ (Autoload-dev)
- Actual test classes: `*Test.php`
- Test namespaces: `UserFrosting\Sprinkle\CRUD6\Tests\*`

These are **the actual tests** that only run in dev context.

## Pattern Summary

### Official Sprinkles (Standalone Testing)
```
app/tests/AdminTestCase.php          → autoload-dev
app/tests/Unit/SomeTest.php          → autoload-dev
app/tests/Integration/OtherTest.php  → autoload-dev
```

### CRUD6 (Integration Testing in UserFrosting Context)
```
app/src/Testing/AdminTestCase.php    → autoload (always available)
app/src/Testing/TracksApiCalls.php   → autoload (always available)
app/tests/Unit/SomeTest.php          → autoload-dev
app/tests/Integration/OtherTest.php  → autoload-dev
```

## Conclusion
CRUD6's approach of putting `AdminTestCase` in `app/src/Testing/` is the **correct pattern** for a sprinkle that:
1. Runs integration tests in a full UserFrosting context
2. Tests functionality when installed as a dependency
3. Needs test base classes available to external test runners

This follows the spirit of UserFrosting's pattern where `app/src/Testing/` contains testing utilities that should be available to consumers of the sprinkle.

## Date
December 14, 2024
