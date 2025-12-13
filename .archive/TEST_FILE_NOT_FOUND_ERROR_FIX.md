# Fix for "Test file not found" Error in CI Workflow

## Issue
After fixing the `status_any` validation issue, the CI workflow still showed an error:

```
| Step 3 | Authenticated | API | ❌ FAIL | Total: 62, Passed: 0, Warnings: 0, Failed: 62 |
| Step 4 | Authenticated | Frontend | ❌ ERROR | Test file not found |
```

User question: "what is the 'Test file not found' error?"

## Root Cause

The workflow has 4 test steps that run sequentially:
1. Test unauthenticated API paths
2. Test unauthenticated frontend paths
3. Test authenticated API paths
4. Test authenticated frontend paths

Each step redirects output to a file (e.g., `/tmp/test-results-auth-frontend.txt`).

When test-paths.php detects test failures, it exits with code 1 (line 609 in test-paths.php):

```php
if ($failedTests > 0) {
    echo "❌ Some tests failed (actual code/SQL errors detected)\n";
    exit(1);  // ← This causes the workflow step to fail
}
```

The workflow execution flow was:
1. ✅ Step 1 (unauth API) - completed
2. ✅ Step 2 (unauth frontend) - completed  
3. ❌ Step 3 (auth API) - ALL 62 tests failed → script exits with code 1 → **workflow step fails and stops**
4. ⏭️ Step 4 (auth frontend) - **NEVER RUNS** because Step 3 failed
5. ✅ Summary step - runs due to `if: always()` but can't find `/tmp/test-results-auth-frontend.txt`

Result: "Test file not found" error for Step 4.

## Why the Summary Step Still Runs

The "Generate test summary table" step has `if: always()` condition (line 496 in workflow):

```yaml
- name: Generate test summary table
  if: always()  # ← Runs even if previous steps fail
  run: |
    # Generates summary from all test result files
    extract_summary "/tmp/test-results-auth-frontend.txt" "Authenticated" "Frontend"
```

The `extract_summary` function checks if the file exists (line 517):

```bash
if [ -f "$file" ]; then
    # Extract test counts and generate summary
else
    echo "| Step $step | $auth_status | $type | ❌ ERROR | Test file not found |"
fi
```

Since Step 4 never ran, the file doesn't exist → "Test file not found" error.

## Solution

The test-paths.php script already has a `CONTINUE_ON_FAILURE` environment variable designed for exactly this scenario (lines 589-603):

```php
$continue_on_failure = getenv('CONTINUE_ON_FAILURE') ?: 'false';
if ($continue_on_failure === 'true') {
    // In report mode, always exit 0 to allow the workflow to continue
    if ($failedTests > 0) {
        echo "⚠️  Some tests failed (actual code/SQL errors detected)\n";
        echo "   Continuing workflow to collect all test results...\n";
    }
    exit(0);  // ← Always exit 0, even with failures
} else {
    // In strict mode, fail on errors
    if ($failedTests > 0) {
        exit(1);  // ← Exit with failure code
    }
}
```

### Implementation

Add `CONTINUE_ON_FAILURE=true` environment variable to all test steps in the workflow:

```yaml
- name: Test authenticated API paths
  env:
    CONTINUE_ON_FAILURE: 'true'  # ← Added this
  run: |
    php test-paths.php ... > /tmp/test-results-auth-api.txt 2>&1
    cat /tmp/test-results-auth-api.txt
```

This was added to all 4 test steps:
- Test unauthenticated API paths
- Test unauthenticated frontend paths
- Test authenticated API paths
- Test authenticated frontend paths

## Result

With `CONTINUE_ON_FAILURE=true`:

1. ✅ Step 1 (unauth API) - completes, exits 0
2. ✅ Step 2 (unauth frontend) - completes, exits 0
3. ✅ Step 3 (auth API) - completes with failures, but exits 0 (not 1)
4. ✅ Step 4 (auth frontend) - **NOW RUNS** because Step 3 didn't fail the workflow
5. ✅ Summary step - has all 4 result files, generates complete summary
6. ❌ Summary step - fails at line 586-588 if total_failed > 0

The workflow now:
- Collects complete test results from all steps
- Generates a comprehensive summary table
- Fails at the summary step (by design) if any tests failed
- Provides visibility into all test results, not just the first failure

## Verification

Test the environment variable behavior:

```bash
# Without CONTINUE_ON_FAILURE (exits 1 on failure)
php test-paths.php config.json unauth api
echo $?  # Returns: 1

# With CONTINUE_ON_FAILURE=true (exits 0 even on failure)
CONTINUE_ON_FAILURE=true php test-paths.php config.json unauth api
echo $?  # Returns: 0
```

Output changes:
- Without: `❌ Some tests failed (actual code/SQL errors detected)`
- With: `⚠️  Some tests failed (actual code/SQL errors detected)\n   Continuing workflow to collect all test results...`

## Impact

### Before Fix
```
Step 3: Authenticated API - 62 failed → workflow stops
Step 4: Authenticated Frontend - never runs → "Test file not found"
Summary: Incomplete results
```

### After Fix
```
Step 1: Unauthenticated API - completes with results
Step 2: Unauthenticated Frontend - completes with results
Step 3: Authenticated API - completes with results (62 failed)
Step 4: Authenticated Frontend - completes with results
Summary: Complete results from all steps → fails if needed
```

## Related Issues

Note: The authenticated API tests showing "62 failed, 0 passed" is a **separate issue** likely related to:
- Authentication cookie conversion not working properly
- Admin user lacking required permissions
- Database test data (ID 100) not existing
- API endpoints returning 401/403 instead of expected status codes

This fix ensures we can see the complete picture of what's failing, making it easier to diagnose the root cause of authentication/permission issues.

## Date
December 13, 2024

## Commit
47362aa - Set CONTINUE_ON_FAILURE env var for all test steps to prevent workflow stoppage

## Files Changed
- `.github/workflows/integration-test.yml` - Added `CONTINUE_ON_FAILURE=true` env var to 4 test steps
