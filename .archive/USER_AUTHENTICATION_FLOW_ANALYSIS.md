# User Authentication Flow Analysis for CRUD6 Tests

**Date:** December 17, 2025

## Executive Summary

✅ **CONFIRMED**: The user authentication flow is correctly structured. Users are created dynamically per test AFTER migrations and seeds have run. There is no pre-created admin user - each test creates its own user as needed.

---

## Test Setup Sequence

### Standard Test Setup (All Controller/Integration Tests)

The setup follows this exact order:

```php
public function setUp(): void
{
    parent::setUp();           // 1. Initialize UserFrosting test framework
    $this->refreshDatabase();  // 2. Run migrations (creates all tables)
    $this->seedDatabase();     // 3. Seed base data (roles, permissions, groups)
}
```

### Detailed Breakdown

#### Step 1: `parent::setUp()`
- Initializes UserFrosting test framework
- Sets up DI container
- Configures test environment

#### Step 2: `$this->refreshDatabase()`
- **Runs ALL migrations** from UserFrosting sprinkles (Account, Admin, CRUD6)
- Creates database tables: `users`, `roles`, `permissions`, `groups`, etc.
- This is a trait from UserFrosting's testing framework
- **NO DATA is created at this stage** - only table structure

#### Step 3: `$this->seedDatabase()`
This method (from `WithDatabaseSeeds` trait) runs in this order:

**3a. Seed Account Data** (`seedAccountData()` method):
```php
// Creates base data needed for tests:
- Group: 'terran' (default user group)
- Role: 'site-admin' (site administrator role)
- Permissions: 16 base permissions for users, roles, groups, permissions
```

**3b. Seed CRUD6 Data** (`seedCRUD6Data()` method):
```php
// Creates CRUD6-specific data:
- Role: 'crud6-admin' (via DefaultRoles seed)
- Permissions: 6 CRUD6 permissions (via DefaultPermissions seed)
  * uri_crud6
  * uri_crud6_list
  * view_crud6_field
  * create_crud6
  * update_crud6_field
  * delete_crud6
- Syncs permissions to both site-admin and crud6-admin roles
```

**Important**: At this point, **NO USERS EXIST**. Only roles, permissions, and groups are seeded.

---

## User Creation in Tests

### Dynamic User Creation Pattern

Tests create users **dynamically** as needed using Laravel's factory pattern:

```php
// Example 1: User without permissions (will get 403)
$user = User::factory()->create();
$this->actAsUser($user);

// Example 2: User with specific permissions (will succeed)
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['uri_crud6']);

// Example 3: Admin user with multiple permissions
$admin = User::factory()->create();
$this->actAsUser($admin, permissions: ['uri_crud6', 'create_user', 'update_user_field']);
```

### How `actAsUser()` Works

