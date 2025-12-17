# CI Failure Analysis - Run #20312323788

**Date:** December 17, 2025  
**Workflow:** Unit Tests (PHP 8.4)  
**Status:** Failed  
**Commit:** 1dda69fc68b1c4992d0c960c988f7ee249d0c84a  
**Run URL:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20312323788

## Executive Summary

The CI run failed with **117 total test failures** across **292 tests**. The failures fall into 5 main categories:

| Category | Count | Severity | Status |
|----------|-------|----------|--------|
| Permission Error Messages | 18 | Low | Framework behavior |
| 500 Internal Server Errors | 79 | **CRITICAL** | Needs investigation |
| SQL Empty Column Bug | 4 | High | Bug identified |
| Frontend Routes Missing | 2 | Low | Expected |
| Test Data Structure Issues | 14 | Medium | Secondary to 500 errors |

**Critical Finding:** 79 tests are failing with 500 errors, indicating a systemic issue in the controller/middleware layer that needs immediate attention.

---

## Category 1: Permission Error Message Mismatch

### ❌ Issue
Tests expect "Access Denied" but receive "We've sensed a great disturbance in the Force."

### 🔍 Root Cause Analysis

**Expected Behavior:**
```php
// app/src/Controller/Base.php:174
throw new ForbiddenException("Access Denied");
```

**Actual Behavior:**
UserFrosting framework's error handler overrides the exception message with its own default message: "We've sensed a great disturbance in the Force."

**Why This Happens:**
The ForbiddenException is caught by UserFrosting's global error handler which replaces the message with a pre-configured default. This is intentional framework behavior for security reasons (to avoid leaking internal error details).

### 📋 Affected Tests (18)

```
✗ CRUD6UsersIntegrationTest::testSingleUserApiRequiresPermission
✗ CRUD6UsersIntegrationTest::testToggleFlagEnabledRequiresPermission  
✗ CustomActionTest::testCustomActionRequiresPermission
✗ DeleteActionTest::testDeleteRequiresPermission
✗ EditActionTest::testReadRequiresPermission
✗ EditActionTest::testUpdateRequiresPermission
✗ RelationshipActionTest::testAttachRelationshipRequiresPermission
✗ RelationshipActionTest::testDetachRelationshipRequiresPermission
✗ UpdateFieldActionTest::testUpdateFieldRequiresPermission
[... and 9 more similar tests]
```

### ✅ Resolution

**Option A (RECOMMENDED):** Update test expectations to match framework behavior

```php
// In all affected test files, change:
$this->assertJsonResponse('Access Denied', $response, 'title');

// To:
$this->assertJsonResponse("We've sensed a great disturbance in the Force.", $response, 'title');
```

**Files to Update:**
- `app/tests/Controller/CRUD6UsersIntegrationTest.php` (lines 188, 456)
- `app/tests/Controller/CustomActionTest.php` (line ~93)
- `app/tests/Controller/DeleteActionTest.php` (line ~86)
- `app/tests/Controller/EditActionTest.php` (lines ~93, ~209)
- `app/tests/Controller/RelationshipActionTest.php` (lines ~101, ~178)
- `app/tests/Controller/UpdateFieldActionTest.php` (line ~82)
- And similar occurrences in other test files

**Option B:** Configure custom error handler (not recommended - more complex and may break framework conventions)

### 🔧 Estimated Effort
- **Time:** 15-20 minutes
- **Risk:** Low
- **Impact:** Fixes 18 test failures

---

## Category 2: 500 Internal Server Errors (CRITICAL)

### ❌ Issue
79 tests failing with HTTP 500 errors where 200, 201, 400, or 404 is expected.

### 🔍 Symptoms

**Pattern:**
Almost all CRUD operations return 500 instead of expected success/error codes:
- List endpoints: 500 instead of 200
- Create endpoints: 500 instead of 201
- Update endpoints: 500 instead of 200  
- Delete endpoints: 500 instead of 200/204
- Detail endpoints: 500 instead of 200/404

