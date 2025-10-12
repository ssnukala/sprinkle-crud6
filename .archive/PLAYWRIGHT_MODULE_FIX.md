# Playwright Module Fix - Integration Testing

## Issue

The integration test workflow was failing with the error:
```
Error: Cannot find module 'playwright'
Require stack:
```

This occurred when the screenshot capture script tried to execute `const { chromium } = require('playwright');` in the GitHub Actions workflow.

## Root Cause

The workflow was attempting to use Playwright in two ways:
1. `npx playwright install chromium --with-deps` (line 182) - Installs browser binaries
2. `const { chromium } = require('playwright');` (line 279) - Requires the npm package

The problem was that `npx playwright install` only installs the browser binaries, not the playwright npm package itself. The `require('playwright')` statement needs the playwright package to be installed in `node_modules`.

## Solution

### Changes Made

1. **package.json**
   - Added `playwright` as a devDependency (version ^1.49.0)
   - This allows local developers to install playwright for screenshot testing

2. **.github/workflows/integration-test.yml**
   - Added `npm install playwright` before `npx playwright install chromium --with-deps`
   - This ensures the playwright package is available when the screenshot script runs

3. **INTEGRATION_TESTING.md**
   - Added documentation for installing Playwright locally
   - Provides clear instructions for developers who want to run screenshot tests

## Technical Details

### Why This Fix Works

When the workflow runs:
1. `npm install playwright` installs the playwright npm package into `node_modules`
2. `npx playwright install chromium --with-deps` installs the Chromium browser binaries
3. The screenshot script can now successfully `require('playwright')`

### Package Dependency Strategy

Playwright is added as a **devDependency** rather than a regular dependency because:
- It's only needed for development/testing purposes
- It's a large package (~100MB with browsers)
- Production code doesn't need it
- The CI workflow explicitly installs it when needed

## Testing

The fix was validated by:
1. ✅ JSON syntax validation of package.json
2. ✅ YAML syntax validation of workflow file
3. ✅ Simulating the require() statement to confirm it would fail without the package
4. ✅ Reviewing the workflow steps to ensure proper installation order

## Files Modified

1. `.github/workflows/integration-test.yml` - Added npm install playwright
2. `package.json` - Added playwright devDependency
3. `INTEGRATION_TESTING.md` - Added installation instructions

## Expected Behavior After Fix

When the integration test workflow runs:
1. ✅ Playwright npm package is installed
2. ✅ Chromium browser binaries are installed
3. ✅ Screenshot script can require('playwright') successfully
4. ✅ Screenshots are captured and uploaded as artifacts

## Related Issues

This fix was inspired by a similar solution in the `ssnukala/sprinkle-learntegrate` repository, where the same Playwright installation approach was used successfully.

## Date

2025-10-12
