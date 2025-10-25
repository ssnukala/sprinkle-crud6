# Controller Method Signature Fix Summary

## Issue

All CRUD6 controller child classes had incompatible `__invoke` method signatures compared to their parent `Base` class. This caused a PHP compilation error when the controllers were invoked:

```
Declaration of UserFrosting\Sprinkle\CRUD6\Controller\ApiAction::__invoke(
    UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface $crudModel,
    Psr\Http\Message\ServerRequestInterface $request,
    Psr\Http\Message\ResponseInterface $response
): Psr\Http\Message\ResponseInterface must be compatible with 
UserFrosting\Sprinkle\CRUD6\Controller\Base::__invoke(
    array $crudSchema,
    UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface $crudModel,
    Psr\Http\Message\ServerRequestInterface $request,
    Psr\Http\Message\ResponseInterface $response
): Psr\Http\Message\ResponseInterface
```

## Root Cause

The `Base` class defined the `__invoke` method signature with `array $crudSchema` as the first parameter, but all child controller classes were missing this parameter in their method signatures.

This violated PHP's Liskov Substitution Principle - child classes must have compatible method signatures with their parent class.

## UserFrosting 6 Middleware Injection Pattern

The fix follows UserFrosting 6's middleware injection pattern where:

1. **Middleware (`CRUD6Injector`)** sets request attributes:
   ```php
   $request = $request
       ->withAttribute('crudModel', $instance)
       ->withAttribute('crudSchema', $schema);
   ```

2. **AbstractInjector** (parent of CRUD6Injector) automatically injects these attributes as controller parameters based on parameter type and name matching

3. **Controllers** receive these as direct parameters, NOT by calling `$request->getAttribute()`:
   ```php
   public function __invoke(
       array $crudSchema,              // Auto-injected from 'crudSchema' attribute
       CRUD6ModelInterface $crudModel, // Auto-injected from 'crudModel' attribute
       ServerRequestInterface $request,
       ResponseInterface $response
   ): ResponseInterface
   ```

This pattern is identical to UserFrosting's `GroupInjector` + `GroupApi` pattern from sprinkle-admin.

## Solution

Added `array $crudSchema` as the first parameter to all controller `__invoke` method signatures:

### Files Modified

1. `app/src/Controller/ApiAction.php`
2. `app/src/Controller/CreateAction.php`
3. `app/src/Controller/DeleteAction.php`
4. `app/src/Controller/EditAction.php`
5. `app/src/Controller/RelationshipAction.php`
6. `app/src/Controller/SprunjeAction.php`
7. `app/src/Controller/UpdateFieldAction.php`

### Changes Made

For each controller:
- **Added** `array $crudSchema` as the first parameter to `__invoke()` method
- **Removed** redundant `$request->getAttribute('crudSchema')` calls since the schema is now directly injected
- **Updated** PHPDoc comments to document the auto-injected parameter

### Example Change (ApiAction.php)

**Before:**
```php
public function __invoke(
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    $crudSchema = $request->getAttribute('crudSchema');
    // ...
}
```

**After:**
```php
public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    // $crudSchema is now directly available as a parameter
    // ...
}
```

## Verification

1. **Syntax Check**: All 25 PHP files in `app/src` pass syntax validation
2. **Signature Verification**: Custom verification script confirmed all controllers have compatible signatures
3. **Pattern Compliance**: Changes follow UserFrosting 6's middleware injection pattern as documented in custom instructions

## Impact

- **Minimal Change**: Only 7 files modified with surgical changes
- **Lines Changed**: 17 insertions, 24 deletions
- **Behavior**: No functional changes - controllers receive the same data, just via parameter injection instead of manual retrieval
- **Benefits**: 
  - Fixes PHP compilation error
  - Follows UserFrosting 6 patterns
  - More type-safe (array type hint on $crudSchema)
  - Cleaner code (no redundant getAttribute calls)

## Testing

While full integration tests could not be run due to missing dependencies in CI environment, the changes:
- Pass all syntax validation
- Follow established UserFrosting 6 patterns
- Are minimal and surgical
- Maintain backward compatibility at the functional level

## References

- UserFrosting 6 sprinkle-admin: `GroupInjector` + `GroupApi` pattern
- UserFrosting 6 custom instructions: Middleware Injection Pattern section
- PR #119: Previous fix that established this pattern (later broken)
