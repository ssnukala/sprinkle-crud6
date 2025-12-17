# CI Test Failure Analysis - Run #20288345286

**Date**: December 17, 2025  
**Branch**: main  
**Commit**: 9e1f61ab47bfb5747ec8b8cb297e21f8e45ac3d8  
**Workflow**: Unit Tests (.github/workflows/unit-tests.yml)  
**PHP Version**: 8.4

## Executive Summary

The CI run failed with **78 test failures** and **5 errors** out of 292 total tests (73.3% pass rate). The failures fall into several distinct categories, with the majority (approximately 50+ failures) caused by **permission/authorization issues returning 403 Forbidden** when 200 OK is expected.

---

## Test Results Overview

| Metric | Count | Percentage |
|--------|-------|------------|
| Total Tests | 292 | 100% |
| Passing | 205 | 70.2% |
| Failures | 78 | 26.7% |
| Errors | 5 | 1.7% |
| Warnings | 3 | 1.0% |
| Skipped | 1 | 0.3% |

---

## Error Categories & Analysis

### Category 1: Permission/Authorization Issues (40+ failures)
**Severity**: 🔴 CRITICAL  
**Impact**: High - Blocks most CRUD operations

#### Description
The majority of test failures return **HTTP 403 Forbidden** instead of the expected **200 OK** or other success codes. This affects:
- User update operations
- Create operations
- Delete operations
- Custom actions
- Relationship operations (attach/detach)
- Field update operations (toggle flags)

#### Example Failures
```
testSingleUserApiRequiresPermission
  Expected: 'Access Denied'
  Actual: null (returned 200 instead of 403)

testToggleFlagEnabledUpdatesUserStatus
  Expected: 200
  Actual: 403 Forbidden

testUpdateUserSuccess
  Expected: 200
  Actual: 403 Forbidden

testAttachRelationshipSuccess
  Expected: 200
  Actual: 403 Forbidden
```

#### Root Cause Analysis
1. **Permission middleware may be misconfigured** in test environment
2. **Test user may not have required permissions** granted
3. **Permission checking logic** may be too restrictive
4. **site-admin role** may not have all required CRUD6 permissions attached

#### Affected Test Classes
- `CRUD6UsersIntegrationTest`
- `CRUD6GroupsIntegrationTest`
- `EditActionTest`
- `CreateActionTest`
- `CustomActionTest`
- `RelationshipActionTest`
- `UpdateFieldActionTest`
- `SchemaBasedApiTest`

#### Proposed Resolution
**Step 1**: Verify permission seeding
```bash
# Check if CRUD6 permissions are properly attached to site-admin role
# Location: app/src/Database/Seeds/DefaultPermissions.php
```

**Step 2**: Verify test user permissions
```php
// In test setup, ensure user has permissions:
$user = $this->createTestUser(['permissions' => ['crud6.*']]);
```

**Step 3**: Review permission middleware configuration
```php
// Check: app/src/Routes/CRUD6Routes.php
// Ensure routes have proper permission checks
```

---

### Category 2: HTTP Status Code Mismatches (15+ failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - API contract violations

#### Description
Controllers return incorrect HTTP status codes that don't match REST standards or test expectations:

| Operation | Expected | Actual | Standard |
|-----------|----------|--------|----------|
| Create (POST) | 200 | 201 | ✅ 201 is correct |
| Delete (already deleted) | 404 | 200 | ❌ Should be 404 |
| Validation error | 400 | 500 | ❌ Should be 400 |
| Duplicate resource | 400 | 500 | ❌ Should be 400 |

#### Example Failures
```
testCreateUserSuccess
  Expected: 200
  Actual: 201 Created
  
testDeleteAlreadyDeletedUserReturns404
  Expected: 404
  Actual: 200

testCreateUserWithValidationErrors
  Expected: 400
  Actual: 500
```

#### Root Cause
1. **Create operations** return 201 (REST standard) but tests expect 200
2. **Soft delete** not properly checking if resource already deleted
3. **Validation errors** throwing exceptions (500) instead of returning 400
4. **Error handling middleware** not catching validation exceptions

#### Proposed Resolution
**Option A**: Update tests to accept REST-standard status codes
```php
// Change from:
$this->assertResponseStatus(200);

// To:
$this->assertResponseStatus([200, 201]); // Accept both
```

**Option B**: Update controllers to return consistent codes
```php
// In CreateAction.php - keep 201 (it's correct)
return $response->withStatus(201);

// In validation error handlers:
catch (ValidationException $e) {
    return $response->withStatus(400); // Not 500
}
```

---

### Category 3: Soft Delete Not Working (3 failures)
**Severity**: 🔴 CRITICAL  
**Impact**: Medium - Data integrity issue

#### Description
Soft delete functionality is not working properly. Tests show:
- `deleted_at` field remains null after deletion
- Already-deleted resources return 200 instead of 404
- Soft-deleted records can be deleted again

#### Example Failures
```
testDeleteUserSoftDelete
  Failed asserting that null is not null
  (deleted_at should be set)

testDeleteAlreadyDeletedUserReturns404
  Expected: 404
  Actual: 200
```

