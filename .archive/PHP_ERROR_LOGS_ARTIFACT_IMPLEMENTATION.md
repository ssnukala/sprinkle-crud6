# PHP Error Logs Artifact Implementation

**Date:** 2025-12-10  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20102656573/job/57677567603  
**Problem:** The "Capture and display PHP error logs" step only shows the last 50 lines in the workflow output, making it difficult to see all errors for comprehensive debugging.

## Solution

Added a new GitHub Actions workflow step to upload complete PHP error logs as downloadable artifacts, while keeping the existing step that displays the last 50 lines in the workflow output for quick visibility.

## Changes Made

### 1. New Artifact Upload Step

Added after the "Upload browser console errors as artifact" step:

```yaml
- name: Upload PHP error logs as artifacts
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: php-error-logs
    path: |
      userfrosting/app/logs/*.log
      userfrosting/app/storage/logs/*.log
    if-no-files-found: ignore
    retention-days: 30
```

**Key Features:**
- **Runs always:** `if: always()` ensures logs are uploaded even when tests fail
- **Complete logs:** Captures ALL `.log` files from both log directories
- **No failure on missing files:** `if-no-files-found: ignore` prevents errors if no logs exist
- **30-day retention:** Matches the retention period of other artifacts (screenshots, network requests)

### 2. Updated Console Output Documentation

Added to the "Summary" step's console output:

```bash
echo "📋 **View Complete PHP Error Logs:**"
echo "   Direct link: https://github.com/${{ github.repository }}/actions/runs/${{ github.run_id }}"
echo "   Look for 'Artifacts' section at the bottom of the page"
echo "   Download 'php-error-logs' artifact"
echo "   Contains complete UserFrosting log files (userfrosting.log and any other .log files)"
echo "   Note: Workflow output shows last 50 lines; artifact contains full logs for detailed debugging"
```

### 3. Updated GitHub Actions Step Summary

Added new section to `$GITHUB_STEP_SUMMARY`:

```markdown
### 📋 View Complete PHP Error Logs
Complete UserFrosting log files have been uploaded for detailed debugging.

**To view the complete PHP error logs:**
1. Scroll to the bottom of the workflow run page (link above)
2. Look for the **Artifacts** section
3. Click on **php-error-logs** to download
4. Extract the ZIP file to view complete log files:
   - `userfrosting.log` - Main UserFrosting application log
   - Any other `.log` files from `app/logs/` and `app/storage/logs/`
5. Note: The workflow output displays last 50 lines; artifact contains full logs

**What's included:**
- Complete `userfrosting.log` file with all PHP errors, warnings, and debug messages
- All log files from `userfrosting/app/logs/` directory
- All log files from `userfrosting/app/storage/logs/` directory
- Full stack traces and error details for comprehensive debugging

> **Note:** PHP error logs are retained for 30 days
```

## Benefits

### Two-Tier Debugging Approach

1. **Quick visibility (existing):**
   - Step: "Capture and display PHP error logs"
   - Shows last 50 lines in workflow output
   - Good for quick checks and common issues
   - No need to download anything

2. **Complete debugging (new):**
   - Step: "Upload PHP error logs as artifacts"
   - Provides complete log files as downloadable artifacts
   - Essential for complex issues requiring full context
   - Includes all error messages, stack traces, and debug output

### User Experience

- **Easy access:** Artifacts appear at the bottom of the workflow run page
- **Clear documentation:** Both console output and step summary explain how to access logs
- **Consistent pattern:** Follows the same artifact upload pattern as screenshots and network requests
- **No extra work:** Logs are automatically collected and uploaded with no manual intervention needed

## File Locations Captured

The artifact includes all `.log` files from:

1. **`userfrosting/app/logs/`** directory:
   - `userfrosting.log` - Main application log
   - Any custom log files created by the application

2. **`userfrosting/app/storage/logs/`** directory:
   - UserFrosting 6 standard log location
   - May contain additional framework logs

## Testing

- ✅ YAML syntax validated with yamllint
- ✅ YAML structure validated with Python YAML parser
- ✅ File paths match UserFrosting 6 log directory structure
- ✅ Follows existing GitHub Actions artifact upload patterns
- ✅ Consistent with other artifact configurations in the workflow

## Related Artifacts

The workflow now provides these downloadable artifacts:

1. **integration-test-screenshots** - Frontend UI screenshots
2. **network-requests-summary** - Network request tracking data
3. **browser-console-errors** - Browser console error messages
4. **php-error-logs** - Complete PHP and UserFrosting logs (NEW)

All artifacts are retained for 30 days and use `if: always()` to ensure they're uploaded even on test failures.

## Next Steps

When the workflow runs, users can:

1. Navigate to: https://github.com/ssnukala/sprinkle-crud6/actions
2. Click on any workflow run
3. Scroll to the bottom of the page
4. Click "php-error-logs" in the Artifacts section
5. Download and extract the ZIP file
6. Review complete `userfrosting.log` and any other log files

This provides comprehensive debugging information that was previously unavailable or truncated in the workflow output.
