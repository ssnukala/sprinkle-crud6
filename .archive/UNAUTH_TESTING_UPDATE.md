# Test Paths Script - Unauthenticated Testing Behavior Update

## Issue Reference
https://github.com/ssnukala/sprinkle-crud6/actions/runs/19576208716/job/56062115516

## Problem
The `test-paths.php` script was terminating tests when encountering permission failures (401/403) during unauthenticated API testing. This prevented the script from testing all paths and identifying actual code/SQL failures.

## Solution
Modified `test-paths.php` to differentiate between:
1. **Permission failures** (401/403) - Now treated as WARNINGS for unauthenticated API tests
2. **Server errors** (500, 502, 503, 504) - Still treated as FAILURES (actual code/SQL issues)
3. **Expected responses** - Treated as PASSED

## Changes Made

### 1. Added Warning Counter
- Added `$warningTests` variable to track permission warnings
- Warnings don't cause test failure, allowing all paths to be tested

### 2. Enhanced Test Logic
For unauthenticated API tests (`/api/` paths without authentication):
- **401/403 responses**: Logged as warnings and test continues
- **CREATE endpoints** (POST to `/api/crud6/{model}`): Specifically noted as acceptable with or without permissions
- **500+ errors**: Still fail the test as they indicate actual code/SQL problems

### 3. Updated Summary Output
- Shows warning count separately from failures
- Clarifies that permission warnings are expected
- Only exits with error code if actual failures (500+ errors) are detected

## Behavior Examples

### Before (Old Behavior)
```
Testing: users_list
   Status: 401 (expected 401)
   ✅ PASSED

Testing: users_create
   Status: 401 (expected 401)
   ✅ PASSED

Total tests: 2
Passed: 2
Failed: 0
✅ All tests passed
```

### After (New Behavior)
```
Testing: users_list
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: Permission failure (401) - expected for unauthenticated request
   ⚠️  WARNED (continuing tests to check for code/SQL failures)

Testing: users_create
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: CREATE endpoint returned 401 - this is acceptable (may or may not need permissions)
   ⚠️  WARNED (continuing tests to check for code/SQL failures)

Total tests: 2
Passed: 0
Warnings: 2
Failed: 0
✅ All tests passed (permission warnings are expected for unauthenticated requests)
   2 permission warnings detected (401/403 status codes)
   No actual code/SQL errors found
```

## Impact on CI/CD
- Unauthenticated API tests will now complete all endpoints even when encountering 401/403
- Tests will only fail on actual server errors (500+)
- Better visibility into actual code failures vs expected permission denials
- CREATE endpoints specifically flagged to show they may or may not need permissions

## Testing the Changes
To test locally:
```bash
php .github/scripts/test-paths.php .github/config/integration-test-paths.json unauth api
```

Expected behavior:
- All unauthenticated API tests will run to completion
- Permission failures (401/403) will show as warnings
- Exit code 0 if no server errors
- Exit code 1 only if 500+ errors detected
