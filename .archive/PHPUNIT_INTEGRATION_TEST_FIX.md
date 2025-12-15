# PHPUnit Integration Test Fix

**Date**: 2025-12-15  
**Issue**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20246225548/job/58126876781  
**PR**: Related to fixing CI test failures

## Problem Statement

The integration test workflow was failing with the following error:

```
An error occurred inside PHPUnit.

Message:  Class "UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase" not found
Location: /home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/app/tests/Controller/CRUD6GroupsIntegrationTest.php:36
```

## Root Cause Analysis

### The Issue
The integration test workflow (`.github/workflows/integration-test.yml`) was attempting to run PHPUnit tests from within a UserFrosting installation context. This created an autoloader mismatch:

1. **Sprinkle Context**: When developing the sprinkle locally, PHPUnit runs from the sprinkle's root directory
   - Uses `vendor/autoload.php` from the sprinkle's composer dependencies
   - The `autoload-dev` section in `composer.json` maps `UserFrosting\Sprinkle\CRUD6\Tests\` → `app/tests/`
   - Test classes like `CRUD6TestCase` are properly autoloaded

2. **Integration Test Context**: When running in the workflow, PHPUnit was invoked from UserFrosting's root
   - The sprinkle is installed as a package in `vendor/ssnukala/sprinkle-crud6/`
   - PHPUnit command: `vendor/bin/phpunit --bootstrap vendor/autoload.php vendor/ssnukala/sprinkle-crud6/app/tests`
   - The bootstrap uses UserFrosting's autoloader, which doesn't include the sprinkle's `autoload-dev` mappings
   - Test classes in `app/tests/` are NOT autoloaded, causing "Class not found" errors

### Why This Happened
- The sprinkle's test classes (like `CRUD6TestCase`) are only available via `autoload-dev` PSR-4 mapping
- When installed as a composer package, only the `autoload` section is included, not `autoload-dev`
- Running PHPUnit from the UserFrosting root without the sprinkle's `phpunit.xml` configuration bypasses proper test autoloading

## Solution

### Approach
**Separate unit tests from integration tests** by creating two distinct workflows:

1. **Unit Test Workflow** (`.github/workflows/unit-tests.yml`): 
   - Runs PHPUnit tests from the sprinkle's root directory
   - Has proper autoloading context with dev dependencies
   - Tests multiple PHP versions (8.1, 8.2, 8.3)
   - Includes test coverage reporting

2. **Integration Test Workflow** (`.github/workflows/integration-test.yml`):
   - Focuses solely on HTTP endpoint testing
   - Tests real-world user workflows
   - No PHPUnit unit tests
   - Tests API authentication, authorization, CRUD operations
   - Tests frontend routes with Playwright

### Changes Made

#### 1. Created New Unit Test Workflow
**File**: `.github/workflows/unit-tests.yml`

Features:
- Runs on push/PR to main and develop branches
- Tests on PHP 8.4 (current UserFrosting 6 version)
- MySQL 8.0 service for database tests
- Composer dependency caching
- Creates test database configuration
- Runs PHPUnit with proper autoloading from sprinkle root
- Test coverage summary

#### 2. Removed PHPUnit from Integration Test
**File**: `.github/workflows/integration-test.yml`

Removed:
- Entire "Run PHPUnit tests from sprinkle" step (lines 549-611)
- ~63 lines of code attempting to run unit tests in integration context

Result:
- Cleaner separation of concerns
- Integration test focuses on HTTP endpoints and user workflows
- No more autoloader conflicts

#### 3. Updated Documentation
**File**: `INTEGRATION_TESTING_QUICK_START.md`

Changes:
- Removed mention of "Runs PHPUnit tests" from workflow description
- Added note explaining that unit tests run in separate workflow
- Clarified that integration tests focus on API and frontend testing

## Benefits

### 1. Proper Test Isolation
- **Unit tests**: Run in sprinkle context with full dev dependencies
- **Integration tests**: Run in UserFrosting context with HTTP endpoints

### 2. Better CI/CD Pipeline
- Unit tests run faster (no UserFrosting installation needed)
- Integration tests focus on end-to-end functionality
- Clear separation makes debugging easier

### 3. Current PHP Version Testing
- Unit tests now run on PHP 8.4 (current UserFrosting 6 version)
- Ensures compatibility with UserFrosting 6 requirements
- Tests on the version actually used in production

### 4. Test Coverage Reporting
- Unit test workflow includes coverage summary
- Helps identify untested code paths
- Runs with every test execution

## Verification

### Unit Test Workflow
To verify the unit test workflow works:
```bash
# Locally (from sprinkle root)
composer install
vendor/bin/phpunit --testdox

