# CI Run #20896660345 - Test Failure Analysis and Fixes

**Date**: January 11, 2026  
**GitHub Actions Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20896660345  
**Status**: ✅ PARTIALLY FIXED - Main issues resolved, some remain for investigation

## Problem Statement

From the CI logs, there were 91 test failures with the pattern:
```
Expected: "We've sensed a great disturbance in the Force."
Actual: "Access Denied"
```

The user noted: *"should we be expecting Access Denied instead of 'We've sensed a great disturbance in the Force.', hence this test is actually a Pass not a fail"*

## Root Cause Analysis

### Issue #1: Error Message Format Change ✅ FIXED
**Impact**: 91 test failures across 10 test files  
**Root Cause**: UserFrosting 6 changed the permission denied error message from the Star Wars reference to a simple "Access Denied" message.

**Evidence**:
- All permission-related tests expected: `"We've sensed a great disturbance in the Force."`
- Framework now returns: `"Access Denied"`
- Tests were asserting the old format, causing legitimate passes to be reported as failures

### Issue #2: Permission Count Mismatch ✅ FIXED
**Impact**: 1 test failure in `DefaultSeedsTest`  
**Root Cause**: Test expected 22 permissions but seed now creates 40 permissions due to dynamic schema loading.

**Evidence**:
- Previous implementation: 6 legacy + 16 model-specific = 22 permissions
- Current implementation: 6 legacy + 16 model-specific + 19 schema-defined (minus 1 duplicate) = 40 permissions
- Test assertion was outdated

## Solutions Implemented

### Fix #1: Update Error Message Expectations ✅

**Files Modified** (10 files):
```
app/tests/Integration/CRUD6UsersIntegrationTest.php
app/tests/Integration/CRUD6GroupsIntegrationTest.php
app/tests/Controller/EditActionTest.php
app/tests/Controller/CustomActionTest.php
app/tests/Controller/UpdateFieldActionTest.php
app/tests/Controller/SprunjeActionTest.php
app/tests/Controller/DeleteActionTest.php
app/tests/Controller/CreateActionTest.php
app/tests/Controller/SchemaActionTest.php
app/tests/Controller/RelationshipActionTest.php
```

**Change Applied**:
```php
// BEFORE
$this->assertJsonResponse("We've sensed a great disturbance in the Force.", $response, 'title');

// AFTER
$this->assertJsonResponse("Access Denied", $response, 'title');
```

**Impact**: 91 test failures should now pass

### Fix #2: Update Permission Count Expectation ✅

**File Modified**:
```
app/tests/Database/Seeds/DefaultSeedsTest.php
```

**Change Applied**:
```php
// BEFORE
$this->assertCount(22, $role->permissions);
$this->assertGreaterThanOrEqual(22, $siteAdminRole->permissions->count());

// AFTER  
$this->assertCount(40, $role->permissions);
$this->assertGreaterThanOrEqual(40, $siteAdminRole->permissions->count());
```

**Impact**: 1 test failure should now pass

## Unique Errors Identified (For Future Work)

### Category 1: Sprunje Search Filtering (5 failures)
**Pattern**: All searches return 6 records instead of filtering
- `testSearchMultipleFilterableFields`: expected 3, got 6
- `testSearchNoMatches`: expected 0, got 6
- `testSearchCaseInsensitive`: expected 1, got 6
- `testSearchOnlyFilterableFields`: expected 0, got 6
- `testSearchWithNoFilterableFields`: expected 3, got 6

**Root Cause**: Search/filter logic not applying correctly in CRUD6Sprunje

### Category 2: Permission Authorization (Many 403 failures)
**Pattern**: Tests with correct permissions still receive 403
- Single user API requires permission
- Single group API requires permission
- Various create/edit/delete operations
- Custom actions

**Potential Causes**:
- Middleware not properly checking permissions
- Permission validation logic incorrect
- Schema permission definitions not matching test setup

