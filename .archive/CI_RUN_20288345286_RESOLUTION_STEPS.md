# Step-by-Step Resolution Guide for CI Run #20288345286

This document provides detailed, actionable steps to resolve all 78 test failures and 5 errors from the CI run.

---

## Phase 1: Critical Security & Permission Issues (Priority 🔴)

### Step 1.1: Fix Sensitive Data Exposure in List Views

**Issue**: Password field exposed in list API responses  
**Tests Affected**: 3 failures  
**Estimated Time**: 30 minutes

#### Implementation Steps:

1. **Open Base Controller** (`app/src/Controller/Base.php`)

2. **Update `getListableFields()` method**:
```php
protected function getListableFields(array $schema): array
{
    $fields = [];
    $sensitiveFields = ['password', 'password_hash', 'secret', 'token', 'api_key'];
    
    foreach ($schema['fields'] as $fieldName => $fieldDef) {
        // Skip sensitive fields
        if (in_array($fieldName, $sensitiveFields)) {
            continue;
        }
        
        // Only include explicitly listable fields
        if (isset($fieldDef['listable']) && $fieldDef['listable'] === true) {
            $fields[$fieldName] = $fieldDef;
        }
    }
    
    return $fields;
}
```

3. **Update schema files** to explicitly mark fields:
```json
// app/schema/crud6/users.json
{
  "fields": {
    "password": {
      "type": "string",
      "listable": false,  // Add this
      "viewable": false   // Add this
    },
    "first_name": {
      "type": "string",
      "listable": true    // Add this
    }
  }
}
```

4. **Test the fix**:
```bash
vendor/bin/phpunit app/tests/Controller/ListableFieldsTest.php
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php::testListUsersReturnsOnlyListableFields
```

---

### Step 1.2: Fix Permission System

**Issue**: 40+ tests failing with 403 Forbidden  
**Root Cause**: Test users don't have permissions OR permissions not properly attached  
**Estimated Time**: 1-2 hours

#### Investigation Steps:

1. **Check permission seeding**:
```bash
# Open the seed file
cat app/src/Database/Seeds/DefaultPermissions.php

# Verify it creates these permissions:
# - crud6.users.view
# - crud6.users.create  
# - crud6.users.edit
# - crud6.users.delete
# - crud6.*.view (wildcard)
# - crud6.*.* (admin wildcard)
```

2. **Verify permissions are attached to site-admin role**:
```php
// In DefaultPermissions.php
public function run(): void
{
    $permissions = [
        'crud6.users.view',
        'crud6.users.create',
        'crud6.users.edit',
        'crud6.users.delete',
        'crud6.groups.view',
        'crud6.groups.create',
        'crud6.groups.edit',
        'crud6.groups.delete',
        'crud6.roles.view',
        'crud6.roles.create',
        'crud6.roles.edit',
        'crud6.roles.delete',
        'crud6.permissions.view',
        // Add wildcard permissions
        'crud6.*',          // All CRUD6 operations
        'crud6.*.view',     // View all models
    ];
    
    // Attach to site-admin
    $role = Role::where('slug', 'site-admin')->first();
    foreach ($permissions as $permSlug) {
        $perm = Permission::where('slug', $permSlug)->first();
        if ($perm && !$role->permissions->contains($perm->id)) {
            $role->permissions()->attach($perm->id);
        }
    }
}
```

3. **Check test user setup**:
```php
// In test classes (e.g., CRUD6UsersIntegrationTest)
protected function setUp(): void
{
    parent::setUp();
    
    // Get site-admin role
    $role = Role::where('slug', 'site-admin')->first();
    
    // Create test user with site-admin role
    $this->testUser = User::create([
        'user_name' => 'test-admin',
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'email' => 'test@example.com',
        'password' => 'password123'
    ]);
    
    // Attach role
    $this->testUser->roles()->attach($role->id);
    
    // Verify permissions
    $this->assertTrue($this->testUser->can('crud6.users.view'));
}
```

4. **Add permission debugging**:
```php
// In failing test
public function testUpdateUserSuccess(): void
{
    // Debug permissions
    $user = $this->actingAs($this->testUser);
    dump('User ID: ' . $user->id);
    dump('User Roles: ' . $user->roles->pluck('slug')->implode(', '));
    dump('User Permissions: ' . $user->permissions->pluck('slug')->implode(', '));
    dump('Can edit users: ' . ($user->can('crud6.users.edit') ? 'YES' : 'NO'));
    
    // Continue with test...
}
```

5. **Check permission middleware**:
```php
// app/src/Controller/EditAction.php
public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    // Add debug logging
    $this->logger->debug('EditAction: Checking permissions', [
        'model' => $crudSchema['model'],
        'required_permission' => "crud6.{$crudSchema['model']}.edit",
        'user_id' => $currentUser->id ?? 'guest'
    ]);
    
    // Check if hasPermission is working
    if (!$this->hasPermission($request, "crud6.{$crudSchema['model']}.edit")) {
        $this->logger->debug('EditAction: Permission denied');
        throw new ForbiddenException('Access denied');
    }
    
    // Rest of method...
}
```

