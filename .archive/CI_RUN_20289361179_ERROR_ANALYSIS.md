# CI Test Run #20289361179 - Comprehensive Error Analysis

**Workflow URL:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20289361179/job/58270173063

**Date:** December 17, 2025

**Status:** FAILED (292 tests, 104 failures, 13 errors)

---

## Executive Summary

The test run shows widespread 500 Internal Server Error responses across multiple CRUD6 API endpoints. The primary issues are:

1. **500 errors instead of expected responses (200, 400, 404)**
2. **Permission checks returning 500 errors instead of 403 responses**
3. **Field configuration issues (listable, readonly, viewable attributes)**

---

## Error Categories

### 1. Permission/Authentication Errors (Highest Priority)

**Pattern:** Tests expecting "Access Denied" (403) but receiving "We've sensed a great disturbance in the Force" (500)

**Affected Tests:**
- `testUsersListApiRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testSingleUserApiRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testToggleFlagEnabledRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testCustomActionRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testDeleteRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testReadRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testUpdateRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testAttachRelationshipRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testDetachRelationshipRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testUpdateFieldRequiresPermission` - Expected: "Access Denied", Got: "We've sensed a great disturbance in the Force"
- `testSchemaRequiresPermission` - Expected: "Access Denied", Got: "Users" (200 response when should be 403)

**Root Cause:** Controllers are throwing uncaught exceptions instead of properly handling permission denied scenarios.

**Paths Returning 500:**
- `GET /api/crud6/users`
- `GET /api/crud6/users/{id}`
- `PUT /api/crud6/users/{id}/flag_enabled`
- `PUT /api/crud6/users/{id}/flag_verified`
- `DELETE /api/crud6/users/{id}`
- `PUT /api/crud6/users/{id}`
- `POST /api/crud6/users/{id}/relationships/{relationship}`
- `DELETE /api/crud6/users/{id}/relationships/{relationship}`
- `PATCH /api/crud6/users/{id}/field/{field}`
- `GET /api/crud6/users/schema`

**User Context:** Tests create admin users via:
```php
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['uri_crud6']);
```

The admin user is created dynamically for each test using UserFrosting's factory pattern.

---

### 2. CRUD Operation 500 Errors (Critical)

**Pattern:** Expected 200/201 responses, receiving 500 errors

**Affected Operations:**

#### ApiAction (List/Detail)
- `testUsersListApiReturnsUsers` - Expected 200, got 500 at `/api/crud6/users`
- `testSingleUserApiReturnsUser` - Expected 200, got 500 at `/api/crud6/users/{id}`
- `testSingleUserApiReturns404ForNonExistent` - Expected 404, got 500 at `/api/crud6/users/9999`

#### CreateAction
- `testCreateUserSuccess` - Expected 200/201, got 500 at `POST /api/crud6/users`
- `testCreateUserWithValidationErrors` - Expected 400, got 500
- `testCreateUserWithDuplicateUsername` - Expected 400, got 500
- `testCreateUserHashesPassword` - Expected 200/201, got 500
- `testCreateUserAppliesDefaultValues` - Expected 200/201, got 500
- `testCreateUserWithPivotDataTimestamps` - Expected 200/201, got 500

#### EditAction (Read/Update)
- `testReadUserSuccess` - Expected 200, got 500 at `GET /api/crud6/users/{id}`
- `testUpdateUserSuccess` - Expected 200, got 500 at `PUT /api/crud6/users/{id}`
- `testPartialUpdateOnlyChangesSpecifiedFields` - Expected 200, got 500
- `testUpdateUserWithValidationErrors` - Expected 400, got 500
- `testUpdateUserWithDuplicateUsernameRejected` - Expected 400, got 500
- `testUpdatePasswordIsHashed` - Expected 200, got 500
- `testUpdateNonExistentUserReturns404` - Expected 404, got 500
- `testEmptyUpdateRequestSucceeds` - Expected 200, got 500
- `testBooleanFieldsCanBeUpdated` - Expected 200, got 500

#### DeleteAction
- `testDeleteUserSoftDelete` - Expected 200, got 500 at `DELETE /api/crud6/users/{id}`
- `testDeleteNonExistentUserReturns404` - Expected 404, got 500
- `testDeleteAlreadyDeletedUserReturns404` - Expected 404, got 500
- `testCascadeDeleteChildRecords` - Expected 200, got 500