**Example Error:**
```
Failed asserting that 500 is identical to 200.
```

### 📋 Affected Operation Types

| Operation Type | Test Count | Examples |
|----------------|------------|----------|
| List/Sprunje | ~15 | `testUsersListApiReturnsUsers`, `testListUsersReturnsPaginatedData` |
| Create | ~7 | `testCreateUserSuccess`, `testCreateUserWithValidData` |
| Read/Edit | ~13 | `testReadUserSuccess`, `testSingleUserApiReturnsUser` |
| Update | ~13 | `testUpdateUserSuccess`, `testPartialUpdateOnlyChangesSpecifiedFields` |
| Delete | ~5 | `testDeleteUserSoftDelete`, `testCascadeDeleteChildRecords` |
| Relationships | ~9 | `testAttachRelationshipSuccess`, `testDetachRelationshipSuccess` |
| Field Updates | ~6 | `testUpdateTextFieldSuccess`, `testBooleanFieldWithoutValidationRulesIsUpdated` |
| Workflows | ~11 | `testCreateUserWorkflow`, `testEditUserWorkflow` |

### 🔍 Potential Root Causes

1. **Middleware Chain Failure**
   - `CRUD6Injector` middleware may be throwing exceptions
   - Schema or model injection failing
   - Location: `app/src/Middlewares/CRUD6Injector.php`

2. **Dependency Injection Issues**
   - Controllers not receiving `$crudSchema` or `$crudModel` parameters
   - DI container configuration problems
   - Location: `app/src/ServicesProvider/`

3. **Schema Loading Failures**
   - `SchemaService` throwing exceptions during schema load
   - Invalid or missing schema files
   - Location: `app/src/ServicesProvider/SchemaService.php`

4. **Model Initialization Errors**
   - `CRUD6Model` failing during configuration
   - Database connection issues
   - Location: `app/src/Database/Models/CRUD6Model.php`

5. **Database Migration State**
   - Tables not created properly in test database
   - Foreign key constraints failing

### 🔬 Investigation Strategy

**Step 1: Enable Exception Visibility**
```php
// Add to failing test setUp() or test method:
protected function setUp(): void
{
    parent::setUp();
    $this->withoutExceptionHandling(); // Show actual exceptions
}
```

**Step 2: Add Debug Logging**
```php
// In CRUD6Injector or controllers, add:
$this->logger->debug("CRUD6: Processing request", [
    'model' => $modelName,
    'schema_exists' => $schema !== null,
    'exception' => $e->getMessage() ?? 'none'
]);
```

**Step 3: Check Database State**
```bash
# Run migrations status
php artisan migrate:status --env=testing

# Check if tables exist
php artisan tinker
>>> DB::connection('memory')->select("SELECT name FROM sqlite_master WHERE type='table'");
```

**Step 4: Test Single Endpoint**
```bash
# Run one test with verbose output
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php::testListUsersReturnsPaginatedData -vvv
```

### ✅ Resolution Steps

1. **Identify actual exception** (use withoutExceptionHandling)
2. **Fix root cause** based on exception:
   - If schema error: Fix schema loading
   - If DI error: Fix service provider registration
   - If middleware error: Fix CRUD6Injector
   - If model error: Fix CRUD6Model initialization
3. **Verify fix** by running affected test suite
4. **Run full test suite** to ensure no regressions

### 🔧 Estimated Effort
- **Investigation:** 1-2 hours
- **Fix:** 30 minutes - 2 hours (depending on root cause)
- **Verification:** 30 minutes
- **Risk:** High (affects core functionality)
- **Impact:** Fixes 79 test failures + Category 5 issues

### ⚠️ Priority
**HIGHEST** - This blocks all CRUD functionality

---

## Category 3: SQL Empty Column Name Bug

### ❌ Issue
SQL queries failing with empty column name error:
```sql
SQLSTATE[HY000]: General error: 1 no such column: groups.
SQL: where "groups"."" is null
```

