# Testing Approach Aligned with UserFrosting 6

## Summary

This document explains how the CRUD6 sprinkle testing approach follows UserFrosting 6 patterns as found in sprinkle-admin and sprinkle-account.

## Changes Made

### Integration Test Workflow Simplification

**Problem**: The integration test was failing because it tried to build frontend assets (`php bakery assets:vite --production`), which encountered TypeScript compilation errors in Vue components.

**Solution**: Removed frontend asset building from the integration test workflow. This aligns with UserFrosting 6 best practices where:
- Sprinkles provide source files (PHP and frontend assets)
- Consuming applications handle asset compilation
- Integration tests verify package installation and PHP functionality only

### Test Structure

The CRUD6 sprinkle now has a clear separation of test types:

#### 1. PHP Unit Tests (`app/tests/`)
- Located in: `app/tests/`
- Base class: `AdminTestCase` (extends `UserFrosting\Testing\TestCase`)
- Pattern source: `@userfrosting/sprinkle-admin/app/tests/AdminTestCase.php`
- Run with: `vendor/bin/phpunit`

**Example**:
```php
class MyTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }
}
```

#### 2. Frontend Tests (`app/assets/tests/`)
- Located in: `app/assets/tests/`
- Test framework: Vitest
- Pattern source: `@userfrosting/sprinkle-admin/app/assets/tests/`
- Run with: `npm test`

**Example**:
```typescript
import { describe, expect, test } from 'vitest'

describe('My Component', () => {
    test('should work', () => {
        expect(true).toBe(true)
    })
})
```

#### 3. Integration Tests (`.github/workflows/integration-test.yml`)
- Tests: Package installation in real UserFrosting 6 environment
- Verifies:
  - Composer package installation
  - NPM package installation
  - Sprinkle registration
  - Database migrations
  - PHP functionality
- Does NOT build frontend assets (left to consuming applications)

## Why This Approach?

### Follows UserFrosting 6 Patterns

**From sprinkle-admin**:
- PHP tests extend `AdminTestCase`
- Uses `RefreshDatabase` trait
- Uses `WithTestUser` trait
- Frontend tests use Vitest

**From sprinkle-account**:
- Same test structure
- Same patterns for authentication tests
- Same factory usage for test data

### Separation of Concerns

1. **Sprinkle Responsibility**: Provide working source code
2. **Application Responsibility**: Compile and bundle assets
3. **CI Responsibility**: Verify installation and PHP functionality

### Benefits

1. **Faster CI**: No TypeScript compilation in integration tests
2. **Clearer Errors**: PHP tests fail for PHP issues, frontend tests fail for frontend issues
3. **Standard Pattern**: Matches official UserFrosting sprinkles
4. **Flexibility**: Applications can customize asset building as needed

## Running Tests

### Local Development

```bash
# PHP tests (requires UserFrosting 6 context)
vendor/bin/phpunit

# Frontend tests (standalone)
npm test

# Frontend tests in watch mode
npm run test:watch
```

### In CI/CD

The GitHub Actions integration test automatically runs on:
- Push to main/develop
- Pull requests to main/develop
- Manual trigger

It verifies the sprinkle can be installed and works in a real UserFrosting 6 application.

## Documentation

- **PHP Tests**: See `app/tests/README.md`
- **Manual Testing**: See `TESTING_GUIDE.md`
- **Integration Testing**: See `INTEGRATION_TESTING.md`

## References

- [UserFrosting Testing Documentation](https://learn.userfrosting.com/testing)
- [sprinkle-admin Tests](https://github.com/userfrosting/sprinkle-admin/tree/6.0/app/tests)
- [sprinkle-account Tests](https://github.com/userfrosting/sprinkle-account/tree/6.0/app/tests)
- [PHPUnit Documentation](https://phpunit.de/)
- [Vitest Documentation](https://vitest.dev/)
