# Testing Guide for UserFrosting 6 Sprinkles

## Quick Reference

### Running Tests Locally (Development)

When developing within the sprinkle repository:

```bash
cd sprinkle-crud6
composer install
vendor/bin/phpunit
```

The local `phpunit.xml` configuration works fine because `vendor/autoload.php` is relative to the sprinkle root.

### Running Tests in CI (Integration Testing)

When the sprinkle is installed as a dependency in a UserFrosting application:

```bash
cd userfrosting  # UserFrosting application root
vendor/bin/phpunit \
  --bootstrap vendor/autoload.php \
  --testdox \
  --colors=always \
  vendor/ssnukala/sprinkle-crud6/app/tests
```

This ensures:
- UserFrosting's autoloader is used (has all framework classes)
- Full application context is available
- Tests can access `UserFrosting\Testing\TestCase`
- DI container is properly initialized

## UserFrosting 6 Testing Patterns

### 1. Base Test Case Structure

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

### 2. Integration Test Structure

```php
// app/tests/Integration/MyTest.php
namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class MyTest extends AdminTestCase
{
    use RefreshDatabase;      // Database testing support
    use WithTestUser;         // User authentication in tests
    use MockeryPHPUnitIntegration;  // Mockery integration
    
    public function testSomething(): void
    {
        // Full UserFrosting application context available
        $user = $this->actAsUser();  // From WithTestUser trait
        
        // Make HTTP requests
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequest($request);
        
        // Assertions
        $this->assertJsonResponse(200, $response);
    }
}
```

### 3. Unit Test Structure

```php
// app/tests/Unit/MyUnitTest.php
namespace UserFrosting\Sprinkle\CRUD6\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyUnitTest extends TestCase
{
    // Pure unit tests don't need UserFrosting context
    // Extend PHPUnit\Framework\TestCase directly
}
```

## CI Workflow Configuration

### GitHub Actions Step

```yaml
- name: Run PHPUnit tests from sprinkle
  run: |
    cd userfrosting
    
    # Validate environment
    if [ ! -d "vendor/ssnukala/sprinkle-crud6" ]; then
      echo "❌ CRUD6 sprinkle not found in vendor directory"
      exit 1
    fi
    
    if [ ! -f "vendor/bin/phpunit" ]; then
      echo "❌ PHPUnit not found in vendor/bin/"
      exit 1
    fi
    
    # Run tests from UserFrosting root
    vendor/bin/phpunit \
      --bootstrap vendor/autoload.php \
      --testdox \
      --colors=always \
      vendor/ssnukala/sprinkle-crud6/app/tests
```

## Common Traits for Testing

### RefreshDatabase
From `UserFrosting\Sprinkle\Core\Testing\RefreshDatabase`
- Automatically runs migrations before each test
- Resets database after each test
- Use for integration tests that need database access

### WithTestUser
From `UserFrosting\Sprinkle\Account\Testing\WithTestUser`
- Creates test users with specific roles/permissions
- Provides `actAsUser()` method for authenticated requests
- Use for tests that require authentication

### MockeryPHPUnitIntegration
From `Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration`
- Integrates Mockery with PHPUnit
- Auto-verifies mock expectations after each test
- Use when mocking dependencies

## Official References

### UserFrosting 6 Repositories
- **Framework**: https://github.com/userfrosting/framework/tree/6.0
  - Base `TestCase` class: `src/Testing/TestCase.php`
  - Framework tests: `tests/`

- **sprinkle-core**: https://github.com/userfrosting/sprinkle-core/tree/6.0
  - Core tests: `app/tests/`
  - `RefreshDatabase` trait
  - `CoreTestCase` base class

- **sprinkle-account**: https://github.com/userfrosting/sprinkle-account/tree/6.0
  - Account tests: `app/tests/`
  - `WithTestUser` trait
  - `AccountTestCase` base class

- **sprinkle-admin**: https://github.com/userfrosting/sprinkle-admin/tree/6.0
  - Admin tests: `app/tests/`
  - `AdminTestCase` base class
  - Examples of API testing

### Documentation
- UserFrosting Testing Docs: https://learn.userfrosting.com/testing
- PHPUnit Documentation: https://phpunit.de/documentation.html

## Key Takeaways

1. ✅ **Always run PHPUnit from UserFrosting root** in CI/integration tests
2. ✅ **Use UserFrosting's autoloader** for full framework access
3. ✅ **Extend `UserFrosting\Testing\TestCase`** for integration tests
4. ✅ **Specify `$mainSprinkle`** in your base test case
5. ✅ **Use testing traits** for common functionality (database, users, etc.)
6. ❌ **Don't try to run PHPUnit from vendor subdirectories** in CI
7. ❌ **Don't rely on sprinkle's phpunit.xml** for CI testing
8. ❌ **Don't expect sprinkles to have their own CI workflows** (they're tested in context)

## Troubleshooting

### "Class not found" errors
**Cause**: PHPUnit not using UserFrosting's autoloader
**Fix**: Ensure `--bootstrap vendor/autoload.php` is specified and running from UF root

### "Main sprinkle not set" errors
**Cause**: Test case doesn't specify `$mainSprinkle` property
**Fix**: Add `protected string $mainSprinkle = YourSprinkle::class;` to base test case

### Database connection errors
**Cause**: Test environment not configured
**Fix**: Ensure `.env` is configured and `RefreshDatabase` trait is used

### Authentication errors
**Cause**: Tests require authenticated user but none is set
**Fix**: Use `WithTestUser` trait and call `$this->actAsUser()` before making requests