#### Root Cause
The CRUD6Model may not be properly configured with SoftDeletes trait:
```php
// Check: app/src/Database/Models/CRUD6Model.php
use Illuminate\Database\Eloquent\SoftDeletes;

class CRUD6Model extends Model {
    // Missing: use SoftDeletes;
}
```

#### Proposed Resolution
**Step 1**: Add SoftDeletes trait to CRUD6Model
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class CRUD6Model extends Model implements CRUD6ModelInterface
{
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
}
```

**Step 2**: Update DeleteAction to check for already-deleted
```php
// In DeleteAction.php
if ($model->trashed()) {
    return $this->notFoundResponse($response);
}
```

---

### Category 4: Self-Deletion Prevention Missing (1 failure)
**Severity**: 🟡 MEDIUM  
**Impact**: Low - Security issue

#### Description
Users can delete their own accounts, which should be prevented.

#### Example Failure
```
testCannotDeleteOwnAccount
  Expected: Deletion should fail
  Actual: Deletion succeeded
```

#### Proposed Resolution
```php
// In DeleteAction.php
if ($currentUser->id === $model->id) {
    throw new ForbiddenException('Cannot delete your own account');
}
```

---

### Category 5: Frontend Routes Missing (4 failures)
**Severity**: 🟢 LOW  
**Impact**: Low - Frontend UI not accessible

#### Description
Frontend routes return 404 instead of 200/302/401:
- `/crud6/users` → 404
- `/crud6/users/{id}` → 404
- `/crud6/groups` → 404
- `/crud6/groups/{id}` → 404

#### Root Cause
Frontend routes not registered or middleware blocking them.

#### Proposed Resolution
Check route registration in `app/src/Routes/CRUD6Routes.php`:
```php
// Add frontend routes
$group->get('/crud6/users', PageAction::class)
    ->setName('crud6.users.list');
```

---

### Category 6: API Call Tracking Not Working (9 failures)
**Severity**: 🟢 LOW  
**Impact**: Low - Testing infrastructure issue

#### Description
All `RedundantApiCalls` tests failing with 0 calls tracked when 1+ expected.

#### Example Failures
```
testSingleListCallNoRedundantCalls
  Expected: 1 API call tracked
  Actual: 0 calls tracked
```

#### Root Cause
API call tracking middleware/listener not initialized or not attached to test requests.

#### Proposed Resolution
Verify test setup initializes tracking:
```php
// In test setUp()
$this->tracker = new ApiCallTracker();
$this->app->add($this->tracker);
```

---

### Category 7: Schema/Config Issues (3 failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - API contract issues

#### Issues Identified
1. **Config endpoint not returning debug_mode**
   ```
   Expected: ['debug_mode' => ...]
   Actual: [] (empty array)
   ```

2. **Schema permission check not working**
   ```
   Expected: 'Access Denied' (403)
   Actual: Returns schema (200)
   ```

3. **DI Container missing 'config' entry**
   ```
   Error: No entry or class found for 'config'
   ```

#### Proposed Resolution
```php
// Register config service
$container->set('config', function() {
    return new Config(...);
});
```

---

### Category 8: Listable Fields Including Sensitive Data (3 failures)
**Severity**: 🔴 CRITICAL  
**Impact**: High - Security vulnerability

#### Description
Password field is being returned in list views when it should be excluded.

#### Example Failures
```
testBaseGetListableFieldsOnlyExplicit
  Password field should not be listable by default
  
testListUsersReturnsOnlyListableFields
  password found in list response
```

#### Root Cause
`getListableFields()` method not properly filtering sensitive fields.

#### Proposed Resolution
```php
// In Base controller or CRUD6Model
protected function getListableFields(): array
{
    $fields = $this->crudSchema['fields'];
    
    // Remove sensitive fields
    $sensitive = ['password', 'password_hash', 'secret', 'token'];
    return array_diff_key($fields, array_flip($sensitive));
}
```

---

### Category 9: Model Configuration Issues (2 failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - Schema configuration

#### Issues
1. **created_at included in hidden fields** when it shouldn't be
2. **Soft delete configuration** not detecting SoftDeletes trait

#### Proposed Resolution
Review `CRUD6Model::configureFromSchema()` method.

---

### Category 10: Search Not Working Properly (6 failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - Search functionality broken

#### Description
Sprunje search tests failing - returns all records instead of filtered results.

#### Example
```
testSearchAcrossMultipleFields
  Expected: 2 groups matching "Alpha"
  Actual: 4 groups (all records returned)
```

#### Root Cause
Search filtering not applied in CRUD6Sprunje or filterable fields not configured properly.

---

### Category 11: Readonly/Editable Field Logic (2 failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - Field access control

#### Issues
```
testGetEditableFieldsWithReadonly
  Readonly field 'id' should not be in editable list
  
testGetValidationRulesOnlyIncludesEditableFields
  Readonly fields should not have validation rules
