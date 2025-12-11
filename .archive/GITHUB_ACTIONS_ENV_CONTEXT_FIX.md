# GitHub Actions Workflow Fix - ENV Context in Services Section

**Date**: 2025-12-11  
**Issue**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20145941751  
**Error**: Invalid workflow file: .github/workflows/integration-test.yml#L1 (Line: 31, Col: 16): Unrecognized named-value: 'env'

## Problem Description

The GitHub Actions workflow file `.github/workflows/integration-test.yml` was failing validation with the error:

```
Unrecognized named-value: 'env'. Located at position 1 within expression: env.MYSQL_VERSION
```

This occurred on line 31 in the services section:

```yaml
services:
  mysql:
    image: mysql:${{ env.MYSQL_VERSION }}  # ❌ INVALID - env context not supported here
```

## Root Cause

GitHub Actions **does not support** the `env` context in job-level `services` definitions. The `env` context is only available in workflow steps, not in service container definitions.

From GitHub Actions documentation:
- ✅ `env` context is available in: steps, job outputs, and job-level conditionals
- ❌ `env` context is NOT available in: services, container definitions, strategy matrices

## Solution

Since the workflow file is auto-generated from `integration-test-config.json`, the fix required updating the generator script `.github/testing-framework/scripts/generate-workflow.js`.

### Changes Made

1. **Updated workflow generator** (`.github/testing-framework/scripts/generate-workflow.js`):
   - Extract MySQL version from config at template generation time
   - Hardcode the version directly in the services section
   - Removed `MYSQL_VERSION` from the env section (no longer needed)

2. **Regenerated workflow file** (`.github/workflows/integration-test.yml`):
   - Changed from: `image: mysql:${{ env.MYSQL_VERSION }}`
   - Changed to: `image: mysql:8.0`

### Before (Broken)

```javascript
// generate-workflow.js
env:
  MYSQL_VERSION: "${testing?.mysql_version || '8.0'}"

services:
  mysql:
    image: mysql:\${{ env.MYSQL_VERSION }}  // ❌ Invalid
```

### After (Fixed)

```javascript
// generate-workflow.js
const mysqlVersion = testing?.mysql_version || '8.0';

// env section - MYSQL_VERSION removed

services:
  mysql:
    image: mysql:${mysqlVersion}  // ✅ Hardcoded at generation time
```

## Testing

The fix was validated by:
1. Regenerating the workflow file using the updated generator script
2. Verifying the MySQL version is hardcoded on line 30: `image: mysql:8.0`
3. Confirming no remaining `MYSQL_VERSION` references in the services section
4. Confirming the workflow structure is valid for GitHub Actions

## Impact

- **Minimal**: The MySQL version is still configurable via `integration-test-config.json`
- **Breaking Changes**: None - the workflow behaves identically, just with a valid syntax
- **Future Updates**: To change MySQL version, update `integration-test-config.json` and regenerate the workflow

## Related Files

- `.github/testing-framework/scripts/generate-workflow.js` - Generator script
- `.github/workflows/integration-test.yml` - Generated workflow file
- `integration-test-config.json` - Configuration source
