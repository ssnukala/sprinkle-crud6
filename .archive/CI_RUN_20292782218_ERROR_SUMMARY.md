# CI Run #20292782218 - Comprehensive Error Summary

**Date**: 2025-12-17T05:33:16Z  
**Branch**: main  
**Commit**: dbefd4a (Merge pull request #332)  
**Workflow**: Unit Tests (PHP 8.4)  
**Status**: ❌ FAILED

## Test Results Overview

| Metric | Count |
|--------|-------|
| **Total Tests** | 292 |
| **Assertions** | 794 |
| **✅ Passed** | 158 |
| **❌ Failures** | 107 |
| **⚠️ Errors** | 17 |
| **⚠️ Warnings** | 9 |
| **🔶 Deprecations** | 1 |
| **⏭️ Skipped** | 1 |
| **⚠️ Risky** | 2 |

## Error Categories (Prioritized by Impact)

### 🔴 CRITICAL: Category 1 - Widespread 500 Internal Server Errors (~90 tests)

**Root Cause**: All CRUD operations returning 500 instead of expected HTTP status codes

**Affected Test Suites**:
- CreateAction (6 failures)
- EditAction (13 failures)
- DeleteAction (6 failures)
- RelationshipAction (8 failures)
- SprunjeAction (7 failures)
- UpdateFieldAction (5 failures)
- CustomAction (2 failures)
- Frontend workflows (11 failures)
- Integration tests (20+ failures)

**Pattern**: Tests expect `200`, `201`, `400`, or `404` but get `500`

**Examples**:
```
❌ Create user success
   Failed asserting that 500 is identical to 200

❌ Edit user success  
   Failed asserting that 500 is identical to 200

❌ Delete user soft delete
   Failed asserting that 500 is identical to 200
```

**Resolution Strategy**:
1. Check error logs in controllers for unhandled exceptions
2. Verify middleware chain execution
3. Validate database connection/migrations
4. Inspect CRUD6Injector middleware
5. Check if schema loading is failing silently

---

### 🟡 HIGH: Category 2 - Permission/Authorization Message Mismatches (~15 tests)

**Issue**: Tests expecting "Access Denied" receiving different error messages

**Sub-categories**:

#### 2A. Generic Error Message (9 tests)
**Expected**: `"Access Denied"`  
**Actual**: `"We've sensed a great disturbance in the Force."`

**Affected Tests**:
- `Toggle flag enabled requires permission`
- `Custom action requires permission`
- `Delete requires permission`
- `Edit (read) requires permission`
- `Update requires permission`
- `Attach relationship requires permission`
- `Detach relationship requires permission`
- `Update field requires permission`

**Root Cause**: Generic error handler catching exceptions and returning Star Wars message instead of specific permission denial

**Location**: Likely in error handler middleware or ForbiddenException handler

#### 2B. Verbose Access Denied Messages (6 tests)
**Expected**: `"Access Denied"`  
**Actual**: `"Access denied for {action} on {model}"` (e.g., "Access denied for create on users")

**Affected Tests**:
- `Create requires permission`
- `List requires permission`

**Root Cause**: Line 174 in `/app/src/Controller/Base.php`:
```php
throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
```

**Resolution**:
```php
// Option 1: Match test expectations exactly
throw new ForbiddenException("Access Denied");

// Option 2: Update all tests to expect verbose messages
$this->assertResponseStatus(403);
$this->assertJsonAlert('Access denied for create on users');
```

---

### 🟡 HIGH: Category 3 - Schema Configuration Issues (8 tests)

#### 3A. Listable Fields Configuration (3 tests)

**Test**: `Base get listable fields only explicit`
```
❌ Created_at field should not be listable by default
   Failed asserting that an array does not contain 'created_at'
```

**Test**: `Sprunje action get listable fields from schema only explicit`
```
❌ Password field should not be listable by default
   Failed asserting that an array does not contain 'password'
```

**Test**: `Readonly fields not automatically listable`
```
❌ Failed asserting that actual size 3 matches expected size 1
```

**Root Cause**: The `getListableFields()` method in Base.php is including fields that should be excluded by default

**Current Logic** (lines 264-304 in Base.php):
- Sensitive field names checked: password, password_hash, secret, token, api_key, api_token
- Timestamp fields (created_at, updated_at, deleted_at) NOT excluded by default

**Required Fix**:
```php
protected function getListableFields(string $modelName): array
{
    $listable = [];
    $fields = $this->getFields($modelName);
    
    // Sensitive field names that should never be listable by default
    $sensitiveFieldNames = ['password', 'password_hash', 'secret', 'token', 'api_key', 'api_token'];
    $sensitiveTypes = ['password'];
    
    // Timestamp fields that should not be listable by default
    $timestampFields = ['created_at', 'updated_at', 'deleted_at'];

    foreach ($fields as $name => $field) {
        // Exclude timestamp fields unless explicitly marked listable
        if (in_array($name, $timestampFields)) {
            if (!isset($field['listable']) || $field['listable'] !== true) {
                continue;
            }
        }
        
        // Exclude readonly fields unless explicitly marked listable
        if (isset($field['readonly']) && $field['readonly'] === true) {
            if (!isset($field['listable']) || $field['listable'] !== true) {
                continue;
            }
        }
        
        // ... rest of logic
    }
    return $listable;
}
```

#### 3B. Viewable Attribute Filtering (1 test)

**Test**: `Viewable attribute filtering`
```
❌ password should be readonly
   Failed asserting that null is true
```

**Issue**: Password field not being marked as readonly in the schema

**Resolution**: Ensure password fields have `readonly: true` in schema or default to readonly

#### 3C. Context-Specific Method Call (1 test)

**Test**: `Title field included in detail context`
```
⚠️ ReflectionException: Trying to invoke non static method 
   UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter::getContextSpecificData() 
   without an object
```

**Root Cause**: Test attempting to call instance method statically

**File**: `app/tests/ServicesProvider/SchemaFilteringTest.php:655`

**Resolution**: Fix test to instantiate SchemaFilter class before calling method

---

### 🟠 MEDIUM: Category 4 - Database Seeding Errors (4 tests)

**Test Suite**: `DefaultSeeds`

**All 4 tests failing with**:
```
⚠️ Error: Call to undefined method 
   UserFrosting\Sprinkle\CRUD6\Tests\Database\Seeds\DefaultSeedsTest::seedAccountData()
```

**Affected Tests**:
1. Default roles seed
2. Default permissions seed  
3. Seed sequence
4. Seed idempotency

**File**: `app/tests/Database/Seeds/DefaultSeedsTest.php:47`

**Root Cause**: Method `seedAccountData()` does not exist in test class

**Resolution**: 
1. Check if method should be inherited from parent class
2. Implement the missing method
3. Or refactor tests to use correct seeding approach from UserFrosting 6

---

### 🟠 MEDIUM: Category 5 - Config/Debug Mode Issues (2 tests)

#### 5A. Config Endpoint Response

**Test**: `Config endpoint returns debug mode`
```
❌ Failed asserting that two arrays are equal
   Expected: []
   Actual: ['debug_mode' => true]
```

**Issue**: Test expects empty array but endpoint returns debug_mode

**Resolution**: Either:
- Update test to expect `['debug_mode' => true]`
- Or remove debug_mode from endpoint response if not needed

#### 5B. DI Container Configuration

**Test**: `Config endpoint returns debug mode when enabled`
```
⚠️ DI\NotFoundException: No entry or class found for 'config'
```

**File**: `app/tests/Controller/ConfigActionTest.php:51`

**Root Cause**: DI container not configured with 'config' service binding

**Resolution**: Register config service in test's DI container setup

---

### 🔵 LOW: Category 6 - Frontend Route Issues (2 tests)

**Test**: `Frontend users list route exists`
```
❌ Frontend route should exist and return 200, 302, or 401, got 404
```

**Test**: `Frontend single user route exists`
```
❌ Frontend route should exist and return 200, 302, or 401, got 404
```

**File**: `app/tests/Controller/CRUD6UsersIntegrationTest.php`

**Root Cause**: Frontend routes not registered or middleware blocking access

**Resolution**: 
- Verify routes are registered in route definitions
- Check if routes require authentication/permissions
- May need to register frontend routes separately from API routes

---

### 🔵 LOW: Category 7 - Test Data/State Issues (3 tests)

#### 7A. Pagination Test

**Test**: `List users pagination works`
```
❌ Pages should return different data
   Failed asserting that null is not equal to null
```

**Root Cause**: First API call returning 500, so both pages return null

**Resolution**: Fix Category 1 (500 errors) first

#### 7B. Schema Call Tracking

**Test**: `Schema call tracking`
```
❌ Should track 1 schema call
   Failed asserting that actual size 0 matches expected size 1
```

**Root Cause**: Schema service not being called or tracker not recording calls

**Resolution**: Check RedundantApiCallsTest tracker implementation

#### 7C. Sorting/Filtering Tests

**Test**: `List users sorting works`
```
⚠️ TypeError: array_column(): Argument #1 ($array) must be of type array, null given
```

**Test**: `List users search works`  
**Test**: `List users filtering works` (risky - no assertions)

**Root Cause**: API returning null/500 instead of array data

**Resolution**: Fix Category 1 (500 errors) first

---

### 🔵 LOW: Category 8 - Integration Test Field Validation (2 tests)

**Test**: `Update nonexistent field returns error`
```
❌ Error should mention the field name
   Failed asserting that 'Oops, looks like our server might have goofed...' contains "nonexistent_field"
```

**Test**: `Update readonly field returns error`
```
❌ Error should mention field is readonly or not editable
   Failed asserting that false is true
```

**Root Cause**: Generic error messages not providing field-specific feedback

**Resolution**: Enhance error handling in UpdateFieldAction to return field-specific errors

---

## Debug Logging Analysis

### ✅ Good News: Proper Debug Logging Pattern

The codebase follows UserFrosting 6 standards:
- Uses `$this->logger->debug()` through protected `debugLog()` method
- Only logs when `crud6.debug_mode` config is enabled
- Structured logging with context arrays

**Files with debug logging**:
1. `app/src/Controller/Base.php` - Base controller debug wrapper
2. `app/src/Controller/RelationshipAction.php` - Relationship debugging  
3. `app/src/ServicesProvider/SchemaTranslator.php`
4. `app/src/ServicesProvider/SchemaFilter.php`
5. `app/src/ServicesProvider/SchemaService.php`
6. `app/src/ServicesProvider/SchemaActionManager.php`
7. `app/src/ServicesProvider/SchemaCache.php`

**✅ NO ISSUES FOUND** - All debug logging is conditional and follows standards

**Action Items**:
- No debug log removal needed
- All debug logs use proper pattern: `$this->debugLog($message, $context)`
- No `error_log()`, `var_dump()`, `print_r()` found

---

## Recommended Resolution Order

### Phase 1: Critical Fixes (Blocks Most Tests)
1. **Investigate 500 Errors** - This is blocking 90+ tests
   - Add error logging to controllers
   - Check middleware execution
   - Validate schema loading
   - Test database connectivity

2. **Fix Permission Messages** - 15 tests
   - Update Base.php line 174 to throw "Access Denied" 
   - OR update all tests to expect verbose messages
   - Find and fix generic "Force" error message handler

### Phase 2: High Priority Fixes
3. **Fix Listable Fields Logic** - 3 tests
   - Exclude timestamp fields by default
   - Exclude readonly fields by default
   - Update getListableFields() in Base.php

4. **Fix Database Seeding** - 4 tests
   - Implement or inherit seedAccountData() method
   - Update DefaultSeedsTest.php

### Phase 3: Medium Priority Fixes
5. **Fix Config Endpoint** - 2 tests
   - Align test expectations with actual response
   - Register config service in DI container

6. **Fix Schema Filtering Test** - 1 test  
   - Fix ReflectionException in SchemaFilteringTest.php

### Phase 4: Low Priority Fixes
7. **Frontend Routes** - 2 tests
8. **Field Validation Messages** - 2 tests
9. **Test Data Issues** - Will auto-fix when Phase 1 complete

---

## Files Requiring Changes

### Must Change
- [ ] `app/src/Controller/Base.php` - Fix validateAccess() message, getListableFields()
- [ ] Find and fix generic error handler (Force message)
- [ ] `app/tests/Database/Seeds/DefaultSeedsTest.php` - Add seedAccountData()
- [ ] `app/tests/ServicesProvider/SchemaFilteringTest.php` - Fix static method call

### Should Change
- [ ] `app/tests/Controller/ConfigActionTest.php` - Fix DI config
- [ ] `app/src/Controller/ConfigAction.php` - Align response format
- [ ] Route definitions for frontend routes

### Will Auto-Fix (Once Phase 1 Complete)
- Test pagination, sorting, filtering, searching
- Many integration tests

---

## Next Steps

1. **Immediate**: Run tests locally with verbose output to capture actual 500 error messages
2. **Investigate**: Add temporary debug logging to CRUD6Injector and Base controller
3. **Fix**: Start with Phase 1 critical fixes
4. **Validate**: Run full test suite after each phase
5. **Document**: Update this file as fixes are applied

---

## Status Tracking

- [x] Analysis complete
- [ ] Phase 1 fixes applied
- [ ] Phase 2 fixes applied
- [ ] Phase 3 fixes applied
- [ ] Phase 4 fixes applied
- [ ] All tests passing