```

#### Proposed Resolution
```php
protected function getEditableFields(): array
{
    return array_filter($this->schema['fields'], function($field) {
        return !($field['readonly'] ?? false) 
            && !($field['auto_increment'] ?? false)
            && !($field['computed'] ?? false);
    });
}
```

---

### Category 12: Relationship Operations (2 failures)
**Severity**: 🟡 MEDIUM  
**Impact**: Medium - M2M relationships

#### Issues
- `testRoleUsersNestedEndpointHandlesAmbiguousColumn` - Returns 0 users when 3 expected
- Attach/detach operations returning 403

---

### Category 13: Test Infrastructure Issues (4 errors)
**Severity**: 🟢 LOW  
**Impact**: Low - Test implementation

#### Errors
1. **CRUD6Injector tests** trying to access non-existent property `$currentModelName`
2. **ConfigAction test** - DI Container missing 'config' entry
3. **SchemaFiltering test** - Calling non-static method statically

---

## Priority Action Plan

### 🔴 CRITICAL - Fix Immediately
1. **Permission Issues** (40+ failures)
   - [ ] Verify DefaultPermissions seed attaches all permissions to site-admin
   - [ ] Check test user setup includes proper permissions
   - [ ] Review permission middleware configuration

2. **Sensitive Data Exposure** (3 failures)
   - [ ] Filter password from listable fields
   - [ ] Filter password from visible fields
   - [ ] Add security test for other sensitive fields

3. **Soft Delete** (3 failures)
   - [ ] Add SoftDeletes trait to CRUD6Model
   - [ ] Update DeleteAction to check trashed status
   - [ ] Add deleted_at to timestamps

### 🟡 MEDIUM - Fix Soon
4. **Status Code Mismatches** (15+ failures)
   - [ ] Decide on REST standard vs test expectations
   - [ ] Update validation error handling (400 not 500)
   - [ ] Fix duplicate resource error handling

5. **Self-Deletion Prevention** (1 failure)
   - [ ] Add self-deletion check in DeleteAction

6. **Search Functionality** (6 failures)
   - [ ] Fix CRUD6Sprunje search filtering
   - [ ] Verify filterable field configuration

7. **Model Configuration** (4 failures)
   - [ ] Review getEditableFields() logic
   - [ ] Fix readonly field detection
   - [ ] Review schema normalization

### 🟢 LOW - Can Be Deferred
8. **Frontend Routes** (4 failures)
   - [ ] Register frontend page routes
   - [ ] Add page controllers

9. **API Call Tracking** (9 failures)
   - [ ] Fix test infrastructure setup
   - [ ] Initialize tracker properly

10. **Test Infrastructure** (4 errors)
    - [ ] Fix CRUD6Injector tests
    - [ ] Register config service in DI
    - [ ] Fix SchemaFiltering test

---

## Testing Strategy

### Before Making Changes
```bash
# Run full test suite to establish baseline
vendor/bin/phpunit

# Run specific test class to verify failure
vendor/bin/phpunit app/tests/Controller/EditActionTest.php
```

### After Each Fix
```bash
# Run related test class
vendor/bin/phpunit app/tests/Controller/[TestClass].php

# Verify no regressions
vendor/bin/phpunit app/tests/Integration/
```

### Final Validation
```bash
# Run full suite
vendor/bin/phpunit

# Should see 0 failures, 0 errors
```

---

## Root Cause Summary

The test failures indicate three main systemic issues:

1. **Permission System**: Either test setup doesn't grant permissions, or permission checks are overly restrictive
2. **Schema Configuration**: Fields marked as readonly/sensitive are not being properly filtered
3. **Error Handling**: Validation and other errors are throwing 500 instead of returning proper 4xx codes

Fixing these three root causes should resolve approximately 60-70% of the test failures.

---

## Files Requiring Changes

### High Priority
- `app/src/Database/Seeds/DefaultPermissions.php` - Verify permission seeding
- `app/src/Database/Models/CRUD6Model.php` - Add SoftDeletes, fix hidden fields
- `app/src/Controller/Base.php` - Fix getListableFields(), getEditableFields()
- `app/src/Controller/DeleteAction.php` - Add soft delete check, self-deletion check
- `app/src/Controller/CreateAction.php` - Fix error handling (400 not 500)
- `app/src/Controller/EditAction.php` - Fix error handling

### Medium Priority
- `app/src/Sprunje/CRUD6Sprunje.php` - Fix search filtering
- `app/src/Routes/CRUD6Routes.php` - Add frontend routes
- `app/src/ServicesProvider/*` - Register config service

### Low Priority
- `app/tests/Middlewares/CRUD6InjectorTest.php` - Fix test implementation
- `app/tests/Integration/RedundantApiCallsTest.php` - Fix test infrastructure

---

## Conclusion

The CI run reveals a mix of critical security issues (sensitive data exposure), functional problems (permissions, soft delete), and API contract issues (status codes). The permission issues account for the majority of failures and should be the first priority to address.

**Estimated Effort**: 
- Critical fixes: 2-4 hours
- Medium priority: 3-5 hours  
- Low priority: 1-2 hours
- **Total**: 6-11 hours

**Risk Assessment**:
- 🔴 Security risk: Password exposure in list views
- 🟡 Functional risk: Most CRUD operations blocked by permission issues
- 🟢 Minor risk: Test infrastructure issues don't affect production
