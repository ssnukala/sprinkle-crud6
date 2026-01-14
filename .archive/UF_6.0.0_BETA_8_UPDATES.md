# UserFrosting 6.0.0-beta.8 Compatibility Updates

**Date**: 2026-01-14  
**Release**: [UserFrosting 6.0.0-beta.8](https://github.com/userfrosting/UserFrosting/releases/tag/6.0.0-beta.8)

## Summary

This document describes the updates made to sprinkle-crud6 to maintain compatibility with UserFrosting 6.0.0-beta.8.

## UserFrosting 6.0.0-beta.8 Changes

The following changes were made in UserFrosting 6.0.0-beta.8:

1. **Updated vite.config.ts**: Removed monorepo packages from `optimizeDeps` (only external packages like uikit remain)
2. **Docker**: Suppress PHP deprecation warnings in custom PHP ini
3. **Node engine version**: Specified node engine version in package.json (>= 18)
4. **GitHub Actions**: Convert workflow files to reusable templates

## Changes Made to sprinkle-crud6

### 1. Package Configuration (`package.json`)

**Added node engine specification**:
```json
"engines": {
  "node": ">= 18"
}
```

This aligns with UserFrosting's requirement and ensures Node.js 18 or later is used.

### 2. Vite Configuration (`vite.config.ts`)

**Clarified optimizeDeps configuration**:
- Added comment explaining that `limax` and `lodash.deburr` are external dependencies specific to CRUD6
- These are NOT part of the UserFrosting monorepo and should remain in optimizeDeps
- UserFrosting removed monorepo packages from optimizeDeps, but CRUD6's dependencies are external

```typescript
optimizeDeps: {
    // Pre-bundle limax and its dependencies for optimal performance
    // This improves Vite cold-start time and ensures consistent behavior
    // Note: These are external dependencies specific to CRUD6, not UF monorepo packages
    include: ['limax', 'lodash.deburr']
},
```

### 3. Docker Configuration (`.devcontainer/Dockerfile`)

**Updated PHP and Node.js versions**:
- PHP: 8.2 → 8.4 (aligns with UF beta.7 update)
- Node: 20 → 22 (aligns with UF beta.7 update)

**Suppressed deprecation warnings**:
```dockerfile
error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
```

This matches UserFrosting's Docker configuration and reduces noise from PHP 8.4 deprecation warnings during development.

### 4. GitHub Actions Workflows

**Updated Node.js version to 22**:
- `.github/workflows/integration-test.yml`
- `.github/workflows/vitest-tests.yml`

**Updated configuration file**:
- `integration-test-config.json`: Changed `node_version` from "20" to "22"

**Regenerated workflow**:
- Re-ran `generate-workflow.js` to apply updated configuration

## Testing & Validation

All changes have been validated:

✅ PHP syntax check: All files pass  
✅ JSON validation: `package.json` and `integration-test-config.json` are valid  
✅ vite.config.ts: Maintains CRUD6-specific optimizeDeps configuration  
✅ Docker: Updated to PHP 8.4 and Node 22  
✅ GitHub Actions: Updated to Node 22

## Compatibility Notes

### vite.config.ts - Important

The `optimizeDeps.include` configuration in CRUD6's `vite.config.ts` is **different from and correct compared to** UserFrosting's configuration:

- **UserFrosting**: Removed monorepo packages, kept only external packages (uikit)
- **CRUD6**: Keeps external packages specific to CRUD6 functionality (limax, lodash.deburr)

This is **intentional and correct** - each sprinkle may have its own external dependencies that need optimization.

### Version Requirements

After these updates, sprinkle-crud6 requires:
- **PHP**: 8.4 or later
- **Node.js**: 18 or later (22 recommended)
- **UserFrosting**: 6.0.0-beta.8 or later

## Migration Guide

If you're updating from an earlier version:

1. **Update Node.js**: Install Node.js 22 (or at least 18)
2. **Update PHP**: Install PHP 8.4
3. **Update dependencies**: Run `composer update` and `npm install`
4. **Rebuild containers**: If using Docker, rebuild your containers with `docker-compose build`

No code changes are required - all updates are to configuration and tooling.

## Files Changed

- `package.json` - Added node engine specification
- `vite.config.ts` - Clarified optimizeDeps comment
- `.devcontainer/Dockerfile` - Updated PHP 8.4, Node 22, suppressed deprecations
- `.github/workflows/integration-test.yml` - Updated Node 22
- `.github/workflows/vitest-tests.yml` - Updated Node 22
- `integration-test-config.json` - Updated Node 22

## References

- [UserFrosting 6.0.0-beta.8 Release](https://github.com/userfrosting/UserFrosting/releases/tag/6.0.0-beta.8)
- [UserFrosting 6.0.0-beta.7 Release](https://github.com/userfrosting/UserFrosting/releases/tag/6.0.0-beta.7) (PHP 8.4 update)
- [CRUD6 CHANGELOG.md](../CHANGELOG.md)
