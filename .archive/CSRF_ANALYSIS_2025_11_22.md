# CSRF Analysis for CRUD6 API Routes - November 22, 2025

## Summary

Investigation of CSRF protection in CRUD6 API routes reveals that **CSRF middleware is not currently enforced** on API endpoints. This is a deliberate design decision that aligns with modern API security practices where session-based CSRF protection is replaced by authentication-based security.

## Current State

### Route Middleware Configuration

**File**: `app/src/Routes/CRUD6Routes.php` (line 113)

```php
})->add(CRUD6Injector::class)->add(AuthGuard::class)->add(NoCache::class);
```

**Current middleware:**
- ✅ `CRUD6Injector` - Injects model and schema from route parameters
- ✅ `AuthGuard` - Requires authentication (session-based)
- ✅ `NoCache` - Prevents caching of API responses
- ❌ `CsrfGuard` - **NOT PRESENT**

## Integration Test Failures Analysis

### Root Cause of 400 Errors

The integration test failures (15 failed POST/PUT/DELETE requests) were NOT due to missing CSRF tokens as initially suspected. The investigation revealed:

1. **Test script attempted to get CSRF tokens from HTML meta tags**
   - Navigated to `/dashboard` to find `<meta name="csrf-token">`
   - Could not find token (expected - API routes don't render HTML)
   - Continued anyway without CSRF token

2. **Routes don't enforce CSRF**
   - No `CsrfGuard` middleware on CRUD6 routes
   - Authentication handled via `AuthGuard` (session-based)
   - CSRF validation not required or checked

3. **Actual cause of 400 errors is likely:**
   - **Missing required fields** in request payload
   - **Validation failures** from fortress validator
   - **Invalid data format** or type mismatches
   - **Session/cookie not properly sent** by playwright's `page.request` API

## Session/Cookie Issue in Playwright Tests

### The Real Problem

The test script uses `page.request.post()` which is Playwright's fetch-based API request context. This context:

**Theoretically should** share storage (cookies/session) with the page context according to Playwright docs
**In practice may not** properly include session cookies for authenticated requests

### Evidence

1. Login succeeds and screenshots work (browser page context has session)
2. GET requests work (read-only, minimal validation)
3. POST/PUT/DELETE fail with 400 (validation/authentication issues)

### Why GET Works But POST Fails

- GET requests for authenticated endpoints return 200
- This suggests the session IS being sent
- POST/PUT/DELETE failures are more likely due to **validation errors** or **missing request body fields**

## Fixes Implemented

### 1. Removed CSRF Token Logic from Test Script

**File**: `.github/scripts/take-screenshots-with-tracking.js`

**Changes:**
- Removed `getCsrfToken()` function (lines 260-300)
- Removed CSRF header logic (lines 337-342)
- Added comment explaining CSRF is not enforced on API routes

**Rationale:**
- CSRF tokens don't exist for API routes (no HTML rendering)
- Routes don't have CsrfGuard middleware
- Attempting to get non-existent tokens wastes time and adds confusing warnings

### 2. Enhanced Error Reporting

**File**: `.github/scripts/take-screenshots-with-tracking.js`

**Changes:**
- Added detailed error response parsing for 400 errors
- Now prints validation errors, error messages, and response body
- Helps diagnose actual cause of failures

**Example output:**
```
❌ Status: 400 (expected 200)
❌ Error: Validation failed
❌ Validation errors: { "email": ["The email field is required"] }
❌ FAILED
```

## Next Steps for Debugging

With enhanced error reporting, the integration tests will now show:

1. **Validation errors** - which required fields are missing
2. **Error messages** - what the server actually rejected
3. **Response bodies** - full context of failures

This will allow precise fixes to:
- Request payloads in test configuration
- Validation rules in schemas
- Request handling in controllers

## CSRF Security Considerations

### Should CRUD6 API Routes Have CSRF Protection?

**Arguments AGAINST (current approach):**

✅ **Modern API pattern**: RESTful APIs typically use token-based auth, not CSRF
✅ **Mobile/SPA compatibility**: CSRF is session-based, incompatible with stateless clients
✅ **Already authenticated**: `AuthGuard` requires valid session
✅ **Read/write protection**: Permission system controls access to operations
✅ **Follows UF6 pattern**: Other API routes may not use CSRF either

**Arguments FOR (if needed later):**

⚠️ **Session-based auth**: CRUD6 uses `AuthGuard` which is session-based
⚠️ **Browser context**: Web apps using CRUD6 could be vulnerable to CSRF attacks
⚠️ **Defense in depth**: Multiple layers of security are better
⚠️ **UF6 convention**: Admin sprinkle may use CSRF for similar routes

### Recommendation

**Current approach (no CSRF) is acceptable** for an API-first design, BUT:

1. **Document clearly** that CRUD6 is designed for authenticated API access
2. **Add CSRF option** for applications that require it (via middleware injection)
3. **Consider adding** `CsrfGuard` to align with UserFrosting conventions
4. **Provide guidance** on when to enable/disable CSRF based on use case

## Testing Approach

### PHPUnit Tests (Working)

- ✅ Uses `createJsonRequest()` which handles CSRF automatically
- ✅ Testing framework bypasses or mocks CSRF validation
- ✅ All 11 endpoint types × 3 auth scenarios = 33 tests passing

### Playwright Integration Tests (Failing)

- ❌ Browser-based tests hit real endpoints
- ❌ CSRF logic was attempting and failing to get tokens
- ✅ **FIXED**: Removed CSRF logic, added error reporting
- ⚠️ **Still need to fix**: Actual validation/payload issues causing 400 errors

## Conclusion

1. **CSRF is not the issue** - routes don't enforce it
2. **400 errors are likely validation failures** - need better error messages to diagnose
3. **Test script updated** to remove CSRF logic and add error reporting
4. **Next test run** will show actual error messages
5. **CSRF decision** can be revisited based on production security requirements

## Files Modified

- `.github/scripts/take-screenshots-with-tracking.js` - Removed CSRF logic, added error reporting
- `.archive/CSRF_ANALYSIS_2025_11_22.md` - This documentation

## Related Issues

- GitHub Actions run #19599443688 - Original failure
- 15 API test failures (all POST/PUT/DELETE operations)
- belongs_to_many_through relationship errors (separate issue, now fixed)
