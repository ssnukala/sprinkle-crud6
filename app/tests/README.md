# CRUD6 Sprinkle Testing Guide

This directory contains tests for the CRUD6 sprinkle, following UserFrosting 6 testing patterns from sprinkle-admin and sprinkle-account.

## Testing Philosophy

**Important**: All PHPUnit tests in this directory require the UserFrosting 6 framework to run. This sprinkle cannot be tested in complete isolation because it depends on UserFrosting's authentication, routing, database, and service container infrastructure.

Following UserFrosting 6 best practices:

1. **PHPUnit Tests**: Tests in this directory test PHP functionality within UserFrosting context (controllers, models, services, etc.)
2. **Frontend Tests**: Frontend tests are in `app/assets/tests/` and use Vitest
3. **Integration Tests**: GitHub Actions workflow tests package installation in a real UserFrosting 6 environment
4. **Asset Building**: Left to consuming applications - we don't build assets in CI

## Test Structure

```
app/tests/
├── CRUD6TestCase.php           # Base test case for CRUD6 tests
├── Integration/                # Multi-step workflow and relationship tests
│   ├── CRUD6UsersIntegrationTest.php    # Full user model integration
│   ├── CRUD6GroupsIntegrationTest.php   # Full group model integration
│   ├── SchemaBasedApiTest.php           # Schema-driven API testing
│   ├── FrontendUserWorkflowTest.php     # Frontend workflow simulation
│   ├── RedundantApiCallsTest.php        # API call optimization tests
│   ├── NestedEndpointsTest.php          # Nested relationship endpoints
│   └── RoleUsersRelationshipTest.php    # Role-user relationships
├── Controller/                 # Individual controller action tests
│   ├── CreateActionTest.php    # POST /api/crud6/{model}
│   ├── EditActionTest.php      # PUT /api/crud6/{model}/{id}
│   ├── DeleteActionTest.php    # DELETE /api/crud6/{model}/{id}
│   ├── SprunjeActionTest.php   # GET /api/crud6/{model} (list)
│   └── ...                     # Other controller action tests
├── Database/                   # Database model and migration tests
├── Middlewares/                # Middleware tests
├── ServicesProvider/           # Service provider tests
└── Sprunje/                    # Sprunje (data listing) tests
```

## Running Tests

### PHPUnit Tests (Require UserFrosting Framework)

```bash
# Run all PHPUnit tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit app/tests/Integration/SchemaBasedApiTest.php

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

All PHP tests should extend `CRUD6TestCase` which provides the CRUD6 sprinkle context:

```php
<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class MyTest extends CRUD6TestCase
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

The GitHub Actions integration test workflow (`.github/workflows/integration-test.yml`):
- Creates a fresh UserFrosting 6 installation
- Installs the CRUD6 sprinkle as a Composer and NPM package
- Runs database migrations
- Verifies PHP functionality
- Tests with full PHP server and Vite dev server
- Uses Playwright for browser automation
- Does NOT build frontend assets (left to consuming applications)

### PHPUnit vs GitHub Integration Testing

**PHPUnit Tests** (`vendor/bin/phpunit`):
- Run within the sprinkle's development environment
- Require UserFrosting framework dependencies
- Test individual components and workflows
- Use test database with RefreshDatabase trait
- Fast feedback during development

**GitHub Integration Tests** (`.github/workflows/integration-test.yml`):
- Test the sprinkle as an installed package
- Validate Composer and NPM installation
- Verify sprinkle works in a fresh UserFrosting 6 project
- Test end-to-end with browser automation
- Confirm production-like deployment

Both are essential - PHPUnit for development, GitHub Actions for release validation.

## Test Patterns from UserFrosting 6

### RefreshDatabase Trait
Use for tests that need database access:

```php
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class MyTest extends CRUD6TestCase
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

class MyTest extends CRUD6TestCase
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

The GitHub Actions workflows run on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches
- Manual trigger via `workflow_dispatch`

### PHPUnit Tests Workflow (`.github/workflows/phpunit-tests.yml`)

Runs all PHPUnit tests in the sprinkle's development environment:
1. Sets up PHP and MySQL database
2. Installs Composer dependencies
3. Generates CRUD6 schema files and translations
4. Runs `vendor/bin/phpunit --testdox --colors=always`
5. Reports test coverage

**Note**: These tests require the UserFrosting 6 framework to run. They test:
- Controller actions (authentication, authorization, CRUD operations)
- Database models and relationships
- Service providers and middleware
- Sprunje data listing and filtering
- Integration workflows and API endpoints

### Integration Test Workflow (`.github/workflows/integration-test.yml`)

Tests the sprinkle as an installed package in a fresh UserFrosting 6 project:
1. Creates fresh UserFrosting 6 installation
2. Installs CRUD6 sprinkle via Composer and NPM
3. Runs migrations and creates admin user
4. Generates and loads test data
5. Starts PHP server and Vite dev server
6. Tests API endpoints (unauthenticated and authenticated)
7. Tests frontend pages with Playwright
8. Captures screenshots and logs

## Test Organization: Integration vs Controller

### Integration Tests (`app/tests/Integration/`)
Multi-step workflows and relationship tests that span multiple endpoints:

- **SchemaBasedApiTest.php**: Dynamic schema-driven testing of all CRUD endpoints
- **CRUD6UsersIntegrationTest.php**: Full user model workflow testing
- **CRUD6GroupsIntegrationTest.php**: Full group model workflow testing
- **FrontendUserWorkflowTest.php**: Simulates real frontend user interactions
- **RedundantApiCallsTest.php**: Tests for API call optimization
- **NestedEndpointsTest.php**: Tests nested relationship endpoints
- **RoleUsersRelationshipTest.php**: Tests many-to-many relationships
- **BooleanToggleSchemaTest.php**: Schema-based boolean toggle testing

### Controller Tests (`app/tests/Controller/`)
Focused tests for individual controller actions:

- **CreateActionTest.php**: POST /api/crud6/{model}
- **EditActionTest.php**: PUT /api/crud6/{model}/{id}
- **UpdateFieldActionTest.php**: PUT /api/crud6/{model}/{id}/{field}
- **DeleteActionTest.php**: DELETE /api/crud6/{model}/{id}
- **SprunjeActionTest.php**: GET /api/crud6/{model} (list)
- **SchemaActionTest.php**: GET /api/crud6/{model}/schema
- **RelationshipActionTest.php**: Relationship attach/detach operations

Both directories contain tests that require UserFrosting framework, but they differ in scope:
- **Integration**: Multi-step workflows, cross-model operations, relationship testing
- **Controller**: Single action focus, specific endpoint validation

## References

- [UserFrosting Testing Documentation](https://learn.userfrosting.com/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vitest Documentation](https://vitest.dev/)
- [sprinkle-admin Tests](https://github.com/userfrosting/sprinkle-admin/tree/6.0/app/tests)
- [sprinkle-account Tests](https://github.com/userfrosting/sprinkle-account/tree/6.0/app/tests)
