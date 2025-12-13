# Login Authentication Fix Summary

**Date:** 2025-12-13  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20187139387/job/57959136665  
**PR:** copilot/fix-login-authentication-issue

## Problem

The integration tests were failing during the authentication step with:
```
⚠️  Warning: Could not extract CSRF token from login page
   Checked for both meta tag (<meta name="csrf-token">) and input field patterns
   Attempting login without CSRF token...
❌ Login failed (HTTP 400)
```

## Root Cause

The workflow had two authentication mechanisms:
1. **Playwright login** (`login-admin.js`) - Successfully logs in and saves session to `/tmp/admin-auth-state.json`
2. **PHP curl login** (`test-paths.php`) - Attempts to extract CSRF token from HTML

The issue was that `test-paths.php` was trying to perform its own login using curl, which:
- Cannot execute JavaScript to render Vue.js components
- Cannot extract CSRF tokens from dynamically-rendered content
- Results in HTTP 400 when attempting login without valid CSRF token

Even though the Playwright login succeeded, the PHP test script ignored it and tried to login again using curl.

## Solution

Modified `test-paths.php` to reuse the Playwright session cookies instead of performing its own login:

### 1. Added Cookie Conversion Function

```php
function convertPlaywrightToCurlCookies($playwrightStateFile, $cookieJar) {
    // Reads Playwright storageState JSON format:
    // {
    //   "cookies": [
    //     {"name": "PHPSESSID", "value": "...", "domain": "localhost", ...}
    //   ]
    // }
    
    // Converts to Netscape cookie jar format:
    // localhost  FALSE  /  FALSE  2147483647  PHPSESSID  session_value
}
```

### 2. Modified Authentication Flow

**Before:**
```php
if ($hasAuthTests) {
    performLogin($baseUrl, $username, $password, $cookieJar);  // Always curl login
}
```

**After:**
```php
if ($hasAuthTests) {
    $loginSuccessful = false;
    
    // Try Playwright session first
    if (file_exists($playwrightStateFile)) {
        if (convertPlaywrightToCurlCookies($playwrightStateFile, $cookieJar)) {
            $loginSuccessful = true;  // Use Playwright cookies
        }
    }
    
    // Fallback to curl login if needed
    if (!$loginSuccessful) {
        performLogin($baseUrl, $username, $password, $cookieJar);
    }
}
```

### 3. Updated Workflow

Added Playwright state file path as parameter to PHP test script:

```yaml
php test-paths.php config.json auth api /tmp/admin-auth-state.json
```

## Benefits

1. **Eliminates CSRF token extraction issues** - No longer needs to parse Vue.js-rendered HTML
2. **Reuses authenticated session** - More efficient, logs in only once via Playwright
3. **Backward compatible** - Falls back to curl login if Playwright session unavailable
4. **More reliable** - Playwright handles JavaScript-rendered content properly

## Testing

Validated cookie conversion with test Playwright state file:

```json
{
  "cookies": [
    {"name": "PHPSESSID", "value": "test_session_123", ...},
    {"name": "uf_csrf_token", "value": "test_csrf_456", ...}
  ]
}
```

Converted successfully to Netscape format:
```
# Netscape HTTP Cookie File
localhost	FALSE	/	FALSE	2147483647	PHPSESSID	test_session_123
localhost	FALSE	/	FALSE	1735200000	uf_csrf_token	test_csrf_456
```

## Files Changed

1. `.github/testing-framework/scripts/test-paths.php`
   - Added `convertPlaywrightToCurlCookies()` function
   - Modified authenticated test section to use Playwright cookies
   - Added parameter for Playwright state file path

2. `.github/workflows/integration-test.yml`
   - Updated authenticated API test step to pass Playwright state file
   - Updated authenticated frontend test step to pass Playwright state file

## Expected Outcome

After this fix:
1. Playwright login succeeds and saves session to `/tmp/admin-auth-state.json`
2. PHP test script reads Playwright cookies and converts to curl format
3. Authenticated tests use Playwright session cookies
4. All tests pass without CSRF token extraction errors

## Related Issues

This fix addresses the same underlying issue that affects any curl-based testing of Vue.js applications where CSRF tokens are dynamically rendered.
