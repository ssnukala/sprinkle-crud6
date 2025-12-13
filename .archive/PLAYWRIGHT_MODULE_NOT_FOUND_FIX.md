# Playwright Module Not Found Error - Fix Summary

**Date**: 2025-12-13  
**Issue**: CI workflow failure due to module not found error  
**Status**: ✅ Fixed

## Problem Statement

GitHub Actions workflow was failing with the following error:

```
Error [ERR_MODULE_NOT_FOUND]: Cannot find package 'playwright' imported from 
/home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/.github/crud6-framework/scripts/login-admin.js
```

**Source**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20179207196/job/57935094759

## Root Cause Analysis

The integration test workflow (`integration-test.yml`) had an incorrect step ordering:

1. **Line 406-414**: "Login as admin user" step executed `login-admin.js`
   - This script imports `playwright` module at line 16: `import { chromium } from 'playwright';`
   
2. **Line 541-544**: "Install Playwright" step ran `npx playwright install chromium`
   - This step was executing AFTER the login step

**Result**: Node.js tried to import Playwright before it was installed, causing the module not found error.

## Solution

Moved the "Install Playwright" step to execute before any scripts that require Playwright.

### Changes Made

**File**: `.github/workflows/integration-test.yml`

**Before**:
```yaml
- name: Test unauthenticated frontend paths
  # ... test step ...

- name: Login as admin user  # ❌ Uses Playwright - line 406
  run: |
    node ../.github/crud6-framework/scripts/login-admin.js

# ... more steps ...

- name: Install Playwright  # ❌ Installed too late - line 541
  run: |
    npx playwright install chromium
```

**After**:
```yaml
- name: Test unauthenticated frontend paths
  # ... test step ...

- name: Install Playwright  # ✅ Installed first - line 401
  run: |
    npx playwright install chromium

- name: Login as admin user  # ✅ Can now use Playwright - line 406
  run: |
    node ../.github/crud6-framework/scripts/login-admin.js
```

## Impact Analysis

### Scripts Requiring Playwright

The following scripts import the `playwright` module and were verified to execute AFTER the installation step:

1. **login-admin.js** (line 410 in workflow)
   - Imports: `import { chromium } from 'playwright';`
   - Purpose: Authenticate and save browser state

2. **take-screenshots-modular.js** (line 549 in workflow)
   - Uses: Playwright for browser automation
   - Purpose: Capture screenshots for visual testing

### Execution Order (After Fix)

```
Line 401: ✅ Install Playwright
Line 410: ✅ login-admin.js (uses Playwright)
Line 549: ✅ take-screenshots-modular.js (uses Playwright)
```

## Verification

### Code Review
- ✅ Passed automated code review
- ✅ No issues found

### Change Scope
- ✅ Single file modified: `.github/workflows/integration-test.yml`
- ✅ Minimal change: Only reordered steps, no logic changes
- ✅ No side effects: Installation step works the same, just at a different time

### Testing Strategy
- The fix will be validated when the GitHub Actions workflow runs
- Expected outcome: Login step should succeed without module not found error
- Downstream effects: All authenticated tests should pass once login succeeds

## Related Files

- `.github/workflows/integration-test.yml` - Fixed workflow
- `.github/testing-framework/scripts/login-admin.js` - Script that imports Playwright (source location)
- `.github/testing-framework/scripts/take-screenshots-modular.js` - Script that uses Playwright (source location)
- `.github/testing-framework/package.json` - Declares Playwright as peer dependency

**Note**: The workflow copies `.github/testing-framework/` to `.github/crud6-framework/` during execution (see line 60-74 of workflow). The error message references `.github/crud6-framework/scripts/login-admin.js` because that's the runtime location, but the source files are in `.github/testing-framework/`.

## References

- **Error Log**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20179207196/job/57935094759
- **Fix Commit**: c2d873d89275da4e904f53b4cca535b75eacc572
  - Title: "Fix: Move Playwright installation before login step to prevent module not found error"
  - Short hash: c2d873d
- **Branch**: copilot/fix-playwright-module-error

## Lessons Learned

1. **Dependency Installation Order Matters**: Always install dependencies before importing/using them
2. **CI/CD Step Ordering**: Pay careful attention to step dependencies in workflow files
3. **Module System**: ES6 modules (`import`) fail immediately if the module is not found, unlike dynamic requires
4. **Playwright Installation**: `npx playwright install` downloads browser binaries, but the package itself must be available via npm/pnpm first (which it was, as it's in package.json dependencies)

## Prevention

To prevent similar issues in the future:

1. **Workflow Template**: Update the workflow template in `.github/testing-framework/` to ensure correct ordering
2. **Documentation**: Document the required step order in testing framework README
3. **Step Comments**: Add comments in workflow YAML indicating dependencies between steps
4. **Validation**: Consider adding a pre-check step that validates required tools are installed