#### RelationshipAction
- `testAttachRelationshipSuccess` - Expected 200, got 500 at `POST /api/crud6/users/{id}/relationships/roles`
- `testDetachRelationshipSuccess` - Expected 200, got 500 at `DELETE /api/crud6/users/{id}/relationships/roles`
- `testAttachMultipleRelationships` - Expected 200, got 500
- `testDetachMultipleRelationships` - Expected 200, got 500
- `testAttachToNonExistentUserReturns404` - Expected 404, got 500
- `testDetachFromNonExistentUserReturns404` - Expected 404, got 500

#### UpdateFieldAction
- `testBooleanFieldWithoutValidationRulesIsUpdated` - Expected 200, got 500 at `PATCH /api/crud6/users/{id}/field/flag_enabled`
- `testUpdateTextField Success` - Expected 200, got 500
- `testUpdateFieldNonExistentUserReturns404` - Expected 404, got 500
- `testUpdateFlagVerifiedField` - Expected 200, got 500

#### SprunjeAction
- `testListUsersReturnsPaginatedData` - Expected 200, got 500 at `GET /api/crud6/users/list`
- `testListUsersReturnsEmptyForNoMatches` - Expected 200, got 500

---

### 3. Field Configuration Issues

#### Listable Fields
**Test:** `testBaseGetListableFieldsOnlyExplicit`
- **Issue:** `created_at` field is listable when it shouldn't be by default
- **Expected:** Only explicitly listable fields should be returned
- **Actual:** Timestamp fields are automatically included

**Test:** `testSprunjeActionGetListableFieldsFromSchemaOnlyExplicit`
- **Issue:** `password` field is listable when it should never be
- **Expected:** Sensitive fields should not be listable
- **Actual:** Password field is included in listable fields

**Test:** `testReadonlyFieldsNotAutomaticallyListable`
- **Issue:** Expected 1 listable field, got 3
- **Expected:** Readonly fields should not be automatically listable
- **Actual:** Multiple readonly fields are being marked as listable

#### Viewable/Readonly Attributes
**Test:** `testViewableAttributeFiltering`
- **Issue:** `password` should be readonly
- **Expected:** Password field marked as readonly
- **Actual:** `readonly` attribute is null

---

### 4. Integration/Workflow Errors

All workflow tests in `FrontendUserWorkflowTest` are failing with 500 errors:

- `testCreateUserWorkflow` - Expected 200, got 500
- `testEditUserWorkflow` - Expected 200, got 500
- `testToggleUserEnabledWorkflow` - Expected 200, got 500
- `testAssignRolesToUserWorkflow` - Expected 200, got 500
- `testRemoveRoleFromUserWorkflow` - Expected 200, got 500
- `testDeleteUserWorkflow` - Expected 200, got 500
- `testCreateGroupWorkflow` - Expected 201, got 500
- `testCreateRoleWithPermissionsWorkflow` - Expected 201, got 500
- `testSearchAndFilterUsersWorkflow` - Expected 200, got 500
- `testViewNestedRelationshipWorkflow` - Expected 200, got 500

All tests in `SchemaBasedApiTest` are failing:
- `testUsersModelCompleteApiIntegration` - Expected 200, got 500
- `testRolesModelCompleteApiIntegration` - Expected 200, got 500
- `testGroupsModelCompleteApiIntegration` - Expected 200, got 500
- `testPermissionsModelCompleteApiIntegration` - Expected 200, got 500

---

### 5. Nested Endpoint Errors

All nested endpoint tests are failing with 500 errors:
- `testNestedEndpointRequiresPermission` - Expected 403, got 500
- `testPermissionDetailEndpoint` - Expected 200, got 500
- `testRolePermissionsNestedEndpoint` - Expected 200, got 500
- `testPermissionRolesNestedEndpoint` - Expected 200, got 500
- `testRoleDetailEndpoint` - Expected 200, got 500

---

### 6. Redundant API Call Detection Issues

All redundant API call tests are failing:
- `testSingleListCallNoRedundantCalls` - Expected 200, got 500
- `testSchemaCallTracking` - Should track 1 schema call, tracked 0
- `testDetectsRedundantApiCalls` - Expected 200, got 500
- `testAssertApiCallCount` - Expected 200, got 500
- `testTrackingMultipleDifferentCalls` - Expected 200, got 500
- `testCRUD6CallIdentification` - Expected 200, got 500
- `testComplexWorkflowNoRedundantCalls` - Expected 200, got 500
- `testTrackerReset` - Expected 200, got 500
- `testRedundantCallsReport` - Expected 200, got 500

---

### 7. Other Specific Errors

#### ConfigAction
**Test:** `testConfigEndpointReturnsDebugMode`
- **Expected:** Empty array response
- **Actual:** Array contains `['debug_mode' => true]`
- **Issue:** Debug mode is being included when it shouldn't be

