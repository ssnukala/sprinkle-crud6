# PHPUnit Integration Testing Implementation

## Summary

This document describes the implementation of PHPUnit testing in the CRUD6 sprinkle's integration testing workflow, addressing the requirement to enable unit tests that were previously disabled.

## Background

### Previous State
- PHPUnit tests were disabled in the old integration workflow (line 822 of `.archive/pre-framework-migration/integration-test.yml.backup`)
- The disabled step used: `if: false # Temporarily disabled - session handler issues in CI`
- Tests were meant to verify all CRUD6 API endpoints with authentication scenarios

### Issue Reference
From problem statement:
> the integration testing framework is working well now, want to look at the unit testing that was not working even in the old version, the unit testing was disabled to focus on the integration testing aspects, we should look at the testing framework now and see how we can build in php unit testing, the objective for the framework is to execute the php unit tests of the sprinkle it is deployed into in this case app/tests

## Solution Overview

Added a new PHPUnit test step to the integration workflow that:
1. Runs after complete UserFrosting 6 environment setup
2. Executes tests from the installed sprinkle location
3. Leverages full UserFrosting framework context
4. Reports test results with proper exit codes

## Implementation Details

### Workflow Step Location

The PHPUnit step is positioned in `.github/workflows/integration-test.yml` after:
- UserFrosting 6 project creation
- CRUD6 sprinkle installation via Composer
- Database setup with migrations
- Admin user creation
- Test data loading
- Application servers started (PHP and Vite)
- Authenticated/unauthenticated path tests
- Screenshot capture

And before:
- Server shutdown

### Step Implementation

```yaml
- name: Run PHPUnit tests from sprinkle
  run: |
    cd userfrosting
    
    # Display environment information
    echo "Test Environment:"
    echo "  - UserFrosting 6 framework: AVAILABLE"
    echo "  - CRUD6 sprinkle: INSTALLED"
    echo "  - Database: MySQL with migrations and test data"
    echo "  - Admin user: Created (ID 1)"
    echo "  - Application: Running on localhost:8080"
    
    # Verify sprinkle installation
    if [ ! -d "vendor/ssnukala/sprinkle-crud6" ]; then
      echo "❌ CRUD6 sprinkle not found"
      exit 1
    fi
    
    # Change to sprinkle directory
    cd vendor/ssnukala/sprinkle-crud6
    
    # Run PHPUnit with proper error handling
    ../../../vendor/bin/phpunit --testdox --colors=always
    TEST_EXIT_CODE=$?
    
    if [ $TEST_EXIT_CODE -eq 0 ]; then
      echo "✅ All PHPUnit Tests Passed"
    else
      echo "❌ Some PHPUnit Tests Failed"
      exit $TEST_EXIT_CODE
    fi
```

### Key Design Decisions

1. **Working Directory**: Tests execute from `vendor/ssnukala/sprinkle-crud6/`
   - Ensures `phpunit.xml` bootstrap path (`vendor/autoload.php`) is correct
   - Aligns with sprinkle's directory structure

2. **PHPUnit Binary**: Uses `../../../vendor/bin/phpunit`
   - Leverages UserFrosting project's PHPUnit installation
   - Ensures version compatibility

3. **Configuration**: Uses sprinkle's `phpunit.xml`
   - Points to `app/tests/` directory
   - Bootstraps `vendor/autoload.php`
   - Includes source coverage from `app/src/`

4. **Test Context**: Full UserFrosting framework available
   - All framework classes accessible
   - Database connection established
   - Application fully configured
   - Test data present

## UserFrosting Framework Integration

The PHPUnit step integrates with existing UserFrosting operations:

| Operation | Purpose | Framework Usage |
|-----------|---------|----------------|
| Database Migrations | Create tables | `php bakery migrate` |
| User Creation | Admin setup | `php bakery create:admin-user` |
| Asset Building | Compile frontend | `php bakery bake` |
| PHP Server | Run application | `php bakery serve` |
| Vite Server | Dev server | `php bakery assets:vite` |
| **PHPUnit Tests** | **Validate functionality** | **`vendor/bin/phpunit`** |

All operations use the same UserFrosting 6 installation and share the same environment.

## Test Environment

Tests run with access to:

### Database
- MySQL 8.0 service
- All migrations applied
- Test data loaded (users, roles, groups, permissions, etc.)
- Admin user (ID 1) created

### Application
- UserFrosting 6 beta
- CRUD6 sprinkle installed
- PHP server running on localhost:8080
- Vite dev server running
- All routes registered
- All sprinkles loaded

