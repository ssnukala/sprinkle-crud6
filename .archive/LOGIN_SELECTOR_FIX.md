# Login Page Test Fix - Selector Update

## Issue
The integration test was failing with a timeout error when trying to log in:
```
page.waitForSelector: Timeout 10000ms exceeded.
Call log:
  - waiting for locator('input[name="user_name"]') to be visible
```

## Root Cause
The test script was using incorrect selectors for the UserFrosting 6 login form:
- **Old selectors (incorrect)**: 
  - `input[name="user_name"]` - username field
  - `input[name="password"]` - password field  
  - `button[type="submit"]` - submit button

- **Actual UserFrosting 6 form** uses `data-test` attributes instead of `name` attributes:
  - `input[data-test="username"]` - username field
  - `input[data-test="password"]` - password field
  - `button[data-test="submit"]` - submit button

## Solution
Updated `.github/scripts/take-authenticated-screenshots.js` to use the correct `data-test` attribute selectors that match the actual UserFrosting 6 login form structure.

### Changes Made
1. Changed `input[name="user_name"]` → `input[data-test="username"]`
2. Changed `input[name="password"]` → `input[data-test="password"]`
3. Changed `button[type="submit"]` → `button[data-test="submit"]`

## Verification
Created and ran a test script (`/tmp/test-login-form-selectors.js`) that validates:
- ✅ All selectors can find their target elements
- ✅ Fill operations work correctly with the new selectors
- ✅ Values are properly set in the form fields

## Benefits
- **Test-friendly**: Using `data-test` attributes is a best practice for testing
- **Stable**: Test attributes are less likely to change than CSS classes or element types
- **Clear intent**: Makes it obvious which elements are meant for testing

## References
- UserFrosting 6 login form HTML (from problem statement)
- UserFrosting Account Sprinkle: https://github.com/userfrosting/sprinkle-account/blob/6.0/app/assets/views/PageLogin.vue
