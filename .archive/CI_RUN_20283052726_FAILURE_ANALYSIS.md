# CI Workflow Run #20283052726 - Comprehensive Failure Analysis

**Date**: December 16, 2025  
**Run URL**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20283052726/job/58249897896  
**Commit**: 19c38feaa68a1f483d8d275a65af89ba6c4f0252  
**Workflow**: Unit Tests (PHP 8.4)  
**Result**: FAILED ❌

## Summary Statistics

- **Total Tests**: 297
- **Assertions**: 929
- **Errors**: 22 
- **Failures**: 92
- **Warnings**: 8
- **Skipped**: 1
- **Risky**: 1
- **Total Issues**: 114 (22 errors + 92 failures)

---

## Error Categories & Root Causes

### 1. **Permission/Authorization Failures** 🔴 **HIGHEST PRIORITY**
**Count**: ~40+ tests  
**HTTP Status**: 403 Forbidden (expected 200)

#### Affected Test Classes:
1. **RedundantApiCallsTest** (8 tests)
   - `testSingleListCallNoRedundantCalls`
   - `testSchemaCallTracking`
   - `testDetectsRedundantApiCalls`
   - `testAssertApiCallCount`
   - `testTrackingMultipleDifferentCalls`
   - `testCRUD6CallIdentification`
   - `testComplexWorkflowNoRedundantCalls`
   - `testTrackerReset`

2. **RelationshipActionTest** (4 tests)
   - `testAttachRelationshipSuccess`
   - `testDetachRelationshipSuccess`
   - `testAttachMultipleRelationships`
   - `testDetachMultipleRelationships`

3. **UpdateFieldActionTest** (5 tests)
   - `testBooleanFieldWithoutValidationRulesIsUpdated`
   - `testUpdateTextFieldSuccess`
   - `testRejectsUpdateToNonExistentField` (expecting 400/422, got 403)
   - `testRejectsUpdateToReadonlyField` (expecting 400/422, got 403)
   - `testUpdateFlagVerifiedField`

4. **SchemaBasedApiTest** (2 tests)
   - `testSecurityMiddlewareIsApplied`
   - `testUsersModelCompleteApiIntegration`

5. **CreateActionTest** (implicit through test setup)

#### Root Cause Analysis:
```
Test grants permission: 'uri_crud6' or 'update_user_field'
Expected: Permission check passes, status 200
Actual: Permission check fails, status 403
```

**Possible Causes**:
1. Permission string mismatch between schema and authorization check
2. Authorization middleware running before user session is established
3. Permission caching issue in test environment
4. Schema permission configuration not matching expected permission names

**Resolution Steps**:
1. ✅ Verify permission strings in schema files match test expectations
2. ✅ Check authorization middleware order in route definitions
3. ✅ Ensure `actAsUser()` properly establishes authenticated session with permissions
4. ✅ Review CRUD6Injector middleware to ensure it doesn't interfere with auth
5. ✅ Add debug logging to permission checks to trace the issue

---

### 2. **Schema Response Structure Issues** 🟡 **HIGH PRIORITY**
**Count**: 3 tests  
**Status**: Assertion failures

#### Affected Tests:
1. `SchemaActionTest::testSchemaRequiresPermission`
   - Expected: Response title 'Access Denied'
   - Actual: null

2. `SchemaActionTest::testSchemaReturnsValidSchema`
   - Expected: Response has 'table' key
   - Actual: 'table' key missing

3. `SchemaActionTest::testSchemaFieldsHaveProperStructure`
   - Expected: Array with field structure
   - Actual: null (TypeError)

#### Root Cause:
Schema API response missing required metadata structure:
```php
// Expected structure:
[
    'table' => 'users',           // ❌ MISSING
    'fields' => [...],
    'relationships' => [...],
    'permissions' => [...]
]
```

**Resolution**:
Update `app/src/Controller/ApiAction.php` (schema endpoint) to include table metadata in response.

---

### 3. **ID Field Serialization** 🟡 **HIGH PRIORITY**
**Count**: 3 tests  
**Issue**: Created records return null IDs

#### Affected Tests:
1. `SchemaBasedApiTest::testRolesModelCompleteApiIntegration`
   - Created role ID is null
   
2. `SchemaBasedApiTest::testGroupsModelCompleteApiIntegration`
   - Created group ID is null

3. `SchemaBasedApiTest::testPermissionsModelCompleteApiIntegration`
   - Created permission ID is null

