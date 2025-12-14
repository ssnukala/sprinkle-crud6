# Revert PR #309 and PR #310, Add Dynamic ID Extraction for Detail Testing

**Date:** 2024-12-14  
**Issue:** The last 2 PRs broke the integration testing login functionality

## Problem Statement

The integration tests were failing with login errors after PR #309 and PR #310 were merged:
- **PR #310:** Integration test module path fix
- **PR #309:** Logging for activities 404 errors

The near-successful integration test (https://github.com/ssnukala/sprinkle-crud6/actions/runs/20201455652/job/57993294443#step:32:1067) just needed to address the missing records issue, but the subsequent PRs broke the login process.

## Solution

### 1. Reverted PRs #309 and #310

Used `git revert` to cleanly remove the changes from both PRs:

```bash
git revert -m 1 --no-edit c68263c  # PR #310
git revert -m 1 --no-edit c8fd6e4  # PR #309
```

**Files Removed:**
- `.archive/INTEGRATION_TEST_MODULE_PATH_FIX.md`
- `.archive/DYNAMIC_ID_EXTRACTION_SOLUTION.md`
- `.archive/PR_SUMMARY_404_DEBUGGING.md`
- `.archive/SEED_SQL_ID_VERIFICATION.md`
- `.github/config/test-record-validation.json`
- `.github/testing-framework/scripts/determine-test-ids.php`
- `.github/testing-framework/scripts/extract-test-ids-from-api.php`
- `.github/testing-framework/scripts/validate-test-records.php`

**Files Restored:**
- `.github/workflows/integration-test.yml` (reverted to pre-PR state)
- `.github/testing-framework/scripts/generate-workflow.js` (reverted to pre-PR state)
- `.github/testing-framework/scripts/test-authenticated-unified.js` (reverted to pre-PR state)
- `app/src/Middlewares/CRUD6Injector.php` (reverted to pre-PR state)
- `app/config/default.php` (reverted to pre-PR state)

### 2. Added New Functionality (Without Modifying Playwright/Authentication)

**Key Requirements:**
- Do NOT modify Playwright or login process (they were working)
- Add logging for API calls with missing data (404s as warnings, not errors)
- Use list API responses to extract IDs for detail page testing

**Implementation:**

#### A. ID Extraction from List Responses

Added tracking object and helper functions to `test-authenticated-unified.js`:

```javascript
// Track extracted IDs from list API responses
const extractedModelIds = {};

// Helper functions
function extractModelName(path)      // Extract model name from API path
function isListEndpoint(path)        // Check if path is a list endpoint
function isDetailEndpoint(path)      // Check if path has an ID
function replaceIdInPath(path, modelName)  // Replace hardcoded ID with extracted ID
```

#### B. Dynamic ID Replacement

When testing detail endpoints (e.g., `/api/crud6/activities/100`), the script now:
1. Checks if a list endpoint for the same model was tested previously
2. If an ID was extracted from the list response, replaces the hardcoded ID (100) with the extracted ID
3. Logs the ID replacement for debugging

Example output:
```
🔄 Using extracted ID 23 for activities (was 100)
```

#### C. ID Extraction Logic

After a successful list API response, the script:
1. Checks if the response is an object (JSON)
2. Looks for records in multiple formats:
   - `responseBody.rows` (Sprunje format)
   - `responseBody.data` (alternative format)
   - `responseBody` itself (if it's an array)
3. Extracts the ID from the **last record** in the list
4. Stores it in `extractedModelIds` for use in subsequent detail calls

Example output:
```
📋 Extracted ID 23 from activities list (5 records found)
```

#### D. 404 Handling as Warnings

Changed 404 responses to be treated as warnings (not failures):

```javascript
const isMissingData = status === 404;

if (isMissingData) {
    result = 'WARNING';
    resultIcon = '⚠️';
}
```

Log output for 404s:
```
⚠️ WARNING: Record not found (status 404)
Note: This likely indicates missing test data, not an API error
Tip: Ensure test data exists for model 'activities'
```

## Code Changes

### File: `.github/testing-framework/scripts/test-authenticated-unified.js`

**Changes:**
- Added 116 lines of new code
- Modified 2 lines
- Total: 775 lines (was 661 lines)

**Key Sections:**
1. **Lines 296-350:** Helper functions for ID extraction and path manipulation
2. **Lines 351-372:** Dynamic ID replacement logic before making API calls
3. **Lines 465-470:** 404 detection and warning classification
4. **Lines 510-535:** ID extraction from list responses
5. **Lines 536-550:** Enhanced 404 logging with context

## Testing Strategy

The changes preserve the existing Playwright authentication logic while adding:

1. **Dynamic Test Data:** Uses actual IDs from the database instead of hardcoded values
2. **Better Error Context:** 404s are logged as missing data warnings, not test failures
3. **Sequential Dependencies:** List endpoints must be tested before detail endpoints for the same model
4. **Fallback Behavior:** If no ID is extracted, uses the original hardcoded ID

## Expected Behavior

### Before Changes
- Detail endpoints like `/api/crud6/activities/100` would fail with 404 if record ID 100 doesn't exist
- 404s were treated as test failures
- No dynamic ID extraction

### After Changes
- Detail endpoints use IDs extracted from list responses
- 404s are logged as warnings (missing data context)
- Tests adapt to actual database state
- Login and authentication remain unchanged

## Benefits

1. **More Resilient Tests:** Adapts to database state instead of requiring specific IDs
2. **Better Diagnostics:** 404s clearly indicate missing data, not code errors
3. **No Breaking Changes:** Playwright and authentication logic untouched
4. **Maintains Login Functionality:** Reverted the PRs that broke login

## Files Changed

**Commit 1:** `Revert "Merge pull request #310..."`
**Commit 2:** `Revert "Merge pull request #309..."`
**Commit 3:** `Add ID extraction from list API responses for dynamic detail testing`

Total files changed: 12 files (11 reverted, 1 enhanced)

## Related Issues

- Original issue: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20211398838/job/58017771860
- Near-successful test: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20201455652/job/57993294443#step:32:1067
- PR #309: Add logging for activities 404
- PR #310: Fix integration testing error

## Verification

To verify the changes work:
1. Run integration tests - login should succeed
2. Check that list endpoints extract IDs
3. Verify detail endpoints use extracted IDs
4. Confirm 404s are logged as warnings, not failures

```bash
# Run integration test
.github/workflows/integration-test.yml
```
