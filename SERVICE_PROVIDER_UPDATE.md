# Service Provider Pattern Update

## Summary

Updated the CRUD6 sprinkle to follow UserFrosting 6 beta service provider patterns as found in `userfrosting/sprinkle-core` and `userfrosting/sprinkle-account`.

## Changes Made

### 1. Created `SchemaServiceProvider.php`

**File:** `app/src/ServicesProvider/SchemaServiceProvider.php`

- Implements `ServicesProviderInterface` (standard UF6 pattern)
- Registers `SchemaService` using `\DI\autowire()` for dependency injection
- Follows the exact pattern used in UF6 core sprinkles (e.g., `I18nService`, `CacheService`)

```php
class SchemaServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            SchemaService::class => \DI\autowire(SchemaService::class),
        ];
    }
}
```

### 2. Updated `CRUD6.php` Main Sprinkle Class

**File:** `app/src/CRUD6.php`

- Added `SchemaServiceProvider` to the `getServices()` method
- Added proper `use` statement for the new service provider
- Now both service providers are registered:
  - `CRUD6ModelService` - for model interface mapping
  - `SchemaServiceProvider` - for schema service registration

```php
public function getServices(): array
{
    return [
        CRUD6ModelService::class,
        SchemaServiceProvider::class,
    ];
}
```

### 3. Added Tests

**File:** `app/tests/ServicesProvider/SchemaServiceProviderTest.php`

- Tests that `SchemaServiceProvider` implements `ServicesProviderInterface`
- Tests that `register()` returns an array of service definitions
- Tests that `SchemaService` is properly registered
- Tests that autowiring is configured correctly

## Why These Changes Were Necessary

### Before
The `SchemaService` class existed but was not registered in any service provider. This meant:
- The DI container would use default resolution
- No control over service instantiation
- Not following UF6 patterns

### After
With the `SchemaServiceProvider`:
- `SchemaService` is explicitly registered with the DI container
- Follows UserFrosting 6 beta patterns exactly as found in core sprinkles
- Provides clear documentation of service dependencies
- Enables proper singleton pattern for the service
- Makes dependency injection explicit and testable

## UserFrosting 6 Pattern Reference

This implementation follows the same pattern used in:

1. **`userfrosting/sprinkle-core`**:
   - `CacheService` - registers cache implementations
   - `I18nService` - registers translation services
   - `SessionService` - registers session handlers

2. **`userfrosting/sprinkle-account`**:
   - `ModelsService` - registers model interface mappings
   - `AuthService` - registers authentication services

## Service Provider vs Service Class

**Service Providers** (implement `ServicesProviderInterface`):
- `CRUD6ModelService` - registers model mappings
- `SchemaServiceProvider` - registers SchemaService

**Service Classes** (registered by providers):
- `SchemaService` - actual service implementation

**Middleware** (NOT service providers):
- `CRUD6Injector` - extends `AbstractInjector`
- `SchemaInjector` - middleware implementation

Middlewares/Injectors do NOT need service providers - they are resolved by the container when added to routes.

## Verification

All changes have been validated:
- ✓ PHP syntax validation passed
- ✓ Service provider pattern matches UF6 core
- ✓ Dependency injection properly configured
- ✓ Tests added for service provider registration
- ✓ All existing functionality preserved
