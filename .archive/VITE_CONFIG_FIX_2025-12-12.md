# Vite Config Fix - December 12, 2025

## Issue
GitHub Actions workflow run [#20157684535](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20157684535/job/57863454528) was failing with a Vite configuration error:

```
Error: Expected ":" but found ","
    vite.config.ts:53:19:
      53 │             'limax', 'lodash.deburr',
         │                    ^
         ╵                    :
failed to load config from /home/runner/work/sprinkle-crud6/sprinkle-crud6/userfrosting/vite.config.ts
```

## Root Cause
The workflow file `.github/workflows/integration-test.yml` had a simplified sed command that was adding both `limax` and `lodash.deburr` packages on a single line:

```bash
sed -i "/include: \\[/a \\            \'limax\', \'lodash.deburr\'," vite.config.ts
```

This produced invalid TypeScript syntax:
```typescript
include: [
            'limax', 'lodash.deburr',  // ❌ Invalid: both on one line after the bracket
]
```

## Solution
Restored the comprehensive awk-based solution from the backup file `.archive/pre-framework-migration/integration-test.yml.backup` (lines 144-220).

The working solution handles multiple scenarios:

1. **Already configured**: Checks if limax is already present and skips if found
2. **Single-line arrays**: `include: ['existing']` → `include: ['existing', 'limax', 'lodash.deburr']`
3. **Multi-line arrays**: Adds items as separate lines before the closing bracket with proper formatting
4. **Missing include array**: Creates the include array within existing optimizeDeps
5. **Missing optimizeDeps**: Creates the entire optimizeDeps section after plugins block

## Valid Output Examples

### Multi-line array (correct):
```typescript
optimizeDeps: {
    include: [
        'existing-pkg',
        'limax',
        'lodash.deburr'
    ]
}
```

### Single-line array (correct):
```typescript
optimizeDeps: {
    include: ['existing-pkg', 'limax', 'lodash.deburr']
}
```

### New optimizeDeps section (correct):
```typescript
plugins: [vue(), ViteYaml()],
optimizeDeps: {
    // Include CommonJS dependencies for sprinkle-crud6
    // limax uses lodash.deburr which is a CommonJS module
    include: ['limax', 'lodash.deburr']
},
```

## Testing
All three cases were tested locally with awk commands and confirmed to produce valid TypeScript syntax.

## Files Changed
- `.github/workflows/integration-test.yml` - Restored working vite.config.ts configuration step

## References
- Backup file: `.archive/pre-framework-migration/integration-test.yml.backup` (lines 144-220)
- Failed workflow: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20157684535
- PR: (to be added)
