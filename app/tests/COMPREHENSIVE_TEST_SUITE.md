# CRUD6 Comprehensive Test Suite

**Created**: 2025-11-19  
**Purpose**: Independent testing infrastructure for CRUD6 to detect bugs without relying on dependent sprinkles like C6Admin  
**Status**: ✅ Complete Test Coverage

## Overview

This test suite provides comprehensive coverage of all CRUD6 API endpoints and features. The tests are designed to be run independently, without requiring C6Admin or other dependent sprinkles, allowing early detection of bugs directly in the CRUD6 codebase.

## Test Organization

### Modular Structure

Tests are organized by controller/feature for easy maintenance and targeted testing:

```
app/tests/Controller/
├── CreateActionTest.php          - POST   /api/crud6/{model}
├── EditActionTest.php             - PUT    /api/crud6/{model}/{id}
├── UpdateFieldActionTest.php      - PUT    /api/crud6/{model}/{id}/{field}
├── DeleteActionTest.php           - DELETE /api/crud6/{model}/{id}
├── SprunjeActionTest.php          - GET    /api/crud6/{model} (list)
├── SchemaActionTest.php           - GET    /api/crud6/{model}/schema
├── CRUD6UsersIntegrationTest.php  - Full user model integration tests
├── CRUD6GroupsIntegrationTest.php - Full group model integration tests
└── BooleanToggleSchemaTest.php    - Schema-based validation tests
```

## Test Coverage Matrix

### Backend API Tests

| Feature | Endpoint | Test File | Coverage |
|---------|----------|-----------|----------|
| **Create** | POST /api/crud6/{model} | CreateActionTest.php | ✅ Complete |
| - Authentication | | ✅ | Requires login |
| - Authorization | | ✅ | Requires create permission |
| - Field Validation | | ✅ | Required, unique, format |
| - Data Transformation | | ✅ | Password hashing |
| - Default Values | | ✅ | Schema defaults applied |
| - Database Insert | | ✅ | Record created |
| - Response Format | | ✅ | Success message + data |
| **Update (Full)** | PUT /api/crud6/{model}/{id} | EditActionTest.php | ✅ Complete |
| - Authentication | | ✅ | Requires login |
| - Authorization | | ✅ | Requires update permission |
| - Partial Updates | | ✅ | Only changed fields |
| - Field Validation | | ✅ | Email, unique constraints |
| - Password Hashing | | ✅ | New password hashed |
| - Readonly Protection | | ✅ | ID cannot change |
| - Not Found (404) | | ✅ | Invalid ID |
| **Update (Field)** | PUT /api/crud6/{model}/{id}/{field} | UpdateFieldActionTest.php | ✅ Complete |
| - Boolean Toggle Fix | | ✅ | Empty validation schema |
| - Field Validation | | ✅ | Non-existent fields |
| - Readonly Protection | | ✅ | Readonly fields |
| - Authentication | | ✅ | Requires login |
| - Authorization | | ✅ | Requires permission |
| **Boolean Toggles** | PUT /api/crud6/users/{id}/flag_* | CRUD6UsersIntegrationTest.php | ✅ Complete |
| - Toggle Enabled | | ✅ | flag_enabled true ↔ false |
| - Toggle Verified | | ✅ | flag_verified true ↔ false |
| - Database Update | | ✅ | Values persisted |
| - Response Data | | ✅ | Updated value returned |
| **Delete** | DELETE /api/crud6/{model}/{id} | DeleteActionTest.php | ✅ Complete |
| - Authentication | | ✅ | Requires login |
| - Authorization | | ✅ | Requires delete permission |
| - Soft Delete | | ✅ | deleted_at set |
| - Not Found (404) | | ✅ | Invalid/deleted ID |
| - Self-Delete Prevention | | ✅ | Cannot delete own account |
| **List/Sprunje** | GET /api/crud6/{model} | SprunjeActionTest.php | ✅ Complete |
| - Pagination | | ✅ | size, page parameters |
| - Sorting | | ✅ | sorts[field]=asc/desc |
| - Filtering | | ✅ | filters[field]=value |
| - Search | | ✅ | Text search |
| - Empty Results | | ✅ | No matches |
| - Metadata | | ✅ | count, listable, etc. |
| - Field Visibility | | ✅ | Only listable fields |
| **Schema** | GET /api/crud6/{model}/schema | SchemaActionTest.php | ✅ Complete |
| - Schema Structure | | ✅ | model, table, fields |
| - Field Definitions | | ✅ | type, label, validation |
| - Actions | | ✅ | toggle_enabled, etc. |
| - Non-Existent Model | | ✅ | Error handling |

