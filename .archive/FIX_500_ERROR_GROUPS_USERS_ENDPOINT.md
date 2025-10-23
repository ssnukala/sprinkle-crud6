# Fix: 500 Internal Server Error on /api/crud6/groups/1/users Endpoint

**Date**: 2025-10-23  
**Issue**: GET request to `/api/crud6/groups/1/users?size=10&page=0` returns 500 Internal Server Error  
**PR Branch**: `copilot/fix-internal-server-error`

## Problem Summary

When accessing the endpoint `/api/crud6/groups/1/users` to retrieve users in a specific group, the API returned a 500 Internal Server Error instead of the expected user list.

## Root Cause Analysis

Multiple controller actions were incorrectly passing `ServerRequestInterface $request` as the second parameter to `SchemaService` methods:

1. **Method Signature Issue**: 
   - `SchemaService::getSchema(string $model, ?string $connection = null): array`
   - Expected second parameter: `?string $connection` (optional database connection name)
   - Received: `ServerRequestInterface $request` object

2. **Affected Methods**:
   - `SchemaService::getSchema()` - expects optional string connection name
   - `SchemaService::getModelInstance()` - accepts only one parameter (model name)

## Files Modified

### 1. app/src/Controller/SprunjeAction.php
**Purpose**: Handles listing and filtering for CRUD6 models, including related data

**Changes**:
- **Line 83**: Changed `getSchema($relation, $request)` to `getSchema($relation)`
- **Line 101**: Changed `getModelInstance($relation, $request)` to `getModelInstance($relation)`

**Context**: This action handles the `/api/crud6/{model}/{id}/{relation}` route, which is used to retrieve related records (e.g., users in a group).

### 2. app/src/Controller/UpdateFieldAction.php
**Purpose**: Handles partial updates of individual fields

**Changes**:
- **Line 73**: Changed `getSchema($modelName, $request)` to `getSchema($modelName)`

**Context**: This action handles the `PUT /api/crud6/{model}/{id}/{field}` route.

### 3. app/src/Controller/RelationshipAction.php
**Purpose**: Manages many-to-many relationship attachments/detachments

**Changes**:
- **Line 71**: Changed `getSchema($modelName, $request)` to `getSchema($modelName)`

**Context**: This action handles `POST` and `DELETE` requests to `/api/crud6/{model}/{id}/{relation}`.

## Tests Added

### app/tests/Controller/CRUD6GroupsIntegrationTest.php

Added comprehensive test coverage for the groups/{id}/users endpoint:

1. **testGroupUsersApiRequiresAuthentication**: Verifies 401 response for unauthenticated requests
2. **testGroupUsersApiRequiresPermission**: Verifies 403 response for users without permission
3. **testGroupUsersApiReturnsUsers**: Verifies successful response with user list for authorized users
4. **testGroupUsersApiReturns404ForNonExistent**: Verifies 404 response for non-existent group

## Technical Details

### The Bug Chain

1. **Request**: `GET /api/crud6/groups/1/users?size=10&page=0`
2. **Route Match**: `/api/crud6/{model}/{id}/{relation}` → `SprunjeAction`
3. **Middleware**: `CRUD6Injector` loads group with ID=1, injects schema and model
4. **Controller**: `SprunjeAction::__invoke()` is called
5. **Error Point**: Line 83 calls `getSchema($relation, $request)`
   - PHP type error: Expected `?string`, received `ServerRequestInterface`
   - Results in 500 Internal Server Error

### Why This Wasn't Caught Earlier

The `getSchema()` method signature uses optional type hinting:
```php
public function getSchema(string $model, ?string $connection = null): array
```

When called with `getSchema($model, $request)`, PHP doesn't immediately throw an error during syntax checking. The error only occurs at runtime when the method tries to use the `$request` object as a string connection name.

### Correct Usage

The correct usage patterns in the codebase:

1. **Without connection override**:
   ```php
   $schema = $this->schemaService->getSchema($modelName);
   ```

2. **With connection override** (from middleware):
   ```php
   $schema = $this->schemaService->getSchema($modelName, $this->currentConnectionName);
   ```

## Verification Steps

### Syntax Validation
```bash
find app/src app/tests -name "*.php" -exec php -l {} \;
```
Result: All files pass syntax validation

### Search for Similar Issues
```bash
grep -rn "getSchema.*\$request\|getModelInstance.*\$request" app/src/ --include="*.php"
```
Result: No remaining occurrences found (except `getSchemaFromRequest` which is a different method)

## Impact Assessment

### Direct Impact
- Fixes 500 error on `/api/crud6/groups/{id}/users` endpoint
- Fixes potential 500 errors on:
  - `PUT /api/crud6/{model}/{id}/{field}` (UpdateFieldAction)
  - `POST/DELETE /api/crud6/{model}/{id}/{relation}` (RelationshipAction)
  - Any other relation-based queries through SprunjeAction

### Side Effects
- None expected - changes are purely corrective
- All fixed methods now match their intended signatures
- No changes to public API contracts

## Testing Recommendations

For full verification in a live environment:

1. **Test the fixed endpoint**:
   ```bash
   curl -H "Authorization: Bearer <token>" \
        "http://localhost:8500/api/crud6/groups/1/users?size=10&page=0"
   ```
   Expected: 200 OK with JSON response containing user list

2. **Test field update**:
   ```bash
   curl -X PUT -H "Authorization: Bearer <token>" \
        -H "Content-Type: application/json" \
        -d '{"value": "new_value"}' \
        "http://localhost:8500/api/crud6/{model}/{id}/{field}"
   ```
   Expected: 200 OK with updated field

3. **Test relationship operations**:
   ```bash
   curl -X POST -H "Authorization: Bearer <token>" \
        -H "Content-Type: application/json" \
        -d '{"related_id": 123}' \
        "http://localhost:8500/api/crud6/{model}/{id}/{relation}"
   ```
   Expected: 200 OK with success message

## Related Schema Configuration

The fix enables proper use of the `detail` section in schema files:

```json
{
  "model": "groups",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name", "last_name", "flag_enabled"],
    "title": "GROUP.USERS"
  }
}
```

This configuration:
- Specifies that groups have a detail view showing related users
- Uses `group_id` as the foreign key to filter users
- Lists specific fields to display in the user list
- The fix ensures this configuration is properly loaded and used

## Code Review Notes

### Type Safety
The fix improves type safety by ensuring method calls match their signatures. Consider adding PHPStan or Psalm to catch such issues during development.

### Database Connection Handling
The current implementation correctly supports:
1. Default connection (no second parameter)
2. Connection override via URL (e.g., `/api/crud6/users@db1`)
3. Path-based schema lookup with connection (e.g., `schema://crud6/db1/users.json`)

The fixed code now properly uses these patterns.

## Conclusion

This fix resolves a critical type mismatch issue that caused 500 errors when accessing related data through the CRUD6 API. The changes are minimal and surgical, affecting only the incorrect method calls while preserving all existing functionality.

All modified code passes syntax validation, and comprehensive tests have been added to prevent regression.
