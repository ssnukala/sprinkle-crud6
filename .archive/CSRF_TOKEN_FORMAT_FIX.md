# CSRF Token Format Fix for UserFrosting 6

## Issue
All authenticated API tests were failing with CSRF errors in GitHub Actions CI.

**Failed CI Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20198668250/job/57986368501#step:32:61

## Root Cause

The test script `.github/testing-framework/scripts/test-authenticated-unified.js` was using an incorrect CSRF token format:

### ❌ Incorrect Implementation (Before)
```javascript
// Looking for single meta tag
const metaTag = document.querySelector('meta[name="csrf-token"]');
const csrfToken = metaTag.getAttribute('content');

// Setting single header
headers['X-CSRF-Token'] = csrfToken;
```

### ✅ Correct Implementation (After)
```javascript
// UserFrosting 6 uses TWO meta tags
const nameTag = document.querySelector('meta[name="csrf_name"]');
const valueTag = document.querySelector('meta[name="csrf_value"]');

const tokens = {
    name: nameTag.getAttribute('content'),
    value: valueTag.getAttribute('content')
};

// Setting TWO headers
headers['csrf_name'] = tokens.name;
headers['csrf_value'] = tokens.value;
```

## UserFrosting 6 CSRF Protection

UserFrosting 6 implements a **dual-token CSRF protection system**:

1. **Meta Tags in HTML:**
   - `<meta name="csrf_name" content="csrf_token_name">`
   - `<meta name="csrf_value" content="actual_token_value">`

2. **HTTP Headers for API Requests:**
   - `csrf_name: csrf_token_name`
   - `csrf_value: actual_token_value`

This dual-token approach provides enhanced security by:
- Using a dynamic token name (not just a fixed header name)
- Requiring both the token name and value to be present
- Making CSRF attacks more difficult to execute

## Files Fixed

### Primary Fix
- `.github/testing-framework/scripts/test-authenticated-unified.js`
  - Updated `getCsrfToken()` → `getCsrfTokens()`
  - Changed return type from `string` → `{name: string, value: string}`
  - Updated all header assignments to use both tokens
  - Enhanced logging and error messages

### Already Correct (No Changes Needed)
- `.github/testing-framework/scripts/take-screenshots-with-tracking.js`
  - Already implemented correctly from lines 305-491
  - Served as reference for the fix

## Testing Strategy

The fix enables the following test flow:

1. **Login** → Establish authenticated session
2. **Navigate to a page** → Extract CSRF tokens from meta tags
3. **Make API requests** → Include both csrf_name and csrf_value headers
4. **State-changing operations** (POST/PUT/DELETE) require CSRF tokens
5. **Read-only operations** (GET) don't require CSRF tokens

## Implementation Details

### Token Extraction
```javascript
async function getCsrfTokens() {
    let tokens = await page.evaluate(() => {
        const nameTag = document.querySelector('meta[name="csrf_name"]');
        const valueTag = document.querySelector('meta[name="csrf_value"]');
        
        if (nameTag && valueTag) {
            return {
                name: nameTag.getAttribute('content'),
                value: valueTag.getAttribute('content')
            };
        }
        return null;
    });
    
    // Fallback: Navigate to dashboard if not found
    if (!tokens) {
        await page.goto(`${baseUrl}/dashboard`);
        tokens = await page.evaluate(/* same extraction code */);
    }
    
    return tokens;
}
```

### Token Usage in API Requests
```javascript
// Get CSRF tokens for state-changing operations
let csrfTokens = null;
if (['POST', 'PUT', 'DELETE'].includes(method)) {
    csrfTokens = await getCsrfTokens();
    if (csrfTokens && csrfTokens.name && csrfTokens.value) {
        headers['csrf_name'] = csrfTokens.name;
        headers['csrf_value'] = csrfTokens.value;
    } else {
        // Warning: Request will likely fail
        console.log('⚠️  WARNING: No CSRF tokens available!');
    }
}
```

## Validation

### Before Fix
```
❌ POST /api/crud6/users → 400 Bad Request (Missing CSRF token)
❌ PUT /api/crud6/users/1 → 400 Bad Request (Missing CSRF token)
❌ DELETE /api/crud6/users/1 → 400 Bad Request (Missing CSRF token)
```

### After Fix
```
✅ POST /api/crud6/users → 200 OK (With csrf_name + csrf_value headers)
✅ PUT /api/crud6/users/1 → 200 OK (With csrf_name + csrf_value headers)
✅ DELETE /api/crud6/users/1 → 200 OK (With csrf_name + csrf_value headers)
```

## Future Considerations

### For New Test Scripts
When creating new test scripts that interact with UserFrosting 6 APIs:

1. **Always extract BOTH CSRF tokens** from meta tags
2. **Include BOTH headers** for POST/PUT/DELETE requests
3. **Use descriptive logging** to aid debugging
4. **Reference existing implementations:**
   - `.github/testing-framework/scripts/test-authenticated-unified.js`
   - `.github/testing-framework/scripts/take-screenshots-with-tracking.js`
   - `.archive/pre-framework-migration/scripts-backup/take-screenshots-with-tracking.js`

### Common Pitfalls to Avoid
- ❌ Looking for single `csrf-token` meta tag
- ❌ Setting single `X-CSRF-Token` header
- ❌ Assuming CSRF tokens are optional for GET requests only
- ❌ Not handling missing CSRF tokens gracefully

### Best Practices
- ✅ Extract tokens immediately after login
- ✅ Cache tokens for the duration of the session
- ✅ Log token name (not value) for debugging
- ✅ Provide fallback navigation to get tokens if missing
- ✅ Validate token structure before using

## Related Issues
- Original issue: All authenticated API tests failing with CSRF errors
- CI failure reference: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20198668250/job/57986368501#step:32:61
- Fix PR: [This PR]

## References
- UserFrosting 6 CSRF documentation: [To be added]
- Working implementation (archived): `.archive/pre-framework-migration/scripts-backup/take-screenshots-with-tracking.js`
- Current implementation: `.github/testing-framework/scripts/take-screenshots-with-tracking.js`