### Frontend Tests

| Feature | Path | Test Status | Notes |
|---------|------|-------------|-------|
| User List Page | /crud6/users | ⏳ Manual | Route exists (CRUD6UsersIntegrationTest) |
| User Detail Page | /crud6/users/{id} | ⏳ Manual | Route exists (CRUD6UsersIntegrationTest) |
| Group List Page | /crud6/groups | ⏳ Manual | Route exists (CRUD6GroupsIntegrationTest) |
| Group Detail Page | /crud6/groups/{id} | ⏳ Manual | Route exists (CRUD6GroupsIntegrationTest) |

**Frontend Testing**: These tests verify routes exist but require browser automation (Playwright/Selenium) or manual testing for full UI validation. Consider adding:
- `app/tests/Frontend/` directory for Playwright tests
- Screenshots comparison tests
- Vue component unit tests

### Schema Tests

| Schema File | Test File | Coverage |
|-------------|-----------|----------|
| users.json | BooleanToggleSchemaTest.php | ✅ Complete |
| groups.json | BooleanToggleSchemaTest.php | ✅ Complete |
| permissions.json | BooleanToggleSchemaTest.php | ✅ Complete |
| roles.json | BooleanToggleSchemaTest.php | ✅ Complete |
| activities.json | BooleanToggleSchemaTest.php | ✅ Complete |

## Running Tests

### Full Test Suite

```bash
# Run all tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Targeted Test Runs

```bash
# Run only create action tests
vendor/bin/phpunit app/tests/Controller/CreateActionTest.php

# Run only boolean toggle tests
vendor/bin/phpunit app/tests/Controller/CRUD6UsersIntegrationTest.php --filter Toggle

# Run only sprunje tests
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php

# Run all integration tests
vendor/bin/phpunit app/tests/Controller/CRUD6*IntegrationTest.php
```

### Quick Validation (No Dependencies Required)

```bash
# Syntax check all tests
find app/tests -name "*.php" -exec php -l {} \;