**Test:** `testConfigEndpointReturnsDebugModeWhenEnabled`
- **Error:** `DI\NotFoundException: No entry or class found for 'config'`
- **Issue:** DI container not properly configured for config service

#### DefaultSeeds
**Test:** `testDefaultPermissionsSeed`
- **Expected:** 6 permissions
- **Actual:** 22 permissions
- **Issue:** More permissions are being seeded than expected

#### SchemaAction
**Test:** `testSchemaRequiresPermission`
- **Expected:** "Access Denied" (403)
- **Actual:** "Users" (200 response)
- **Issue:** Schema endpoint is public when it should require permission

#### SchemaBasedApi
**Test:** `testSecurityMiddlewareIsApplied`
- **Expected:** 200
- **Actual:** 403
- **Issue:** Security middleware is preventing access when user has permission

---

## Debug Output Issues

The following tests contain excessive debug output that should be removed when successful:

- API call tracking summaries in `CRUD6UsersIntegrationTest`
- Debug logging in various integration tests
- Connection verification output in test setup

These debug messages make it difficult to identify actual failures and should only be shown when tests fail.

---

## Root Cause Analysis

### Primary Issue: Uncaught Exceptions in Controllers

The widespread 500 errors suggest that controllers are throwing uncaught exceptions rather than handling errors gracefully. Common causes:

1. **Missing try-catch blocks** in controller action methods
2. **Improper error middleware** configuration
3. **Missing model/schema validation** before operations
4. **Incorrect dependency injection** causing null references
5. **Permission checking failures** not being caught

### Secondary Issue: Schema Configuration

Field visibility and readonly attributes are not being properly configured or filtered:

1. **Default field visibility** settings are too permissive
2. **Sensitive fields** (password, timestamps) not properly excluded
3. **Readonly attributes** not being set correctly in schema

### Tertiary Issue: Permission Middleware

Permission checking is not working correctly:

1. **AuthorizationException** not being caught and converted to 403
2. **Permission checks** failing silently and causing 500 errors
3. **Route middleware** not properly configured

---

## Recommended Fixes (Priority Order)

### 1. Add Global Exception Handler (Highest Priority)
Add try-catch blocks in all controller actions or implement a global exception handler to:
- Catch authorization exceptions → return 403
- Catch validation exceptions → return 400
- Catch not found exceptions → return 404
- Catch all other exceptions → return 500 with proper error message

### 2. Fix Permission Checking
- Ensure `AuthorizationException` is properly caught
- Add permission checks at the start of each controller action
- Return proper 403 responses for permission denied

### 3. Fix Field Configuration
- Set password field as `readonly: true` and `listable: false`
- Set timestamp fields (`created_at`, `updated_at`) as `listable: false` by default
- Implement proper field filtering based on context (list, form, detail)

### 4. Add Null Checks
- Verify model exists before operations
- Check schema is loaded before use
- Validate request parameters

### 5. Remove Debug Output
- Remove or conditionally display API call tracking summaries
- Remove debug logging from successful test paths
- Only show debug output on test failures

### 6. Fix Schema Endpoint Permissions
- Add proper permission checking to schema endpoint
- Should require `uri_crud6` permission

---

## Next Steps

1. **Examine controller error handling** - Review all controller action methods for proper exception handling
2. **Review middleware configuration** - Ensure error handling middleware is properly configured
3. **Add logging** - Add detailed logging to identify where exceptions are being thrown
4. **Fix one controller at a time** - Start with ApiAction, then CreateAction, etc.
5. **Re-run tests incrementally** - Test each fix before moving to the next

---

## Test Statistics

- **Total Tests:** 292
- **Assertions:** 820  
- **Errors:** 13
- **Failures:** 104
- **Warnings:** 9
- **Skipped:** 1
- **Risky:** 2

---

## Additional Context

### User Authentication in Tests
Tests create admin users using:
```php
$user = User::factory()->create();
$this->actAsUser($user, permissions: ['uri_crud6']);
```

The user is created dynamically with the specified permissions. The tests use UserFrosting's `WithTestUser` trait from `sprinkle-account`.

### Paths Being Tested
All CRUD6 API paths follow the pattern:
- List: `GET /api/crud6/{model}`
- Detail: `GET /api/crud6/{model}/{id}`
- Create: `POST /api/crud6/{model}`
- Update: `PUT /api/crud6/{model}/{id}`
- Delete: `DELETE /api/crud6/{model}/{id}`
- Field Update: `PATCH /api/crud6/{model}/{id}/field/{field}`
- Relationships: `POST/DELETE /api/crud6/{model}/{id}/relationships/{relationship}`
- Schema: `GET /api/crud6/{model}/schema`

All these paths are returning 500 errors instead of expected responses.
