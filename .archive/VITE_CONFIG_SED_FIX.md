# Vite Config Sed Command Fix

## Issue
GitHub Actions integration test workflow failed with TypeScript syntax error when configuring `vite.config.ts`:

```
Error: Expected ":" but found ","
    vite.config.ts:53:15:
      53 │         'limax',
         │                ^
         ╵                :
```

**Workflow Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19617234948/job/56171638913

## Root Cause

The sed command in `.github/workflows/integration-test.yml` (line 166) used an incorrect range pattern:

```bash
sed -i "/plugins: \[/,/\],/a \    optimizeDeps: {...}," vite.config.ts
```

**Problem:** The range pattern `/plugins: \[/,/\],/` matches from `plugins: [` to **EVERY** occurrence of `],` in the file, causing the `optimizeDeps` block to be inserted multiple times:

```typescript
export default defineConfig({
    plugins: [vue(), ViteYaml()],
    optimizeDeps: { ... },  // ← Inserted here (correct)
    test: {
    optimizeDeps: { ... },  // ← Also inserted here (wrong!)
        coverage: {
    optimizeDeps: { ... },  // ← And here (wrong!)
            include: ['app/assets/**/*.*'],
    optimizeDeps: { ... },  // ← And here (wrong!)
            exclude: ['app/assets/tests/**/*.*']
        },
        exclude: [
            ...configDefaults.exclude,
            './vendor/**/*.*',
        ],
    }
})
```

This created invalid TypeScript syntax where object properties appeared outside of objects.

## Solution

Replaced the sed command with an awk command that handles both single-line and multi-line plugins arrays:

```bash
awk '
  /plugins:/ {in_plugins=1} 
  in_plugins && /\],/ {
    print;
    print "    optimizeDeps: {";
    print "        // Include CommonJS dependencies for sprinkle-crud6";
    print "        // limax uses lodash.deburr which is a CommonJS module";
    print "        include: ['\''limax'\'', '\''lodash.deburr'\'']";
    print "    },";
    in_plugins=0;
    next;
  } 
  {print}
' vite.config.ts > vite.config.ts.tmp && mv vite.config.ts.tmp vite.config.ts
```

**Fix:** The awk script:
1. Detects when it enters the plugins section
2. Finds the first closing `],` after `plugins:`
3. Inserts the `optimizeDeps` block after that line
4. Works for both single-line (`plugins: [vue()],`) and multi-line plugins arrays

## Additional Improvements

1. **Idempotency Check:** Added check to skip modification if limax is already configured:
   ```bash
   if grep -q "'limax'" vite.config.ts || grep -q '"limax"' vite.config.ts; then
     echo "limax already in vite.config.ts, skipping configuration"
   ```

2. **Fixed Include Array Modification:** Changed from append to substitution for adding to existing include arrays:
   ```bash
   # Before (broken - appends outside array)
   sed -i "/include: \[/a \        'limax',\n        'lodash.deburr'," vite.config.ts
   
   # After (fixed - substitutes within array)
   sed -i "s/include: \[\(.*\)\]/include: [\1, 'limax', 'lodash.deburr']/" vite.config.ts
   ```

## Testing

All scenarios tested and verified, including both single-line and multi-line plugins arrays:

### 1. No optimizeDeps - Single-line Plugins (Most Common)
Creates complete optimizeDeps block after single-line plugins:
```typescript
export default defineConfig({
    plugins: [vue(), ViteYaml()],
    optimizeDeps: {
        include: ['limax', 'lodash.deburr']
    },
    // ... rest of config
})
```

### 2. No optimizeDeps - Multi-line Plugins
Creates complete optimizeDeps block after multi-line plugins:
```typescript
export default defineConfig({
    plugins: [
        vue(),
        ViteYaml()
    ],
    optimizeDeps: {
        include: ['limax', 'lodash.deburr']
    },
    // ... rest of config
})
```

### 3. optimizeDeps Exists, No Include
Adds include array to existing optimizeDeps:
```typescript
export default defineConfig({
    plugins: [vue()],
    optimizeDeps: {
        include: ['limax', 'lodash.deburr'],
        exclude: ['some-package']
    }
})
```

### 4. Both optimizeDeps and Include Exist
Appends to existing include array:
```typescript
export default defineConfig({
    plugins: [vue()],
    optimizeDeps: {
        include: ['existing-pkg', 'limax', 'lodash.deburr']
    }
})
```

### 5. Idempotency Test
Skips modification when limax is already present (prevents duplicates).

## Files Changed

- `.github/workflows/integration-test.yml` (lines 144-175)
  - Fixed sed command for creating optimizeDeps block
  - Fixed sed command for adding to existing include array
  - Added idempotency check

## Verification

The fix ensures that:
- ✅ Valid TypeScript syntax is generated
- ✅ optimizeDeps block inserted exactly once
- ✅ Idempotent (safe to run multiple times)
- ✅ Handles all three configuration scenarios
- ✅ CommonJS dependencies (limax, lodash.deburr) properly pre-bundled by Vite

## Related

- **Issue:** Integration test workflow fails with vite.config.ts syntax error
- **Workflow Step:** "Configure vite.config.ts for CommonJS dependencies"
- **Context:** sprinkle-crud6 uses limax which depends on lodash.deburr (CommonJS modules that need pre-bundling)