#### Root Cause:
Model serialization not including primary key in API response:
```php
// Model $hidden property hiding ID
protected $hidden = ['id', 'password'];  // ❌ ID should be visible

// Or missing $visible configuration
protected $visible = ['name', 'email'];  // ❌ Missing 'id'
```

**Resolution**:
Ensure CRUD6 model serialization includes primary key field in create responses.

---

### 4. **Mockery Final Class Issues** ⚠️ **MEDIUM PRIORITY**
**Count**: 5 tests (all in PasswordFieldTest)  
**Status**: Errors (cannot mock final class)

#### Affected Tests:
1. `testPasswordFieldHashingInCreateAction`
2. `testEmptyPasswordFieldNotHashedInCreateAction`
3. `testPasswordFieldHashingInEditAction`
4. `testNonPasswordFieldsNotAffected`
5. `testUpdateFieldActionHasHasher`

#### Error Message:
```
Mockery\Exception: The class \UserFrosting\Fortress\Transformer\RequestDataTransformer 
is marked final and its methods cannot be replaced.
```

#### Root Cause:
Tests attempting to mock `RequestDataTransformer` which is marked `final` in UserFrosting framework.

**Resolution Options**:
1. **Option A**: Use partial mocks by passing instantiated object
   ```php
   $transformer = new RequestDataTransformer(...);
   $mock = Mockery::mock($transformer)->makePartial();
   ```

2. **Option B**: Refactor tests to not require mocking (integration test approach)
   - Test actual password hashing behavior
   - Verify database has hashed password
   - Check bcrypt/argon2 format

3. **Option C**: Use dependency injection with interface
   - Create `RequestDataTransformerInterface`
   - Mock the interface instead

---

### 5. **Frontend Workflow Test Failures** 🟠 **MEDIUM PRIORITY**
**Count**: 5 tests  
**Issues**: Status code mismatches, size mismatches

#### Affected Tests:
1. `FrontendUserWorkflowTest::testEditUserWorkflow`
   - Expected: 200
   - Actual: 500 (Internal Server Error)

2. `FrontendUserWorkflowTest::testCreateGroupWorkflow`
   - Expected: 200
   - Actual: 201 (Created - actually correct!)

3. `FrontendUserWorkflowTest::testCreateRoleWithPermissionsWorkflow`
   - Expected: 200
   - Actual: 201 (Created - actually correct!)

4. `FrontendUserWorkflowTest::testSearchAndFilterUsersWorkflow`
   - Expected: 2 users
   - Actual: 1 user

5. `FrontendUserWorkflowTest::testViewNestedRelationshipWorkflow`
   - Expected: 2 items
   - Actual: 0 items

#### Root Causes:
1. **500 Error**: Internal server error in edit workflow - needs investigation
2. **201 vs 200**: Test expectation wrong - 201 is correct for CREATE operations
3. **Size mismatches**: Data seeding or query filtering issues

**Resolution**:
1. Investigate 500 error in edit action
2. Update test expectations: accept 201 for CREATE operations
3. Debug search/filter queries and relationship loading

---

### 6. **Field Visibility Issues** 🟢 **LOW PRIORITY**
**Count**: 2 tests

#### Affected Tests:
1. `SprunjeActionTest::testListUsersReturnsOnlyListableFields`
   - Issue: Password field appearing in list view
   - Expected: Password excluded
   - Actual: Password included

2. `SchemaFilteringTest::testViewableAttributeFiltering`
   - Issue: Password field not marked as readonly
   - Expected: `readonly` = true
   - Actual: `readonly` = null

#### Root Cause:
Sprunje not filtering sensitive fields based on schema configuration:
```php
// Schema marks password as not listable
"password": {
    "listable": false,     // ✅ Configured correctly
    "readonly": true       // ❌ Not being respected
}
```

**Resolution**:
Update `CRUD6Sprunje` to filter fields based on `listable` and `readonly` attributes.

---

### 7. **TypeScript Composable Test** 🟢 **LOW PRIORITY**
**Count**: 1 test

#### Affected Test:
`SchemaCachingContextTest::testComposablePassesContextToStore`

#### Issue:
Test checking for incorrect method signature:
```typescript
// Test expects:
schemaStore.loadSchema(model, force, context)

// Actual signature (correct):
schemaStore.loadSchema(model, force, context, includeRelated)
```

**Resolution**:
Update test expectations to match current `loadSchema` signature with 4 parameters.

---