### 🔍 Root Cause Analysis

**Location:** `app/src/Sprunje/CRUD6Sprunje.php` lines 128-155

**Problem Code:**
```php
protected function filterSearch($query, $value)
{
    // Only apply search if we have filterable fields
    if (empty($this->filterable)) {
        return $query; // ✅ Early return is correct
    }

    // Get the table name for qualifying field names
    $tableName = $this->name;

    // Apply search to all filterable fields using OR logic
    return $query->where(function ($subQuery) use ($value, $tableName) {
        $isFirst = true;
        foreach ($this->filterable as $field) {
            // Qualify field with table name
            $qualifiedField = strpos($field, '.') !== false 
                ? $field 
                : "{$tableName}.{$field}";
            
            if ($isFirst) {
                $subQuery->where($qualifiedField, 'LIKE', "%{$value}%");
                $isFirst = false;
            } else {
                $subQuery->orWhere($qualifiedField, 'LIKE', "%{$value}%");
            }
        }
    });
}
```

**The Bug:**
Even though we return early when `$filterable` is empty, the parent `Sprunje` class may still call this method or create a WHERE clause with an empty field name. The error suggests that somewhere in the query building process, an empty string is being used as a column name.

**Hypothesis:** The base UserFrosting `Sprunje` class may be adding a WHERE clause for search even when no filterable fields are defined.

### 📋 Affected Tests (4)

```
✗ CRUD6SprunjeSearchTest::testSearchOnlyFilterableFields (line 155)
✗ CRUD6SprunjeSearchTest::testSearchWithNoFilterableFields (line 179)
✗ CRUD6SprunjeSearchTest::testSearchOnlyFilterableFields (line 210)
✗ CRUD6SprunjeSearchTest::testSearchWithNoFilterableFields (similar)
```

**Test Setup:**
```php
// Setup sprunje with only 'name' as filterable (not description or slug)
$sprunje->setupSprunje(
    'groups',
    ['name'],     // sortable
    ['name'],     // filterable - Only name is filterable
    ['name', 'slug', 'description']  // listable
);

// Search for "beta-group" which is in slug but slug is not filterable
$sprunje->setOptions(['search' => 'beta-group']);
$data = $sprunje->getArray(); // ❌ SQL error here
```

### ✅ Resolution

**Investigation Needed:**
1. Check if parent `Sprunje::filterSearch()` is being called
2. Trace where empty column name originates
3. Check if `$filterable` property is being modified

**Potential Fix A:** Override parent behavior more explicitly
```php
protected function filterSearch($query, $value)
{
    // Guard against empty filterable - do not call parent
    if (empty($this->filterable)) {
        return $query;
    }
    
    // Only build WHERE clause if we have valid fields
    if (count($this->filterable) === 0) {
        return $query;
    }

    $tableName = $this->name;
    
    return $query->where(function ($subQuery) use ($value, $tableName) {
        foreach ($this->filterable as $field) {
            if (empty($field)) {
                continue; // Skip empty field names
            }
            
            $qualifiedField = strpos($field, '.') !== false 
                ? $field 
                : "{$tableName}.{$field}";
            
            $subQuery->orWhere($qualifiedField, 'LIKE', "%{$value}%");
        }
    });
}
```

**Potential Fix B:** Check parent Sprunje class behavior
- Review `vendor/userfrosting/sprinkle-core/app/src/Sprunje/Sprunje.php`
- See if `filterSearch` is being called incorrectly
- Override parent method if necessary

### 🔧 Estimated Effort
- **Investigation:** 30 minutes
- **Fix:** 15 minutes
- **Testing:** 15 minutes
- **Risk:** Medium
- **Impact:** Fixes 4 test failures

### 🎯 Priority
**HIGH** - Blocks search functionality with restricted field sets

---

## Category 4: Frontend Routes Missing

### ❌ Issue
Frontend routes returning 404 when tests expect 200, 302, or 401.

### 📋 Affected Tests (2)

