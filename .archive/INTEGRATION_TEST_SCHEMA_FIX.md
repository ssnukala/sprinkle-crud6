# Integration Test Schema Directory Fix

## Issue
Integration test workflow was failing at the "Generate Schema-Driven Tests" step with error:
```
❌ Error: ENOENT: no such file or directory, scandir 'app/schema/crud6'
```

**GitHub Actions Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20102014102/job/57675219595

## Root Cause
The test generator script (`.github/scripts/generate-schema-tests.js`) expects to find schema files in `app/schema/crud6` directory within the sprinkle-crud6 repository. However:

1. **By design**, this sprinkle doesn't include its own schema files in `app/schema/crud6`
2. Schema files are only provided as **examples** in `examples/schema/` directory
3. The integration test workflow was missing a step to copy these example schemas into the expected location

## Workflow Structure
The integration test has two separate schema copy operations:

1. **Line ~222**: Copies schemas from sprinkle-crud6's `examples/schema` → UserFrosting app's `app/schema/crud6`
   - Purpose: Makes schemas available to the UserFrosting application for runtime testing
   
2. **Line ~612** (NEW): Copies schemas from sprinkle-crud6's `examples/schema` → sprinkle-crud6's `app/schema/crud6`
   - Purpose: Makes schemas available to the test generator script
   - Also merges locale messages for model-specific translations

## Solution Implemented
Added a new workflow step **"Copy schema and locale to sprinkle-crud6 for test generation"** at line 612 of `.github/workflows/integration-test.yml`.

This step performs two operations:

### 1. Copy Schema Files
Copies required schema files from `examples/schema/` to `app/schema/crud6/`:
- users.json
- groups.json
- roles.json
- permissions.json
- activities.json

### 2. Merge Locale Messages
Merges locale translation messages from `examples/locale/en_US/messages.php` into `app/locale/en_US/messages.php`:
- Creates directory structure if needed
- Preserves existing app locale messages
- Adds model-specific translations from examples
- Uses PHP's `array_merge()` to combine both sources

## Why Two Separate Copy Operations?

### UserFrosting App Copy (Line ~222)
- **Location**: `userfrosting/app/schema/crud6/`
- **Purpose**: Runtime schema access for the UserFrosting application
- **Used by**: Backend API endpoints, frontend pages, database operations
- **Timing**: Early in workflow (before app startup)

### Sprinkle-crud6 Copy (Line ~612) 
- **Location**: `sprinkle-crud6/app/schema/crud6/`
- **Purpose**: Test generation and validation
- **Used by**: `.github/scripts/generate-schema-tests.js`
- **Timing**: Just before test generation step

## Files Modified
- `.github/workflows/integration-test.yml` - Added new step with schema and locale copying logic

## Testing
- ✅ YAML syntax validated with Python yaml parser
- ✅ All required schema files verified to exist in `examples/schema/`
- ✅ Locale messages file verified to exist in `examples/locale/en_US/`
- ⏳ Integration test workflow will validate on next run

## Related
- **Problem Statement**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20102014102/job/57675219595
- **Fix Commit**: 422d488
- **PR Branch**: copilot/fix-missing-schema-folders

## Future Considerations
If the list of required schemas changes, update the `REQUIRED_SCHEMAS` variable in both copy operations:
- Line ~233 (UserFrosting app copy)
- Line ~626 (sprinkle-crud6 copy for testing)
