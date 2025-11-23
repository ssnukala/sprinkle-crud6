# 400 Errors Fix Summary

## Issue
Integration test workflow run [#19601537006](https://github.com/ssnukala/sprinkle-crud6/actions/runs/19601537006) was failing with 15 API test failures, all returning HTTP 400 errors instead of expected 200 responses.

## Problem Details

### Failed Tests
All mutating API operations (POST, PUT, DELETE) were failing with 400 Bad Request errors:

**Users API:**
- `users_create` (POST /api/crud6/users)
- `users_update` (PUT /api/crud6/users/2)
- `users_update_field` (PUT /api/crud6/users/2/flag_enabled)
- `users_custom_action` (POST /api/crud6/users/2/a/reset_password)
- `users_relationship_attach` (POST /api/crud6/users/2/roles)
- `users_relationship_detach` (DELETE /api/crud6/users/2/roles)
- `users_delete` (DELETE /api/crud6/users/2)

**Groups API:**
- `groups_create` (POST /api/crud6/groups)
- `groups_update` (PUT /api/crud6/groups/1)
- `groups_delete` (DELETE /api/crud6/groups/1)

**Roles API:**
- `roles_create` (POST /api/crud6/roles)
- `roles_update` (PUT /api/crud6/roles/1)
- `roles_delete` (DELETE /api/crud6/roles/1)

**Permissions API:**
- `permissions_create` (POST /api/crud6/permissions)
- `permissions_delete` (DELETE /api/crud6/permissions/1)

### Error Pattern
- **Symptom**: HTTP 400 Bad Request
- **Expected**: HTTP 200 OK
- **Common Factor**: All failures were on state-changing operations (POST, PUT, DELETE)
- **Working Tests**: All GET requests passed successfully

## Root Cause

### Missing CSRF Token Handling
The `.github/scripts/take-screenshots-with-tracking.js` script was not including CSRF tokens in API requests for mutating operations.

### Misleading Comment
Lines 289-290 contained an incorrect comment:
```javascript
// Note: CSRF protection is not currently enforced on CRUD6 API routes
// The routes use AuthGuard for authentication but not CsrfGuard
```

This was **incorrect** - CRUD6 API routes DO enforce CSRF protection for POST/PUT/DELETE operations.

### Why GET Requests Worked
UserFrosting 6 (like most frameworks) only requires CSRF tokens for state-changing operations (POST, PUT, DELETE, PATCH). Read operations (GET) don't require CSRF tokens, which is why all GET requests in the test suite passed.

## Solution

### Implementation
Added CSRF token support to `take-screenshots-with-tracking.js` matching the pattern already implemented in `test-authenticated-api-paths.js`:

1. **Added `getCsrfToken()` function** (lines 259-302):
   ```javascript
   async function getCsrfToken(page, baseUrl) {
       // Try to get CSRF token from meta tag on current page
       let csrfToken = await page.evaluate(() => {
           const metaTag = document.querySelector('meta[name="csrf-token"]');
           return metaTag ? metaTag.getAttribute('content') : null;
       });
       
       if (csrfToken) {
           return csrfToken;
       }
       
       // If no token on current page, navigate to dashboard to get one
       // ...
   }
   ```

2. **Updated `testApiPath()` to include CSRF tokens** (lines 337-343):
   ```javascript
   // Get CSRF token for state-changing operations (POST, PUT, DELETE)
   if (['POST', 'PUT', 'DELETE'].includes(method)) {
       const csrfToken = await getCsrfToken(page, baseUrl);
       if (csrfToken) {
           headers['X-CSRF-Token'] = csrfToken;
       }
   }
   ```

3. **Removed misleading comment** about CSRF not being enforced

### How It Works
1. After login, the page contains a `<meta name="csrf-token">` tag with the CSRF token
2. For each POST/PUT/DELETE request, the script:
   - Retrieves the CSRF token from the current page's meta tag
   - If not found, navigates to `/dashboard` to get a fresh token
   - Includes the token in the `X-CSRF-Token` header
3. UserFrosting validates the CSRF token and allows the request

## Why This Issue Occurred

### Separate Script Files
The repository has two similar but separate scripts:
- `test-authenticated-api-paths.js` - Correctly implements CSRF token handling
- `take-screenshots-with-tracking.js` - Was missing CSRF token handling

### Integration Test Uses Both
The workflow uses `take-screenshots-with-tracking.js` for:
1. Taking screenshots (works without CSRF)
2. Testing authenticated API endpoints (needs CSRF for mutations)

The screenshot functionality worked fine, but the API testing portion failed because it didn't include CSRF tokens.

## Testing

### Validation Steps
1. ✅ JavaScript syntax check passed: `node --check take-screenshots-with-tracking.js`
2. ⏳ CI integration test pending (will validate fix in workflow)

### Expected Results
After this fix, all 15 previously failing API tests should pass:
- POST requests will succeed with proper CSRF tokens
- PUT requests will succeed with proper CSRF tokens
- DELETE requests will succeed with proper CSRF tokens
- GET requests will continue to work as before

## Related Files

### Modified
- `.github/scripts/take-screenshots-with-tracking.js` - Added CSRF token support

### Reference
- `.github/scripts/test-authenticated-api-paths.js` - Contains the correct CSRF implementation pattern

### Workflow
- `.github/workflows/integration-test.yml` - Uses the fixed script

## Commit
- **SHA**: 4a3ee66
- **Branch**: copilot/fix-400-errors
- **Message**: "Add CSRF token support to screenshot script API tests"

## Prevention

### Code Review Guidelines
1. When creating new scripts that make authenticated API requests, always include CSRF token handling for POST/PUT/DELETE
2. Reference existing working scripts (like `test-authenticated-api-paths.js`) for patterns
3. Test both GET and mutating operations in CI

### Script Consolidation Opportunity
Consider consolidating the API testing logic from both scripts into a shared module to avoid duplication and ensure consistency.

## References
- Issue: GitHub Actions run [#19601537006](https://github.com/ssnukala/sprinkle-crud6/actions/runs/19601537006)
- UserFrosting 6 CSRF Documentation: [CSRF Protection](https://learn.userfrosting.com/security/csrf)
- Playwright Request Context: [API Testing](https://playwright.dev/docs/api-testing)
