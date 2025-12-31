# GitHub Actions v3 to v4 Upgrade

**Date:** 2025-12-31  
**Issue:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20620372510/job/59221008004  
**PR:** copilot/fix-deprecated-upload-artifact

## Problem

GitHub Actions workflows were using deprecated v3 versions of artifact and cache actions:
- `actions/upload-artifact@v3` - Deprecated as of 2024-04-16
- `actions/cache@v3` - Also deprecated

Error message:
```
Error: This request has been automatically failed because it uses a deprecated 
version of `actions/upload-artifact: v3`. 
Learn more: https://github.blog/changelog/2024-04-16-deprecation-notice-v3-of-the-artifact-actions/
```

## Solution

Upgraded all deprecated actions from v3 to v4:

### Files Modified

1. **`.github/workflows/phpunit-tests.yml`**
   - `actions/cache@v3` → `actions/cache@v4` (line 49)
   - `actions/upload-artifact@v3` → `actions/upload-artifact@v4` (line 105)

2. **`.github/testing-framework/docs/WORKFLOW_EXAMPLE.md`**
   - Updated documentation examples to use v4 (lines 292, 298)

### Files Already Using v4

- `.github/workflows/integration-test.yml` - Already using `actions/upload-artifact@v4`
- All workflow templates in `.github/testing-framework/` - Already using v4

## Verification

All workflow files now use current action versions:
- ✅ `actions/checkout@v4`
- ✅ `actions/setup-node@v4`
- ✅ `actions/cache@v4` (upgraded)
- ✅ `actions/upload-artifact@v4` (upgraded)
- ✅ `shivammathur/setup-php@v2`

## Testing

- YAML syntax validated with yamllint
- Git diff reviewed to confirm only version numbers changed
- No breaking changes in v4 API that affect our usage

## Migration Notes

The v4 upgrade is backward compatible for our use cases:
- No changes needed to artifact upload/download parameters
- Cache functionality remains the same
- All existing workflows will continue to work

## References

- [GitHub Blog: Deprecation notice v3 of artifact actions](https://github.blog/changelog/2024-04-16-deprecation-notice-v3-of-the-artifact-actions/)
- [actions/upload-artifact v4 release notes](https://github.com/actions/upload-artifact/releases/tag/v4.0.0)
- [actions/cache v4 release notes](https://github.com/actions/cache/releases/tag/v4.0.0)
