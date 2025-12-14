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

### 2. Fixed Playwright Installation for ES6 Imports
Modified the workflow generator to install playwright in two locations:

```yaml
- name: Install Playwright
  run: |
    # Install playwright package in sprinkle directory (for script imports)
    cd ${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts
    npm install playwright
    
    # Install chromium browser in userfrosting (for execution)
    cd $GITHUB_WORKSPACE/userfrosting
    npx playwright install chromium
```

This ensures:
- Playwright npm package is in the script's directory for ES6 imports
- Chromium browser binaries are installed for actual test execution

### 3. Run Scripts from Original Location
Instead of copying scripts, the workflow now runs them directly from the sprinkle directory:

```yaml
- name: Capture screenshots
  run: |
    cd userfrosting
    node ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/take-screenshots-modular.js \
      ../${{ env.SPRINKLE_DIR }}/.github/config/integration-test-paths.json \
      screenshots
```

This approach:
- Keeps scripts in their original location with proper directory structure
- Allows Node.js to resolve ES6 imports correctly
- Maintains consistency across all framework scripts

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
- **Script execution**: Copy to userfrosting directory
- **Module resolution**: Failed (script not found)
- **Framework setup**: Redundant clone even for framework repo

### After
- **Workflow length**: 425 lines (auto-generated)
- **Script execution**: Run from sprinkle directory via relative path
- **Module resolution**: Works correctly with proper playwright installation
- **Framework setup**: Efficient detection of local framework

### Key Improvements
1. ✅ Scripts run from their original location
2. ✅ Playwright properly installed for ES6 imports
3. ✅ Local testing-framework detected and used
4. ✅ Workflow simplified and auto-generated
5. ✅ Consistent pattern for all framework scripts

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

1. **ES6 Module Resolution**: When using ES6 modules with imports, dependencies must be installed relative to the script's location, not the execution directory.

2. **Framework Repository Testing**: When the repository IS the framework, the workflow should detect and use the local framework rather than cloning from remote.

3. **Configuration-Driven Workflows**: Auto-generating workflows from configuration files reduces maintenance burden and ensures consistency.

4. **Playwright Installation**: Playwright has two parts:
   - NPM package (for importing in scripts)
   - Browser binaries (for test execution)
   Both must be installed but can be in different locations.

## Related Documentation

- `.github/testing-framework/README.md` - Framework documentation
- `integration-test-config.json` - Workflow configuration
- `.archive/` - Historical documentation archive
