# Authenticated API Testing Fix

## Issue Summary
Integration tests were failing because:
1. ~~Admin user appeared to be created twice~~ **CLARIFIED**: This was a misunderstanding - the "2nd attempt" was just `bakery bake` command output, not a duplicate user creation
2. Authenticated API testing was not sending payloads or CSRF tokens for POST/PUT/DELETE requests

## Root Causes

### Issue 1: Admin User Configuration (Minor Cleanup)
- The `integration-test-seeds.json` had `admin_user.enabled: true`
- This configuration was misleading - no code actually creates the admin user from this config
- The admin user is correctly created via `php bakery create:admin-user` in the workflow BEFORE seeds run
- Having this config enabled was confusing, so disabled it for clarity (no functional impact)

### Issue 2: Missing Payloads and CSRF Tokens
The new `test-authenticated-unified.js` script (lines 151-187) was using a simple approach:
```javascript
const response = await page.request.fetch(`${baseUrl}${apiPath.path}`, {
    method: apiPath.method
});
```

This didn't:
- Send payloads from `apiPath.payload`
- Retrieve or send CSRF tokens
- Handle different HTTP methods properly

The working script `.archive/pre-framework-migration/scripts-backup/test-authenticated-api-paths.js` had the proper implementation (lines 39-166).

## Fixes Applied

### Fix 1: Disabled Admin User in Seeds Config
File: `.github/config/integration-test-seeds.json`

```json
"admin_user": {
  "enabled": false,  // Changed from true
  "description": "Admin user is created via bakery create:admin-user in workflow before seeds run - DO NOT create here to avoid duplicate"
}
```

### Fix 2: Enhanced API Testing Script
File: `.github/testing-framework/scripts/test-authenticated-unified.js`

Added comprehensive HTTP request handling:

1. **CSRF Token Retrieval** (lines 151-188):
```javascript
async function getCsrfToken() {
    // Try current page first
    let csrfToken = await page.evaluate(() => {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    });
    
    // Fallback to dashboard if needed
    if (!csrfToken) {
        await page.goto(`${baseUrl}/dashboard`, ...);
        csrfToken = await page.evaluate(...);
    }
    
    return csrfToken;
}
```

2. **Payload Support** (lines 201, 218-219):
```javascript
const payload = apiPath.payload || {};

if (Object.keys(payload).length > 0) {
    console.log(`   Payload: ${JSON.stringify(payload)}`);
}
```

3. **Proper HTTP Method Handling** (lines 209-243):
```javascript
// Get CSRF token for state-changing operations
if (['POST', 'PUT', 'DELETE'].includes(method)) {
    const csrfToken = await getCsrfToken();
    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
    }
}

// Make request with appropriate method
if (method === 'GET') {
    response = await page.request.get(url, { headers });
} else if (method === 'POST') {
    response = await page.request.post(url, { 
        headers,
        data: payload 
    });
} else if (method === 'PUT') {
    response = await page.request.put(url, { 
        headers,
        data: payload 
    });
} else if (method === 'DELETE') {
    response = await page.request.delete(url, { 
        headers,
        data: payload 
    });
}
```

4. **Enhanced Error Logging** (line 258):
```javascript
// Increased from 200 chars to 500 chars for better debugging
console.log(`   Response body (first 500 chars): ${body.substring(0, 500)}`);
```

## Testing Required

### Expected Behavior After Fix
1. **Admin User Creation**: Should only happen once in the workflow step "Create admin user" - no duplicate attempts
2. **POST Requests**: Should include payloads like:
   ```json
   {
     "user_name": "apitest",
     "first_name": "API",
     "last_name": "Test",
     "email": "apitest@example.com",
     "password": "TestPassword123"
   }
   ```
3. **CSRF Tokens**: Should be retrieved and sent in `X-CSRF-Token` header for POST/PUT/DELETE
4. **PUT Requests**: Should update records with provided payloads
5. **DELETE Requests**: Should properly delete with any required payloads

### Test Cases from Config
From `integration-test-paths.json`:
- `users_create` (POST): Creates new user with full payload
- `users_update` (PUT): Updates user with partial payload
- `users_update_field_user_name` (PUT): Updates single field
- Custom actions (POST): Execute with empty payloads but CSRF token

## Workflow Order (Correct)
1. Run migrations (`php bakery migrate`)
2. **Create admin user** (`php bakery create:admin-user`) ✅ ONCE
3. Copy schema files
4. Generate and create tables from schemas
5. Generate and load SQL seed data (test data IDs 100+)
6. Run PHP seeds (roles, permissions - IDs in 1-99 range)
7. Validate seed data
8. Test seed idempotency
9. Start servers
10. Run authenticated API tests (now with payloads & CSRF!)

## References
- Working pattern: `.archive/pre-framework-migration/scripts-backup/test-authenticated-api-paths.js`
- Fixed file: `.github/testing-framework/scripts/test-authenticated-unified.js`
- Config: `.github/config/integration-test-paths.json`
- Workflow: `.github/workflows/integration-test.yml`

## Commit
Commit: Fix authenticated API testing and admin user configuration
- Added payload and CSRF token support to test-authenticated-unified.js
- Added proper HTTP method handling (GET/POST/PUT/DELETE) with payloads
- Improved error reporting with longer response snippets (500 chars)
- Disabled duplicate admin_user creation in seeds config
