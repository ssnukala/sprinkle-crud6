# Authentication Fix Summary

**Date:** 2025-12-12  
**Issue:** Integration tests failing with 401 errors for authenticated paths  
**GitHub Actions Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20177155306/job/57928086856

## Problem Statement

The integration test workflow was failing because the `test-paths.php` script was not properly authenticating users before testing authenticated API and frontend paths. All authenticated requests were returning 401 (Unauthorized) instead of the expected 200 (OK) status.

### Root Cause

The `test-paths.php` script (lines 88-96 in the original code) had a placeholder comment indicating authentication was needed, but it only added the `-L` (follow redirects) flag to curl commands without establishing an authenticated session:

```php
// Add authentication if needed
if ($isAuth && $username && $password) {
    // For authenticated requests, we need to first get a session cookie
    // This is simplified - in a real scenario, you'd need to login first
    $curlCmd .= "-L "; // Follow redirects
}
```

The `take-screenshots-modular.js` script had the correct authentication logic using Playwright (lines 82-106), which successfully logged in and maintained session state across requests.

## Solution Implemented

### 1. Added `performLogin()` Function (Lines 61-160)

Created a new function that:
- **Fetches login page** to get initial session cookies
- **Extracts CSRF token** from the HTML form using multiple regex patterns
- **Submits login credentials** with CSRF token via POST request
- **Uses curl cookie jar** (`-b` and `-c` flags) to maintain session
- **Validates login success** by checking response content for logged-in indicators

```php
function performLogin($baseUrl, $username, $password, $cookieJar) {
    // Step 1: Get login page and CSRF token
    $curlCmd = "curl -s -o {$tmpFile} -c {$cookieJar} -L '{$loginPageUrl}' 2>&1";
    
    // Extract CSRF token with multiple fallback patterns
    if (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/', ...)) {
        $csrfToken = $matches[1];
    }
    
    // Step 2: Submit login form with CSRF token
    $postData = [
        'user_name' => $username,
        'password' => $password,
        'csrf_token' => $csrfToken,
    ];
    
    $curlCmd = "curl -s -o {$tmpFile} -w '%{http_code}' -b {$cookieJar} -c {$cookieJar} -L " .
               "-X POST -H 'Content-Type: application/x-www-form-urlencoded' " .
               "--data '{$postDataString}' '{$loginUrl}' 2>&1";
    
    // Validate login success
    return ($hasLoggedInIndicators && !$isStillOnLoginPage);
}
```

### 2. Updated `testPath()` Function (Lines 162-310)

Modified to support authenticated sessions:
- Added `$cookieJar` parameter
- Uses cookie jar for authenticated requests
- Supports JSON payloads for POST/PUT/PATCH requests
- Maintains authentication state across multiple test requests

```php
function testPath($name, $pathConfig, $baseUrl, $isAuth = false, $username = null, $password = null, $cookieJar = null) {
    // Add authentication cookie jar if needed
    if ($isAuth && $cookieJar && file_exists($cookieJar)) {
        // Use cookie jar for authenticated requests
        $curlCmd .= "-b {$cookieJar} -c {$cookieJar} ";
    }
    
    // Add payload for POST/PUT/PATCH requests
    if (isset($pathConfig['payload']) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $payload = json_encode($pathConfig['payload']);
        $curlCmd .= "-H 'Content-Type: application/json' --data '{$payload}' ";
    }
}
```

### 3. Updated Test Flow (Lines 312-360)

Modified the authenticated test execution to:
- Call `performLogin()` before running any authenticated tests
- Pass the cookie jar to all authenticated test calls
- Skip authenticated tests if login fails
- Support testing both authenticated and unauthenticated paths in a single run

```php
if ($hasAuthTests) {
    // Perform login before testing authenticated paths
    if (!performLogin($baseUrl, $username, $password, $cookieJar)) {
        echo "❌ Authentication failed - skipping authenticated tests\n\n";
        // Count skipped tests
    } else {
        // Login successful, proceed with authenticated tests
        foreach ($authPaths['api'] as $name => $pathConfig) {
            testPath($name, $pathConfig, $baseUrl, true, $username, $password, $cookieJar);
        }
    }
}
```

### 4. Added Cleanup (Lines 389-391)

Ensures the cookie jar temporary file is removed after tests complete:

```php
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}
```

## Testing Approach

The solution enables comprehensive testing of both access control and functionality:

### Unauthenticated Tests
- Tests paths without authentication
- Expects 401/403 status codes
- Validates that protected resources are properly secured
- Warnings (not failures) for expected permission denials

### Authenticated Tests
1. **Login Phase:**
   - Fetches login page
   - Extracts CSRF token
   - Submits credentials
   - Establishes authenticated session

2. **Test Phase:**
   - Uses session cookies for all requests
   - Expects 200 status codes
   - Validates proper functionality for authorized users
   - Tests both API and frontend paths

## Expected Results

### Before Fix
```
Testing: users_schema
   Description: Get users schema definition
   Method: GET
   Path: /api/crud6/users/schema
   ❌ Status: 401 (expected 200)
   ❌ FAILED
```

### After Fix

**Unauthenticated Test:**
```
Testing: users_schema
   Description: Attempt to access users schema without authentication
   Method: GET
   Path: /api/crud6/users/schema
   ⚠️  Status: 401 (expected 401)
   ⚠️  WARNING: Permission failure (401) - expected for unauthenticated request
   ⚠️  WARNED (continuing tests to check for code/SQL failures)
```

**Authenticated Test:**
```
=========================================
Authenticating User
=========================================
Username: admin
Login URL: http://localhost:8080/account/sign-in

✅ CSRF token obtained
✅ Login successful (HTTP 200)
   Session established

Testing: users_schema
   Description: Get users schema definition
   Method: GET
   Path: /api/crud6/users/schema
   ✅ Status: 200 (expected 200)
   ✅ Validation: JSON contains expected keys
   ✅ PASSED
```

## Integration with Workflow

The workflow now properly tests both scenarios:

1. **Step: Test API and frontend paths (unauthenticated)**
   - Validates access control is working
   - Expects 401/403 for protected resources

2. **Step: Test API and frontend paths (authenticated)**
   - Logs in as admin user
   - Validates full functionality
   - Expects 200 for all tests

## Files Modified

- `.github/testing-framework/scripts/test-paths.php` - Added authentication support (165 lines added, 24 lines modified)

## Related References

- **Original authenticated screenshot script:** `.archive/pre-framework-migration/scripts-backup/take-authenticated-screenshots.js` (lines 30-62)
- **Working Playwright authentication:** `.github/testing-framework/scripts/take-screenshots-modular.js` (lines 82-106)
- **Integration test paths config:** `.github/config/integration-test-paths.json`
- **Workflow definition:** `.github/workflows/integration-test.yml` (step at line 375)

## Benefits

1. **Proper Authentication:** Session-based authentication using UserFrosting 6 login flow
2. **CSRF Protection:** Extracts and uses CSRF tokens correctly
3. **Dual Testing:** Tests both authenticated and unauthenticated scenarios
4. **Better Validation:** Confirms both access control and functionality
5. **Reusable Pattern:** Cookie jar approach can be used for other test scripts
6. **Clear Reporting:** Distinguishes between permission failures (expected) and actual errors

## Future Improvements

Potential enhancements:
1. Cache authenticated session across multiple test runs
2. Add support for testing different user roles (admin, user, guest)
3. Add detailed logging of authentication flow for debugging
4. Support for API token authentication in addition to session cookies
5. Parallel test execution with separate cookie jars per thread