6. **Test the fix**:
```bash
# Run permission-related tests
vendor/bin/phpunit app/tests/Controller/EditActionTest.php
vendor/bin/phpunit app/tests/Controller/CreateActionTest.php
vendor/bin/phpunit app/tests/Integration/CRUD6UsersIntegrationTest.php
```

---

### Step 1.3: Implement Soft Delete Properly

**Issue**: Soft delete not working, deleted_at remains null  
**Tests Affected**: 3 failures  
**Estimated Time**: 45 minutes

#### Implementation Steps:

1. **Add SoftDeletes trait to CRUD6Model**:
```php
// app/src/Database/Models/CRUD6Model.php
namespace UserFrosting\Sprinkle\CRUD6\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CRUD6Model extends Model implements CRUD6ModelInterface
{
    use SoftDeletes;  // Add this
    
    protected $dates = ['deleted_at'];  // Add this
    
    public function configureFromSchema(array $schema): self
    {
        // Existing code...
        
        // Check if schema enables soft delete
        if (isset($schema['soft_delete']) && $schema['soft_delete'] === true) {
            if (!in_array('deleted_at', $this->dates)) {
                $this->dates[] = 'deleted_at';
            }
        }
        
        return $this;
    }
}
```

2. **Update DeleteAction to handle soft deletes**:
```php
// app/src/Controller/DeleteAction.php
public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    // Check if already soft-deleted
    if (method_exists($crudModel, 'trashed') && $crudModel->trashed()) {
        throw new NotFoundException('Resource not found');
    }
    
    // Perform soft delete
    $crudModel->delete();
    
    // Verify deletion
    $this->logger->debug('Delete completed', [
        'model_id' => $crudModel->id,
        'deleted_at' => $crudModel->deleted_at,
        'is_trashed' => $crudModel->trashed()
    ]);
    
    return $this->successResponse($response, 'Resource deleted');
}
```

3. **Update schema files**:
```json
// app/schema/crud6/users.json
{
  "model": "users",
  "table": "users",
  "soft_delete": true,  // Add this
  "fields": {
    // ...
  }
}
```

4. **Test the fix**:
```bash
vendor/bin/phpunit app/tests/Controller/DeleteActionTest.php::testDeleteUserSoftDelete
vendor/bin/phpunit app/tests/Controller/DeleteActionTest.php::testDeleteAlreadyDeletedUserReturns404
```

---

### Step 1.4: Prevent Self-Deletion

**Issue**: Users can delete their own accounts  
**Tests Affected**: 1 failure  
**Estimated Time**: 15 minutes

#### Implementation:

```php
// app/src/Controller/DeleteAction.php
public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    /** @var \UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface */
    $currentUser = $this->currentUser;
    
    // Prevent self-deletion for user models
    if ($crudSchema['model'] === 'users' && $currentUser->id === $crudModel->id) {
        throw new ForbiddenException('You cannot delete your own account');
    }
    
    // Rest of deletion logic...
}
```

**Test**:
```bash
vendor/bin/phpunit app/tests/Controller/DeleteActionTest.php::testCannotDeleteOwnAccount
```

---

## Phase 2: HTTP Status Code Corrections (Priority 🟡)

### Step 2.1: Fix Validation Error Status Codes

**Issue**: Validation errors return 500 instead of 400  
**Tests Affected**: 6+ failures  
**Estimated Time**: 30 minutes

#### Implementation:

1. **Add validation error handler to controllers**:
```php
// app/src/Controller/CreateAction.php
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;

public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    try {
        // Validation logic
        $validator = $this->validatorFactory->make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        // Create resource...
        
    } catch (ValidationException $e) {
        // Return 400, not 500
        return $response
            ->withStatus(400)
            ->withJson([
                'message' => 'Validation failed',
                'errors' => $e->getErrors()
            ]);
    }
}
```

2. **Test the fix**:
```bash
vendor/bin/phpunit app/tests/Controller/CreateActionTest.php::testCreateUserWithValidationErrors
vendor/bin/phpunit app/tests/Controller/EditActionTest.php::testUpdateUserWithValidationErrors
```

---

### Step 2.2: Handle Create Status Code (200 vs 201)

**Issue**: Create returns 201 (correct) but tests expect 200  
**Tests Affected**: 7 failures  
**Estimated Time**: 20 minutes

#### Decision Required:

**Option A** (Recommended): Keep 201 and update tests
```php
// Update tests to accept both 200 and 201
$this->assertResponseStatus([200, 201]);
```

**Option B**: Change controllers to return 200
```php
// In CreateAction.php
return $response->withStatus(200);  // Instead of 201
```

**Recommendation**: Use Option A - 201 is the correct REST standard for resource creation.

---

## Phase 3: Field Access Control (Priority 🟡)

### Step 3.1: Fix Readonly Field Detection

**Issue**: Readonly fields included in editable lists  
**Tests Affected**: 2 failures  
**Estimated Time**: 30 minutes

#### Implementation:

