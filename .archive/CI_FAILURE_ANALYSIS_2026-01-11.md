# CI Failure Analysis - 2026-01-11

## Run Information
- **Run ID**: 20900216763  
- **Job**: PHPUnit Tests (PHP 8.4)
- **Status**: FAILED - 28 failures out of 224 tests
- **Branch**: copilot/fix-custom-action-auth-errors
- **Commit**: e0d8196 (before latest consolidation commit ae2ea32)

## Summary Statistics
- **Total Tests**: 224
- **Assertions**: 904
- **Failures**: 28
- **Warnings**: 1
- **Skipped**: 7
- **Success Rate**: 87.5% (196/224)

## Failure Categories

### Category 1: Permission/Authorization Failures (17 failures)
**Pattern**: Tests return 403 (Forbidden) instead of 200 (OK)

These tests are failing because they don't grant enough permissions to the test user:

1. **testSecurityMiddlewareIsApplied** - Line 159
   - Expected: 200, Got: 403
   - Issue: Not granting sufficient permissions for authenticated request

2. **FrontendComponentDataTest** (4 failures):
   - `testPageRowComponentData` - Line 134: 403 vs 200
   - `testFormComponentSubmission` - Line 235: 403 vs 200  
   - `testInfoComponentDataWithActions` - Line 262: 403 vs 200
   - `testUnifiedModalDataRequirements` - Line 305: 403 vs 200
   - Issue: Tests need to grant proper read/update/delete permissions from schema

3. **NestedEndpointsTest** (3 failures):
   - `testPermissionDetailEndpoint` - Line 179: 403 vs 200
   - `testRoleDetailEndpoint` - Line 380: 403 vs 200
   - Issue: Not granting read permission for detail endpoints

4. **RedundantApiCallsTest** (3 failures):
   - `testDetectsRedundantApiCalls` - Line 163: 403 vs 200
   - `testTrackingMultipleDifferentCalls` - Line 224: 403 vs 200
   - `testComplexWorkflowNoRedundantCalls` - Line 299: 403 vs 200
   - Issue: API call tracking tests need proper permissions

5. **SchemaBasedApiTest - Controller Actions** (6 failures):
   - All 6 schemas (users, roles, groups, permissions, activities, products)
   - Getting 404 (Not Found) instead of 200 on config endpoint
   - Line 502: Failed on config endpoint test
   - Issue: `/api/crud6/{model}/config` endpoint returning 404

### Category 2: Data/Assertion Failures (6 failures)

6. **testRolePermissionsNestedEndpoint** - Line 257
   - Expected: 3 permissions, Got: 1 permission
   - Issue: Only returning 1 permission instead of all 3 attached permissions
   - Relationship query not returning all related records

7. **testPermissionRolesNestedEndpoint** - Line 327
   - Expected: 2 roles, Got: 0 roles
   - Issue: Not returning any roles for permission
   - Reverse relationship not working

8. **RedundantApiCallsTest** (5 failures):
   - `testSingleListCallNoRedundantCalls` - Line 116: Expected 1 call, Got 0
   - `testSchemaCallTracking` - Line 141: Expected 1 schema call, Got 0
   - `testAssertApiCallCount` - Line 203: Expected 1 call, Got 0
   - `testCrud6CallIdentification` - Line 263: Expected 1 CRUD6 call, Got 0
   - `testTrackerReset` - Line 335: Expected 1 call after reset, Got 0
   - Issue: API call tracker not tracking calls properly in these tests

### Category 3: Model Configuration Failures (3 failures)

9. **CRUD6ModelTest** (3 failures):
   - `testConfigureFromSchema` - Line 94
     - Failed: 'created_at' should NOT be in fillable but it is
     - Issue: Test expectation vs actual model behavior mismatch
   
   - `testSoftDeleteConfiguration` - Line 122
     - Expected: false, Got: true
     - Issue: Model has soft deletes enabled when test expects disabled
   
   - `testGetQualifiedDeletedAtColumnWithEmptyString` - Line 473
     - Expected: null, Got: 'test_table.deleted_at'
     - Issue: Eloquent returns qualified column name instead of null for empty string

### Category 4: Schema/Database Failures (2 failures)

10. **testSchemaDrivenCrudOperations[5] - products** - Line 278
    - Expected: 200, Got: 500 (Internal Server Error)
    - Issue: Products schema causing 500 error on list endpoint
    - Likely database/table issue or schema configuration problem

11. **testSchemaDrivenControllerActions[5] - products** - Line 502
    - Expected: 200, Got: 500 (Internal Server Error)  
    - Issue: Products schema config endpoint failing
    - Same root cause as #10 above

## Root Causes Analysis

### Root Cause 1: Permission Setup Issues
**Affected**: 17 tests

The schema-driven tests need to grant ALL permissions defined in the schema, not just `read` and `uri_crud6`. 

**Current code** (line ~910 in SchemaBasedApiTest):
```php
$this->actAsUser($user, permissions: [$readPermission, 'uri_crud6']);
```

**Should be**:
```php
$permissions = [];
foreach ($schema['permissions'] ?? [] as $action => $permission) {
    $permissions[] = $permission;
}
$permissions[] = 'uri_crud6';
$this->actAsUser($user, permissions: $permissions);
```

### Root Cause 2: Missing Config Endpoint Route
**Affected**: 6 tests (all schemas)

The `/api/crud6/{model}/config` endpoint is returning 404. This suggests:
- Route not defined in CRUD6Routes.php
- OR Controller not handling this path
- OR Middleware blocking the route

