# CI Run 20281252400 - Test Failure Analysis

**Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20281252400/job/58243527414  
**Date:** 2025-12-16  
**Branch:** main  
**Status:** FAILED  
**Tests:** 297 tests, 905 assertions, 30 errors, 92 failures, 8 warnings, 1 skipped, 1 risky

## Summary of Issues

The test run shows **122 failing tests** with several distinct categories of errors. The excessive logging issue mentioned in the original problem statement appears to have been resolved (no database configuration/seeding logs visible).

---

## Category 1: Permission/Authorization Failures (CRITICAL)

**Count:** ~40+ failures  
**Status Code:** 403 Forbidden

### Error Pattern
```
Failed asserting that 403 is identical to 200.
```

### Affected Tests
- `RedundantApiCallsTest`: All 8 tests failing with 403 errors
- `SchemaBasedApiTest::testSecurityMiddlewareIsApplied`: 403 instead of 200
- `SchemaBasedApiTest::testUsersModelCompleteApiIntegration`: 403 instead of 200  
- `RelationshipActionTest::testAttachRelationshipSuccess`: 403 instead of 200
- `RelationshipActionTest::testDetachRelationshipSuccess`: 403 instead of 200
- `RelationshipActionTest::testAttachMultipleRelationships`: 403 instead of 200
- `RelationshipActionTest::testDetachMultipleRelationships`: 403 instead of 200
- `UpdateFieldActionTest::testBooleanFieldWithoutValidationRulesIsUpdated`: 403 instead of 200
- `UpdateFieldActionTest::testUpdateTextFieldSuccess`: 403 instead of 200
- `UpdateFieldActionTest::testUpdateFlagVerifiedField`: 403 instead of 200

### Root Cause
Tests are getting 403 (Forbidden) responses, indicating:
1. **User authentication/authorization setup issue** in test environment
2. **Permission checks failing** - users don't have required permissions
3. **Test user fixtures missing roles/permissions** 

### Resolution
1. Verify test setup creates authenticated admin users with proper permissions
2. Check that CRUD6 permissions are properly seeded in test database
3. Ensure `ActingAsUser` or equivalent test authentication is working correctly
4. Review WithDatabaseSeeds trait to ensure it creates admin-level test users

---

## Category 2: HTTP Status Code Expectations (MEDIUM)

**Count:** 2 failures

### Error Pattern
```
Failed asserting that 201 is identical to 200.
```

### Affected Tests
- `SchemaBasedApiTest::testRolesModelCompleteApiIntegration`
- `SchemaBasedApiTest::testGroupsModelCompleteApiIntegration`
- `SchemaBasedApiTest::testPermissionsModelCompleteApiIntegration`

### Root Cause
Tests expect `200 OK` for create operations, but API correctly returns `201 Created` (RESTful standard).

### Resolution
**Update test expectations** to accept `201 Created` for POST create operations:
```php
// Change from:
$response->assertStatus(200);

// Change to:
$response->assertStatus(201);  // or assertCreated()
```

---

## Category 3: TypeError - Mock Configuration Issues (HIGH)

**Count:** 7 failures

### Error Pattern
```
TypeError: UserFrosting\Sprinkle\CRUD6\Controller\SprunjeAction::__construct(): 
Argument #7 ($config) must be of type UserFrosting\Config\Config, 
MockObject_UserSprunje_5ee6eb9e given
```

### Affected Tests
- `ListableFieldsTest::testNonListableFieldsNotIncluded`
- `ListableFieldsTest::testDefaultListableFields`
- `ListableFieldsTest::testExplicitlyListableFields`
- `ListableFieldsTest::testReadonlyFieldsNotAutomaticallyListable`
- `ListableFieldsTest::testEmptySchemaReturnsEmptyArray`
- `ListableFieldsTest::testSchemaWithoutFieldsReturnsEmptyArray`

### Root Cause
Test is mocking UserSprunje but passing it as the `$config` parameter (7th argument) to SprunjeAction constructor. The constructor expects `UserFrosting\Config\Config` object, not a mocked Sprunje.

