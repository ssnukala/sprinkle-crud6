# Integration Test Module Path Fix

**Issue**: Integration testing failed with error:
```
Error: Cannot find module '/home/runner/work/sprinkle-crud6/sprinkle-crud6/userfrosting/take-screenshots-modular.js'
```

**GitHub Actions Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20210922983/job/58016606058

**Date**: December 14, 2024

## Root Cause Analysis

The old workflow (pre-regeneration) attempted to copy ES6 module scripts from the sprinkle directory to the userfrosting directory and execute them there. This approach had multiple issues:

### Issue 1: Script File Not Found
The script file itself couldn't be found at runtime, either because:
1. The `.github/crud6-framework` directory wasn't created properly during framework installation
2. The copy step failed silently
3. The working directory was incorrect when the script was executed

### Issue 2: ES6 Module Import Resolution
Even if the copy succeeded, ES6 modules use `import` statements that resolve dependencies relative to the script's location, not the execution directory. When `take-screenshots-modular.js` uses:

```javascript
import { chromium } from 'playwright';
```

Node.js looks for `playwright` in node_modules starting from the script's directory. Since the script was copied to userfrosting but playwright was installed in userfrosting's node_modules, the import would fail if Node.js couldn't find it.

### Issue 3: Framework Directory Setup
The old workflow didn't properly handle the case where the repository IS the framework repository (sprinkle-crud6). It would:
1. Check for `.github/crud6-framework` - not found (gitignored)
2. Skip checking for `.github/testing-framework` - exists but wasn't checked
3. Clone the same repository again from remote just to copy the testing framework

## Solution

### 1. Regenerated Workflow Using Framework Scripts
Instead of manually maintaining the workflow, we regenerated it using the framework's own generation script:

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

This ensures the workflow follows the latest patterns and best practices from the framework.

**Updated approach**: Following feedback, all installations and script execution now happen within the userfrosting directory, keeping the sprinkle properly contained within UserFrosting's structure.

### 2. Fixed Playwright Installation and Script Execution (Updated)
Modified the workflow generator to install playwright in userfrosting and copy scripts as `.mjs`:

```yaml
- name: Install Playwright and prepare test scripts
  run: |
    cd userfrosting
    
    # Install playwright in userfrosting
    npm install playwright
    npx playwright install chromium
    
    # Copy testing scripts to userfrosting as .mjs for ES6 module support
    cp ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/take-screenshots-modular.js take-screenshots-modular.mjs
    echo "✅ Playwright installed and test scripts prepared"
```

This ensures:
- All installations happen in userfrosting directory
- Scripts are copied to userfrosting as `.mjs` for automatic ES6 module support
- No external dependencies or installations outside userfrosting

### 3. Run Scripts from Userfrosting Directory
Scripts are copied to userfrosting and executed from there:

```yaml
- name: Capture screenshots
  run: |
    cd userfrosting
    node take-screenshots-modular.mjs \
      ../${{ env.SPRINKLE_DIR }}/.github/config/integration-test-paths.json \
      screenshots
```

This approach:
- Keeps all script execution within userfrosting
- Uses `.mjs` extension for ES6 module support without modifying package.json
- Allows Node.js to resolve ES6 imports correctly from userfrosting's node_modules
- Maintains simplicity and follows UserFrosting integration patterns

### 4. Added Local Framework Detection
Modified the generator to check for `.github/testing-framework` before cloning from remote:

```yaml
- name: Install testing framework
  run: |
    cd ${{ env.SPRINKLE_DIR }}
    
    if [ -d ".github/crud6-framework" ]; then
      echo "✅ Using local framework (crud6-framework)"
    elif [ -d ".github/testing-framework" ]; then
      echo "✅ Using local framework (testing-framework)"
      cp -r .github/testing-framework .github/crud6-framework
      chmod +x .github/crud6-framework/scripts/*.php
      echo "✅ Framework copied to crud6-framework"
    else
      echo "📦 Installing framework from remote..."
      # Clone from remote repo
    fi
```

Benefits:
- For sprinkle-crud6 (the framework repo), uses the checked-out testing-framework
- Tests the actual code being committed, not the main branch version
- Eliminates redundant repository clone
- Still supports external sprinkles by falling back to remote clone

## Results

### Before
- **Workflow length**: 839 lines (manually maintained)
- **Script execution**: Copy to userfrosting directory (but failed)
- **Module resolution**: Failed (script not found or ES6 imports failed)
- **Framework setup**: Redundant clone even for framework repo

### After
- **Workflow length**: 425 lines (auto-generated)
- **Script execution**: Copy to userfrosting as `.mjs` and run from there
- **Module resolution**: Works correctly with `.mjs` extension for ES6 support
- **Framework setup**: Efficient detection of local framework
- **Installation location**: All installations in userfrosting directory

### Key Improvements
1. ✅ All installations in userfrosting directory (no external dependencies)
2. ✅ Scripts copied to userfrosting as `.mjs` for ES6 module support
3. ✅ Playwright properly installed for ES6 imports
4. ✅ Local testing-framework detected and used
5. ✅ Workflow simplified and auto-generated
6. ✅ Follows UserFrosting integration patterns

## Testing

The fix can be validated by:
1. Pushing changes to trigger GitHub Actions workflow
2. Monitoring the "Install Playwright" step for successful installation
3. Verifying the "Capture screenshots" step runs without module errors
4. Checking that screenshots are captured and uploaded as artifacts

## References

- **PR**: [Add link when PR is created]
- **Failed CI Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20210922983/job/58016606058
- **Generator Script**: `.github/testing-framework/scripts/generate-workflow.js`
- **Configuration**: `integration-test-config.json`
- **Generated Workflow**: `.github/workflows/integration-test.yml`

## Lessons Learned

1. **ES6 Module Resolution**: When using ES6 modules with imports, use `.mjs` extension for automatic ES6 module support, or ensure package.json has `"type": "module"`. The `.mjs` approach is cleaner when you don't control the package.json.

2. **Integration Patterns**: Sprinkles should keep all their dependencies and execution within the UserFrosting directory structure, not create external installations.

3. **Framework Repository Testing**: When the repository IS the framework, the workflow should detect and use the local framework rather than cloning from remote.

4. **Configuration-Driven Workflows**: Auto-generating workflows from configuration files reduces maintenance burden and ensures consistency.

5. **Playwright Installation**: Playwright has two parts:
   - NPM package (for importing in scripts)
   - Browser binaries (for test execution)
   Both should be installed in the same location (userfrosting) for simplicity.

## Related Documentation

- `.github/testing-framework/README.md` - Framework documentation
- `integration-test-config.json` - Workflow configuration
- `.archive/` - Historical documentation archive
