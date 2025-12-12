# Cat Commands and Route Configuration Verification

**Date:** 2025-12-12  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20173022877/job/57913697090  
**PR:** Fix vue routes configuration

## Problem Statement

Integration tests were failing with 500 errors and "Not Found" messages:
- All API tests returning 500 - page not found
- Frontend screenshots showing "The Requested resource could not be found"
- Only `before_login_selectors.png` showing content
- Suspected vue routes configuration issue

## Investigation Findings

### 1. Route Configuration is Correct
Compared current route configuration with working version (commit 7116e16):

**Working Version (7116e16):**
```yaml
- name: Configure routes (simple pattern)
  run: |
    cd userfrosting
    
    # Simple array spread pattern
    sed -i "/import AdminRoutes from '@userfrosting\\/sprinkle-admin\\/routes'/a import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'" app/assets/router/index.ts
    sed -i '/\\.\\.\\.AccountRoutes,/a \\            ...CRUD6Routes,' app/assets/router/index.ts
```

**Current Version:** ✅ IDENTICAL (only added cat command for visibility)

### 2. File Paths Verified
- ✅ `app/assets/router/index.ts` is the CORRECT path for UserFrosting 6
- ✅ Routes in sprinkle-crud6 are at `app/assets/routes/` (exports at `./routes`)
- ✅ Package.json correctly exports routes: `"./routes": "./app/assets/routes/index.ts"`

### 3. All Configuration Steps Verified
Compared all configuration steps with working version:

| Step | Status | Notes |
|------|--------|-------|
| MyApp.php | ✅ Identical | CRUD6 sprinkle registration |
| main.ts | ✅ Identical | CRUD6Sprinkle import and use |
| router/index.ts | ✅ Identical | CRUD6Routes import and spread |
| vite.config.ts | ✅ Enhanced | Complex version with better error handling |

## Solution Implemented

### 1. Added Cat Commands for Visibility

Modified `.github/testing-framework/scripts/generate-workflow.js` to add cat commands after each configuration step:

**After MyApp.php:**
```yaml
echo ""
echo "✅ MyApp.php configured"
echo "Updated app/src/MyApp.php:"
cat app/src/MyApp.php
```

**After main.ts:**
```yaml
echo ""
echo "✅ main.ts configured"
echo "Updated app/assets/main.ts:"
cat app/assets/main.ts
```

**After router/index.ts:**
```yaml
echo ""
echo "✅ Routes configured (simple pattern)"
echo "Updated app/assets/router/index.ts:"
cat app/assets/router/index.ts
```

**After vite.config.ts:**
```yaml
echo ""
echo "✅ Vite configuration updated"
echo "Updated vite.config.ts:"
cat vite.config.ts
```

### 2. Folder Reorganization

Renamed confusing folder structure:
- **Before:** `.github/workflow/` and `.github/workflows/` (confusing!)
- **After:** `.github/custom-scripts/` and `.github/workflows/` (clear!)

Updated references:
- `integration-test-config.json`: Updated custom script path
- `custom-script.js`: Updated path in documentation
- `README.md`: Updated example paths

### 3. Regenerated Workflow

Regenerated `.github/workflows/integration-test.yml` with:
- All cat commands included
- Updated custom script paths
- Verified configuration matches working version

## How Cat Commands Help Debug 500 Errors

The cat commands will display in CI logs:

1. **MyApp.php Content** - Verify:
   - `use UserFrosting\Sprinkle\CRUD6\CRUD6;` import is added
   - `CRUD6::class,` is added to sprinkles array
   - sed command executed correctly

2. **main.ts Content** - Verify:
   - `import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'` is added
   - `app.use(CRUD6Sprinkle)` is added
   - Import statement is properly formatted

3. **router/index.ts Content** - Verify:
   - `import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'` is added
   - `...CRUD6Routes,` is added to routes array
   - Array spread syntax is correct
   - Import path is correct

4. **vite.config.ts Content** - Verify:
   - `optimizeDeps` section exists
   - `include: ['limax', 'lodash.deburr']` is present
   - Configuration is properly formatted

## Expected Output in CI Logs

After these changes, CI logs will show:

```
✅ MyApp.php configured
Updated app/src/MyApp.php:
<?php
namespace UserFrosting\App;
use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\CRUD6\CRUD6;  ← VERIFY THIS LINE
class MyApp
{
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
            CRUD6::class,  ← VERIFY THIS LINE
        ];
    }
}

✅ main.ts configured
Updated app/assets/main.ts:
import { createApp } from 'vue'
import AdminSprinkle from '@userfrosting/sprinkle-admin'
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'  ← VERIFY THIS LINE
...
app.use(AdminSprinkle)
app.use(CRUD6Sprinkle)  ← VERIFY THIS LINE

✅ Routes configured (simple pattern)
Updated app/assets/router/index.ts:
import AccountRoutes from '@userfrosting/sprinkle-account/routes'
import AdminRoutes from '@userfrosting/sprinkle-admin/routes'
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'  ← VERIFY THIS LINE
...
const routes = [
    ...CoreRoutes,
    ...AccountRoutes,
    ...CRUD6Routes,  ← VERIFY THIS LINE
    ...AdminRoutes,
]

✅ Vite configuration updated
Updated vite.config.ts:
...
optimizeDeps: {
    include: ['limax', 'lodash.deburr']  ← VERIFY THIS LINE
}
...
```

## Potential Root Causes for 500 Errors

If configuration files show correctly in logs, check:

1. **NPM Package Installation**
   - Is `@ssnukala/sprinkle-crud6` package installed?
   - Check: `npm list @ssnukala/sprinkle-crud6` in CI logs

2. **Build Errors**
   - Does `npm run build` complete successfully?
   - Are there TypeScript compilation errors?

3. **Route Export Issues**
   - Does `app/assets/routes/index.ts` export routes correctly?
   - Are route definitions valid?

4. **Module Resolution**
   - Can Vite resolve `@ssnukala/sprinkle-crud6/routes`?
   - Is package.json exports field correct?

5. **Runtime Errors**
   - Check browser console in screenshots
   - Look for JavaScript errors in frontend

## Files Modified

1. `.github/testing-framework/scripts/generate-workflow.js`
   - Added cat commands after MyApp.php configuration
   - Added cat commands after main.ts configuration
   - Added cat commands after router/index.ts configuration
   - Added cat commands after vite.config.ts configuration

2. `.github/workflow/` → `.github/custom-scripts/`
   - Renamed folder to avoid confusion with workflows folder

3. `integration-test-config.json`
   - Updated custom script path

4. `.github/workflows/integration-test.yml`
   - Regenerated with new cat commands

## Validation

✅ Route configuration matches working version (7116e16)
✅ All configuration sed commands are identical
✅ File paths are correct for UserFrosting 6
✅ Cat commands added for all configuration steps
✅ Folder structure cleaned up
✅ Workflow regenerated successfully

## Conclusion

The route configuration itself is correct and matches the previously working version. The 500 errors are likely caused by:
- NPM package installation issues
- Build/compilation errors
- Runtime JavaScript errors

The added cat commands will provide complete visibility into:
- What configuration changes were applied
- Whether sed commands executed correctly
- Exact file content after configuration

This will help quickly identify the actual root cause of the 500 errors in future CI runs.