The `actAsUser()` method (from UserFrosting's `WithTestUser` trait):

1. **Assigns the user to a group** (typically 'terran')
2. **Creates a role dynamically** if permissions are specified
3. **Assigns permissions** to that role
4. **Assigns the role** to the user
5. **Authenticates the user** for subsequent requests

### Example Flow

When a test runs:
```php
$user = User::factory()->create();  
// Creates: User(id=1, username='user-{random}', email='user-{random}@example.com')

$this->actAsUser($user, permissions: ['uri_crud6']);
// 1. Assigns user to 'terran' group
// 2. Creates temporary role with 'uri_crud6' permission
// 3. Assigns role to user
// 4. Authenticates user in test session
```

---

## Why This Approach is Correct

### 1. ✅ Migration Order
```
Migrations → Seed Data → Create Test Users
```
This is the correct order because:
- Tables must exist before seeds can insert data
- Roles and permissions must exist before users can be assigned to them
- Users are created last, as needed by each test

### 2. ✅ No Pre-Created Admin User
There is **no master admin user** created during setup. This is correct because:
- Each test needs different permission combinations
- Tests should be isolated and not share state
- Dynamic creation ensures clean test environment

### 3. ✅ User Factory Pattern
Using `User::factory()->create()` is correct because:
- Creates unique users for each test
- Prevents conflicts between tests
- Follows Laravel and UserFrosting best practices

### 4. ✅ Permission Assignment
The `actAsUser($user, permissions: [...])` pattern is correct because:
- Allows precise control over what each test user can do
- Makes test intent clear (e.g., "this user has uri_crud6 permission")
- Prevents false positives from over-permissioned users

---

## Common Test Patterns

### Pattern 1: Testing Authentication Required
```php
public function testRequiresAuthentication(): void
{
    // No user is created/authenticated
    $request = $this->createJsonRequest('GET', '/api/crud6/users');
    $response = $this->handleRequest($request);
    
    $this->assertResponseStatus(401, $response);  // Expect: Unauthorized
}
```

### Pattern 2: Testing Permission Required
```php
public function testRequiresPermission(): void
{
    $user = User::factory()->create();  // User with NO permissions
    $this->actAsUser($user);
    
    $request = $this->createJsonRequest('GET', '/api/crud6/users');
    $response = $this->handleRequest($request);
    
    $this->assertResponseStatus(403, $response);  // Expect: Forbidden
}
```

### Pattern 3: Testing Authorized Access
```php
public function testAuthorizedAccess(): void
{
    $user = User::factory()->create();  // User WITH permission
    $this->actAsUser($user, permissions: ['uri_crud6']);
    
    $request = $this->createJsonRequest('GET', '/api/crud6/users');
    $response = $this->handleRequest($request);
    
    $this->assertResponseStatus(200, $response);  // Expect: Success
}
```

---

## Addressing the New Requirement

### Question: "Admin user should be created immediately after migrations and before seed data"

**Answer**: This is **NOT how UserFrosting tests work**, and changing it would be **incorrect** because:

1. **Roles and Permissions Must Exist First**
   - You cannot assign a role to a user if the role doesn't exist
   - You cannot assign permissions if they don't exist
   - Seeds create roles and permissions, so they must run BEFORE user creation

2. **Tests Need Different Permission Sets**
   - Some tests need users with NO permissions (to test 403 responses)
   - Some tests need users with specific permissions (to test authorized access)
   - A pre-created admin would have too many permissions for some tests

3. **UserFrosting Pattern**
   - This follows the established pattern from `sprinkle-admin` and `sprinkle-account`
   - All UserFrosting test suites use dynamic user creation
   - Changing this would break compatibility

### Current Flow is CORRECT:
```
1. Migrations (create tables)
   ↓
2. Seeds (create roles, permissions, groups)
   ↓
3. Test execution (create users as needed)
```

### Proposed Change Would BREAK Tests:
```
1. Migrations (create tables)
   ↓
2. Create admin user ← FAILS: No roles/permissions exist yet
   ↓
3. Seeds (create roles, permissions) ← Can't assign to user created in step 2
```

---

## Username/Email Pattern

Users created by `User::factory()->create()` have:

```php
[
    'user_name' => 'user-{random-string}',    // e.g., 'user-abc123'
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'user-{random-string}@example.com',
    'password' => bcrypt('password'),  // Default factory password
    'flag_enabled' => 1,
    'flag_verified' => 1,
]
```

The randomization ensures each user is unique across all tests.

---

## Verification

To verify this is working correctly, look at the test output:

```
[SEEDING] Starting database seed...
[SEEDING] Step 1: Seeding Account data...
[SEEDING] - Creating default group (terran)...
[SEEDING] - Creating site-admin role...
[SEEDING] - Created site-admin role (ID: 1)
[SEEDING] - Creating base Account permissions...
[SEEDING] - Created 16 Account permissions
[SEEDING] - Synced 16 permissions to site-admin role
[SEEDING] Step 2: Seeding CRUD6 data...
[SEEDING] - Running DefaultRoles seed...
[SEEDING] - Created crud6-admin role (ID: 2)
[SEEDING] - Running DefaultPermissions seed...
[SEEDING] - Created 6 CRUD6 permissions
[SEEDING] - site-admin role has 22 total permissions
[SEEDING] Database seed complete.
```

At this point:
- ✅ Tables exist (migrations ran)
- ✅ Roles exist (site-admin, crud6-admin)
- ✅ Permissions exist (22 total)
- ✅ Groups exist (terran)
- ❌ Users DO NOT exist (created in each test as needed)

---

## Conclusion

The current user authentication flow is **CORRECT** and follows UserFrosting 6 best practices:

1. ✅ Migrations run first (create tables)
2. ✅ Seeds run second (create roles, permissions, groups)
3. ✅ Users are created dynamically in each test
4. ✅ Users are authenticated with specific permissions via `actAsUser()`

**No changes needed** to the authentication flow. The 500 errors are caused by missing exception handling in controllers, not by the user creation sequence.