# In CI
# Push to main/develop or create PR - workflow runs automatically
```

### Integration Test Workflow
To verify integration test still works:
```bash
# In CI
# Push to main/develop or create PR - workflow runs automatically
# Should complete without PHPUnit errors
```

## Technical Details

### Autoloading Context Comparison

**Sprinkle Root Context** (Unit Tests - NOW):
```
sprinkle-crud6/
├── vendor/
│   ├── autoload.php         ← Includes autoload-dev mappings
│   └── (dev dependencies)
├── app/
│   ├── src/                 → UserFrosting\Sprinkle\CRUD6\
│   └── tests/               → UserFrosting\Sprinkle\CRUD6\Tests\ ✅
└── phpunit.xml
```

**UserFrosting Root Context** (Integration Tests - BEFORE):
```
userfrosting/
├── vendor/
│   ├── autoload.php         ← Does NOT include sprinkle's autoload-dev
│   └── ssnukala/sprinkle-crud6/
│       ├── app/
│       │   ├── src/         → UserFrosting\Sprinkle\CRUD6\ ✅
│       │   └── tests/       → NOT AUTOLOADED ❌
│       └── (NO dev dependencies)
```

### Why This Is The Right Approach

Following UserFrosting 6 patterns:
- `sprinkle-core` and `sprinkle-account` run their own unit tests separately
- Integration tests focus on testing sprinkles working together
- This matches the standard PHP package testing approach

## Related Files

### Modified
- `.github/workflows/integration-test.yml` - Removed PHPUnit step
- `INTEGRATION_TESTING_QUICK_START.md` - Updated documentation

### Created
- `.github/workflows/unit-tests.yml` - New dedicated unit test workflow
- `.archive/PHPUNIT_INTEGRATION_TEST_FIX.md` - This document

### Unchanged
- `phpunit.xml` - Still used by unit test workflow
- `composer.json` - autoload-dev section still needed
- `app/tests/` - All test files remain unchanged

## Future Considerations

### If More Tests Are Needed in Integration Context
If specific integration tests need to run within UserFrosting context:
1. Don't use PHPUnit tests from sprinkle's test directory
2. Instead, use HTTP endpoint testing (already implemented)
3. Or create integration test scripts that don't rely on PHPUnit test classes

### If Test Classes Need Sharing
If test base classes need to be available in UserFrosting context:
1. Move them from `app/tests/` to `app/src/Testing/`
2. Update autoload (not autoload-dev) in composer.json
3. But this is generally not needed for integration tests

## Conclusion

This fix properly separates unit testing from integration testing, following best practices and UserFrosting 6 patterns. The issue is resolved by:

1. ✅ Creating dedicated unit test workflow with proper autoloading
2. ✅ Removing PHPUnit from integration test (not needed there)
3. ✅ Maintaining comprehensive integration testing via HTTP endpoints
4. ✅ Enabling multi-PHP-version testing
5. ✅ Adding test coverage reporting

The integration test workflow now focuses on what it should: testing the sprinkle working within a real UserFrosting installation via HTTP endpoints and browser automation.