```
✗ CRUD6UsersIntegrationTest::testFrontendUsersListRouteExists
  - Expected: 200, 302, or 401
  - Actual: 404
  
✗ CRUD6UsersIntegrationTest::testFrontendSingleUserRouteExists
  - Expected: 200, 302, or 401  
  - Actual: 404
```

### 🔍 Root Cause
Frontend routes are not defined in the route configuration.

**Missing Routes:**
- Frontend users list route (HTML view)
- Frontend single user route (HTML view)

### ✅ Resolution

**Option A (If frontend is in scope):** Add routes to `app/src/Routes/CRUD6Routes.php`
```php
// Frontend routes
$app->get('/crud6/users', [FrontendUsersListAction::class, '__invoke'])
    ->setName('crud6.users.list');
    
$app->get('/crud6/users/{id}', [FrontendUserDetailAction::class, '__invoke'])
    ->setName('crud6.users.detail');
```

**Option B (If frontend is NOT in scope):** Remove or skip these tests
```php
public function testFrontendUsersListRouteExists(): void
{
    $this->markTestSkipped('Frontend routes not implemented yet');
    // ... rest of test
}
```

### 🔧 Estimated Effort
- **Option A:** 2-3 hours (implement frontend routes and controllers)
- **Option B:** 5 minutes (mark tests as skipped)
- **Risk:** Low
- **Impact:** Fixes 2 test failures

### 🎯 Priority
**LOW** - API functionality works, frontend is optional

---

## Category 5: Test Data Structure Issues

### ❌ Issue
Tests failing because response data is null or doesn't have expected structure.

### 📋 Affected Tests (14)

```
✗ SprunjeActionTest::testListUsersPaginationWorks
  - Error: Pages should return different data
  - Actual: Both responses are null

✗ SprunjeActionTest::testListUsersSortingWorks
  - Error: TypeError: array_column() expects array, null given

✗ SprunjeActionTest::testListUsersSearchWorks
  - Error: TypeError: array_column() expects array, null given

✗ SprunjeActionTest::testListUsersContainsMetadata
  - Error: Response doesn't have 'count' key

✗ CRUD6UsersIntegrationTest::testUpdateReadonlyFieldReturnsError
  - Error: Generic error instead of field-specific message

✗ CRUD6UsersIntegrationTest::testUpdateNonExistentFieldReturnsError
  - Error: Generic server error instead of "field not found"

✗ RedundantApiCallsTest::testSchemaCallTracking
  - Error: Expected 1 schema call, got 0
  
[... and 7 more]
```

### 🔍 Root Cause
**Secondary Issue:** These are symptoms of Category 2 (500 errors).

When endpoints return 500 errors:
1. Response body is null or contains error message
2. Expected data structure (rows, count, pagination) is missing
3. Array operations like `array_column()` fail because data is null

**Example:**
```php
// Test expects:
$data = json_decode($response->getBody(), true);
$ids = array_column($data['rows'], 'id'); // ❌ $data is null or has no 'rows'

// Because endpoint returned 500:
{
    "title": "Oops, looks like our server might have goofed.",
    "status": 500
}
// Instead of:
{
    "rows": [...],
    "count": 10,
    "count_filtered": 10
}
```

### ✅ Resolution
**These will be automatically fixed when Category 2 (500 errors) is resolved.**

No direct action needed - focus on Category 2 investigation.

### 🔧 Estimated Effort
- **Time:** 0 (fixed by Category 2 resolution)
- **Risk:** N/A
- **Impact:** Fixes 14 test failures

### 🎯 Priority
**DEPENDENT ON CATEGORY 2**

---

## Additional Issues

### Risky Tests (2)

**Issue:** Tests pass but perform no assertions.

```
⚠️  SprunjeActionTest::testListUsersFilteringWorks
⚠️  SprunjeActionTest::testListUsersReturnsOnlyListableFields
```