### Resolution
Fix test setup in `ListableFieldsTest.php` line 275:
```php
// Current (incorrect):
$action = new SprunjeAction($schema, $model, $request, $response, $ci, $sprunje, $mockSprunje);

// Should be (correct):
$action = new SprunjeAction($schema, $model, $request, $response, $ci, $sprunje, $config);
```

---

## Category 4: Mockery Final Class Issues (MEDIUM)

**Count:** 4 failures

### Error Pattern
```
Mockery\Exception: The class \UserFrosting\Fortress\Transformer\RequestDataTransformer 
is marked final and its methods cannot be replaced.
```

### Affected Tests
- `PasswordFieldTest::testPasswordFieldHashingInCreateAction`
- `PasswordFieldTest::testEmptyPasswordFieldNotHashedInCreateAction`
- `PasswordFieldTest::testPasswordFieldHashingInEditAction`
- `PasswordFieldTest::testNonPasswordFieldsNotAffected`

### Root Cause
`RequestDataTransformer` is marked `final` in UserFrosting 6, preventing Mockery from mocking it.

### Resolution
Use **partial mocks** instead:
```php
// Instead of:
$transformer = Mockery::mock(RequestDataTransformer::class);

// Use partial mock:
$transformer = Mockery::mock(RequestDataTransformer::class)->makePartial();

// Or use a real instance:
$transformer = new RequestDataTransformer($schema, $config);
```

---

## Category 5: Argument Count Mismatch (MEDIUM)

**Count:** 1 failure

### Error Pattern
```
ArgumentCountError: Too few arguments to function 
UserFrosting\Sprinkle\CRUD6\Controller\UpdateFieldAction::__construct(), 
9 passed ... and exactly 11 expected
```

### Affected Test
- `PasswordFieldTest::testUpdateFieldActionHasHasher`

### Root Cause
`UpdateFieldAction` constructor signature changed (now requires 11 arguments), but test only passes 9.

### Resolution
Update `PasswordFieldTest.php` line 284 to pass all required constructor arguments:
```php
// Check UpdateFieldAction constructor signature:
public function __construct(
    SchemaService $schemaService,
    ClassMapper $classMapper,
    AlertStream $alert,
    Translator $translator,
    RequestDataTransformer $transformer,
    EventDispatcher $eventDispatcher,
    DebugLoggerInterface $logger,
    Config $config,
    Hasher $hasher,              // argument 9
    ValidatorInterface $validator, // argument 10 - MISSING
    AuthorizationInterface $authorizer // argument 11 - MISSING
) { }
```

---

## Category 6: Undefined Method Errors (HIGH)

**Count:** 4 failures

### Error Pattern
```
Error: Call to undefined method 
UserFrosting\Sprinkle\CRUD6\Tests\Integration\NestedEndpointsTest::getName()
```

### Affected Tests
- `NestedEndpointsTest::testNestedEndpointRequiresAuthentication`
- `NestedEndpointsTest::testNestedEndpointRequiresPermission`
- `NestedEndpointsTest::testRolePermissionsNestedEndpoint`
- `NestedEndpointsTest::testPermissionRolesNestedEndpoint`

### Root Cause
Test is calling `$this->getName()` which doesn't exist in PHPUnit 10+. PHPUnit changed from `getName()` to `name()`.

### Resolution
Update `NestedEndpointsTest.php` line 68:
```php
// Change from:
$testName = $this->getName();

// Change to:
$testName = $this->name();
```

---

## Category 7: Assertion Failures - Data Issues (MEDIUM)

**Count:** Several failures

### Error Patterns

#### 7a. Null Values
```
Failed asserting that null matches expected 'test_permission'.
Failed asserting that null matches expected 'test_role'.
```
**Affected:**
- `NestedEndpointsTest::testPermissionDetailEndpoint`
- `NestedEndpointsTest::testRoleDetailEndpoint`

