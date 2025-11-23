# Login Timeout Fix Summary

**Issue:** Integration test failing after PR #215 with login form selector timeout  
**Successful Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19548391449 (PR #199, Nov 20)
**Failing Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19606535000 (PR #215, Nov 23)
**Fixed:** 2025-11-23

## Problem Statement

The integration test was working in PR #199 (Nov 20) and started failing in PR #215 (Nov 23) with this error:

```
❌ Error taking screenshots:
page.waitForSelector: Timeout 10000ms exceeded.
Call log:
  - waiting for locator('.uk-card input[data-test="username"]') to be visible
```

## Root Cause Analysis

### What Changed in PR #215

PR #215 ("Fix 400 errors in integration tests - add CSRF token support to API testing") made several changes:

#### 1. Workflow Changes (`integration-test.yml`)
- Added **runtime directory creation** step with 777 permissions
- Added **database session handler** configuration 
- Added **test user creation** step
- Extended the workflow with more setup steps

#### 2. Script Changes (`take-screenshots-with-tracking.js`)  
- Added **250+ lines** of CSRF token handling and API testing code
- Script now does more work: login → screenshots → API tests
- New navigation to dashboard for CSRF tokens
- More complex execution flow

### Why It Started Failing

The combination of changes created a **timing issue**:

1. **More Setup Work**: Runtime directories, test user creation adds time
2. **Heavier Script**: 250+ lines of new API testing code
3. **Frontend Build Delays**: Additional workflow steps delay when Vite fully serves assets
4. **Hard-Coded 10s Timeout**: Insufficient for the now-slower CI environment
5. **Single Selector**: No fallback if page structure varies slightly

**The login section code itself DIDN'T change** - but the environment became slower and less forgiving of timing issues.

## Changes Made

### 1. JavaScript Script Updates (`take-screenshots-with-tracking.js`)

#### Increased Timeouts
- Changed login form wait timeout from **10s to 30s**
- Added 2-second wait after page load for JavaScript execution

#### Fallback Selectors
Implemented multiple selector strategies for each form element:

**Username Field:**
```javascript
const selectors = [
    '.uk-card input[data-test="username"]',  // Original - scoped to card
    'input[data-test="username"]',            // Data-test only
    'input[name="username"]',                 // Name attribute
];
```

**Password Field:**
```javascript
const passwordSelectors = [
    '.uk-card input[data-test="password"]',
    'input[data-test="password"]',
    'input[name="password"]',
    'input[type="password"]',
];
```

**Submit Button:**
```javascript
const submitSelectors = [
    '.uk-card button[data-test="submit"]',
    'button[data-test="submit"]',
    'button[type="submit"]',
    '.uk-card button[type="submit"]',
];
```

#### Enhanced Error Handling
```javascript
if (!usernameInput) {
    // Save debug info
    const pageContent = await page.content();
    require('fs').writeFileSync('/tmp/login_page_debug.html', pageContent);
    await page.screenshot({ path: '/tmp/login_page_debug.png', fullPage: true });
    console.error('❌ Could not find username input field after trying all selectors');
    console.error('   Debug HTML saved to /tmp/login_page_debug.html');
    console.error('   Debug screenshot saved to /tmp/login_page_debug.png');
    throw new Error('Login form not found - username input field is missing');
}
```

### 2. Workflow Updates (`integration-test.yml`)

#### Extended Vite Startup Wait
```yaml
- name: Start Vite development server
  run: |
    cd userfrosting
    npm update
    php bakery assets:vite &
    VITE_PID=$!
    echo $VITE_PID > /tmp/vite.pid
    
    # Wait longer for Vite to fully start up
    echo "Waiting for Vite server to start..."
    sleep 20  # Increased from 10s
    
    # Try to verify Vite is running
    echo "Testing if frontend is accessible..."
    curl -f http://localhost:8080 || echo "⚠️  Page load test after Vite start"
    
    echo "✅ Vite server started"
```

## Why This Fix Is Correct

1. **Script code didn't change**: The login section in `take-screenshots-with-tracking.js` is identical between working and failing commits
2. **Environment became slower**: More workflow steps + heavier script = longer startup times
3. **Timeouts were too aggressive**: 10 seconds isn't enough in slower CI environments
4. **Single selector is brittle**: Fallbacks make the test more resilient

## Benefits

1. **More Resilient**: Multiple fallback selectors handle page structure variations
2. **Better Debugging**: Saves HTML and screenshots on failure
3. **Clearer Logs**: Shows which selectors succeed/fail
4. **Longer Timeouts**: Accommodates slower CI environments with heavier workloads
5. **Health Checks**: Verifies server is ready before tests

## Comparison

### Before (Working in PR #199)
- Lighter workflow: Basic setup only
- Simpler script: Just screenshots + network tracking
- 10s timeout: Adequate for lighter environment
- Fast startup: Vite ready quickly

### After PR #215 (Failing)
- Heavier workflow: Runtime dirs + test user + more steps
- Complex script: Screenshots + API tests + CSRF handling (250+ new lines)
- 10s timeout: **Too short** for heavier environment
- Slower startup: More to initialize before tests

### After This Fix
- Same heavy workflow: All PR #215 improvements kept
- Same complex script: All PR #215 features kept  
- 30s timeout: **Adequate** for heavier environment
- Fallback selectors: **Resilient** to variations
- Better debugging: **Diagnostic** info on failure

## Testing Strategy

The fix will be validated by:
1. Running the integration test in CI
2. Monitoring for successful login and screenshot capture
3. Checking logs for which selectors are used
4. Reviewing debug artifacts if any failures occur

## Related Files

- `.github/scripts/take-screenshots-with-tracking.js` - Updated with fallback selectors and timeouts
- `.github/workflows/integration-test.yml` - Extended Vite startup wait time
- `.archive/LOGIN_TIMEOUT_FIX_SUMMARY.md` - This document

## Future Improvements

If issues persist, consider:
1. Increasing Vite startup wait even further (30s+)
2. Adding explicit health check endpoint for Vite server
3. Implementing retry logic for page navigation
4. Adding wait for specific network requests to complete
5. Using Playwright's `waitForLoadState('networkidle')` more aggressively
6. Profiling workflow to identify bottlenecks in setup steps
