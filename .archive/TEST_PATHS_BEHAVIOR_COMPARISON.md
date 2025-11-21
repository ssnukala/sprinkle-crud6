# Test-Paths.php Behavior Comparison

## Overview
This document shows the exact behavior changes in test-paths.php for handling unauthenticated API testing.

## Test Scenario: Unauthenticated API Endpoint Tests

### Configuration Example
```json
{
  "paths": {
    "unauthenticated": {
      "api": {
        "users_list": {
          "method": "GET",
          "path": "/api/crud6/users",
          "expected_status": 401
        },
        "users_create": {
          "method": "POST",
          "path": "/api/crud6/users",
          "expected_status": 401
        },
        "users_delete": {
          "method": "DELETE",
          "path": "/api/crud6/users/1",
          "expected_status": 401
        }
      }
    }
  }
}
```

## Old Behavior (Before Fix)

### Test Execution
```
=========================================
Testing Unauthenticated API Paths
=========================================

Testing: users_list
   Description: Attempt to access users list without authentication
   Method: GET
   Path: /api/crud6/users
   ✅ Status: 401 (expected 401)
   ✅ PASSED

Testing: users_create
   Description: Attempt to create user without authentication
   Method: POST
   Path: /api/crud6/users
   ✅ Status: 401 (expected 401)
   ✅ PASSED

Testing: users_delete
   Description: Attempt to delete user without authentication
   Method: DELETE
   Path: /api/crud6/users/1
   ✅ Status: 401 (expected 401)
   ✅ PASSED

=========================================
Test Summary
=========================================
Total tests: 3
Passed: 3
Failed: 0
Skipped: 0

✅ All tests passed
```

### Issues with Old Behavior
1. ❌ Tests showed as "PASSED" even though they got permission failures
2. ❌ Didn't distinguish between expected permission failures and actual errors
3. ❌ Could miss actual 500 errors if they occurred
4. ❌ No special handling for CREATE endpoints

## New Behavior (After Fix)

### Test Execution
```
=========================================
Testing Unauthenticated API Paths
=========================================

Testing: users_list
   Description: Attempt to access users list without authentication
   Method: GET
   Path: /api/crud6/users
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: Permission failure (401) - expected for unauthenticated request
   ⚠️  WARNED (continuing tests to check for code/SQL failures)

Testing: users_create
   Description: Attempt to create user without authentication
   Method: POST
   Path: /api/crud6/users
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: CREATE endpoint returned 401 - this is acceptable (may or may not need permissions)
   ⚠️  WARNED (continuing tests to check for code/SQL failures)

Testing: users_delete
   Description: Attempt to delete user without authentication
   Method: DELETE
   Path: /api/crud6/users/1
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: Permission failure (401) - expected for unauthenticated request
   ⚠️  WARNED (continuing tests to check for code/SQL failures)

=========================================
Test Summary
=========================================
Total tests: 3
Passed: 0
Warnings: 3
Failed: 0
Skipped: 0

✅ All tests passed (permission warnings are expected for unauthenticated requests)
   3 permission warnings detected (401/403 status codes)
   No actual code/SQL errors found
```

### Benefits of New Behavior
1. ✅ Clearly shows permission failures as WARNINGS
2. ✅ CREATE endpoints specially noted (may or may not need permissions)
3. ✅ Tests continue even with permission failures
4. ✅ Exits with code 0 (success) when only permission warnings
5. ✅ Would exit with code 1 only if 500+ errors detected

## Server Error Scenario

### When a 500 Error Occurs
```
Testing: users_custom_action
   Description: Execute custom action on user
   Method: POST
   Path: /api/crud6/users/1/a/broken_action
   ❌ Status: 500 (expected 401)
   ❌ FAILED: Server error detected - possible code/SQL failure
   ❌ Error: Call to undefined function now()

=========================================
Test Summary
=========================================
Total tests: 4
Passed: 0
Warnings: 3
Failed: 1
Skipped: 0

❌ Some tests failed (actual code/SQL errors detected)
   Note: Permission failures (401/403) are warnings, not failures
```

Exit Code: **1** (failure due to actual error)

## Logic Flow

```
┌─────────────────────────────────────┐
│   Execute HTTP Request              │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Get HTTP Status Code              │
└──────────────┬──────────────────────┘
               │
               ▼
       ┌───────┴───────┐
       │ Status ==     │
       │ Expected?     │
       └───┬───────┬───┘
           │       │
          YES     NO
           │       │
           ▼       ▼
       ┌───────┐  ┌─────────────────────────┐
       │PASSED │  │Is Unauth API Test?      │
       └───────┘  └──────┬──────────────────┘
                         │
                    ┌────┴────┐
                   YES       NO
                    │         │
                    ▼         ▼
          ┌──────────────┐  ┌────────┐
          │401/403?      │  │FAILED  │
          └───┬──────┬───┘  └────────┘
              │      │
             YES    NO
              │      │
              ▼      ▼
        ┌─────────┐ ┌──────────────┐
        │WARNING  │ │500+?         │
        │(continue│ └───┬──────┬───┘
        │testing) │     │      │
        └─────────┘    YES    NO
                        │      │
                        ▼      ▼
                   ┌─────────┐ ┌────────┐
                   │FAILED   │ │FAILED  │
                   │(actual  │ └────────┘
                   │error)   │
                   └─────────┘
```

## Exit Code Decision Tree

```
┌─────────────────────────────────────┐
│   All Tests Complete                │
└──────────────┬──────────────────────┘
               │
               ▼
       ┌───────┴───────┐
       │Failed > 0?    │
       └───┬───────┬───┘
           │       │
          YES     NO
           │       │
           ▼       ▼
       ┌───────┐  ┌─────────────────┐
       │EXIT 1 │  │Warnings > 0?    │
       │(error)│  └──────┬──────┬───┘
       └───────┘         │      │
                        YES    NO
                         │      │
                         ▼      ▼
                    ┌────────┐ ┌────────┐
                    │EXIT 0  │ │EXIT 0  │
                    │(warn)  │ │(pass)  │
                    └────────┘ └────────┘
```

## Key Points

### Permission Failures (401/403)
- **Old**: Counted as PASSED if expected status was 401/403
- **New**: Counted as WARNING for unauthenticated API tests
- **Exit Code**: 0 (success)

### CREATE Endpoints
- **Old**: No special handling
- **New**: Special warning noting they may or may not need permissions
- **Exit Code**: 0 (success)

### Server Errors (500+)
- **Old**: Counted as FAILED
- **New**: Still counted as FAILED (actual code/SQL error)
- **Exit Code**: 1 (failure)

### Test Continuation
- **Old**: Would stop on first failure
- **New**: Continues testing all endpoints to find all issues

## Impact on CI/CD

### Before
- Test run would show all permission failures as "passed"
- Hard to distinguish between expected permission denials and actual bugs
- If a 500 error occurred, it would be buried among "passed" tests

### After
- Clear separation between warnings (expected) and failures (actual errors)
- CREATE endpoints specifically noted
- All endpoints tested even when encountering permission failures
- Easy to see at a glance if there are actual code/SQL errors
- Exit code properly reflects actual test status (0 = warnings only, 1 = actual failures)
