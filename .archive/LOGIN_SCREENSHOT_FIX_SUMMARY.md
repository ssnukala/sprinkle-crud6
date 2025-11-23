# Login Screenshot Fix Summary

## Date
November 23, 2025

## Issue
Integration test failing with error: `require is not defined` during screenshot capture

## Failed Run
- Run ID: 19606761538
- Job: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19606761538/job/56146677140
- Error: `Fatal error: ReferenceError: require is not defined`
- Location: `take-screenshots-with-tracking.js:585`

## Working Run (Reference)
- Run ID: 19548391449
- URL: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19548391449

## Root Cause
Line 585 in `take-screenshots-with-tracking.js` used `require('fs')` in an ES6 module:
```javascript
require('fs').writeFileSync('/tmp/login_page_debug.html', pageContent);
```

The file uses ES6 `import` statements at the top but had a legacy CommonJS `require()` call, causing a fatal error.

## Symptoms
1. Script crashed before login could complete
2. Username field selectors timing out (30 seconds per selector × 3 selectors = 90s)
3. Error occurred when trying to save debug HTML
4. No screenshots captured successfully
5. No visibility into what was rendering on the page

## Solution

### Fix 1: ES6 Module Error (Commit 4dae2c7)
**Changed:** Line 585 from `require('fs').writeFileSync()` to `writeFileSync()`
**Reason:** `writeFileSync` is already imported at the top of the file via `import { readFileSync, writeFileSync } from 'fs';`

### Fix 2: Enhanced Debugging (Commit 4dae2c7)
Added comprehensive debugging capabilities:

1. **Browser Console Logging**
   - Captures all console errors and warnings from the browser
   - Logs them in real-time during test execution
   - Included in error handler output

2. **Early Screenshots**
   - `/tmp/screenshot_login_page_initial.png` - Immediately after login page loads
   - `/tmp/screenshot_before_login_selectors.png` - Before searching for form fields
   - Helps identify what's actually rendering

3. **CSRF Token Detection**
   - Extracts CSRF token from login page before login attempt
   - Checks multiple common locations:
     - `<meta name="csrf-token">` tag
     - Input fields: `csrf_token`, `_csrf_token`, `csrf-token`
   - Logs token status (found/not found)

4. **Console Error Reporting**
   - All browser console errors/warnings logged with timestamps
   - Errors displayed in failure output for debugging

### Fix 3: Page Info Logging (Commit 033dd66)
Added page navigation debugging:

1. **Page Title and URL Logging**
   - Logs actual page title after navigation
   - Logs actual URL (helps identify redirects)
   - Helps verify we're on the correct page

2. **Reduced Selector Timeout**
   - Changed from 30 seconds to 10 seconds per selector
   - Total wait time: 30 seconds (3 selectors) vs 90 seconds before
   - Faster failure with better diagnostics

## Files Modified
- `.github/scripts/take-screenshots-with-tracking.js`

## Changes Summary

### Before
```javascript
// Line 585 - BROKEN
require('fs').writeFileSync('/tmp/login_page_debug.html', pageContent);

// No console error logging
// No early screenshots
// No CSRF token detection
// No page title/URL logging
// 30 second timeout per selector
```

### After
```javascript
// Line 634 - FIXED
writeFileSync('/tmp/login_page_debug.html', pageContent);

// Added console error logging
const consoleErrors = [];
page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    if (type === 'error' || type === 'warning') {
        consoleErrors.push({ type, text, timestamp: Date.now() });
        console.log(`   🖥️  Browser ${type}: ${text}`);
    }
});

// Added early screenshots
await page.screenshot({ path: '/tmp/screenshot_login_page_initial.png', fullPage: true });
await page.screenshot({ path: '/tmp/screenshot_before_login_selectors.png', fullPage: true });

// Added CSRF token detection
const csrfToken = await page.evaluate(() => {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) return metaTag.getAttribute('content');
    
    const inputField = document.querySelector('input[name="csrf_token"], input[name="_csrf_token"], input[name="csrf-token"]');
    if (inputField) return inputField.value;
    
    return null;
});

// Added page title/URL logging
const pageTitle = await page.title();
const pageUrl = page.url();
console.log(`   Page Title: ${pageTitle}`);
console.log(`   Page URL: ${pageUrl}`);

// Reduced selector timeout
const selectorTimeout = 10000; // 10 seconds per selector
```

## Testing Strategy
The next integration test run will provide:
1. ✅ No "require is not defined" error
2. ✅ Early screenshots showing what renders on login page
3. ✅ Console errors if JavaScript is failing
4. ✅ CSRF token status
5. ✅ Page title/URL to verify correct page
6. ✅ Faster failure (30s vs 90s) with better diagnostics

## Expected Behavior
1. Script executes without syntax errors
2. Navigation to login page succeeds
3. Early screenshot captured showing login form (or redirect page)
4. CSRF token detected and logged (or warning if not found)
5. Page title and URL logged for verification
6. Browser console errors/warnings logged if present
7. Username field selectors attempted with 10s timeout each
8. If selectors fail, comprehensive debugging info available:
   - Early screenshots showing actual page state
   - Page title/URL showing where we landed
   - CSRF token status
   - Browser console errors
   - Debug HTML and screenshot saved

## Debugging Information Available
When login fails, the following artifacts are now available:

1. **Screenshots**
   - `/tmp/screenshot_login_page_initial.png` - What loaded initially
   - `/tmp/screenshot_before_login_selectors.png` - Page state before selector search
   - `/tmp/login_page_debug.png` - Final state when selectors fail
   - `/tmp/screenshot_error.png` - Error state screenshot

2. **Logs**
   - Browser console errors/warnings
   - Page title and URL
   - CSRF token status
   - Selector search results

3. **HTML Dump**
   - `/tmp/login_page_debug.html` - Full page HTML when selectors fail

## Related Issues
- Original issue: Login page selectors timing out
- Root cause: ES6 module syntax error preventing script execution
- Secondary issue: Lack of visibility into page state during login

## Follow-up Actions
- [x] Fix ES6 module error
- [x] Add console error logging
- [x] Add early screenshots
- [x] Add CSRF token detection
- [x] Add page title/URL logging
- [x] Reduce selector timeout
- [ ] Monitor next integration test run
- [ ] Verify screenshots are captured successfully
- [ ] Analyze login page state if selectors still fail

## Commits
1. `4dae2c7` - Fix ES6 module error and add enhanced debugging for login process
2. `033dd66` - Add page info logging and reduce selector timeout

## PR
Branch: `copilot/fix-login-without-csrf-token`
Related to: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19606761538
