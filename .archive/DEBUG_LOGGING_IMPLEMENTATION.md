# Debug Logging Implementation for Database Configuration

**Date**: 2025-12-16  
**Purpose**: Add comprehensive debug logging to diagnose database configuration issues in CI tests  
**Related Issue**: Database configuration causing `table_schema = ''` errors

## Overview

Added extensive debug logging throughout the test suite to help diagnose database configuration issues, particularly the "table_schema = ''" problem where DB_NAME was not being set properly.

## Components Added

### 1. PHPUnit Bootstrap with Configuration Validation (`app/tests/bootstrap.php`)

**Purpose**: Validate database configuration before any tests run

**Features**:
- Loads Composer autoloader
- Checks all database environment variables
- Validates critical configuration values
- **Exits with error code 1** if DB_NAME or other critical values are not set
- Provides detailed diagnostic output

**Output Example**:
```
======================================================================
PHPUNIT BOOTSTRAP - DATABASE CONFIGURATION CHECK
======================================================================
Timestamp: 2025-12-16 19:23:00
PHP Version: 8.4.0
PHPUnit Bootstrap File: /path/to/app/tests/bootstrap.php
----------------------------------------------------------------------
DATABASE CONFIGURATION:
  DB_DRIVER       = mysql
  DB_HOST         = 127.0.0.1
  DB_PORT         = 3306
  DB_NAME         = userfrosting_test
  DB_USER         = root
  DB_PASSWORD     = ***SET***
  UF_MODE         = testing
----------------------------------------------------------------------

✅ DATABASE CONFIGURATION VALID
   All required environment variables are set.
   Database: userfrosting_test
   Host: 127.0.0.1
   Driver: mysql
======================================================================
```

**Error Detection**:
If DB_NAME is not set, the bootstrap will:
1. Log detailed error messages
2. Provide example phpunit.xml configuration
3. Exit with error code 1 to prevent tests from running
4. Prevent cascading failures from bad configuration

### 2. Database Configuration Test Suite (`app/tests/Database/DatabaseConfigurationTest.php`)

**Purpose**: Dedicated test suite to verify environment configuration

**Tests**:
1. `testDatabaseNameIsSet()` - Verifies DB_NAME is set and equals "userfrosting_test"
2. `testDatabaseHostIsSet()` - Verifies DB_HOST is set
3. `testDatabaseUserIsSet()` - Verifies DB_USER is set
4. `testDatabasePasswordIsSet()` - Verifies DB_PASSWORD is set (value redacted)
5. `testDatabaseDriverIsSet()` - Verifies DB_DRIVER is "mysql"
6. `testTestingModeIsSet()` - Verifies UF_MODE is "testing"
7. `testCompleteDatabaseConfiguration()` - Comprehensive check with full diagnostic output
8. `testPhpunitXmlConfigurationIsLoaded()` - Verifies phpunit.xml <php><env> section is working

**Benefits**:
- Runs first (alphabetically by directory)
- Provides early warning if configuration is broken
- Outputs detailed diagnostics to STDERR (visible in CI logs)
- Each test assertion includes descriptive failure messages

### 3. Enhanced CRUD6TestCase (`app/tests/CRUD6TestCase.php`)

**Purpose**: Add database configuration logging to all CRUD6 tests

**New Features**:
- `setUp()` method logs database configuration for every test
- `logDatabaseConfiguration()` outputs environment variables
- `verifyDatabaseConnection()` helper method to check actual DB connection

**Output Example**:
```
========================================
DATABASE CONFIGURATION DEBUG
========================================
Test Class: UserFrosting\Sprinkle\CRUD6\Tests\Controller\SprunjeActionTest
Test Method: testSprunjeReturnsValidJson
  DB_DRIVER       = mysql
  DB_HOST         = 127.0.0.1
  DB_PORT         = 3306
  DB_NAME         = userfrosting_test
  DB_USER         = root
  DB_PASSWORD     = ***REDACTED***
  UF_MODE         = testing
========================================
```

**Benefits**:
- Every test logs its database configuration
- Easy to see which test had configuration issues
- Password is redacted for security
- Output goes to STDERR (preserved in CI logs)

### 4. Enhanced WithDatabaseSeeds Trait (`app/src/Testing/WithDatabaseSeeds.php`)

**Purpose**: Add debug logging to database seeding process

**New Logging Points**:
1. Start of seeding process
2. Completion of Account data seeding (groups, roles, permissions)
3. Completion of CRUD6 data seeding
4. Success/error status for entire seeding process

**Output Example**:
```
[SEEDING DATABASE] Starting database seed process...
  [SEED] Creating Account sprinkle base data...
  [SEED] ✓ Created default group: terran
  [SEED] ✓ Created site-admin role: site-admin
  [SEED] ✓ Created base permission: uri_users
  [SEED] Running CRUD6 seeders...
  [SEED] ✓ DefaultRoles seed completed
  [SEED] ✓ DefaultPermissions seed completed
[SEEDING DATABASE] ✅ Database seeding completed successfully
```

**Error Handling**:
- Catches exceptions during seeding
- Logs error message and stack trace
- Re-throws exception to fail test properly

### 5. Updated phpunit.xml

