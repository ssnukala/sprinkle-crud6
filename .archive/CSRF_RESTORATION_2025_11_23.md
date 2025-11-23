# CSRF Token Loading Restoration - November 23, 2025

## Issue
GitHub Actions integration test run #19617822068 was failing due to missing CSRF token for API requests.

## Root Cause
The test script was not loading CSRF tokens from the home page after login, causing POST/PUT/DELETE API requests to fail.

## Solution
Restore CSRF token loading logic: after login, navigate to home page (`/`) to extract CSRF token from meta tag, then use it for API testing.

## Implementation

### New Flow Sequence
```
1. Navigate to /account/sign-in
2. Fill credentials and submit
3. ✅ Login successful
4. Navigate to / (home page) ← NEW STEP
5. Extract CSRF token from <meta name="csrf-token"> ← NEW STEP
6. Take screenshots of frontend pages
7. Test API endpoints with CSRF token ← UPDATED
```

### Code Changes

#### File: `.github/scripts/take-screenshots-with-tracking.js`

**1. Added getCsrfToken() Function (lines 271-296)**
```javascript
async function getCsrfToken(page, baseUrl) {
    try {
        console.log('🔐 Loading CSRF token from home page...');
        
        // Navigate to home page to extract CSRF token
        await page.goto(`${baseUrl}/`, { waitUntil: 'networkidle', timeout: 15000 });
        
        // Extract CSRF token from meta tag
        const csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            console.log(`   ✅ CSRF token loaded from home page (/)`);
            console.log(`   Token: ${csrfToken.substring(0, 20)}...`);
            return csrfToken;
        } else {
            console.log('   ⚠️  WARNING: Could not find CSRF token on home page');
            return null;
        }
    } catch (error) {
        console.error(`   ❌ Error getting CSRF token: ${error.message}`);
        return null;
    }
}
```

**2. Updated testApiPath() Signature (line 298)**
```javascript
async function testApiPath(page, name, pathConfig, baseUrl, csrfToken = null)
```

**3. Added CSRF Token to Headers (lines 350-354)**
```javascript
// Add CSRF token header for POST/PUT/DELETE requests
if (csrfToken && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
    headers['X-CSRF-Token'] = csrfToken;
    console.log(`   🔐 Including CSRF token in ${method} request`);
}
```

**4. Load CSRF After Login (lines 887-895)**
```javascript
console.log('✅ Logged in successfully');

// Give session a moment to stabilize
await page.waitForTimeout(2000);

// Step 1.5: Load CSRF token from home page
// After successful login, navigate to home page to get CSRF token
// This token will be used for API testing (POST/PUT/DELETE requests)
console.log('');
const csrfToken = await getCsrfToken(page, baseUrl);
if (!csrfToken) {
    console.log('   ⚠️  WARNING: No CSRF token found - API tests may fail for POST/PUT/DELETE');
}
console.log('');
```

**5. Pass CSRF to API Tests (line 1084)**
```javascript
for (const [name, pathConfig] of Object.entries(authApiPaths)) {
    await testApiPath(page, name, pathConfig, baseUrl, csrfToken);
}
```

## Key Features

### CSRF Token Extraction
- Navigates to home page (`/`) only (not `/dashboard`)
- Uses `page.evaluate()` to query DOM for meta tag
- Extracts `content` attribute from `<meta name="csrf-token">`
- Returns token string or null

### CSRF Token Usage
- Only added to POST/PUT/DELETE requests (not GET)
- Header name: `X-CSRF-Token`
- Logs when token is included for debugging
- Gracefully handles missing token (warns but continues)

### Error Handling
- Try-catch around entire getCsrfToken function
- Navigation timeout: 15 seconds
- Returns null on any error
- Clear error messages logged