# Schema validation
for file in app/schema/crud6/*.json; do 
  php -r "echo json_decode(file_get_contents('$file')) ? 'OK' : 'FAIL'; echo ': $file\n';"
done
```

## Test Database Setup

Tests use `RefreshDatabase` trait which:
1. Runs migrations before tests
2. Wraps each test in a transaction
3. Rolls back after each test
4. Ensures clean state for every test

**No manual database setup required!**

## CI/CD Integration

### GitHub Actions Workflow

```yaml
name: CRUD6 Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, pdo, pdo_mysql
      
      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist
      
      - name: Run Tests
        run: vendor/bin/phpunit
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: root
```

## Key Test Features

### 1. API Call Tracking

All integration tests use `TracksApiCalls` trait to:
- Track all API calls made during test
- Detect redundant/duplicate API calls
- Generate summary after each test
- Help optimize API call patterns

Example output:
```
═══════════════════════════════════════════════════════════════
API Call Tracking Summary for testToggleFlagEnabledUpdatesUserStatus
═══════════════════════════════════════════════════════════════
  Total API Calls:        3
  Unique Calls:           3
  Redundant Call Groups:  0
  Schema API Calls:       1
  CRUD6 API Calls:        2

✅ No redundant calls detected
═══════════════════════════════════════════════════════════════
```

### 2. Independent from Dependent Sprinkles

Tests use:
- Local schema files (`app/schema/crud6/*.json`)
- UserFrosting Account models (User, Group) only
- No dependency on C6Admin
- Can run in CRUD6 development environment

### 3. Real Database Testing

Tests use:
- Actual Eloquent models
- Real database transactions
- Factory-generated test data
- Proper authentication/authorization

## Test Coverage Goals

- [x] All CRUD operations (Create, Read, Update, Delete)
- [x] Field-level updates (UpdateFieldAction)
- [x] Boolean toggle actions (flag_enabled, flag_verified)
- [x] List/filter/sort/paginate (Sprunje)
- [x] Schema retrieval and validation
- [x] Authentication requirements
- [x] Authorization/permissions
- [x] Validation errors
- [x] Not Found (404) errors
- [x] Password hashing
- [x] Default values
- [x] Readonly field protection
- [x] Soft delete
- [x] API response formats
- [ ] Relationship operations (attach/detach) - TODO
- [ ] Frontend component tests - TODO
- [ ] Screenshot comparison tests - TODO

## Adding New Tests

### For New Controller Features

1. Create new test file in `app/tests/Controller/`
2. Extend `AdminTestCase`
3. Use traits: `RefreshDatabase`, `WithTestUser`, `TracksApiCalls`
4. Follow naming convention: `{Feature}ActionTest.php`
5. Test all aspects: auth, authz, validation, success, errors

Example template:

```php
<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class MyNewFeatureTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->startApiTracking();
    }

    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    public function testMyNewFeature(): void
    {
        // Your test here
    }
}
```

### For New Models

1. Add schema file to `app/schema/crud6/`
2. Create integration test in `app/tests/Controller/CRUD6{Model}IntegrationTest.php`
3. Add schema validation test in `BooleanToggleSchemaTest.php`
4. Test all CRUD operations for the new model

## Debugging Failed Tests

### Enable Debug Logging

Set `crud6.debug_mode: true` in config to see detailed logs:

```php
'crud6' => [
    'debug_mode' => true,
],
```

### Common Failures

1. **401 Unauthorized**: Check `actAsUser()` is called with proper permissions
2. **404 Not Found**: Verify schema file exists for model
3. **Validation Errors**: Check field names match schema
4. **Boolean Toggle Fails**: Verify fix is applied in UpdateFieldAction

### Viewing Test Database

Tests run in transactions and rollback, but you can inspect state by adding:

```php
// In test method before assertion
$this->dumpDatabaseState();
```

## Continuous Improvement

### Metrics to Track

- Total test count
- Code coverage percentage
- Test execution time
- Redundant API call count
- Test failures over time

### Future Enhancements

1. **Frontend Testing**
   - Add Playwright/Selenium tests
   - Screenshot comparison
   - Vue component unit tests

2. **Performance Testing**
   - Load tests for Sprunje
   - Pagination performance
   - Large dataset handling

3. **Security Testing**
   - SQL injection attempts
   - XSS prevention
   - CSRF protection

4. **Relationship Testing**
   - Many-to-many attach/detach
   - Cascade deletes
   - Relationship actions (on_create, on_update, on_delete)

## Related Documentation

- [BOOLEAN_TOGGLE_FIX_SUMMARY.md](../../.archive/BOOLEAN_TOGGLE_FIX_SUMMARY.md) - Boolean toggle bug fix details
- [QUICK_TEST_GUIDE.md](../../QUICK_TEST_GUIDE.md) - Quick testing reference
- [INTEGRATION_TESTING.md](../../INTEGRATION_TESTING.md) - Integration testing guide
- [README.md](README.md) - Test directory documentation

## Maintenance

### When Adding Features

1. Add tests FIRST (TDD approach)
2. Implement feature
3. Verify all tests pass
4. Update this documentation

### When Fixing Bugs

1. Add failing test that reproduces bug
2. Fix the bug
3. Verify test now passes
4. Add test to prevent regression

### When Modifying Schemas

1. Update schema file
2. Run `BooleanToggleSchemaTest` to validate
3. Update related integration tests if needed
4. Verify no breaking changes

## Support

For issues with tests:
1. Check test output for specific error
2. Review related documentation
3. Check GitHub issues
4. Create new issue with test failure details
