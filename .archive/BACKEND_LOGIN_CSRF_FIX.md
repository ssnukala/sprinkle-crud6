# Backend Login CSRF Token Extraction Fix

**Date:** 2024-12-13  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20186494103/job/57957493188  
**PR:** #[TBD]

## Problem

The backend login process in `test-paths.php` was failing with HTTP 400 errors:

```
⚠️  Warning: Could not extract CSRF token from login page
   Attempting login without CSRF token...
❌ Login failed (HTTP 400)
   Response length: 2903 bytes
❌ Authentication failed - skipping authenticated tests
```

## Root Cause

UserFrosting 6 changed how CSRF tokens are provided:
- **Old approach (UF4/UF5):** Hidden input field `<input name="csrf_token" value="...">`
- **New approach (UF6):** Meta tag `<meta name="csrf-token" content="...">`

The `test-paths.php` script was only looking for input fields, causing it to fail to extract the CSRF token.

Additionally, UserFrosting 6 expects CSRF tokens in:
1. **X-CSRF-Token header** (primary method for API requests)
2. **Form data field** (backward compatibility)

The old code only sent it in form data, not in the header.

## Solution

### File Modified
- `.github/testing-framework/scripts/test-paths.php`

### Changes

#### 1. Updated CSRF Token Extraction (Lines 95-112)

**Before:**
```php
// Extract CSRF token from the login page
$csrfToken = null;
if (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/', $loginPageContent, $matches)) {
    $csrfToken = $matches[1];
}
// ... other input field patterns
```

**After:**
```php
// Extract CSRF token from the login page
// UserFrosting 6 uses meta tags for CSRF tokens: <meta name="csrf-token" content="...">
$csrfToken = null;

// Try meta tag first (UserFrosting 6 standard)
if (preg_match('/<meta[^>]*name=["\']csrf-token["\'][^>]*content=["\']([^"\']+)["\']/', $loginPageContent, $matches)) {
    $csrfToken = $matches[1];
} elseif (preg_match('/<meta[^>]*content=["\']([^"\']+)["\'][^>]*name=["\']csrf-token["\']/', $loginPageContent, $matches)) {
    $csrfToken = $matches[1];
}
// Fallback to input field (older UserFrosting versions)
elseif (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/', $loginPageContent, $matches)) {
    $csrfToken = $matches[1];
}
// ... other input field patterns
```

#### 2. Added X-CSRF-Token Header (Lines 145-154)

**Before:**
```php
// Perform login POST request
$curlCmd = "curl -s -o {$tmpFile} -w '%{http_code}' -b {$cookieJar} -c {$cookieJar} -L " .
           "-X POST -H 'Content-Type: application/x-www-form-urlencoded' " .
           "--data " . escapeshellarg($postDataString) . " " . escapeshellarg($loginUrl) . " 2>&1";
```

**After:**
```php
// Perform login POST request with CSRF token in header
$curlCmd = "curl -s -o {$tmpFile} -w '%{http_code}' -b {$cookieJar} -c {$cookieJar} -L " .
           "-X POST -H 'Content-Type: application/x-www-form-urlencoded' ";

// Add X-CSRF-Token header if available (UserFrosting 6 standard)
if ($csrfToken) {
    $curlCmd .= "-H 'X-CSRF-Token: " . $csrfToken . "' ";
}

$curlCmd .= "--data " . escapeshellarg($postDataString) . " " . escapeshellarg($loginUrl) . " 2>&1";
```

#### 3. Enhanced Debug Output

Added detailed debugging to help diagnose future issues:
- Shows first 20 characters of extracted token
- Saves login page HTML to temp file for inspection
- Saves login response to temp file for inspection
- Reports specific error types (CSRF, Invalid, etc.)

## Validation

### Regex Pattern Testing

Created test script to verify all patterns work correctly:

```php
// Test case 1: UserFrosting 6 meta tag (name first)
$html1 = '<meta name="csrf-token" content="abc123def456">';
// ✅ Extracts: abc123def456

// Test case 2: UserFrosting 6 meta tag (content first)
$html2 = '<meta content="xyz789ghi012" name="csrf-token">';
// ✅ Extracts: xyz789ghi012

// Test case 3: Older UserFrosting input field
$html3 = '<input type="hidden" name="csrf_token" value="old123token456">';
// ✅ Extracts: old123token456

// Test case 4: Real-world HTML with meta tag
// ✅ Extracts: real-token-abc123xyz789
```

All tests passed successfully.

## Why This Works

1. **Meta Tag First:** Checks for UF6's `<meta name="csrf-token">` tag before falling back to input fields
2. **Attribute Order Flexible:** Handles both `name...content` and `content...name` attribute orders
3. **Backward Compatible:** Still supports older UF versions that use input fields
4. **Header Support:** Adds X-CSRF-Token header which is the primary method UF6 expects
5. **Form Data Too:** Also includes token in form data for maximum compatibility

## Related Files

Other scripts already use the correct approach:
- `.github/testing-framework/scripts/login-admin.js` - Uses Playwright which handles this automatically
- `.github/testing-framework/scripts/take-screenshots-with-tracking.js` - Already uses meta tag extraction
- `.github/testing-framework/scripts/test-authenticated-api-paths.js` - Already uses meta tag extraction

## References

- **UserFrosting 6 CSRF Documentation:** Uses meta tags and X-CSRF-Token header
- **config/integration-test-paths.json (line 1423):** Documents X-CSRF-Token header requirement
- **Archive script:** `.archive/pre-framework-migration/scripts-backup/test-authenticated-api-paths.js` shows correct pattern

## Security Improvements (Code Review)

After initial implementation, code review identified security and optimization improvements:

### 1. Shell Injection Prevention
**Issue:** CSRF token was concatenated directly into shell command without escaping.

**Fix:** Added `escapeshellarg()` to properly escape the token:
```php
// Before
$curlCmd .= "-H 'X-CSRF-Token: " . $csrfToken . "' ";

// After (secure)
$curlCmd .= "-H 'X-CSRF-Token: " . escapeshellarg($csrfToken) . "' ";
```

**Why:** CSRF tokens could theoretically contain special shell characters ($, ;, |, &, `, ', etc.). While unlikely in practice, proper escaping prevents potential command injection.

### 2. String Search Optimization
**Issue:** Multiple case-sensitive searches for error detection.

**Fix:** Use `stripos()` for case-insensitive searches:
```php
// Before
if (strpos($loginResponse, 'CSRF') !== false || strpos($loginResponse, 'csrf') !== false)

// After (optimized)
if (stripos($loginResponse, 'CSRF') !== false)
```

**Why:** Reduces redundant string operations and improves performance.

## Testing

This fix will be validated by the integration test workflow running successfully with authenticated API paths.

### Local Testing Performed
1. ✅ PHP syntax validation: No errors
2. ✅ Regex pattern testing: All patterns extract tokens correctly
3. ✅ Shell escaping testing: Special characters properly handled
4. ✅ Case-insensitive search testing: Works correctly with `stripos()`