### Logging
- "🔐 Loading CSRF token from home page..."
- "✅ CSRF token loaded from home page (/)"
- "Token: [first 20 chars]..." (security - don't log full token)
- "🔐 Including CSRF token in [METHOD] request"
- Warning if token not found

## Testing

### Syntax Validation
```bash
$ node --check .github/scripts/take-screenshots-with-tracking.js
✅ JavaScript syntax is valid
```

### Logic Tests
- ✅ CSRF token extraction works correctly
- ✅ GET requests: No CSRF token (correct)
- ✅ POST requests: Has CSRF token (correct)
- ✅ PUT requests: Has CSRF token (correct)
- ✅ DELETE requests: Has CSRF token (correct)
- ✅ Token truncation works correctly

## Expected Integration Test Output

```
========================================
Testing Authenticated API Endpoints
========================================
Using existing authenticated session from screenshots
Using CSRF token from home page for state-changing requests

Testing: users_create
   Description: Create a new user
   Method: POST
   Path: /api/crud6/users
   📦 Payload: { "user_name": "testuser", ... }
   🔐 Including CSRF token in POST request
   ✅ Status: 200 (expected 200)
   ✅ PASSED

Testing: users_update
   Description: Update user details
   Method: PUT
   Path: /api/crud6/users/2
   📦 Payload: { "user_name": "updatedname", ... }
   🔐 Including CSRF token in PUT request
   ✅ Status: 200 (expected 200)
   ✅ PASSED

Testing: users_delete
   Description: Delete a user
   Method: DELETE
   Path: /api/crud6/users/2
   🔐 Including CSRF token in DELETE request
   ✅ Status: 200 (expected 200)
   ✅ PASSED
```

## Benefits

1. **Security**: Properly includes CSRF tokens for state-changing operations
2. **Compatibility**: Follows UserFrosting security patterns
3. **Debugging**: Clear logging helps troubleshoot issues
4. **Robustness**: Graceful error handling prevents test failures
5. **Simplicity**: Single home page path (`/`), not multiple fallbacks

## Commits

1. **8f6c752** - Initial plan
2. **01c1827** - Add CSRF token loading from home page after login
3. **d13192c** - Simplify CSRF token loading to only use home page (/)

## Changes Summary

- **Lines added**: 52
- **Lines removed**: 14
- **Net change**: +38 lines
- **Files modified**: 1 (`.github/scripts/take-screenshots-with-tracking.js`)

## Related Documentation

- `.archive/CSRF_ANALYSIS_2025_11_22.md` - Previous CSRF analysis
- `.archive/CSRF_REMOVAL_FOR_TESTING_2025_11_23.md` - Why CSRF was removed
- `.archive/CSRF_TOKEN_INVESTIGATION.md` - CSRF patterns in UF6
- `.archive/CSRF_REDIRECT_LOOP_FIX.md` - Previous CSRF issues

## Success Criteria

- [x] getCsrfToken() function implemented
- [x] CSRF token loaded after login
- [x] CSRF token passed to API tests
- [x] X-CSRF-Token header added to POST/PUT/DELETE
- [x] Error handling implemented
- [x] Logging added
- [x] Syntax validated
- [x] Logic tested
- [ ] Integration test passes (pending GitHub Actions run)

## Next Steps

1. Run GitHub Actions integration test workflow
2. Verify CSRF token is loaded: look for "✅ CSRF token loaded from home page (/)"
3. Verify API tests pass: look for "✅ PASSED" for POST/PUT/DELETE operations
4. Review logs for any warnings or errors
5. If tests pass, close original issue

## Notes

- This restores CSRF token loading that was previously removed
- The requirement specifically asked to visit home page after login
- Only `/` is used (not `/dashboard` as initially implemented)
- CSRF token is only required for state-changing operations (POST/PUT/DELETE)
- GET requests do not need CSRF tokens

## References

- Original Issue: GitHub Actions run #19617822068/job/56173009300
- Branch: `copilot/restore-csrf-load-logic`
- PR: (To be created)
