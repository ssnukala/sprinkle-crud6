# Login Form Selector Fix Summary

## Issue
GitHub Actions integration test was failing at:
https://github.com/ssnukala/sprinkle-crud6/actions/runs/19372674139/job/55432218093

## Root Cause
The UserFrosting 6 login page (`/account/sign-in`) contains TWO login forms:

1. **Header dropdown login** (`.uf-nav-login`)
   - Located in the navigation bar
   - Contains: username, password, remember-me checkbox, submit button
   
2. **Main body login** (`.uk-card`)
   - Located in the page body
   - Contains: username, password, remember-me checkbox, submit button

Both forms use the same `data-test` attributes:
- `input[data-test="username"]`
- `input[data-test="password"]`
- `button[data-test="submit"]`

The Playwright script was using unqualified selectors that matched BOTH forms, causing the test to fail with an ambiguous selector error.

## Solution
Updated `.github/scripts/take-authenticated-screenshots.js` to use qualified selectors:

**Before:**
```javascript
await page.fill('input[data-test="username"]', username);
await page.fill('input[data-test="password"]', password);
await page.click('button[data-test="submit"]');
```

**After:**
```javascript
await page.fill('.uk-card input[data-test="username"]', username);
await page.fill('.uk-card input[data-test="password"]', password);
await page.click('.uk-card button[data-test="submit"]');
```

The `.uk-card` qualifier ensures we target only the main body login form, not the header dropdown.

## Files Changed
- `.github/scripts/take-authenticated-screenshots.js`
  - Lines 47-48: Added comment and updated selector
  - Lines 51-52: Updated fill selectors
  - Line 59: Updated submit button selector

## Testing
- ✅ JavaScript syntax validated
- ✅ Selector validation test passed
- ✅ No other files affected

## References
- Failed workflow: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19372674139/job/55432218093
- Commit: Fix login form selector ambiguity in integration test
