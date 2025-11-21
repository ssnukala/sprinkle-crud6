# Integration Test Autoload Fix

## Issue
Integration tests were failing with:
```
Class "UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase" not found
Location: app/tests/Integration/FrontendUserWorkflowTest.php:43
```

## Root Cause
When a sprinkle is installed as a Composer dependency in a UserFrosting application:
- The sprinkle's `autoload` section (PSR-4 for `app/src/`) is loaded ✅
- The sprinkle's `autoload-dev` section (PSR-4 for `app/tests/`) is **NOT** loaded ❌

This is standard Composer behavior - dev dependencies and dev autoload configurations are not loaded for packages installed as dependencies.

## Problem Context
The integration test workflow:
1. Creates a UserFrosting project: `composer create-project userfrosting/userfrosting`
2. Installs sprinkle-crud6 as a dependency: `composer require ssnukala/sprinkle-crud6:@dev`
3. Runs PHPUnit from the UserFrosting directory: `vendor/bin/phpunit ../sprinkle-crud6/app/tests/Integration/`

In step 3, PHPUnit loads the test files but cannot find test helper classes like `AdminTestCase` because they're in the `app/tests/` directory which is only mapped in `autoload-dev`.

## Solution
Create a custom PHPUnit configuration and bootstrap file in the UserFrosting directory that:

### 1. PHPUnit Configuration (`phpunit-crud6.xml`)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="bootstrap-crud6.php">
    <testsuites>
        <testsuite name="CRUD6 Integration Tests">
            <directory>../sprinkle-crud6/app/tests/Integration</directory>
        </testsuite>
        <testsuite name="CRUD6 Controller Tests">
            <directory>../sprinkle-crud6/app/tests/Controller</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 2. Bootstrap Script (`bootstrap-crud6.php`)
```php
<?php
// Load UserFrosting vendor autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Manually register CRUD6 test namespace
$loader = require __DIR__ . '/vendor/autoload.php';
$loader->addPsr4('UserFrosting\\Sprinkle\\CRUD6\\Tests\\', __DIR__ . '/../sprinkle-crud6/app/tests/');
```

### 3. Updated Test Command
```bash
# Before (doesn't work):
vendor/bin/phpunit ../sprinkle-crud6/app/tests/Integration/

# After (works):
vendor/bin/phpunit --configuration phpunit-crud6.xml --testsuite "CRUD6 Integration Tests"
```

## How It Works
The bootstrap file uses Composer's autoloader API to manually register the PSR-4 mapping:

```php
$loader->addPsr4('UserFrosting\\Sprinkle\\CRUD6\\Tests\\', __DIR__ . '/../sprinkle-crud6/app/tests/');
```

This allows PHPUnit to find all test helper classes:
- `AdminTestCase` - Base test case for CRUD6 tests
- Any other test utilities in the `app/tests/` directory

## Classes Affected
The following test helper classes are now properly autoloaded:

### Main Test Base Class
- `UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase` - Base test case extending UserFrosting's `TestCase`

### Test Traits (in app/src/Testing - already autoloaded)
- `UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls` - API call tracking trait
- `UserFrosting\Sprinkle\CRUD6\Testing\ApiCallTracker` - API call tracker utility

## Alternative Solutions Considered

### 1. Run tests from sprinkle directory ❌
```bash
cd ../sprinkle-crud6 && vendor/bin/phpunit
```
**Problem:** The sprinkle needs UserFrosting's application context (DI container, database, etc.)

### 2. Install sprinkle in dev mode ❌
```bash
composer require ssnukala/sprinkle-crud6:@dev --dev
```
**Problem:** Sprinkles should be regular dependencies, not dev dependencies

### 3. Move test helpers to app/src/Testing ❌
```bash
mv app/tests/AdminTestCase.php app/src/Testing/AdminTestCase.php
```
**Problem:** Test helpers belong in `app/tests/`, not production code in `app/src/`

### 4. Create symlink to vendor autoload ❌
**Problem:** Doesn't work with Composer's PSR-4 autoloader

## Why This Solution Works
✅ Follows UserFrosting 6 patterns (similar to sprinkle-admin tests)  
✅ Maintains proper separation of test code from production code  
✅ Works with Composer's standard dependency resolution  
✅ No changes needed to sprinkle's `composer.json`  
✅ Reusable pattern for other sprinkles with integration tests  

## Files Modified
- `.github/workflows/integration-test.yml` - Added "Configure PHPUnit for CRUD6 tests" step

## References
- [Composer Autoloading Documentation](https://getcomposer.org/doc/04-schema.md#autoload)
- [PHPUnit Bootstrap Documentation](https://docs.phpunit.de/en/10.0/configuration.html#the-bootstrap-attribute)
- [UserFrosting Testing Documentation](https://learn.userfrosting.com/testing)
- [sprinkle-admin Integration Tests](https://github.com/userfrosting/sprinkle-admin/tree/6.0/app/tests)
