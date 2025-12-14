# PHPUnit Test Order and Path Fix

**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20214545467/job/58025421021

**Date:** 2025-12-14

## Problem Statement

The unit test script in the GitHub Actions workflow was failing with the following error:

```
/home/runner/work/_temp/79b86870-4b94-4df3-961c-508622284e90.sh: line 40: ../../../vendor/bin/phpunit: No such file or directory
Error: Process completed with exit code 127.
```

### Issues Identified

1. **Incorrect PHPUnit Path**: The script was trying to use `../../../vendor/bin/phpunit` which resolved to a non-existent path
2. **Test Execution Order**: PHPUnit tests were running at the very end of the workflow, after all frontend and server startup steps
3. **Path Confusion**: The workflow runs from `userfrosting/vendor/ssnukala/sprinkle-crud6/` but the path didn't correctly point to `userfrosting/vendor/bin/phpunit`

## Solution

### 1. Path Fix

**Directory Structure:**
```
/home/runner/work/sprinkle-crud6/sprinkle-crud6/
├── sprinkle-crud6/                    (checked out sprinkle source)
│   └── phpunit.xml
└── userfrosting/                      (UserFrosting application)
    └── vendor/
        ├── bin/
        │   └── phpunit                ← Target binary
        └── ssnukala/
            └── sprinkle-crud6/        ← Working directory
                ├── app/tests/
                └── phpunit.xml
```

**Path Calculation:**
- Working Directory: `userfrosting/vendor/ssnukala/sprinkle-crud6/`
- PHPUnit Location: `userfrosting/vendor/bin/phpunit`
- Correct Path: `../../bin/phpunit`

**Resolution:**
```
Current:           userfrosting/vendor/ssnukala/sprinkle-crud6/
../                userfrosting/vendor/ssnukala/
../../             userfrosting/vendor/
../../bin/phpunit  userfrosting/vendor/bin/phpunit ✅
```

**Old Incorrect Path:**
```
Current:                  userfrosting/vendor/ssnukala/sprinkle-crud6/
../../../                 userfrosting/
../../../vendor/bin/      ❌ Does not exist!
```

### 2. Test Order Fix

**Before:**
- PHPUnit tests ran at the very end after:
  - ✓ Seed data validation
  - ✓ Seed idempotency tests
  - ✓ Frontend asset build
  - ✓ PHP server startup
  - ✓ Vite dev server startup
  - ✓ API path tests
  - ✓ Frontend path tests
  - ✓ Authenticated tests
  - ✓ Screenshot capture
  - → PHPUnit tests (LAST) ❌

**After:**
- PHPUnit tests now run earlier:
  - ✓ Seed data validation
  - ✓ Seed idempotency tests
  - → PHPUnit tests (MOVED HERE) ✅
  - ✓ Frontend asset build
  - ✓ Server startup
  - ✓ Integration tests
  - ✓ Screenshot capture

**Benefits:**
1. Tests run earlier, catching issues sooner
2. No need for running servers (unit/integration tests use framework directly)
3. Faster failure detection
4. Logical test progression: database → tests → frontend → UI

## Changes Made

### File Modified
- `.github/workflows/integration-test.yml`

### Changes
1. **Moved PHPUnit test step** from line 815 to line 549 (right after "Test seed idempotency")
2. **Fixed PHPUnit path** from `../../../vendor/bin/phpunit` to `../../bin/phpunit`
3. **Removed duplicate step** at the end of the workflow
4. **Updated comments** to reflect the test environment state at this point (no server running yet)

### Diff Statistics
```
.github/workflows/integration-test.yml | 121 +++++++++------
 1 file changed, 60 insertions(+), 61 deletions(-)
```

## Verification

### YAML Validation
```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))"
✅ YAML syntax is valid
```

### Expected Workflow Behavior
After these changes, the workflow should:
1. ✅ Find PHPUnit binary at correct location
2. ✅ Run PHPUnit tests successfully
3. ✅ Execute tests earlier in workflow
4. ✅ Continue to frontend build and integration tests if PHPUnit passes

## Testing
The fix will be validated in the next CI run. The PHPUnit step should now:
- Execute successfully after seed data validation
- Use the correct path to find the PHPUnit binary
- Run all CRUD6 unit and integration tests
- Report results before moving to frontend build

## Related
- PR: #314 (Enable PHPUnit testing in integration workflow)
- Workflow Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20214545467
- Failed Job: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20214545467/job/58025421021