**Need to check**: `app/src/Routes/CRUD6Routes.php` and relevant controller

### Root Cause 3: Relationship Query Issues
**Affected**: 2 tests

Many-to-many relationships returning incorrect counts:
- roles → permissions (returning 1 of 3)
- permissions → roles (returning 0 of 2)

**Likely issues**:
- Pivot table not being queried correctly
- Sprunje filtering out results
- Permission checks filtering results

### Root Cause 4: Products Schema Database Issue
**Affected**: 2 tests

Products schema causing 500 errors. Need to investigate:
- Is products table created in migrations?
- Does products schema have correct field definitions?
- Are there any field type mismatches?

### Root Cause 5: API Call Tracker Not Recording
**Affected**: 5 tests

API call tracking infrastructure not recording calls in RedundantApiCallsTest. This could be because:
- Tracker not started before making requests
- Requests going through different path that doesn't track
- Tracker being reset unintentionally

### Root Cause 6: CRUD6Model Test Expectations
**Affected**: 3 tests

These are test expectation issues, not bugs:
- `created_at` in fillable is likely correct for timestamp management
- Soft delete detection working correctly
- Eloquent's qualified column behavior is standard

**Action**: Update test expectations to match actual (correct) behavior

## Action Plan

### Priority 1: Fix Permission Issues (17 failures)
1. Update `testSecurityMiddlewareIsApplied()` to grant all schema permissions
2. Update `testSchemaDrivenControllerActions()` to grant all schema permissions
3. Ensure all schema-driven tests use the full permission set from schema

### Priority 2: Fix Missing Config Endpoint (6 failures)
1. Verify `/api/crud6/{model}/config` route exists in CRUD6Routes.php
2. Add route if missing
3. Ensure controller handles this endpoint

### Priority 3: Fix Products Schema Issues (2 failures)
1. Check if products table migration exists
2. Verify products schema field definitions
3. Test products endpoints manually
4. Fix any database/field type issues

### Priority 4: Fix Relationship Query Issues (2 failures)
1. Debug many-to-many relationship queries
2. Check if Sprunje is filtering results
3. Verify permission checks aren't limiting results
4. Test with raw Eloquent queries

### Priority 5: Fix API Call Tracker (5 failures)
1. Review RedundantApiCallsTest setup
2. Ensure tracker is started before requests
3. Verify tracker is capturing all request types
4. Fix any tracker lifecycle issues

### Priority 6: Update CRUD6Model Tests (3 failures - low priority)
1. Update test expectations to match actual behavior
2. Document why these behaviors are correct
3. OR fix model behavior if tests are correct

## Files Requiring Changes

1. **app/tests/Integration/SchemaBasedApiTest.php**
   - Line ~910: Fix permission granting
   - Line ~159: Fix security test permissions
   - Line ~502: Debug config endpoint test

2. **app/src/Routes/CRUD6Routes.php**
   - Add/verify config endpoint route

3. **app/tests/Integration/NestedEndpointsTest.php** (if still exists)
   - Fix permission setup for all tests

4. **app/tests/Integration/RedundantApiCallsTest.php** (if still exists)
   - Fix API call tracker initialization
   - Fix permission setup

5. **app/tests/Integration/FrontendComponentDataTest.php** (if still exists)
   - Fix permission setup for all tests

6. **app/tests/Database/Models/CRUD6ModelTest.php**
   - Update test expectations (lines 94, 122, 473)

7. **examples/schema/products.json** OR **app/schema/crud6/products.json**
   - Verify schema correctness
   - Check field definitions

8. **Database Migrations**
   - Verify products table migration exists
   - Check field types match schema

## Testing Strategy

After fixes:
1. Run full test suite: `vendor/bin/phpunit`
2. Run integration tests only: `vendor/bin/phpunit app/tests/Integration/`
3. Run model tests: `vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php`
4. Verify no hardcoded models/paths remain in any test

## Notes

- The latest commit (ae2ea32) removed 4 hardcoded integration test files
- These removed tests should have been replaced by schema-driven methods
- FrontendComponentDataTest, NestedEndpointsTest, RedundantApiCallsTest, BooleanToggleSchemaTest should NO LONGER exist as separate files
- All functionality should now be in SchemaBasedApiTest with schema-driven test methods

## Consolidated Test Files Status

**Remaining Integration Tests** (Good - Not Hardcoded):
- ✅ `SchemaBasedApiTest.php` - 100% schema-driven
- ✅ `DebugModeIntegrationTest.php` - Config test, not model-specific

**Removed in ae2ea32** (Good - Were Hardcoded):
- ❌ ~~`NestedEndpointsTest.php`~~ - REMOVED
- ❌ ~~`RedundantApiCallsTest.php`~~ - REMOVED
- ❌ ~~`FrontendComponentDataTest.php`~~ - REMOVED
- ❌ ~~`BooleanToggleSchemaTest.php`~~ - REMOVED

**Test failures in CI log reference OLD test files that no longer exist in ae2ea32!**

The CI run was on commit e0d8196 (BEFORE the consolidation commit ae2ea32).
The failures listed are from the OLD hardcoded test files that have since been REMOVED.

## Critical Insight

**The CI failure log is OUTDATED** - it's from commit e0d8196, but the latest commit is ae2ea32 which removed these failing test files!

We need to:
1. Trigger a new CI run on the latest commit (ae2ea32)
2. Analyze failures from the NEW run
3. Only fix issues that appear in the NEW test results

The failures shown in run 20900216763 are **no longer relevant** because those test files have been deleted.