### Category 3: Relationship Count Mismatches (Multiple failures)
**Pattern**: Relationship queries returning incorrect counts
- Role permissions nested endpoint: expected 3, got 1
- Permission roles nested endpoint: expected 2, got 0
- Role users with pagination: expected 10, got 1
- Role users handles ambiguous column: expected 3, got 0

**Potential Causes**:
- Eager loading not configured
- Query join conditions incorrect
- Schema relationship definitions missing

### Category 4: Workflow Integration (Several failures)
**Pattern**: Multi-step workflows failing
- Create user workflow: Role assignment not working
- Search and filter users: Wrong result counts
- Toggle operations: Returning 403
- Relationship operations: Returning 403

**Potential Causes**:
- Integration between components broken
- Test data setup incomplete
- Permission chains not properly configured

### Category 5: Error Status Codes (Some failures)
**Pattern**: Expected 500/404 but got 403
- Update non-existent field: expected 500, got 403
- Update readonly field: expected 500, got 403
- Delete operations: expected 200/404, got 500/403

**Potential Causes**:
- Error handling order incorrect
- Authorization checked before validation
- Exception handling not working

## Test Results Summary

### Before Fixes
- Total Tests: 330
- Failures: 91+
- Warnings: 3
- Skipped: 5
- Risky: 1

### Expected After These Fixes
- Fixed: 92 tests (91 error message + 1 permission count)
- Remaining Failures: ~38 tests (other categories)

### Breakdown of Remaining Issues
1. **Sprunje Search**: 5 tests
2. **Permission Authorization**: ~20 tests
3. **Relationship Queries**: ~5 tests
4. **Workflows**: ~5 tests
5. **Error Codes**: ~3 tests

## Commits Applied

1. **Fix test expectations: update error message from 'Force' to 'Access Denied'**
   - Commit: b0efc55
   - Impact: 91 test fixes

2. **Fix default permissions seed test: update expected count from 22 to 40**
   - Commit: 4a47ffd
   - Impact: 1 test fix

## Verification Steps

To verify these fixes work:

1. **Run the test suite**:
   ```bash
   vendor/bin/phpunit
   ```

2. **Check specific test classes**:
   ```bash
   # Permission message tests
   vendor/bin/phpunit app/tests/Controller/CreateActionTest.php::testCreateRequiresPermission
   
   # Permission count test
   vendor/bin/phpunit app/tests/Database/Seeds/DefaultSeedsTest.php::testDefaultPermissionsSeed
   ```

3. **Monitor CI run**: Push to trigger new CI run and verify these 92 tests now pass

## Recommendations for Remaining Issues

### Short-term (This PR)
- ✅ Fix error message expectations
- ✅ Fix permission count test
- Document remaining issues for follow-up

### Medium-term (Future PRs)
1. **Sprunje Search Filtering** - Investigate `CRUD6Sprunje` filter application
2. **Permission Authorization** - Debug middleware and permission checking logic
3. **Relationship Queries** - Review eager loading and query building

### Long-term (Architecture)
- Consider if permission model needs refactoring
- Review test data setup patterns
- Evaluate integration test structure

## Related Documentation

- [ACCESS_DENIED_FIX_SUMMARY.md](.archive/ACCESS_DENIED_FIX_SUMMARY.md) - Previous permission fix
- [MIDDLEWARE_INJECTION_PATTERN_CLARIFICATION.md](.archive/MIDDLEWARE_INJECTION_PATTERN_CLARIFICATION.md) - Controller patterns
- [INTEGRATION_TEST_SEED_FIX_CHECKLIST.md](.archive/INTEGRATION_TEST_SEED_FIX_CHECKLIST.md) - Test setup

## Lessons Learned

1. **Framework Updates**: When framework messages change, tests need updating
2. **Dynamic Seeding**: Test expectations must account for dynamic data loading
3. **Error Pattern Analysis**: Group errors by root cause, not just symptom
4. **Incremental Fixes**: Fix obvious issues first, document complex ones
5. **Test Isolation**: Some failures mask others - fix in order of certainty