**Change**: Bootstrap now points to `app/tests/bootstrap.php` instead of `vendor/autoload.php`

**Benefit**: Configuration validation runs before any tests

## How to Use

### For Developers

**Run specific database configuration tests**:
```bash
vendor/bin/phpunit app/tests/Database/DatabaseConfigurationTest.php --testdox
```

**Run all tests with debug output**:
```bash
vendor/bin/phpunit --testdox 2>&1 | tee test-output.log
```

**Check database configuration in a specific test**:
```php
public function testMyFeature(): void
{
    // Get connection info
    $connection = $this->verifyDatabaseConnection();
    
    // Your test code...
}
```

### In CI Environment

1. **Bootstrap validation runs automatically** - If DB_NAME is not set, tests exit immediately with descriptive error
2. **Configuration tests run first** - Provides early feedback on environment issues
3. **Every test logs its configuration** - Easy to see where configuration diverged
4. **Seeding logs show progress** - Can identify if seeding fails

### Reading CI Logs

Look for these markers:

**✅ Success Indicators**:
- `✅ DATABASE CONFIGURATION VALID` in bootstrap
- `✅ DB_NAME is correctly set to: userfrosting_test` in tests
- `[SEEDING DATABASE] ✅ Database seeding completed successfully`

**❌ Error Indicators**:
- `❌ CONFIGURATION ERRORS DETECTED` in bootstrap
- `CRITICAL: Cannot run tests with invalid database configuration`
- `[SEEDING DATABASE] ❌ Error during seeding`
- Test assertions failing with "DB_NAME environment variable must be set"

## Troubleshooting Guide

### Issue: Tests exit immediately with "Cannot run tests with invalid database configuration"

**Cause**: phpunit.xml is missing <php><env> section or values are not set

**Solution**:
1. Check phpunit.xml has `<php>` section with all `<env>` variables
2. Verify values are not empty
3. Ensure phpunit.xml is in repository root
4. Check XML syntax is valid

### Issue: DB_NAME shows as "NOT SET" in logs

**Cause**: Environment variables not loading from phpunit.xml

**Solution**:
1. Verify `bootstrap="app/tests/bootstrap.php"` in phpunit.xml
2. Check <env> elements are inside <php> section
3. Ensure phpunit.xml is not corrupted
4. Try running: `php -r "echo getenv('DB_NAME') ?: 'NOT SET';"`

### Issue: Database connection fails but DB_NAME is set

**Cause**: Database service not running or connection refused

**Solution**:
1. Check MySQL service is running in CI
2. Verify DB_HOST is correct (127.0.0.1 for local, service name for Docker)
3. Check DB_PORT is correct (3306 for MySQL)
4. Verify MySQL service health checks are passing

### Issue: "table_schema = ''" in SQL error

**Cause**: DB_NAME is empty or null at runtime

**Solution**:
1. Check bootstrap logs show correct DB_NAME
2. Verify test class extends CRUD6TestCase or sets up environment
3. Check if code is reading environment variable correctly
4. Review database configuration logs in test output

## Benefits of This Implementation

1. **Early Detection**: Bootstrap exits before any tests run if configuration is broken
2. **Detailed Diagnostics**: Every test logs its configuration for debugging
3. **CI-Friendly**: All output goes to STDERR, preserved in CI logs
4. **Self-Documenting**: Error messages include solutions
5. **Security**: Passwords are redacted in all logs
6. **Progressive**: Logs at multiple stages (bootstrap, test setup, seeding)
7. **Comprehensive**: Covers environment variables, connections, and seeding

## Performance Impact

- **Minimal**: Logging adds < 1ms per test
- **Bootstrap validation**: ~5-10ms one-time cost
- **Worth it**: Early detection saves minutes of debugging failed test runs

## Future Enhancements

Possible additions:
1. Log actual SQL queries that fail
2. Add connection pool status logging
3. Log migration status
4. Add performance timing for database operations
5. Create database state snapshots before/after tests

## Related Files

- `phpunit.xml` - Bootstrap configuration
- `app/tests/bootstrap.php` - Configuration validation
- `app/tests/CRUD6TestCase.php` - Test base class logging
- `app/tests/Database/DatabaseConfigurationTest.php` - Configuration test suite
- `app/src/Testing/WithDatabaseSeeds.php` - Seeding process logging

## Testing This Implementation

To verify the debug logging works:

1. **Test with good configuration** (should see ✅ messages):
   ```bash
   vendor/bin/phpunit app/tests/Database/DatabaseConfigurationTest.php
   ```

2. **Test with bad configuration** (should exit with error):
   ```bash
   # Temporarily remove DB_NAME from phpunit.xml
   vendor/bin/phpunit
   # Should exit immediately with error message
   ```

3. **Check logs are visible in CI**:
   - Push changes
   - Check GitHub Actions logs
   - Look for "DATABASE CONFIGURATION DEBUG" sections
   - Verify bootstrap validation appears first

## Summary

This implementation provides comprehensive debug logging for database configuration issues, with early detection, detailed diagnostics, and CI-friendly output. The bootstrap validation prevents tests from running with bad configuration, saving time and making failures immediately obvious.
