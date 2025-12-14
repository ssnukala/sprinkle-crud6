# CRUD6 Sprinkle Testing Guide

This directory contains tests for the CRUD6 sprinkle, following UserFrosting 6 testing patterns from sprinkle-admin and sprinkle-account.

## Testing Philosophy

Following UserFrosting 6 best practices:

1. **PHP Unit Tests**: Tests in this directory test PHP functionality (controllers, models, services, etc.)
2. **Frontend Tests**: Frontend tests are in `app/assets/tests/` and use Vitest
3. **Integration Tests**: GitHub Actions workflow tests package installation in a real UserFrosting 6 environment
4. **Asset Building**: Left to consuming applications - we don't build assets in CI

## Test Structure

```
app/tests/
├── AdminTestCase.php           # Base test case for CRUD6 tests
├── Controller/                 # Controller action tests
│   ├── Group/                  # Group-related controller tests
│   ├── Role/                   # Role-related controller tests
│   └── User/                   # User-related controller tests
├── Database/                   # Database model and migration tests
├── Middlewares/                # Middleware tests
├── ServicesProvider/           # Service provider tests
└── Sprunje/                    # Sprunje (data listing) tests
```

## Running Tests

### PHP Unit Tests

```bash
# Run all PHP tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit app/tests/Sprunje/UserSprunjeTest.php

# Run specific test method
vendor/bin/phpunit --filter testBaseSprunje
```

### Frontend Tests

```bash
# Run frontend tests (from sprinkle directory)
npm test

# Run in watch mode
npm run test:watch
```

## Writing Tests

### PHP Test Example

All PHP tests should extend `AdminTestCase` which provides the CRUD6 sprinkle context:

```php
<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class MyTest extends AdminTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    public function testSomething(): void
    {
        // Your test code here
        $this->assertTrue(true);
    }
}
```

### Frontend Test Example

Frontend tests in `app/assets/tests/` use Vitest:

```typescript
import { describe, expect, test } from 'vitest'
import MyComponent from '../components/MyComponent.vue'

describe('MyComponent', () => {
    test('should render correctly', () => {
        expect(MyComponent).toBeDefined()
    })
})
```

## Integration Testing

The GitHub Actions integration test workflow:
- Creates a fresh UserFrosting 6 installation
- Installs the CRUD6 sprinkle as a Composer and NPM package
- Runs database migrations
- Verifies PHP functionality
- Does NOT build frontend assets (left to consuming applications)

See `.github/workflows/integration-test.yml` for details.

## Test Patterns from UserFrosting 6

### RefreshDatabase Trait
Use for tests that need database access:

```php
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class MyTest extends AdminTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }
}
```

### WithTestUser Trait
Use for tests that need authenticated users:

```php
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;

class MyTest extends AdminTestCase
{
    use WithTestUser;

    public function testAsUser(): void
    {
        $user = User::factory()->create();
        $this->actAsUser($user);
        
        // Test authenticated endpoint
    }
}
```

### Factory Pattern
Use Eloquent factories for creating test data:

```php
$user = User::factory()->create([
    'user_name' => 'testuser',
    'email' => 'test@example.com',
]);

$users = User::factory()->count(10)->create();
```

## Test Coverage

Current test coverage includes:
- ✅ Controller actions (create, read, update, delete)
- ✅ Sprunje data listing and filtering
- ✅ Model functionality
- ✅ Service providers
- ✅ Middleware
- ✅ Frontend routing
- ✅ Frontend component imports

## Continuous Integration

The GitHub Actions workflow runs on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches
- Manual trigger via `workflow_dispatch`

Tests verify:
1. Package can be installed via Composer
2. Package can be installed via NPM
3. Sprinkle can be registered in a UserFrosting 6 app
4. Database migrations run successfully
5. Schema files load correctly
6. Basic PHP functionality works
7. **PHPUnit tests execute successfully** (added in integration workflow)

### PHPUnit Testing in CI

The integration workflow now includes a PHPUnit test step that:
- Runs after the UserFrosting 6 application is fully set up
- Executes all tests in `app/tests/` directory
- Uses the sprinkle's `phpunit.xml` configuration
- Tests controllers, models, services, and integration scenarios
- Verifies functionality in a real UserFrosting environment

The tests run within the context of a complete UserFrosting 6 installation with:
- Configured database (MySQL)
- Completed migrations
- Test data loaded
- Admin user created
- All sprinkle dependencies available

## References

- [UserFrosting Testing Documentation](https://learn.userfrosting.com/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vitest Documentation](https://vitest.dev/)
- [sprinkle-admin Tests](https://github.com/userfrosting/sprinkle-admin/tree/6.0/app/tests)
- [sprinkle-account Tests](https://github.com/userfrosting/sprinkle-account/tree/6.0/app/tests)
