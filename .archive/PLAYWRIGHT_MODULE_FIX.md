# Playwright Module Fix - Integration Testing

## Latest Issue (December 2024) - CORRECTED SOLUTION

The integration test workflow was failing with the error:
```
Error [ERR_MODULE_NOT_FOUND]: Cannot find package 'playwright' imported from 
/home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/.github/crud6-framework/scripts/login-admin.js
```

This occurred when scripts in the testing framework tried to use ES module imports: `import { chromium } from 'playwright';`

### Critical Architecture Understanding
**sprinkle-crud6 is a COMPONENT of UserFrosting 6, not a standalone application.**
- All npm dependencies must be in `userfrosting/package.json`
- Sprinkle cannot operate independently
- Testing scripts must run from userfrosting directory to access its node_modules

## Previous Issue (October 2024)

The integration test workflow was failing with the error:
```
Error: Cannot find module 'playwright'
Require stack:
```

This occurred when the screenshot capture script tried to execute `const { chromium } = require('playwright');` in the GitHub Actions workflow.

## Root Cause (Latest Fix - CORRECTED)

The testing framework scripts use ES module imports:
```javascript
import { chromium } from 'playwright';
```

Node.js resolves ES module imports relative to the **script's location**, not the current working directory.

**Initial misunderstanding:** Tried to install playwright in `.github/testing-framework/` directory.

**Actual problem:** sprinkle-crud6 is a UserFrosting 6 component, not standalone. The workflow was:
1. Running scripts from: `.github/crud6-framework/scripts/login-admin.js`
2. Executing from: `cd userfrosting && node ../sprinkle-crud6/.github/crud6-framework/scripts/login-admin.js`
3. Node.js looked for playwright relative to script location: `.github/crud6-framework/node_modules/` (didn't exist)
4. Should have installed playwright in `userfrosting/node_modules/` and run scripts from there

## Root Cause (Previous Fix)

The workflow was attempting to use Playwright in two ways:
1. `npx playwright install chromium --with-deps` - Installs browser binaries (in "Install Playwright browsers" step)
2. `const { chromium } = require('playwright');` - Requires the npm package (in "Take screenshots" step)

The problem was that `npx playwright install` only installs the browser binaries, not the playwright npm package itself. The `require('playwright')` statement needs the playwright package to be installed in `node_modules`.

## Solution (Latest Fix - December 2024) - CORRECTED

**Follow the pattern from pre-framework-migration that worked:**
1. Install Playwright in userfrosting directory (where UserFrosting's node_modules is)
2. Copy scripts TO userfrosting directory
3. Run scripts FROM userfrosting directory

### Changes Made

1. **`.github/testing-framework/package.json`**
   - NO CHANGE - kept as `peerDependencies` (correct for component architecture)

2. **`.github/workflows/integration-test.yml`**
   - **Install Playwright**: `cd userfrosting && npm install playwright && npx playwright install chromium`
   - **Copy scripts**: Copy `login-admin.js` and `take-screenshots-modular.js` to userfrosting directory
   - **Run scripts**: Execute from userfrosting: `node login-admin.js` and `node take-screenshots-modular.js`

### Why This Works
- Scripts are in userfrosting directory where node_modules/playwright exists
- Node.js module resolution finds playwright in userfrosting/node_modules
- Follows UserFrosting 6 component architecture correctly

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

## Testing (Corrected Approach)

The fix was validated by:
1. ✅ YAML syntax validation of workflow file
2. ✅ Comparing with pre-framework-migration backup that worked
3. ✅ Verifying Node.js ES module resolution behavior
4. ✅ Understanding UserFrosting 6 component architecture
5. ✅ Confirming scripts are copied and run from userfrosting directory

## Files Modified (Final Corrected Version)

1. `.github/workflows/integration-test.yml`:
   - Install playwright in userfrosting directory
   - Copy scripts to userfrosting directory
   - Run scripts from userfrosting directory
2. `.github/testing-framework/package.json` - NO CHANGE (remains peerDependencies)

## Expected Behavior After Fix

When the integration test workflow runs:
1. ✅ Playwright npm package is installed in userfrosting/node_modules
2. ✅ Chromium browser binaries are installed
3. ✅ Scripts are copied to userfrosting directory
4. ✅ Scripts run from userfrosting and can import playwright successfully
5. ✅ Screenshots are captured and uploaded as artifacts

## Reference to Working Implementation

This fix follows the exact pattern from `.archive/pre-framework-migration/integration-test.yml.backup` (lines 526-530, 880, 889):
```yaml
- name: Install Playwright browsers for screenshots
  run: |
    cd userfrosting
    npm install playwright
    npx playwright install chromium --with-deps

- name: Take screenshots
  run: |
    cd userfrosting
    cp ../sprinkle-crud6/.github/scripts/take-screenshots-with-tracking.js .
    node take-screenshots-with-tracking.js integration-test-paths.json
```

---

*Documentation created: October 2024*
*Updated with corrected solution: December 2024*