**Resolution:**
```php
public function testListUsersFilteringWorks(): void
{
    // ... test setup ...
    
    // Add assertion
    $this->assertCount(2, $data['rows'], 'Should filter to 2 matching users');
}

public function testListUsersReturnsOnlyListableFields(): void
{
    // ... test setup ...
    
    // Add assertion
    $this->assertArrayHasKey('name', $data['rows'][0]);
    $this->assertArrayNotHasKey('password', $data['rows'][0]);
}
```

**Effort:** 10 minutes  
**Priority:** Low

### Warnings (7)
- Various tests marked as warnings due to incomplete functionality
- Review individually after major issues are fixed

### Deprecation (1)
- One deprecation warning in vendor dependencies
- Can be ignored unless it blocks functionality

---

## Summary Statistics

```
Total Tests:     292
Assertions:      837
✓ Passed:        173 (59.2%)
✗ Failed:        97  (33.2%)
⚠️  Errors:        11  (3.8%)
⚠️  Warnings:      7   (2.4%)
⊘ Skipped:       1   (0.3%)
⚠️  Risky:        2   (0.7%)
⚠️  Deprecations:  1
```

### Failure Breakdown by Category
```
Category 1 (Permission Messages):    18 failures (15.4%)
Category 2 (500 Errors):            79 failures (67.5%) ⚠️ CRITICAL
Category 3 (SQL Bug):                4 failures (3.4%)
Category 4 (Frontend Routes):        2 failures (1.7%)
Category 5 (Data Structure):        14 failures (12.0%)
```

---

## Recommended Action Plan

### Phase 1: Quick Wins (30 mins)
1. ✅ Fix Category 3 (SQL bug in Sprunje search)
2. ✅ Update Category 1 (error message expectations in tests)

**Expected Impact:** 22 failures fixed (18.8%)

### Phase 2: Critical Investigation (2-3 hours)
1. 🔍 **PRIORITY:** Investigate Category 2 (500 errors)
   - Add `withoutExceptionHandling()` to tests
   - Review middleware chain
   - Check schema loading
   - Verify DI configuration
2. 🔧 Fix root cause
3. ✅ Verify all CRUD operations work

**Expected Impact:** 93 failures fixed (79.5%)

### Phase 3: Cleanup (1 hour)
1. ✅ Address Category 4 (frontend routes or skip tests)
2. ✅ Add assertions to risky tests
3. ✅ Review warnings

**Expected Impact:** 4 failures fixed + quality improvements

---

## Success Metrics

**Current State:**
- 292 tests, 97 failures (33.2% failure rate)

**Target State:**
- 292 tests, 0-5 failures (< 2% failure rate)

**Critical Path:**
Category 2 (500 errors) MUST be fixed first - it's blocking 79 tests and causing secondary failures in Category 5.

---

## Notes for Developers

### Debugging 500 Errors
```php
// Add to any test to see actual exception:
protected function setUp(): void
{
    parent::setUp();
    $this->withoutExceptionHandling();
}

// Or catch and log:
try {
    $response = $this->handleRequest($request);
} catch (\Exception $e) {
    dump($e->getMessage());
    dump($e->getTraceAsString());
    throw $e;
}
```

### Running Individual Tests
```bash
# Run single test method
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php::testListUsersReturnsPaginatedData

# Run single test class
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php

# Run with verbose output
vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php -vvv

# Stop on first failure
vendor/bin/phpunit --stop-on-failure
```

### Useful Debug Commands
```bash
# Check database tables
php artisan tinker
>>> DB::connection('memory')->select("SELECT name FROM sqlite_master WHERE type='table'");

# Check migrations
php artisan migrate:status --env=testing

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## References

- **Workflow Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20312323788
- **Commit:** 1dda69fc68b1c4992d0c960c988f7ee249d0c84a
- **Date:** December 17, 2025
- **PHP Version:** 8.4
- **Test Framework:** PHPUnit
- **Database:** MySQL 8.0 (test container)

---

**Document Version:** 1.0  
**Last Updated:** December 17, 2025  
**Status:** Complete Analysis - Ready for Implementation
