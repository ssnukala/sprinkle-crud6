# Security Middleware Test Documentation

## Test: `testSecurityMiddlewareIsApplied()`

**Location:** `app/tests/Integration/SchemaBasedApiTest.php` (lines ~130-200)

### Purpose

This test validates that CRUD6's complete security middleware stack is properly configured and functioning. It ensures that all API endpoints correctly enforce authentication and authorization.

### What This Test Validates

#### 1. Authentication Layer (Test #1)
**Test:** Unauthenticated request → 401 Unauthorized

```php
GET /api/crud6/users (no auth token)
Expected: 401 Unauthorized
```

**Purpose:** Validates that `AuthGuard` middleware is active and properly rejects requests without authentication tokens.

**Middleware:** `UserFrosting\Sprinkle\Account\Authenticate\AuthGuard`

---

#### 2. Authorization Layer (Test #2)
**Test:** Authenticated but no permissions → 403 Forbidden

```php
GET /api/crud6/users (authenticated user with NO permissions)
Expected: 403 Forbidden
```

**Purpose:** Validates that permission checking in `Base::validateAccess()` correctly denies access when user lacks required permissions.

**Controller Method:** `app/src/Controller/Base.php::validateAccess()`

---

#### 3. Successful Access (Test #3) ⚠️ CURRENTLY FAILING
**Test:** Authenticated with all required permissions → 200 OK

```php
GET /api/crud6/users (authenticated user with all CRUD6 permissions)
Expected: 200 OK
```

**Purpose:** Validates that properly authenticated and authorized users can successfully access CRUD6 endpoints.

**Permissions Required:**
- Schema-defined: `uri_crud6` (from users.json permissions.read)
- Legacy CRUD6 permissions: `crud6_`, `delete_crud6_field`, `update_crud6_field`, `uri_crud6_list`, `view_crud6_field`

---

#### 4. Action-Specific Permissions (Test #4)
**Test:** POST request with read-only permission → 403 Forbidden

```php
POST /api/crud6/users (user has 'uri_crud6' but not 'create_user')
Expected: 403 Forbidden
```

**Purpose:** Validates that permission checking is action-specific - having read permission doesn't grant create permission.

**Required Permission:** `create_user` (from users.json permissions.create)

---

## Current Issue (Test #3 Failing with 403)

### Symptoms
- Test grants all 6 CRUD6 legacy permissions
- GET request to `/api/crud6/users` returns 403 instead of 200
- Controller method `validateAccess()` is rejecting the request

### Possible Causes

1. **Permission Mismatch**
   - Schema defines read permission as `uri_crud6`
   - Controller checks for this permission in `validateAccess($schema, 'read')`
   - User might not have `uri_crud6` in granted permissions (verify with debug output)

2. **Permission Not in Database**
   - The `uri_crud6` permission might not exist in the test database
   - Check `DefaultPermissions::register()` seed creates this permission
   - Verify test is using seeded database

3. **Middleware Order**
   - Permission check might be running before user permissions are properly loaded
   - Check middleware stack in `CRUD6Routes.php`

4. **User Role Association**
   - Permissions might need to be attached through a role, not directly to user
   - UserFrosting's permission system typically works through roles

### Debug Logging Added

The test now outputs:
- Schema-defined READ permission (should be `uri_crud6`)
- List of permissions being granted to user
- Response status code
- Response body (if not 200)
- Error title and description (if available)

### Next Steps to Resolve

1. **Run test with debug output** - Check what permission is required vs granted
2. **Verify permission exists** - Query test database for `uri_crud6` permission
3. **Check actAsUser implementation** - Verify permissions are properly attached
4. **Review middleware stack** - Ensure CRUD6Injector sets up schema before permission check
5. **Check Base::validateAccess()** - Add logging to see exactly what permission it's checking

### Related Files

- Test: `app/tests/Integration/SchemaBasedApiTest.php`
- Controller: `app/src/Controller/Base.php` (lines 165-186)
- Schema: `examples/schema/users.json` (lines 9-14)
- Routes: `app/src/Routes/CRUD6Routes.php`
- Middleware: `app/src/Middlewares/CRUD6Injector.php`
- Seeds: `app/src/Database/Seeds/DefaultPermissions.php`

### Expected Behavior

When properly configured:
1. User has permission `uri_crud6`
2. Controller loads users schema
3. Controller calls `validateAccess($schema, 'read')`
4. Method checks `$schema['permissions']['read']` → gets `uri_crud6`
5. Method verifies user has permission `uri_crud6`
6. Access is granted → returns 200 OK

The test validates this entire flow works correctly.
