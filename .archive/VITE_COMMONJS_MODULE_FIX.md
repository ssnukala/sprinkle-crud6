# Vite CommonJS Module Loading Fix

## Issue Summary
**Date:** November 23, 2025  
**Workflow Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19616777511/job/56170535875  
**Error:** Integration tests failing with Vite module loading error

## Problem Description

### Symptoms
- Integration tests fail at the login page
- Browser console shows error: `The requested module '/assets/@fs/.../node_modules/lodash.deburr/index.js?v=c927cf0e' does not provide an export named 'default'`
- Login form never renders (no input fields found)
- Vue app fails to initialize properly

### Root Cause
The `limax` package (v4.1.0), used for slug generation in `useCRUD6Api.ts`, internally depends on `lodash.deburr`. Both of these are **CommonJS modules** that don't provide proper ES module exports.

When Vite (the frontend build tool) tries to load these modules in development mode, it expects ES module syntax (`export default`), but these old packages only provide CommonJS exports (`module.exports`).

### Dependency Chain
```
useCRUD6Api.ts
  ↓ imports
limax (v4.1.0) - CommonJS module
  ↓ depends on
lodash.deburr - CommonJS module (no default export)
  ↓ causes
Vite error - cannot import CommonJS as ES module
```

## Solution

### Fix Applied
Modified `vite.config.ts` to add dependency optimization configuration:

```typescript
export default defineConfig({
    plugins: [vue(), ViteYaml()],
    optimizeDeps: {
        // Include CommonJS dependencies that need to be pre-bundled
        // limax uses lodash.deburr which is a CommonJS module
        include: ['limax', 'lodash.deburr']
    },
    // ... rest of config
})
```

### How It Works
1. **Pre-bundling**: Vite will now pre-bundle `limax` and `lodash.deburr` during dev server startup
2. **Module Conversion**: The pre-bundling process converts CommonJS modules to ES modules
3. **Import Resolution**: Vite can now properly import these modules with ES module syntax
4. **Dependency Cache**: Pre-bundled dependencies are cached in `node_modules/.vite/deps/`

### Benefits
- ✅ Login page loads correctly
- ✅ Integration tests can proceed
- ✅ No runtime errors in browser console
- ✅ Improved dev server startup (dependencies are optimized once)

## Testing

### Verification Steps
1. Run integration test workflow
2. Check that Vite server starts without errors
3. Verify login page loads with form inputs
4. Confirm browser console has no module loading errors

### Expected Outcome
- Vite pre-bundles `limax` and `lodash.deburr` on first run
- Login page renders successfully
- Integration tests can authenticate and take screenshots

## Technical Background

### Vite Dependency Optimization
Vite's `optimizeDeps` configuration allows you to:
- Pre-bundle dependencies that need special handling
- Convert CommonJS modules to ES modules
- Improve cold-start performance
- Handle legacy packages

### When to Use
Add packages to `optimizeDeps.include` when:
- Package is CommonJS and imported as ES module
- Package has many internal modules (improves performance)
- Package causes module resolution errors
- Package needs to be externalized for SSR

### References
- [Vite Dependency Pre-Bundling](https://vitejs.dev/guide/dep-pre-bundling.html)
- [Vite Config - optimizeDeps](https://vitejs.dev/config/dep-options.html#optimizedeps-include)
- [CommonJS to ES Module Migration](https://nodejs.org/api/esm.html#interoperability-with-commonjs)

## Related Files
- `vite.config.ts` - Vite configuration with fix
- `app/assets/composables/useCRUD6Api.ts` - Uses `limax` for slug generation
- `package.json` - Lists `limax` as peer dependency

## Alternative Solutions Considered

### 1. Replace limax with ES module alternative
**Pros:** Modern package, better Vite compatibility  
**Cons:** Breaking change, would need to update all slug generation code  
**Decision:** Not pursued to minimize changes

### 2. Use dynamic imports
**Pros:** Lazy load CommonJS modules  
**Cons:** Complicates composable logic, async complexity  
**Decision:** Not pursued - optimization config is cleaner

### 3. Configure Vite to skip optimization
**Pros:** Simple config change  
**Cons:** Doesn't solve the core module loading issue  
**Decision:** Not pursued - doesn't address root cause

## Future Considerations

### Package Migration
Consider migrating from `limax` to a modern ES module alternative such as:
- `slugify` (ES module, actively maintained)
- `@sindresorhus/slugify` (ES module, TypeScript support)
- Custom slug implementation using native String methods

### Benefits of Migration
- Native ES module support
- No Vite configuration needed
- Smaller bundle size
- Better TypeScript types
- Active maintenance

### Migration Impact
- Would require updating `useCRUD6Api.ts`
- Need to verify slug generation behavior matches
- Update tests for slug generation
- Update package.json dependencies
