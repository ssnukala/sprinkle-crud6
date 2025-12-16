# Test Authorization Issue Analysis

## Problem Statement
122 tests are failing (primarily with 403 Forbidden errors) despite tests correctly using `actAsUser($user, permissions: ['permission_name'])` to create authenticated sessions.

## Current Understanding

### What We Know Works ✅
**Integration Tests (SchemaBasedApiTest)**:
```php
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['uri_users']);
$request = $this->createJsonRequest('GET', '/api/crud6/users');
$response = $this->handleRequestWithTracking($request);
$this->assertResponseStatus(200, $response); // ✅ PASSES
```

### What's Failing ❌
**Controller Tests (RelationshipActionTest, UpdateFieldActionTest, etc.)**:
```php
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['update_user_field']);
$request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/roles", ...);
$response = $this->handleRequestWithTracking($request);
$this->assertResponseStatus(200, $response); // ❌ Gets 403
```

## Schema-Defined Permissions
From `examples/schema/users.json`:
```json
"permissions": {
    "read": "uri_users",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
}
```

## Authorization Flow

### Controller Base Class (`Base.php`)
```php
protected function validateAccess(string|array $modelNameOrSchema, string $action = 'read'): void
{
    $schema = is_string($modelNameOrSchema)
        ? $this->getSchema($modelNameOrSchema)
        : $modelNameOrSchema;

    $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";

    if (!$this->authenticator->checkAccess($permission)) {
        throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
    }
}
```

**Key Questions**:
1. Is `$this->authenticator->checkAccess()` properly recognizing test-granted permissions?
2. Are the permission names exactly matching (case-sensitive)?
3. Is there a difference in how Integration vs Controller tests set up the authenticator?

## Hypotheses

### Hypothesis 1: Permission Naming Mismatch
- Tests grant: `'update_user_field'`
- Controller expects: `'update_user_field'` (from schema)
- **Status**: Appears to match ✅

### Hypothesis 2: Action-to-Permission Mapping Issue
Different actions might map to different permission keys:
- SprunjeAction (list): expects `'read'` → `'uri_users'` ✅
- UpdateFieldAction: expects `'update'` → `'update_user_field'` ✅
- RelationshipAction: expects `'update'` → `'update_user_field'` ✅

But RelationshipAction might be checking for a different action!

### Hypothesis 3: Test vs Integration Test Setup Difference
Integration tests might have additional setup that Controller tests lack:
- Different base test class configuration?
- Different middleware stack?
- Different service container setup?

### Hypothesis 4: WithTestUser Trait Not Fully Seeding Permissions
The `actAsUser($user, permissions: ['perm'])` might:
- Create the user ✅
- Set as authenticated ✅
- Grant permissions in memory but not persist them to DB? ❌
- Not register permissions with Authorizer? ❌

## Investigation Plan

### Step 1: Add Debug Logging
Add temporary debug logging to Base.php `validateAccess()`:
```php
protected function validateAccess(string|array $modelNameOrSchema, string $action = 'read'): void
{
    $schema = is_string($modelNameOrSchema)
        ? $this->getSchema($modelNameOrSchema)
        : $modelNameOrSchema;

    $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
    
    // DEBUG: Log what we're checking
    error_log("CRUD6 validateAccess - action: {$action}, permission: {$permission}, model: {$schema['model']}");
    error_log("CRUD6 validateAccess - user authenticated: " . ($this->authenticator->check() ? 'YES' : 'NO'));
    error_log("CRUD6 validateAccess - checkAccess result: " . ($this->authenticator->checkAccess($permission) ? 'PASS' : 'FAIL'));

    if (!$this->authenticator->checkAccess($permission)) {
        throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
    }
}
```

### Step 2: Compare Test Base Classes
- Check if `CRUD6TestCase` differs from Integration test base
- Verify both use same WithTestUser trait implementation
- Check for different service provider registrations

### Step 3: Check Relationship Action's validateAccess Call
Verify what action parameter RelationshipAction passes:
```php
// In RelationshipAction.php - what does it call?
$this->validateAccess($crudSchema, 'update');  // or 'read'? or 'delete'?
```

### Step 4: Verify Permission Grant Mechanism
Check if WithTestUser trait's `actAsUser()` actually:
1. Creates permission records in database
2. Associates permissions with user
3. Registers permissions with authorization system

## Recommended Solution Approach

### Option A: Make Tests Master Admin (Quick Fix)
```php
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['uri_*']);  // Grant all permissions
```
**Pros**: Quick, will make tests pass
**Cons**: Doesn't test actual permission logic

### Option B: Debug and Fix Permission System (Proper Fix)
1. Add debug logging to understand what's failing
2. Fix the root cause (likely permission registration)
3. Ensure tests properly grant permissions

### Option C: Use Master User from Seed
```php
$masterUser = User::where('user_name', 'admin')->first();
$this->actAsUser($masterUser);  // Use pre-seeded admin with all permissions
```
**Pros**: Uses real permission setup from seeds
**Cons**: Requires seed data to be present

## Next Steps
1. Add debug logging to `validateAccess()`
2. Run failing test and examine logs
3. Compare with passing Integration test logs
4. Identify the exact difference in permission checking
5. Implement fix based on findings

## Test Categories by Failure Type

### Category 1: 403 Permission Errors (70%)
- RedundantApiCallsTest (9 tests)
- RelationshipActionTest (4 tests)
- UpdateFieldActionTest (5 tests)
- SchemaBasedApiTest (4 tests)
- NestedEndpointsTest (2 tests)

### Category 2: Test Infrastructure Errors (20%)
- ListableFieldsTest (3 tests) - Mock type error
- PasswordFieldTest (5 tests) - Final class mock error
- NestedEndpointsTest (4 tests) - Missing getName() method

### Category 3: Data/Schema Errors (10%)
- RoleUsersRelationshipTest (2 tests) - 0 results
- SchemaActionTest (2 tests) - Missing 'table' key
- SchemaFilteringTest (2 tests) - null values

## Conclusion
The authentication sessions ARE being created correctly. The issue is with **permission authorization checking** failing despite permissions being granted via `actAsUser()`. We need to debug why `$this->authenticator->checkAccess($permission)` returns false when it should return true.