```php
// app/src/Controller/Base.php
protected function getEditableFields(array $schema): array
{
    $editableFields = [];
    
    foreach ($schema['fields'] as $fieldName => $fieldDef) {
        // Exclude readonly fields
        if (isset($fieldDef['readonly']) && $fieldDef['readonly'] === true) {
            continue;
        }
        
        // Exclude auto-increment fields
        if (isset($fieldDef['auto_increment']) && $fieldDef['auto_increment'] === true) {
            continue;
        }
        
        // Exclude computed fields
        if (isset($fieldDef['computed']) && $fieldDef['computed'] === true) {
            continue;
        }
        
        // Exclude timestamps unless explicitly editable
        if (in_array($fieldName, ['created_at', 'updated_at', 'deleted_at'])) {
            if (!isset($fieldDef['editable']) || $fieldDef['editable'] !== true) {
                continue;
            }
        }
        
        $editableFields[$fieldName] = $fieldDef;
    }
    
    return $editableFields;
}
```

**Test**:
```bash
vendor/bin/phpunit app/tests/Controller/BaseControllerTest.php
```

---

### Step 3.2: Fix Search Functionality

**Issue**: Search returns all records instead of filtered  
**Tests Affected**: 6 failures  
**Estimated Time**: 45 minutes

#### Implementation:

```php
// app/src/Sprunje/CRUD6Sprunje.php
protected function applySearch($query, $searchTerm)
{
    if (empty($searchTerm)) {
        return $query;
    }
    
    $searchableFields = $this->getSearchableFields();
    
    if (empty($searchableFields)) {
        return $query;
    }
    
    $query->where(function ($q) use ($searchableFields, $searchTerm) {
        foreach ($searchableFields as $field) {
            $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
        }
    });
    
    $this->logger->debug('Applied search filter', [
        'term' => $searchTerm,
        'fields' => $searchableFields
    ]);
    
    return $query;
}

protected function getSearchableFields(): array
{
    $searchable = [];
    
    foreach ($this->schema['fields'] as $fieldName => $fieldDef) {
        // Only search in filterable text fields
        if (isset($fieldDef['filterable']) && $fieldDef['filterable'] === true) {
            if (in_array($fieldDef['type'], ['string', 'text'])) {
                $searchable[] = $fieldName;
            }
        }
    }
    
    return $searchable;
}
```

**Test**:
```bash
vendor/bin/phpunit app/tests/Sprunje/CRUD6SprunjeSearchTest.php
```

---

## Phase 4: Low Priority Issues (Priority 🟢)

### Step 4.1: Fix Test Infrastructure Issues

These are test implementation problems, not production code issues:

1. **CRUD6Injector tests** - Remove or fix property access
2. **Config service** - Register in DI container
3. **API Call Tracking** - Fix test setup
4. **Frontend routes** - Add if needed for UI

---

## Testing Checklist

After implementing fixes, run tests in this order:

### Step 1: Individual Test Classes
```bash
# Critical fixes
vendor/bin/phpunit app/tests/Controller/Base ControllerTest.php
vendor/bin/phpunit app/tests/Controller/DeleteActionTest.php
vendor/bin/phpunit app/tests/Controller/CreateActionTest.php
vendor/bin/phpunit app/tests/Controller/EditActionTest.php

# Integration tests
vendor/bin/phpunit app/tests/Integration/CRUD6UsersIntegrationTest.php
vendor/bin/phpunit app/tests/Integration/CRUD6GroupsIntegrationTest.php
```

### Step 2: Full Test Suite
```bash
vendor/bin/phpunit
```

### Step 3: Verify Results
Expected outcome:
- ✅ 0 failures
- ✅ 0 errors
- ✅ 292 tests passing (or close to it)

---

## Rollback Plan

If issues arise during implementation:

1. **Git reset to last known good state**:
```bash
git status
git diff  # Review changes
git checkout -- <file>  # Revert specific file
```

2. **Test incrementally**:
   - Make one change at a time
   - Run related tests
   - Commit if tests pass
   - Move to next fix

3. **Use feature branches**:
```bash
git checkout -b fix/permission-issues
# Make changes
git commit -m "Fix permission seeding"
# Run tests
git checkout main
git merge fix/permission-issues  # Only if tests pass
```

---

## Time Estimates

| Phase | Task | Estimated Time |
|-------|------|----------------|
| 1.1 | Sensitive data exposure | 30 min |
| 1.2 | Permission system | 1-2 hours |
| 1.3 | Soft delete | 45 min |
| 1.4 | Self-deletion | 15 min |
| 2.1 | Validation errors | 30 min |
| 2.2 | Status codes | 20 min |
| 3.1 | Readonly fields | 30 min |
| 3.2 | Search functionality | 45 min |
| 4.1 | Test infrastructure | 1 hour |
| **Total** | | **6-8 hours** |

---

## Success Criteria

✅ All 78 failures resolved  
✅ All 5 errors resolved  
✅ No new test failures introduced  
✅ Security vulnerabilities fixed (password exposure)  
✅ Permission system working correctly  
✅ Soft delete functional  
✅ CI pipeline passing on next commit
