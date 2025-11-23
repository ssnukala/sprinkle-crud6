# Lodash.deburr Vite Module Loading Fix

## Issue Summary

**Problem**: Frontend login form fails to load with error:
```
The requested module '/assets/@fs/.../node_modules/lodash.deburr/index.js' does not provide an export named 'default'
```

**GitHub Actions Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19616965794/job/56170983416

**Symptoms**:
- Login page loads but shows only `<div id="app"></div>`
- Browser console shows uncaught exception for lodash.deburr
- No input fields render on the page
- Vue app fails to initialize

## Root Cause

1. **CRUD6 sprinkle** uses `limax` package for slug generation (in `app/assets/composables/useCRUD6Api.ts`)
2. **limax** internally depends on `lodash.deburr`
3. **lodash.deburr** is a **CommonJS module** (not ES module)
4. **Vite** tries to import it as an ES module with default export, which fails

## Technical Details

### Import Chain
```
useCRUD6Api.ts
  └─> import slug from 'limax'  (line 4)
       └─> limax internally uses lodash.deburr
            └─> lodash.deburr is CommonJS (no default export)
                 └─> Vite error: module does not provide export named 'default'
```

### Why This Happens
- Modern bundlers (Vite) expect ES modules
- CommonJS modules need special handling to work with ES module imports
- Vite's `optimizeDeps.include` option pre-bundles CommonJS modules into ES modules

## Solution

### For Sprinkle Development
The sprinkle's own `vite.config.ts` already includes the fix:
```typescript
export default defineConfig({
    optimizeDeps: {
        // Include CommonJS dependencies that need to be pre-bundled
        // limax uses lodash.deburr which is a CommonJS module
        include: ['limax', 'lodash.deburr']
    }
})
```

**Note**: This works for developing the sprinkle itself, but not when the sprinkle is installed in a UserFrosting application.

### For Integration Testing / Host Applications
When sprinkle-crud6 is installed in a UserFrosting application, the **host application's** `vite.config.ts` must include the CommonJS dependencies:

```typescript
export default defineConfig({
    optimizeDeps: {
        include: [
            // ... other dependencies
            'limax',
            'lodash.deburr'
        ]
    }
})
```

### Integration Test Fix
The integration test workflow (`.github/workflows/integration-test.yml`) now includes a step to automatically configure the UserFrosting application's `vite.config.ts`:

```yaml
- name: Configure vite.config.ts for CommonJS dependencies
  run: |
    cd userfrosting
    # Add optimizeDeps configuration for limax and lodash.deburr
    # (see workflow file for complete implementation)
```

This step:
1. Checks if `optimizeDeps` section exists in vite.config.ts
2. Adds or updates the `include` array with `limax` and `lodash.deburr`
3. Verifies the configuration by displaying the updated file

## Timeline

- **PR #220**: Added fix to sprinkle's vite.config.ts
  - Commit: 94450b202a8a349d1a05ea84dbd1a05afc278f10
  - Fixed local development, but not integration tests

- **Current Fix**: Added step to integration test workflow
  - Configures UserFrosting application's vite.config.ts during CI
  - Ensures tests pass in GitHub Actions

## Verification

After applying the fix, verify:
1. ✅ Login form loads properly
2. ✅ No JavaScript errors in browser console
3. ✅ Vue app initializes successfully
4. ✅ Input fields render correctly
5. ✅ Integration tests pass

## Documentation for Users

If developers encounter this error when using sprinkle-crud6 in their own UserFrosting applications, they should add the following to their application's `vite.config.ts`:

```typescript
export default defineConfig({
    // ... other config
    optimizeDeps: {
        include: [
            'limax',
            'lodash.deburr'
        ]
    }
})
```

## References

- Vite Dependency Pre-Bundling: https://vitejs.dev/guide/dep-pre-bundling.html
- CommonJS vs ES Modules: https://nodejs.org/api/esm.html
- PR #220: Fix Vite module loading error for CommonJS dependencies