#### 7b. Empty Arrays
```
Failed asserting that actual size 0 matches expected size 3.
Failed asserting that actual size 0 matches expected size 10.
```
**Affected:**
- `RoleUsersRelationshipTest::testRoleUsersNestedEndpointHandlesAmbiguousColumn`
- `RoleUsersRelationshipTest::testRoleUsersNestedEndpointWithPagination`

#### 7c. Schema Missing Keys
```
Failed asserting that an array has the key 'table'.
```
**Affected:**
- `SchemaActionTest::testSchemaReturnsValidSchema`

### Root Cause
1. **Data setup issues**: Tests not properly seeding required data
2. **Permission issues**: 403 errors preventing data retrieval
3. **Schema endpoint**: Not returning full schema structure

### Resolution
1. Fix permission issues first (Category 1)
2. Verify test data seeding in setup methods
3. Check schema endpoint returns complete schema structure

---

## Category 8: Field Visibility Issues (HIGH)

**Count:** 3 failures

### Error Patterns

#### 8a. Password Field Exposed
```
Password should not be in list view
Failed asserting that an array does not have the key 'password'.
```
**Affected:**
- `SprunjeActionTest::testListUsersReturnsOnlyListableFields`

#### 8b. Readonly Field Check Failing
```
password should be readonly
Failed asserting that null is true.
```
**Affected:**
- `SchemaFilteringTest::testViewableAttributeFiltering`

### Root Cause
1. **Password field** is being included in list/sprunje results when it shouldn't be
2. **Field visibility attributes** (`viewable`, `readonly`) not being enforced correctly

### Resolution
1. Update Sprunje to exclude non-viewable fields from results
2. Ensure schema filtering marks `password` fields as `readonly: true` and `viewable: false`
3. Add field visibility checks in data retrieval logic

---

## Category 9: Schema Context Handling (LOW)

**Count:** 2 failures

### Error Patterns

#### 9a. Context Not Passed to Store
```
Composable should pass context to store.loadSchema
Failed asserting that string contains "schemaStore.loadSchema(model, force, context)"
```
**Affected:**
- `SchemaCachingContextTest::testComposablePassesContextToStore`

#### 9b. Non-Static Method Call
```
ReflectionException: Trying to invoke non static method 
UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter::getContextSpecificData() 
without an object
```
**Affected:**
- `SchemaFilteringTest::testTitleFieldIncludedInDetailContext`

### Root Cause
1. **Vue composable**: Not passing `context` parameter in all `loadSchema()` calls
2. **SchemaFilter test**: Trying to call instance method statically

### Resolution
1. Update Vue composable to pass context parameter: `schemaStore.loadSchema(model, force, context, includeRelated)`
2. Fix test to instantiate SchemaFilter before calling method

---

## Category 10: Permission Message Check (LOW)

**Count:** 1 failure

### Error Pattern
```
Failed asserting that null matches expected 'Access Denied'.
```

### Affected Test
- `SchemaActionTest::testSchemaRequiresPermission`

### Root Cause
Permission check returns 403 but response body doesn't contain expected "Access Denied" message.

### Resolution
Verify error response format matches expected structure:
```php
$response->assertStatus(403);
$json = $response->json();
$this->assertEquals('Access Denied', $json['title']); // Check correct key
```

---

## Category 11: Invalid Permission Check Tests (LOW)

**Count:** 2 failures

### Error Patterns
```
Readonly field should be rejected
Failed asserting that an array contains 403.

Non-existent field should be rejected  
Failed asserting that an array contains 403.
```

### Affected Tests
- `UpdateFieldActionTest::testRejectsUpdateToNonExistentField`
- `UpdateFieldActionTest::testRejectsUpdateToReadonlyField`

### Root Cause
Tests expect 403 in response array, but response might be using different status code (422 for validation errors) or different response structure.

### Resolution
Check what status code is actually returned:
```php
// Instead of checking array contains 403:
$this->assertContains(403, [$response->getStatusCode()]);

// Use proper assertion:
$response->assertStatus(422); // or 400 for bad request
```

---

## Priority Order for Fixing

### 🔴 CRITICAL (Must Fix First)
1. **Category 1: Permission/Authorization Issues** (40+ tests)
   - Root cause affects most other failures
   - Fix test user setup and permissions first

