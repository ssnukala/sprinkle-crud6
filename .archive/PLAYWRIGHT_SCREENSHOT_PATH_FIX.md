# Playwright Screenshot Path Fix - Integration Testing

## Issue

The integration test workflow was failing with the error:
```
Error: Cannot find module 'playwright'
Require stack:
- /tmp/take_screenshots.js
```

This occurred when the screenshot capture script tried to execute `const { chromium } = require('playwright');` in the GitHub Actions workflow.

## Root Cause

The problem was with Node.js module resolution:

1. **Script location**: The screenshot script was created in `/tmp/take_screenshots.js`
2. **Playwright installation**: The `playwright` package was installed in `userfrosting/node_modules`
3. **Module resolution**: When Node.js executes a script, it searches for `node_modules` starting from the script's directory, NOT the current working directory

**Sequence that caused the failure**:
```bash
cd userfrosting                           # Change to userfrosting directory
npm install playwright                    # Install playwright in userfrosting/node_modules
cat > /tmp/take_screenshots.js << 'EOF'   # Create script in /tmp
# ... script content ...
EOF
node /tmp/take_screenshots.js             # Node.js looks for modules starting from /tmp
                                          # Cannot find /tmp/node_modules/playwright
                                          # Error: Cannot find module 'playwright'
```

## Solution

### Changes Made

Modified `.github/workflows/integration-test.yml` to create and execute the screenshot script in the `userfrosting` directory where `node_modules` exists:

**Before**:
```yaml
cat > /tmp/take_screenshots.js << 'EOF'
# ... script content ...
EOF
node /tmp/take_screenshots.js
```

**After**:
```yaml
cat > take_screenshots.js << 'EOF'
# ... script content ...
EOF
node take_screenshots.js
```

### Key Changes

1. **Script creation path**: Changed from `/tmp/take_screenshots.js` to `take_screenshots.js` (in current directory `userfrosting`)
2. **Script execution**: Changed from `node /tmp/take_screenshots.js` to `node take_screenshots.js`
3. **Added comment**: Clarified that script is created where playwright is installed

### No Breaking Changes

- ✅ Screenshot output paths remain in `/tmp/` as before (`/tmp/screenshot_*.png`)
- ✅ Artifact upload configuration unchanged
- ✅ All screenshot functionality preserved
- ✅ Error handling unchanged

## Technical Details

### Node.js Module Resolution

When you run `node /path/to/script.js`, Node.js resolves `require('module')` by:
1. Looking for `/path/to/node_modules/module`
2. Looking for `/path/node_modules/module`
3. Looking for `/node_modules/module`
4. And so on up the directory tree

**It does NOT use the current working directory** for module resolution.

### Why This Fix Works

```bash
cd userfrosting                      # Current directory: userfrosting/
npm install playwright               # Installs to: userfrosting/node_modules/playwright
cat > take_screenshots.js << 'EOF'   # Creates: userfrosting/take_screenshots.js
# ... script content ...
EOF
node take_screenshots.js             # Node.js looks from userfrosting/
                                     # Finds userfrosting/node_modules/playwright
                                     # ✅ Success!
```

## Files Modified

1. **`.github/workflows/integration-test.yml`**
   - Line 279: Changed script path from `/tmp/take_screenshots.js` to `take_screenshots.js`
   - Line 279: Added clarifying comment
   - Line 324: Changed execution from `node /tmp/take_screenshots.js` to `node take_screenshots.js`
   - Line 324: Updated comment

## Validation

- [x] YAML syntax validated with Python yaml parser
- [x] Changes are minimal (3 lines modified)
- [x] No breaking changes to functionality
- [x] Artifact paths unchanged
- [x] Error handling preserved

## Expected Behavior After Fix

When the integration test workflow runs:
1. ✅ Playwright npm package is available in `userfrosting/node_modules`
2. ✅ Screenshot script is created in `userfrosting/` directory
3. ✅ Node.js can resolve `require('playwright')` successfully
4. ✅ Screenshots are captured and saved to `/tmp/screenshot_*.png`
5. ✅ Artifacts are uploaded correctly

## Related Issues

This fix addresses the module resolution problem referenced in the issue where screenshots were failing with "Cannot find module 'playwright'". The solution follows best practices for Node.js script execution in environments where dependencies are locally installed.

### Relationship to Previous Fix

A previous fix (documented in `PLAYWRIGHT_MODULE_FIX.md`) added `npm install playwright` to the workflow, which correctly installed the playwright package. However, that fix was incomplete because it didn't address the **module resolution** issue:

- **Previous fix**: Ensured playwright package was installed → ✅ playwright in node_modules
- **This fix**: Ensured Node.js could find the installed package → ✅ module resolution works

Both fixes were necessary for screenshots to work correctly.

---

*Documentation created: October 12, 2025*
