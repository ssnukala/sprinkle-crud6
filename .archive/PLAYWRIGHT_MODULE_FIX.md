# Playwright Module Fix - Integration Testing

## Latest Issue (December 2024)

The integration test workflow was failing with the error:
```
Error [ERR_MODULE_NOT_FOUND]: Cannot find package 'playwright' imported from 
/home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/.github/crud6-framework/scripts/login-admin.js
```

This occurred when scripts in the testing framework tried to use ES module imports: `import { chromium } from 'playwright';`

## Previous Issue (October 2024)

The integration test workflow was failing with the error:
```
Error: Cannot find module 'playwright'
Require stack:
```

This occurred when the screenshot capture script tried to execute `const { chromium } = require('playwright');` in the GitHub Actions workflow.

## Root Cause (Latest Fix)

The testing framework scripts use ES module imports:
```javascript
import { chromium } from 'playwright';
```

Node.js resolves ES module imports relative to the **script's location**, not the current working directory. The workflow was:
1. Installing Playwright in the `userfrosting` directory
2. Running scripts located in `.github/crud6-framework/scripts/`
3. Node.js looked for playwright in `.github/crud6-framework/node_modules/` (didn't exist)
4. Module not found error

## Root Cause (Previous Fix)

The workflow was attempting to use Playwright in two ways:
1. `npx playwright install chromium --with-deps` - Installs browser binaries (in "Install Playwright browsers" step)
2. `const { chromium } = require('playwright');` - Requires the npm package (in "Take screenshots" step)

The problem was that `npx playwright install` only installs the browser binaries, not the playwright npm package itself. The `require('playwright')` statement needs the playwright package to be installed in `node_modules`.

## Solution (Latest Fix - December 2024)

Install Playwright in the testing framework directory where the scripts are located.

### Changes Made

1. **`.github/testing-framework/package.json`**
   - Changed `playwright` from `peerDependencies` to `dependencies`
   - Ensures `npm install` installs playwright locally in the testing framework

2. **`.github/workflows/integration-test.yml`**
   - Added step "Install testing framework dependencies" that runs `npm install` in `.github/crud6-framework`
   - Updated "Install Playwright browsers" to run from `.github/crud6-framework` directory
   - Improved framework installation to handle both `.github/testing-framework` and `.github/crud6-framework` directory names

## Solution (Previous Fix - October 2024)

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

---

*Documentation created: October 2024*
