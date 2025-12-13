# Unified Testing Approach Fix

**Date**: 2025-12-13  
**Issue**: Integration tests failing after login succeeds, empty userfrosting.log  
**Reference**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20193570066/job/57974326738

## Problem Statement

The integration tests were failing even after login succeeded because:
1. Authentication session was lost between separate test steps (login → test API → test frontend)
2. Debug logging was disabled, making troubleshooting difficult
3. Schema files and locale messages weren't being copied to the UserFrosting installation
4. The 3-step approach (separate login, then API tests, then frontend tests) was unreliable

## Solution

Reverted to the proven unified testing approach that was used successfully in earlier versions:
- Perform login and immediately test authenticated endpoints in a single session
- Maintain browser context throughout all tests to preserve authentication state

## Changes Made

### 1. Enable Debug Logging
**File**: `app/config/default.php`
- Changed `'debug_mode' => false` to `'debug_mode' => true`
- This enables detailed CRUD6 debug logging to userfrosting.log

### 2. New Unified Test Script
**File**: `.github/testing-framework/scripts/test-authenticated-unified.js`

This script performs all authenticated testing in one session:
1. **Login**: Navigate to login page and authenticate
2. **Verify**: Confirm authentication succeeded
3. **Test APIs**: Test all authenticated API endpoints using `page.request.fetch()`
4. **Test Frontend**: Test all authenticated frontend pages using `page.goto()`
5. **Capture Screenshots**: Take screenshots of frontend pages as configured

Key features:
- Uses Playwright's browser context to maintain session throughout
- Uses `page.request.fetch()` for API calls (inherits browser cookies)
- Immediately fails if login doesn't succeed
- Provides detailed console output for each test
- Captures error screenshots for debugging

### 3. Copy Schema Files
**Workflow Step**: "Copy schema files to app/schema/crud6"

Copies JSON schema files from `examples/schema` to `app/schema/crud6` in the UserFrosting installation so they're available to the CRUD6 sprinkle.

### 4. Merge Locale Messages
**Workflow Step**: "Merge locale messages.php files"

Merges translation messages from `examples/locale/en_US/messages.php` into `app/locale/en_US/messages.php` so all CRUD6 model translations are available.

Uses PHP to properly merge arrays:
```php
$source = include 'examples/locale/en_US/messages.php';
$dest = include 'app/locale/en_US/messages.php';
$merged = array_merge_recursive($dest, $source);
```

### 5. Updated Workflow
**File**: `.github/workflows/integration-test.yml`

**Replaced**:
- Separate "Login as admin user" step
- Separate "Test authenticated API paths" step  
- Separate "Test authenticated frontend paths" step

**With**:
- Single "Test authenticated paths (unified approach)" step that runs the unified script

**Benefits**:
- Session remains active throughout all tests
- No risk of session expiration between steps
- Simpler workflow with fewer potential failure points
- Faster execution (only one browser launch)

## Why This Works

### Session Persistence
The unified approach keeps the Playwright browser context alive throughout all tests. When you use `page.request.fetch()` or `page.goto()`, Playwright automatically includes all cookies from the browser context, maintaining the authenticated session.

### Old Approach (Failed)
```
Step 1: Login (create context, save state, close browser)
Step 2: Load state, test APIs (may fail if session expired)
Step 3: Load state, test frontend (may fail if session expired)
```

### New Approach (Working)
```
Step 1: Login (create context, keep it open)
Step 2: Test APIs (same context, session active)
Step 3: Test frontend (same context, session active)
Step 4: Close browser
```

## Reference Implementation

This approach is based on the working implementation from:
`.archive/pre-framework-migration/scripts-backup/take-screenshots-modular.js`

That script successfully performed login and screenshot capture in one session, proving the unified approach is reliable.

## Testing

To test locally:
```bash
# Run the unified test script
node .github/testing-framework/scripts/test-authenticated-unified.js \
  .github/config/integration-test-paths.json \
  http://localhost:8080 \
  admin \
  admin123
```

## Future Improvements

1. **Session Validation**: Add explicit session validation checks between test sections
2. **Retry Logic**: Add retry logic for flaky network requests
3. **Parallel Testing**: Consider running API and frontend tests in parallel with separate browser contexts
4. **Performance Metrics**: Capture timing data for each test phase

## Related Files

- `.github/testing-framework/scripts/test-authenticated-unified.js` - New unified test script
- `.github/workflows/integration-test.yml` - Updated workflow
- `app/config/default.php` - Debug mode configuration
- `.archive/pre-framework-migration/scripts-backup/take-screenshots-modular.js` - Original working implementation
