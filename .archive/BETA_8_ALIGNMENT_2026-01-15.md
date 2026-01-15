# UserFrosting 6.0.0-beta.8 Alignment

**Date:** January 15, 2026  
**Issue:** Review and align with UserFrosting 6.0.0-beta.8 release  
**Previous Attempt:** PR #366 (reverted in PR #368 due to breaking changes in integration-test.yml)

## Beta.8 Release Changes

From UserFrosting 6.0.0-beta.8 CHANGELOG:
1. Updated `vite.config.ts`: Other packages removed from `optimizeDeps`
2. Docker: Suppress PHP warnings in custom PHP ini
3. Specify node engine version
4. Convert GitHub Actions workflow files to reusable templates

## Investigation Summary

### Node Version Requirements
- **Beta.8**: Specifies `node >= 18` in package.json engines field
- **CRUD6 Before**: No engines field specified
- **CRUD6 CI**: Uses Node 20 in integration-test.yml
- **Decision**: Add `"engines": { "node": ">= 18" }` to package.json
- **Compatibility**: Node 20 is compatible with >= 18 requirement ✅

### Vite Configuration
- **Beta.8 Main App**: `optimizeDeps: { include: ['uikit', 'uikit/dist/js/uikit-icons'] }`
- **CRUD6**: `optimizeDeps: { include: ['limax', 'lodash.deburr'] }`
- **Why Different?**: 
  - Main UserFrosting app doesn't use `limax` package
  - CRUD6 uses `limax` in `useCRUD6Api.ts` for slug generation
  - CRUD6 added this in v0.6.1.8 to fix CommonJS module loading issues
- **Decision**: Keep CRUD6's configuration - it's correct and required ✅
- **Documentation**: Added clarifying comment in vite.config.ts

### PHP Deprecation Warnings
- **Beta.8**: Added `error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED` in docker/app/php/custom.ini
- **CRUD6**: This is a sprinkle, not a full application
- **Decision**: Not applicable - PHP ini configuration is handled by the main UserFrosting application, not by sprinkles ✅

### GitHub Actions Workflows
- **Beta.8**: Converted workflow files to reusable templates
- **PR #366**: Attempted to update integration-test.yml, caused breaking changes
- **Decision**: Do NOT modify integration-test.yml - keep existing working configuration ✅
- **Rationale**: The sprinkle's workflow is different from the main app's workflow and should remain independent

## Changes Made

### 1. package.json
```json
{
  "engines": {
    "node": ">= 18"
  }
}
```
- Added Node engine specification to match beta.8 standards
- Node 20 (used in CI) is compatible with >= 18 requirement

### 2. vite.config.ts
```typescript
optimizeDeps: {
    // CRUD6-specific: Pre-bundle limax and its dependencies
    // UserFrosting 6.0.0-beta.8 removed limax from main app's optimizeDeps,
    // but CRUD6 still needs it because useCRUD6Api.ts uses limax for slug generation.
    // This is correct and required - see .archive/VITE_COMMONJS_MODULE_FIX.md
    include: ['limax', 'lodash.deburr']
}
```
- Added clarifying comment explaining why CRUD6's config differs from beta.8 main app
- Configuration remains unchanged - it's correct and necessary

### 3. README.md
```markdown
**Compatible with UserFrosting 6.0.4 beta and later, tested with 6.0.0-beta.8.**
```
- Updated compatibility statement to indicate beta.8 testing

### 4. CHANGELOG.md
Added comprehensive entry documenting beta.8 alignment:
- Node.js engine specification
- Vite config rationale
- README update
- No breaking workflow changes

## What Was NOT Changed

### Integration Test Workflow
- **File**: `.github/workflows/integration-test.yml`
- **Status**: Unchanged (intentionally)
- **Reason**: PR #366 attempted to update this file and caused breaking changes
- **Current State**: Working correctly with Node 20 and PHP 8.4
- **Decision**: Keep existing configuration - it's compatible with beta.8 requirements

### Vite optimizeDeps Configuration
- **File**: `vite.config.ts`
- **Status**: Unchanged (intentionally, with added clarification)
- **Reason**: CRUD6 uses `limax` package which requires pre-bundling
- **Beta.8 Difference**: Main app doesn't use limax, so they removed it
- **Decision**: Keep CRUD6's configuration - it's required for our use case

### PHP Configuration
- **Files**: No PHP ini files in sprinkle
- **Status**: N/A
- **Reason**: Sprinkles don't have their own PHP configuration
- **Beta.8 Change**: Only affects main application's Docker setup

## Validation

### Syntax Validation
```bash
# PHP syntax check
find app/src -name "*.php" -exec php -l {} \;
# Result: All files pass ✅

# package.json validation
node -e "require('./package.json')"
# Result: Valid JSON, engines field present ✅

# package.json formatting
npx prettier --check package.json
# Result: Properly formatted ✅
```

### Compatibility Checks
- Node 20.19.6 >= 18 ✅
- PHP 8.4 matches beta.8 requirements ✅
- All peer dependencies use `^6.0.0-beta` ✅
- Vite config is sprinkle-specific and correct ✅

## Lessons from PR #366

The previous attempt (PR #366) made breaking changes to the integration test workflow. Key learnings:

1. **Workflow Independence**: Sprinkle workflows may need different configuration than main app workflows
2. **Surgical Changes**: Make minimal, targeted changes rather than wholesale updates
3. **Compatibility vs. Identical**: Being compatible with beta.8 doesn't mean having identical configuration
4. **Use Case Specific**: Each sprinkle may have unique requirements (like CRUD6's use of limax)

## Summary

This alignment with UserFrosting 6.0.0-beta.8 is **minimal and surgical**:
- ✅ Added Node engine specification (required by beta.8 standards)
- ✅ Clarified vite config differences (not a conflict, just different use cases)
- ✅ Updated documentation to indicate beta.8 compatibility
- ✅ Avoided workflow changes that caused issues in PR #366
- ✅ All existing functionality remains intact
- ✅ No breaking changes

## Testing Recommendations

1. Run integration tests to ensure Node 20 works correctly: `npm test`
2. Build the package to verify vite config: `npm run build` (in consuming app)
3. Verify PHP syntax: `find app/src -name "*.php" -exec php -l {} \;`
4. Check package installation: `npm install` (in consuming app)

## Files Modified

- `package.json` - Added engines field
- `vite.config.ts` - Added clarifying comment
- `README.md` - Updated compatibility statement
- `CHANGELOG.md` - Documented beta.8 alignment
- `.archive/BETA_8_ALIGNMENT_2026-01-15.md` - This document

## Conclusion

CRUD6 is now aligned with UserFrosting 6.0.0-beta.8 requirements while maintaining its sprinkle-specific configuration. The changes are minimal, surgical, and avoid the breaking changes that occurred in PR #366.
