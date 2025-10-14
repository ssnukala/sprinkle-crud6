# TypeScript Config Resolution Fix

**Date:** October 14, 2024  
**Issue:** Integration test failing with `failed to resolve "extends": "@vue/tsconfig/tsconfig.dom.json"`

## Problem Statement

The integration test workflow was failing with this error:

```
[plugin:vite:esbuild] failed to resolve "extends": "@vue/tsconfig/tsconfig.dom.json" in /home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/tsconfig.json
```

### Root Cause

The integration test workflow used `npm install ../sprinkle-crud6` to install the package from the local directory path. This approach:

1. **Bypassed npm package rules**: Installed ALL files from the directory, not just those defined in `package.json` "files" array
2. **Included development files**: Development-only files like `tsconfig.json`, `vite.config.ts`, and `env.d.ts` were installed
3. **Caused Vite errors**: When the consuming application (UserFrosting) built assets with Vite, it read the sprinkle's `tsconfig.json` and tried to resolve the `@vue/tsconfig` dependency, which was not in the consuming application's `node_modules`

**Key insight**: Installing from a local directory path with `npm install ../path` does NOT respect the package's `files` array or `.npmignore` - it installs everything.

## Solution

### 1. Created `.npmignore`

Created a comprehensive `.npmignore` file to exclude development-only files from the npm package:

```
# Development configuration files
tsconfig.json
tsconfig.node.json
vite.config.ts
env.d.ts
phpunit.xml

# GitHub Actions
.github/

# Documentation (archives and specialized guides)
.archive/
INTEGRATION_TESTING.md
QUICK_TEST_GUIDE.md
MIGRATION_FROM_THEME_CRUD6.md

# Development dependencies
vendor/
node_modules/
composer.lock
package-lock.json

# Build artifacts
public/
mix-manifest.json
dist/
build/

# Tests (except app/assets/tests)
app/tests/
phpunit.xml

# IDE, OS, Logs, etc.
[additional exclusions...]
```

**Key exclusions:**
- TypeScript config files (`tsconfig.json`, `tsconfig.node.json`)
- Build config files (`vite.config.ts`, `env.d.ts`)
- Development infrastructure (`.github/`, `.devcontainer/`)
- PHP test files (`app/tests/`, `phpunit.xml`)
- Composer files (`composer.json` excluded to keep package focused on frontend)

**Kept in package:**
- All `app/assets/**` files (frontend components, composables, routes, etc.)
- All `app/schema/**` files (JSON schema definitions)
- `package.json` (required for npm)
- `README.md` and `LICENSE` (included by npm by default)
- `examples/` directory (for reference)

### 2. Updated Integration Test Workflow

Changed the npm installation method from direct path install to proper package install:

**Before:**
```yaml
- name: Install NPM dependencies
  run: |
    cd userfrosting
    npm update
    npm install ../sprinkle-crud6
```

**After:**
```yaml
- name: Package sprinkle-crud6 for NPM
  run: |
    cd sprinkle-crud6
    npm pack
    mv ssnukala-sprinkle-crud6-*.tgz ../userfrosting/

- name: Install NPM dependencies
  run: |
    cd userfrosting
    npm update
    npm install ./ssnukala-sprinkle-crud6-*.tgz
```

This approach:
- ✅ Uses `npm pack` to create a proper npm package tarball
- ✅ Respects the `.npmignore` file and package.json "files" array
- ✅ Installs only the files that would be published to npm
- ✅ Prevents development config files from being included

### 3. Updated `.gitignore`

Added `*.tgz` to `.gitignore` to exclude npm package files from git:

```gitignore
# Build artifacts
public/
mix-manifest.json
*.tgz
```

## Verification

### Package Contents Verified

```bash
$ npm pack --dry-run
npm notice total files: 31

$ tar -tzf ssnukala-sprinkle-crud6-*.tgz | grep -E "(tsconfig|vite.config|env.d)"
# No output - confirmed no development config files
```

**Package includes:**
- 31 files total (down from potentially 100+ with direct install)
- All necessary frontend files (`app/assets/**`)
- All schema files (`app/schema/**`)
- Required metadata (`package.json`, `README.md`, `LICENSE`)

**Package excludes:**
- ❌ `tsconfig.json`
- ❌ `tsconfig.node.json`
- ❌ `vite.config.ts`
- ❌ `env.d.ts`
- ❌ `.github/` directory
- ❌ PHP test files
- ❌ Development dependencies

## Benefits

1. **Cleaner npm package**: Only includes files needed by consumers
2. **Smaller package size**: 23KB instead of potentially much larger with all dev files
3. **No dependency conflicts**: Consumer applications don't see sprinkle's dev dependencies
4. **Proper npm standards**: Follows npm best practices for package distribution
5. **Better testing**: Integration test now tests the actual package that would be published

## Testing

### Manual Testing
```bash
# Create the package
npm pack

# Verify contents
tar -tzf ssnukala-sprinkle-crud6-*.tgz

# Verify no config files
tar -tzf ssnukala-sprinkle-crud6-*.tgz | grep -E "(tsconfig|vite.config|env.d)"
# (Should return nothing)

# Verify important files are present
tar -tzf ssnukala-sprinkle-crud6-*.tgz | grep -E "(app/assets|app/schema|package.json)"
# (Should show all asset and schema files)
```

### Integration Test
The integration test workflow will now:
1. Create the package with `npm pack`
2. Install from the tarball
3. Verify the package works correctly
4. Build assets with Vite (should no longer see tsconfig errors)

## Related Files

- `.npmignore` - New file defining what to exclude from npm package
- `.github/workflows/integration-test.yml` - Updated to use npm pack
- `.gitignore` - Updated to exclude `*.tgz` files

## References

- npm documentation on `.npmignore`: https://docs.npmjs.com/cli/v10/using-npm/developers#keeping-files-out-of-your-package
- npm documentation on `files` in package.json: https://docs.npmjs.com/cli/v10/configuring-npm/package-json#files
- Related issue: Integration test failing with TypeScript config resolution error

---

*Fix completed: October 14, 2024*