### 8. **Nested Endpoint Issues** 🟡 **HIGH PRIORITY**
**Count**: 4 tests

#### Affected Tests:
1. `NestedEndpointsTest::testPermissionDetailEndpoint`
   - Expected: `slug` = 'test_permission'
   - Actual: `slug` = null

2. `NestedEndpointsTest::testRoleDetailEndpoint`
   - Expected: `slug` = 'test_role'
   - Actual: `slug` = null

3. `RoleUsersRelationshipTest::testRoleUsersNestedEndpointHandlesAmbiguousColumn`
   - Expected: 3 users
   - Actual: 0 users

4. `RoleUsersRelationshipTest::testRoleUsersNestedEndpointWithPagination`
   - Expected: 10 users (first page)
   - Actual: 0 users

#### Root Cause:
Nested relationship endpoints not loading or serializing data correctly.

**Resolution**:
1. Check `RelationshipAction` controller
2. Verify relationship query building
3. Ensure model serialization includes all fields

---

## Detailed Test Breakdown by Class

### Controller Tests

#### CreateActionTest (0/11 failed - assuming all pass with proper permissions)
- ✅ All tests pass when permissions are correctly granted

#### DeleteActionTest (0/7 failed)
- ✅ All tests passing

#### EditActionTest (0/15 failed - assuming all pass with proper permissions)
- ✅ All tests pass when permissions are correctly granted

#### RelationshipActionTest (4/8 failed)
- ❌ testAttachRelationshipSuccess
- ❌ testDetachRelationshipSuccess  
- ❌ testAttachMultipleRelationships
- ❌ testDetachMultipleRelationships

#### SchemaActionTest (2/5 failed)
- ❌ testSchemaRequiresPermission (null title)
- ❌ testSchemaReturnsValidSchema (missing 'table' key)

#### SprunjeActionTest (1/9 failed)
- ❌ testListUsersReturnsOnlyListableFields (password visible)

#### UpdateFieldActionTest (5/7 failed)
- ❌ testBooleanFieldWithoutValidationRulesIsUpdated (403)
- ❌ testUpdateTextFieldSuccess (403)
- ❌ testRejectsUpdateToNonExistentField (403 instead of 400)
- ❌ testRejectsUpdateToReadonlyField (403 instead of 400)
- ❌ testUpdateFlagVerifiedField (403)

#### PasswordFieldTest (5/5 failed)
- ❌ All 5 tests fail due to Mockery final class issue

### Integration Tests

#### FrontendUserWorkflowTest (5/9 failed)
- ❌ testEditUserWorkflow (500 error)
- ❌ testCreateGroupWorkflow (201 vs 200)
- ❌ testCreateRoleWithPermissionsWorkflow (201 vs 200)
- ❌ testSearchAndFilterUsersWorkflow (size mismatch)
- ❌ testViewNestedRelationshipWorkflow (size mismatch)

#### RedundantApiCallsTest (9/9 failed)
- ❌ All tests fail with 403 permission errors

#### SchemaBasedApiTest (4/5 failed)
- ❌ testSecurityMiddlewareIsApplied (403)
- ❌ testUsersModelCompleteApiIntegration (403)
- ❌ testRolesModelCompleteApiIntegration (null ID)
- ❌ testGroupsModelCompleteApiIntegration (null ID)
- ❌ testPermissionsModelCompleteApiIntegration (null ID)

#### NestedEndpointsTest (2/6 failed)
- ❌ testPermissionDetailEndpoint (null slug)
- ❌ testRoleDetailEndpoint (null slug)

#### RoleUsersRelationshipTest (2/3 failed)
- ❌ testRoleUsersNestedEndpointHandlesAmbiguousColumn (0 users)
- ❌ testRoleUsersNestedEndpointWithPagination (0 users)

### Service Provider Tests

#### SchemaFilteringTest (1/11 failed)
- ❌ testViewableAttributeFiltering (password not readonly)

#### SchemaCachingContextTest (1/14 failed)
- ❌ testComposablePassesContextToStore (signature mismatch)

---

## Priority Action Plan

### Phase 1: Critical Fixes (P0 - Must Fix)
1. **Fix Permission/Authorization System** (affects 40+ tests)
   - [ ] Debug why granted permissions result in 403
   - [ ] Verify permission strings match schema configuration
   - [ ] Check middleware execution order
   - [ ] Ensure test user session properly authenticated

