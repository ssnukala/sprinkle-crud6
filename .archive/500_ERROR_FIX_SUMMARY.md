# Fix Summary: 500 Error on /api/crud6/groups

## Problem
When accessing `/api/crud6/groups`, the application returned a 500 Internal Server Error. The middleware completed successfully, but the controller was never invoked.

## Root Cause
UserFrosting 6's `AbstractInjector` auto-injects ONE request attribute as a controller parameter based on the `$attribute` property. CRUD6Injector was:
- ✗ Not setting the `$attribute` property
- ✗ Controllers expecting TWO auto-injected parameters: `$crudSchema` and `$crudModel`
- ✗ This caused the framework to fail when trying to invoke the controller

## Solution
Implemented the standard UserFrosting 6 pattern:

### 1. Set `$attribute` in CRUD6Injector
```php
protected string $attribute = 'crudModel';
```

### 2. Update Controller Signatures
```php
// Before (broken)
public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ...)

// After (working)
public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ...)
{
    $crudSchema = $request->getAttribute('crudSchema');
    // ...
}
```

## Files Modified
- `app/src/Middlewares/CRUD6Injector.php` - Added `$attribute` property
- `app/src/Controller/ApiAction.php` - Updated signature
- `app/src/Controller/CreateAction.php` - Updated signature
- `app/src/Controller/DeleteAction.php` - Updated signature + added $request param
- `app/src/Controller/EditAction.php` - Updated signature
- `app/src/Controller/RelationshipAction.php` - Updated signature
- `app/src/Controller/SprunjeAction.php` - Updated signature
- `app/src/Controller/UpdateFieldAction.php` - Updated signature

## Verification
✅ All files have valid PHP syntax
✅ CRUD6Injector has correct `$attribute` setting
✅ All controllers have correct signatures
✅ All controllers retrieve schema from request
✅ Pattern matches UserFrosting 6 core (GroupInjector/GroupApi)

## Expected Result
The `/api/crud6/groups` endpoint should now:
1. Successfully invoke the SprunjeAction controller
2. Return paginated groups data
3. No longer return 500 errors

## Testing Required
1. Manual test: Access `/api/crud6/groups` and verify it returns data
2. Integration test: Run `vendor/bin/phpunit app/tests/Controller/CRUD6GroupsIntegrationTest.php`
3. Full CRUD test: Verify create, read, update, delete operations work

## References
- UserFrosting 6 Pattern: GroupInjector + GroupApi
- Custom Instructions: "CRITICAL: UserFrosting 6 supports automatic injection..."
- Previous Fix: PR #119 established this pattern