### Framework Dependencies
- All UserFrosting core classes
- Sprinkle-core services
- Sprinkle-account authentication
- Sprinkle-admin functionality
- Eloquent ORM
- Slim framework
- PHP-DI container

## Test Coverage

The `app/tests/` directory contains:

### Controller Tests
- `Controller/` - API endpoint tests
- `Controller/CRUD6UsersIntegrationTest.php`
- `Controller/CRUD6GroupsIntegrationTest.php`
- Various action tests (Create, Edit, Delete, etc.)

### Integration Tests
- `Integration/` - Full workflow tests
- `Integration/SchemaBasedApiTest.php`
- `Integration/FrontendUserWorkflowTest.php`
- `Integration/RoleUsersRelationshipTest.php`

### Service Tests
- `ServicesProvider/` - Service functionality
- `ServicesProvider/SchemaServiceTest.php`
- `ServicesProvider/SchemaLoaderTest.php`
- Various schema-related tests

### Model Tests
- `Database/Models/` - Model functionality
- `Database/Models/CRUD6ModelTest.php`

### Other Tests
- `Middlewares/` - Middleware tests
- `Schema/` - Schema validation
- `Sprunje/` - Data listing tests
- `Testing/` - Test utilities

## Benefits

1. **Comprehensive Testing**: All sprinkle functionality validated
2. **Real Environment**: Tests run in actual UserFrosting context
3. **CI Integration**: Automatic execution on every push/PR
4. **Early Detection**: Catch issues before deployment
5. **Framework Validation**: Verify compatibility with UserFrosting 6

## Comparison with Old Workflow

### Old Workflow (Disabled)
```yaml
- name: Run PHPUnit Integration Tests
  if: false # Disabled
  run: |
    vendor/bin/phpunit --configuration phpunit-crud6.xml --testsuite "CRUD6 Integration Tests"
    vendor/bin/phpunit --configuration phpunit-crud6.xml --testsuite "CRUD6 Controller Tests"
    vendor/bin/phpunit --configuration phpunit-crud6.xml --testsuite "CRUD6 Generated Schema Tests"
```

Issues:
- Required special `phpunit-crud6.xml` configuration (not in repo)
- Multiple test suite runs (less efficient)
- Session handler issues in CI environment

### New Workflow (Enabled)
```yaml
- name: Run PHPUnit tests from sprinkle
  run: |
    cd userfrosting
    cd vendor/ssnukala/sprinkle-crud6
    ../../../vendor/bin/phpunit --testdox --colors=always
```

Improvements:
- Uses existing `phpunit.xml` from sprinkle
- Single test run (all suites)
- Proper environment context
- Better error reporting
- Integrated with other UserFrosting operations

## Documentation Updates

### Files Updated

1. **`.github/workflows/integration-test.yml`**
   - Added PHPUnit test step
   - Includes environment information
   - Proper error handling

2. **`app/tests/README.md`**
   - Added CI testing section
   - Documented PHPUnit integration
   - Listed UserFrosting framework operations
   - Explained test environment

3. **`INTEGRATION_TESTING_QUICK_START.md`**
   - Added PHPUnit to workflow features
   - Updated workflow description

## Testing Instructions

### Manual Testing (Local)

```bash
# Setup UserFrosting project
composer create-project userfrosting/userfrosting uf6 "^6.0-beta"
cd uf6

# Install CRUD6 sprinkle
composer require ssnukala/sprinkle-crud6

# Configure and setup
# ... (configure MyApp.php, database, etc.) ...
php bakery migrate
php bakery create:admin-user --username=admin --password=admin123

# Run PHPUnit tests
cd vendor/ssnukala/sprinkle-crud6
../../../vendor/bin/phpunit --testdox
```

### CI Testing

Tests run automatically on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`
- Manual workflow dispatch

View results in GitHub Actions workflow runs.

## Future Enhancements

Potential improvements:
1. Add code coverage reporting
2. Separate test suites for unit vs integration
3. Performance benchmarking
4. Test result artifacts
5. Parallel test execution

## References

- Old workflow backup: `.archive/pre-framework-migration/integration-test.yml.backup`
- PHPUnit config: `phpunit.xml`
- Test base class: `app/tests/AdminTestCase.php`
- UserFrosting testing: https://learn.userfrosting.com/testing

## Conclusion

PHPUnit testing is now fully integrated into the CRUD6 sprinkle's integration workflow. Tests execute in a complete UserFrosting 6 environment with full framework access, validating all sprinkle functionality automatically on every code change.
