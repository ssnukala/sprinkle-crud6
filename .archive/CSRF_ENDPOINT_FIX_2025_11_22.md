# CSRF Endpoint Fix - November 22, 2025

## Issue
GitHub Actions workflow run #19599030778 failed due to CSRF token retrieval issues. The integration test scripts were attempting to call a `/csrf` API endpoint that doesn't exist in UserFrosting 6, causing HTML error pages to be returned instead of JSON responses.

## Root Cause
The `getCsrfToken()` function in the integration test scripts had a fallback mechanism that tried to fetch CSRF tokens from a `/csrf` endpoint when the meta tag wasn't available on the current page:

```javascript
// INCORRECT - This endpoint doesn't exist in UserFrosting 6
const response = await page.request.get(`${baseUrl}/csrf`);
if (response.ok()) {
    const data = await response.json();
    return data.csrf_token || data.token;
}
```

## UserFrosting 6 CSRF Token Architecture
UserFrosting 6 provides CSRF tokens exclusively through meta tags in HTML pages:

```html
<meta name="csrf-token" content="...token_value...">
```

There is **no dedicated `/csrf` API endpoint** in UserFrosting 6. The CsrfGuardMiddleware handles CSRF protection, but tokens must be extracted from the HTML meta tag on authenticated pages.

## Solution
Updated the `getCsrfToken()` function in both test scripts to:

1. First try to get the token from the current page's meta tag
2. If not found, navigate to `/dashboard` to get a fresh token from a known authenticated page
3. Extract the token from the dashboard page's meta tag
4. Return null only if the token still can't be found after navigation

### Updated Code

```javascript
/**
 * Get CSRF token from the page
 * 
 * UserFrosting 6 provides CSRF tokens via meta tags in HTML pages, not via a dedicated API endpoint.
 * This function retrieves the token from the current page's meta tag.
 * If no token is found, it navigates to the dashboard to get a fresh token.
 */
async function getCsrfToken(page, baseUrl) {
    try {
        // Try to get CSRF token from meta tag on current page
        let csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            return csrfToken;
        }
        
        // If no token on current page, navigate to dashboard to get one
        // This ensures we're on a valid UserFrosting page with a CSRF meta tag
        console.warn('   ⚠️  No CSRF token on current page, navigating to dashboard to get token...');
        await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        
        // Try again to get token from dashboard page
        csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            console.log('   ✅ CSRF token retrieved from dashboard page');
            return csrfToken;
        }
        
        console.warn('   ⚠️  Could not find CSRF token meta tag on dashboard page either');
        return null;
    } catch (error) {
        console.warn('   ⚠️  Could not retrieve CSRF token:', error.message);
        return null;
    }
}
```

## Files Modified

### 1. `.github/scripts/take-screenshots-with-tracking.js`
- Updated `getCsrfToken()` function (lines 258-286)
- Removed `/csrf` endpoint call
- Added dashboard navigation fallback

### 2. `.github/scripts/test-authenticated-api-paths.js`
- Updated `getCsrfToken()` function (lines 27-55)
- Removed `/csrf` endpoint call
- Added dashboard navigation fallback

### 3. `.github/config/integration-test-paths.json`
- Updated documentation at line 744
- Changed from: `"Include X-CSRF-Token header with valid token from /csrf endpoint"`
- Changed to: `"Include X-CSRF-Token header with valid token from <meta name='csrf-token'> tag on any authenticated UserFrosting page"`

## Impact
- Integration tests will no longer attempt to call a non-existent endpoint
- CSRF tokens will be reliably retrieved from authenticated pages
- POST/PUT/DELETE operations will properly include CSRF tokens
- No more HTML error pages during CSRF token retrieval

## Testing
All modified files passed syntax validation:
- ✅ `take-screenshots-with-tracking.js` - Node.js syntax check passed
- ✅ `test-authenticated-api-paths.js` - Node.js syntax check passed
- ✅ `integration-test-paths.json` - JSON validation passed

## Related Issues
- GitHub Actions Run: #19599030778
- Issue: "crsf failures, the api to get crsf is not working it is trying to reander a error html page it looks like"

## References
- UserFrosting 6 CSRF Documentation: Meta tag approach
- CsrfGuardMiddleware: Core middleware handling CSRF validation
- Integration test workflow: `.github/workflows/integration-test.yml`

## Commit
- Commit: 8d38a7f
- Branch: copilot/fix-csrf-api-failures
- Date: 2025-11-22
