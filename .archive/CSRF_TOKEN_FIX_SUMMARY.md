# CSRF Token Retrieval Fix - Issue Resolution Summary

**Date:** 2025-11-21  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19584509348/job/56090231790  
**PR:** (Will be filled in when PR is created)  
**Commit:** 2046006

## Problem Description

Integration tests were failing with the following error:

```
CSRF Testing: users_create
   Description: Create new user via CRUD6 API
   Method: POST
   Path: /api/crud6/users
   ⚠️  Could not retrieve CSRF token: apiRequestContext.get: Invalid URL
   ❌ Status: 400 (expected 200)
   ❌ FAILED
```

## Root Cause

The `getCsrfToken()` function in the integration test scripts was using a relative URL `/csrf` with Playwright's `page.request.get()` API:

```javascript
// INCORRECT - causes "Invalid URL" error
const response = await page.request.get('/csrf');
```

### Why This Failed

1. Playwright's `page.request` API requires **full URLs**, not relative paths
2. The browser context's `baseURL` setting does **not** automatically apply to the `page.request` API
3. The `page.request` API is separate from the page navigation API and doesn't inherit the baseURL

## Solution

Updated the `getCsrfToken()` function to:
1. Accept a `baseUrl` parameter
2. Construct the full URL using template literals
3. Updated all function calls to pass the `baseUrl` parameter

### Code Changes

**Before:**
```javascript
async function getCsrfToken(page) {
    try {
        // ... meta tag check ...
        
        // Try to get from /csrf endpoint
        const response = await page.request.get('/csrf');
        // ...
    }
}

// Called as:
const csrfToken = await getCsrfToken(page);
```

**After:**
```javascript
async function getCsrfToken(page, baseUrl) {
    try {
        // ... meta tag check ...
        
        // Try to get from /csrf endpoint (must use full URL for page.request API)
        const response = await page.request.get(`${baseUrl}/csrf`);
        // ...
    }
}

// Called as:
const csrfToken = await getCsrfToken(page, baseUrl);
```

## Files Modified

1. `.github/scripts/test-authenticated-api-paths.js`
   - Updated `getCsrfToken()` function signature
   - Updated the `/csrf` endpoint call
   - Updated the function call to pass `baseUrl`

2. `.github/scripts/take-screenshots-with-tracking.js`
   - Applied identical fixes

## Expected Impact

✅ **Fixed Issues:**
- CSRF token retrieval will now work correctly
- POST/PUT/DELETE API tests will include proper CSRF tokens
- Tests like "users_create" should now pass instead of returning 400 errors
- All state-changing operations (POST/PUT/DELETE) will properly authenticate with CSRF tokens

✅ **No Breaking Changes:**
- Only internal test script changes
- No changes to production code
- No changes to API endpoints
- No changes to UserFrosting CSRF implementation

## Testing

**Validation Steps:**
1. ✅ JavaScript syntax check passed for both files
2. ⏳ CI integration test run (will be validated when workflow runs)
3. ⏳ Verify "users_create" test passes
4. ⏳ Verify all POST/PUT/DELETE operations include CSRF tokens

## Playwright API Reference

For future reference, when using Playwright's `page.request` API:

- ✅ **DO:** Use full URLs with `page.request.get(url)`
  ```javascript
  const response = await page.request.get(`${baseUrl}/api/endpoint`);
  ```

- ❌ **DON'T:** Use relative URLs with `page.request.get(url)`
  ```javascript
  // This will fail with "Invalid URL" error
  const response = await page.request.get('/api/endpoint');
  ```

- 📝 **Note:** The `baseURL` setting in browser context only applies to `page.goto()` and similar navigation APIs, not to `page.request` API.

## Related Documentation

- Playwright Request API: https://playwright.dev/docs/api/class-request
- UserFrosting CSRF Protection: https://learn.userfrosting.com/security/csrf
- Integration Test Configuration: `.github/config/integration-test-paths.json`
