# Parameter Injection Fix - Issue #[number]

**Date:** 2025-10-25
**Issue:** 500 Internal Server Error when accessing `/api/crud6/groups`

## Problem

The CRUD6 middleware (`CRUD6Injector`) was setting two request attributes (`crudModel` and `crudSchema`), but the controllers were expecting both to be auto-injected as method parameters. This caused a 500 error because UserFrosting 6's `AbstractInjector` only supports auto-injecting ONE parameter.

### Error Symptoms
- Middleware completed successfully
- Controller `__invoke` method was never called
- 500 Internal Server Error returned to frontend
- No error logs from controller code

### Root Cause
`AbstractInjector` uses the `$attribute` property to determine which request attribute to auto-inject into controller parameters. CRUD6Injector was:
1. Setting two attributes: `crudModel` and `crudSchema`
2. NOT setting the `$attribute` property
3. Controllers expected BOTH to be auto-injected: `__invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ...)`

## Solution

Follow the UserFrosting 6 pattern where AbstractInjector only auto-injects ONE parameter:

### 1. Set `$attribute` in CRUD6Injector
```php
class CRUD6Injector extends AbstractInjector
{
    protected string $attribute = 'crudModel';  // Tell AbstractInjector what to auto-inject
    // ...
}
```

### 2. Update Controller Signatures
Change from:
```php
public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ...)
```

To:
```php
public function __invoke(CRUD6ModelInterface $crudModel, ...)
{
    $crudSchema = $request->getAttribute('crudSchema');
    // ... rest of controller logic
}
```

## Files Changed

1. **app/src/Middlewares/CRUD6Injector.php**
   - Added `protected string $attribute = 'crudModel';`

2. **All Controllers** (8 files):
   - ApiAction.php
   - CreateAction.php
   - DeleteAction.php (also added $request parameter)
   - EditAction.php
   - RelationshipAction.php
   - SprunjeAction.php
   - UpdateFieldAction.php
   - Base.php (kept as-is, called by children)

## Verification

The fix ensures:
1. ✅ CRUD6Injector sets `$attribute = 'crudModel'`
2. ✅ AbstractInjector auto-injects `$crudModel` as first controller parameter
3. ✅ Controllers manually get `$crudSchema` from request attributes
4. ✅ Pattern matches UserFrosting 6 core (GroupInjector/GroupApi)

## Testing

This fix should be tested with:
1. Integration tests: `CRUD6GroupsIntegrationTest::testGroupsListApiReturnsGroups()`
2. Manual testing: Access `/api/crud6/groups` endpoint
3. Verify all CRUD operations work: list, create, read, update, delete

## References

- UserFrosting 6 Pattern: [GroupInjector + GroupApi](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Middlewares/GroupInjector.php)
- AbstractInjector base class uses `$attribute` property for parameter injection
- Previous fix: PR #119 established this pattern
