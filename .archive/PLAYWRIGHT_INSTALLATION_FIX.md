# Playwright Installation Fix

**Date:** January 14, 2026  
**Issue:** Integration test failing with `ERR_MODULE_NOT_FOUND: Cannot find package 'playwright'`  
**PR:** [Related to CI failure](https://github.com/ssnukala/sprinkle-crud6/actions/runs/21012077661/job/60409202371)

## Problem

The integration test workflow was failing with the following error:

```
Error [ERR_MODULE_NOT_FOUND]: Cannot find package 'playwright' imported from /home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/.github/crud6-framework/scripts/take-screenshots-modular.js
```

## Root Cause

The workflow step "Install Playwright" was only running:

```bash
npx playwright install chromium
```

This command only downloads the Chromium browser binaries but does **NOT** install the `playwright` npm package itself. When the `take-screenshots-modular.js` script tried to `import { chromium } from 'playwright';`, it failed because the package wasn't installed.

## Solution

Added `npm install playwright` before the browser installation command:

```bash
npm install playwright
npx playwright install chromium
```

This ensures:
1. The playwright npm package is installed first
2. Then the Chromium browser binaries are downloaded

## Files Modified

1. **`.github/workflows/integration-test.yml`** - The actual workflow file used by GitHub Actions
2. **`.github/testing-framework/scripts/generate-workflow.js`** - The workflow generator script to ensure future regenerations include the fix

## Verification

### Template Files Already Correct

The following template files already had the correct pattern:
- `.github/testing-framework/crud6-workflow-template.yml` ✅
- `.github/testing-framework/workflow-template.yml` ✅

### Documentation Already Correct

The following documentation files already documented the correct two-step process:
- `.github/testing-framework/docs/CONFIGURATION.md` ✅
- `.github/testing-framework/docs/INSTALLATION.md` ✅
- `.github/testing-framework/docs/WORKFLOW_EXAMPLE.md` ✅
- `.github/testing-framework/docs/API_REFERENCE.md` ✅

### Scripts That Use Playwright

The following scripts import playwright and will now work correctly:
- `.github/testing-framework/scripts/take-screenshots-modular.js`
- `.github/testing-framework/scripts/login-admin.js`
- `.github/testing-framework/scripts/test-authenticated-unified.js`

## Testing

The fix will be validated by running the integration test workflow in CI, which should now pass the "Install Playwright" and "Capture screenshots" steps successfully.

## Lessons Learned

- `npx playwright install <browser>` only downloads browser binaries
- The `playwright` npm package must be installed separately with `npm install playwright`
- Always ensure workflow generation scripts match the templates
- Manual edits to generated workflows can introduce inconsistencies