### 🟠 HIGH (Fix Next)
2. **Category 3: Mock Configuration** (7 tests)
   - Easy to fix, clear solution
3. **Category 6: Undefined Method** (4 tests)
   - Simple PHPUnit 10 compatibility fix
4. **Category 8: Field Visibility** (3 tests)
   - Security-related, important for API

### 🟡 MEDIUM (Fix After High Priority)
5. **Category 2: HTTP Status Codes** (2 tests)
   - Simple test expectation updates
6. **Category 4: Mockery Final Class** (4 tests)
   - Use partial mocks or real instances
7. **Category 5: Argument Count** (1 test)
   - Update constructor call
8. **Category 7: Data/Assertion Issues** (Several tests)
   - Depends on permission fixes

### 🟢 LOW (Fix Last)
9. **Category 9: Schema Context** (2 tests)
   - Feature-specific, low impact
10. **Category 10 & 11: Minor Test Issues** (3 tests)
    - Edge cases and test refinements

---

## Recommended Action Plan

### Step 1: Fix Authentication/Permissions (Resolves ~40 tests)
```php
// In CRUD6TestCase or test setup
protected function createAuthenticatedAdminUser(): User
{
    $user = User::factory()->create();
    $adminRole = Role::where('slug', 'crud6-admin')->first();
    $user->roles()->attach($adminRole);
    return $user;
}

// In tests:
$admin = $this->createAuthenticatedAdminUser();
$response = $this->actingAs($admin)->getJson('/api/crud6/users');
```

### Step 2: Fix Mock/Constructor Issues (Resolves ~12 tests)
- Update `ListableFieldsTest.php` line 275
- Update `PasswordFieldTest.php` line 284
- Change Mockery mocks to partial mocks

### Step 3: PHPUnit 10 Compatibility (Resolves 4 tests)
- Replace `getName()` with `name()` in `NestedEndpointsTest.php`

### Step 4: Field Visibility (Resolves 3 tests)
- Add `viewable: false` to password fields
- Update Sprunje to filter non-viewable fields

### Step 5: Test Expectations (Resolves ~5 tests)
- Update status code assertions (201 vs 200)
- Fix assertion methods for validation errors

### Step 6: Minor Fixes (Resolves remaining tests)
- Schema context passing
- Method invocation fixes
- Edge case handling

---

## Additional Notes

### Test Environment
- PHP 8.4
- MySQL 8.0
- UserFrosting 6 Beta
- PHPUnit 10+

### Logs Removed Successfully ✅
The excessive database configuration and seeding logs mentioned in the original issue are no longer present in the CI output, confirming the logging cleanup was successful.

---

## Files Requiring Changes

### High Priority
1. `/app/tests/CRUD6TestCase.php` - Add proper admin user creation
2. `/app/src/Testing/WithDatabaseSeeds.php` - Ensure CRUD6 permissions seeded correctly
3. `/app/tests/Controller/ListableFieldsTest.php` - Line 275 (mock configuration)
4. `/app/tests/Integration/NestedEndpointsTest.php` - Line 68 (getName → name)
5. `/app/tests/Controller/PasswordFieldTest.php` - Lines 64, 121, 180, 235, 284
6. `/app/src/Sprunje/CRUD6Sprunje.php` - Add field visibility filtering

### Medium Priority
7. `/app/tests/Integration/SchemaBasedApiTest.php` - Update status code expectations
8. `/app/tests/Controller/RelationshipActionTest.php` - Check auth setup
9. `/app/tests/Controller/UpdateFieldActionTest.php` - Fix assertions
10. `/app/schema/crud6/*.json` - Ensure password fields have `viewable: false`

### Low Priority
11. `/assets/composables/useCRUD6Schema.ts` - Pass context parameter
12. `/app/tests/ServicesProvider/SchemaFilteringTest.php` - Line 655
13. `/app/tests/Controller/SchemaActionTest.php` - Update assertions

---

**Total Test Success Rate After Fixes:** Expected 95%+ (280+ of 297 tests passing)
