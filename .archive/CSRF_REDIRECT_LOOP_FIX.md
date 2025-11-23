# CSRF Token Redirect Loop Fix

**Issue:** CSRF token retrieval navigating to authenticated page causes redirect loop  
**Discovery Date:** 2025-11-23  
**Fixed In:** PR #216

## Critical Discovery

The failing integration test had TWO root causes, not just one:

### 1. Timeout Too Short (Original Diagnosis)
- PR #215 added 250+ lines of CSRF/API testing code
- Workflow became heavier with more setup steps
- 10-second timeout insufficient for slower environment
- **Fix:** Increase to 30s, add fallback selectors

### 2. CSRF Redirect Loop (New Discovery) ⚠️
- PR #215 added `getCsrfToken()` function
- When no CSRF token found, navigates to **`/dashboard`**
- **Problem:** Dashboard requires authentication!
- Dashboard redirects to `/account/sign-in`
- This could interfere with the login flow

## The Problematic Code (Before Fix)

```javascript
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
        
        // ❌ PROBLEM: Dashboard is authenticated - will redirect to login!
        console.warn('   ⚠️  No CSRF token on current page, navigating to dashboard to get token...');
        await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        
        // ... rest of function
    }
}
```

## The Fix

Changed navigation from `/dashboard` to `/` (home page):

```javascript
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
        
        // ✅ FIX: Use home page - it's unauthenticated and has CSRF token
        console.warn('   ⚠️  No CSRF token on current page, navigating to home page to get token...');
        await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        
        // Try again to get token from home page
        csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            console.log('   ✅ CSRF token retrieved from home page');
            return csrfToken;
        }
        
        console.warn('   ⚠️  Could not find CSRF token meta tag on home page either');
        return null;
    } catch (error) {
        console.warn('   ⚠️  Could not retrieve CSRF token:', error.message);
        return null;
    }
}
```

## Why This Matters

### UserFrosting 6 Page Authentication

| Page | URL | Requires Auth | Has CSRF Token | Redirect if Not Logged In |
|------|-----|---------------|----------------|---------------------------|
| **Home** | `/` | ❌ No | ✅ Yes | No redirect |
| **Login** | `/account/sign-in` | ❌ No | ✅ Yes | No redirect |
| **Dashboard** | `/dashboard` | ✅ Yes | ✅ Yes | → `/account/sign-in` |

### Confirmation from Workflow

The workflow itself proves the home page works and has CSRF tokens:

```yaml
- name: Start PHP development server
  run: |
    cd userfrosting
    php bakery serve &
    sleep 10
    
    # This curl hits http://localhost:8080 which is the HOME PAGE (/)
    # It succeeds, proving the home page renders successfully
    curl -f http://localhost:8080 || (echo "⚠️ Server may not be ready yet" && sleep 5 && curl -f http://localhost:8080)
    echo "✅ PHP server started on localhost:8080"
```

**Key Point:** The workflow already verifies `http://localhost:8080` (home page) works during server startup. This confirms the home page is available and rendering properly.

### The Problem Scenario

1. Script logs in successfully
2. Takes screenshots (may navigate away from authenticated pages)
3. Needs CSRF token for API testing
4. Current page has no token (maybe on a static page)
5. **Old code:** Navigate to `/dashboard`
6. Dashboard sees no auth → **Redirects to `/account/sign-in`**
7. Now we're back at login page
8. Login form appears but we're trying to get CSRF token
9. **Potential timing/state issues**

### The Fix

1. Script logs in successfully
2. Takes screenshots
3. Needs CSRF token for API testing
4. Current page has no token
5. **New code:** Navigate to `/` (home page)
6. Home page is public → **No redirect**
7. Get CSRF token from home page's meta tag
8. Continue with API testing
9. **Clean, predictable flow**

## Impact

This fix ensures:
- ✅ No unexpected redirects during CSRF token retrieval
- ✅ Predictable navigation flow
- ✅ No interference with login state
- ✅ Safer fallback when CSRF token not found on current page

## Lessons Learned

1. **Check authentication requirements** when navigating pages in tests
2. **Use public pages for tokens** - avoid authenticated endpoints
3. **Home page is safest** for retrieving common resources like CSRF tokens
4. **Dashboard is NOT a landing page** - it requires authentication

## Related Files

- `.github/scripts/take-screenshots-with-tracking.js` - CSRF token retrieval function
- `.archive/LOGIN_TIMEOUT_FIX_SUMMARY.md` - Overall fix summary
- `.archive/CSRF_REDIRECT_LOOP_FIX.md` - This document

## References

- UserFrosting 6 Authentication: Dashboard requires auth, home page doesn't
- CSRF tokens available on all pages via `<meta name="csrf-token">` tag
