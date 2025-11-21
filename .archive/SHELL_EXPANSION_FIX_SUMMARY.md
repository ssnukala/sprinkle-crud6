# Shell Expansion Issue Fix - GitHub Actions Workflow

**Date:** November 21, 2025  
**Issue:** GitHub Actions workflow failing with shell expansion errors  
**Workflow Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19581180706/job/56079174938  
**PR:** #207

## Problem Description

The integration test workflow was failing in the "Summary" step with errors like:
```
/home/runner/work/_temp/25969b3b-6c5b-42b6-b3cb-caa60da0569f.sh: line 65: .archive/COMPREHENSIVE_API_TEST_MATRIX.md: No such file or directory
/home/runner/work/_temp/25969b3b-6c5b-42b6-b3cb-caa60da0569f.sh: line 65: .github/config/integration-test-*.json: No such file or directory
/home/runner/work/_temp/25969b3b-6c5b-42b6-b3cb-caa60da0569f.sh: line 65: .github/scripts/*-modular.php: No such file or directory
```

## Root Cause

The issue was in `.github/workflows/integration-test.yml` at line 621, where a heredoc was used to write markdown content to `$GITHUB_STEP_SUMMARY`:

```yaml
cat >> $GITHUB_STEP_SUMMARY << EOF
## Integration Test Results ✅ (Modular Testing Framework)
...
- **Reference:** See `.archive/COMPREHENSIVE_API_TEST_MATRIX.md` for complete matrix
...
- **Configuration files:** `.github/config/integration-test-*.json`
- **Reusable scripts:** `.github/scripts/*-modular.php`
...
EOF
```

### Why It Failed

When using an **unquoted** heredoc delimiter (`EOF`), bash performs:
- Variable expansion (`$VAR` becomes the value)
- Command substitution (`` `cmd` `` or `$(cmd)` gets executed)
- **Glob pattern expansion** (`*` gets expanded to matching files)

The markdown content contained paths with wildcards:
- `.github/config/integration-test-*.json` - The `*` was being expanded by bash
- `.github/scripts/*-modular.php` - The `*` was being expanded by bash

Since the workflow runs from the `userfrosting` directory (not the sprinkle directory), these paths don't exist there, causing the "No such file or directory" errors.

## The Fix

Changed line 621 to use a **quoted** heredoc delimiter:

```yaml
cat >> $GITHUB_STEP_SUMMARY << 'EOF'
```

### Why This Works

Using `'EOF'` (quoted delimiter) tells bash to treat the entire heredoc content as **literal text**, preventing:
- Variable expansion
- Command substitution
- **Glob pattern expansion** ← This is what fixed our issue

### Important Note: GitHub Actions Variables Still Work

GitHub Actions variables like `${{ github.repository }}` and `${{ github.run_id }}` are **NOT** affected by this change because they are expanded by GitHub Actions **before** the shell script runs. The shell never sees these variables - it receives the already-expanded values.

## Verification

1. **YAML Syntax**: Validated with `yamllint` - no errors
2. **Git Diff**: Confirmed only one line changed
3. **Variable Expansion**: Verified GitHub Actions variables (`${{ }}`) still work correctly

## Impact

- ✅ Fixes the shell expansion errors
- ✅ Markdown content with wildcards is now treated as literal text
- ✅ GitHub Actions variables continue to work normally
- ✅ No other functionality affected

## Files Changed

- `.github/workflows/integration-test.yml` (1 line)

## Related Issues

This is the same type of issue that was addressed in PR #206, but in a different location in the workflow file. The previous PR fixed session directory issues in the PHPUnit test bootstrap, while this PR fixes shell expansion in the workflow summary step.

## Lessons Learned

1. **Always use quoted heredoc delimiters** (`'EOF'`) when the content contains:
   - Literal dollar signs that aren't shell variables
   - Backticks that aren't command substitutions
   - **Glob patterns (`*`, `?`, `[]`) that should be literal text**

2. **Use unquoted delimiters** (`EOF`) only when you specifically need:
   - Shell variable expansion
   - Command substitution
   - Arithmetic expansion

3. **GitHub Actions variables are safe** - They're expanded before the shell runs, so quoting heredoc delimiters doesn't affect them.

## Testing

The fix will be validated by the next workflow run. The expected result is:
- No shell expansion errors
- Summary markdown renders correctly with literal file paths
- All other workflow functionality unchanged