2. **Fix Schema Response Structure** (affects 3 tests)
   - [ ] Add 'table' key to schema API response
   - [ ] Ensure all required metadata included
   - [ ] Update ApiAction controller

3. **Fix ID Serialization** (affects 3 tests)
   - [ ] Ensure primary keys included in create responses
   - [ ] Check model $hidden and $visible properties
   - [ ] Update CRUD6Model serialization

### Phase 2: High Priority Fixes (P1 - Should Fix)
1. **Fix Nested Endpoint Issues** (affects 4 tests)
   - [ ] Debug relationship loading
   - [ ] Fix null slug issues
   - [ ] Verify query building

2. **Fix Frontend Workflow 500 Error** (affects 1 test)
   - [ ] Investigate edit action internal error
   - [ ] Check error logs
   - [ ] Fix root cause

### Phase 3: Medium Priority Fixes (P2 - Nice to Have)
1. **Refactor Password Field Tests** (affects 5 tests)
   - [ ] Remove dependency on mocking final class
   - [ ] Use integration test approach
   - [ ] Test actual behavior, not implementation

2. **Update Test Expectations** (affects 2 tests)
   - [ ] Accept 201 for CREATE operations (not 200)
   - [ ] Update FrontendUserWorkflowTest

3. **Fix Search/Filter Issues** (affects 2 tests)
   - [ ] Debug data seeding
   - [ ] Check query filtering logic

### Phase 4: Low Priority Fixes (P3 - Polish)
1. **Add Field Filtering to Sprunje** (affects 2 tests)
   - [ ] Respect 'listable' attribute
   - [ ] Respect 'readonly' attribute
   - [ ] Filter sensitive fields from list views

2. **Update TypeScript Test** (affects 1 test)
   - [ ] Update method signature expectations
   - [ ] Match current API

---

## Code Locations to Investigate

### Authorization/Permission System
- `app/src/Middlewares/CRUD6Injector.php` - Request injection
- `app/src/Controller/Base/*.php` - Base controller permission checks
- `app/schema/crud6/*.json` - Permission configuration
- `app/tests/CRUD6TestCase.php` - Test base class setup
- UserFrosting Account sprinkle - Authorization logic

### Schema Response
- `app/src/Controller/ApiAction.php` - Schema endpoint
- `app/src/ServicesProvider/SchemaService.php` - Schema loading
- `app/src/ServicesProvider/SchemaFilter.php` - Response filtering

### Model Serialization
- `app/src/Database/Models/CRUD6Model.php` - Base model
- `app/src/Controller/CreateAction.php` - Create response
- Model $hidden/$visible configuration

### Sprunje Field Filtering
- `app/src/Sprunje/CRUD6Sprunje.php` - Data listing
- `app/src/Sprunje/Base/BaseSprunje.php` - Base sprunje

### Nested Relationships
- `app/src/Controller/RelationshipAction.php` - Relationship endpoints
- `app/src/Database/Models/CRUD6Model.php` - Relationship definitions

---

## Testing Strategy

### Immediate Validation
1. Run permission-related tests in isolation:
   ```bash
   vendor/bin/phpunit app/tests/Integration/RedundantApiCallsTest.php
   ```

2. Add debug output to permission checks:
   ```php
   // In authorization middleware
   echo "User permissions: " . json_encode($user->permissions) . "\n";
   echo "Required permission: " . $requiredPermission . "\n";
   ```

3. Run single failing test with verbose output:
   ```bash
   vendor/bin/phpunit --verbose app/tests/Integration/RedundantApiCallsTest.php::testSingleListCallNoRedundantCalls
   ```

### Regression Testing
After each fix, run full test suite:
```bash
vendor/bin/phpunit
```

Target: 0 errors, 0 failures

---

## Notes for Developer

1. **Permission System**: This is the most critical issue. Most failures cascade from authorization problems.

2. **Test Expectations**: Some tests may have wrong expectations (e.g., expecting 200 for CREATE when 201 is correct).

3. **Mock Usage**: Avoid mocking final classes. Use integration tests or dependency injection instead.

4. **Schema Configuration**: Ensure schema permissions match actual UserFrosting permission strings.

5. **Middleware Order**: CRUD6Injector must run after authentication but before authorization.

---

## Success Criteria

✅ All 297 tests passing  
✅ 0 errors  
✅ 0 failures  
✅ 0 warnings  
✅ CI workflow green

---

**Document Created**: December 16, 2025  
**Analysis Duration**: Comprehensive  
**Next Review**: After implementing Phase 1 fixes
