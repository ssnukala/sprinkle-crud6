# Migration Guide: From theme-crud6 to sprinkle-crud6

## Overview

The frontend components from `@ssnukala/theme-crud6` have been merged into `@ssnukala/sprinkle-crud6`. This consolidation follows the UserFrosting 6 sprinkle pattern (similar to sprinkle-admin) and simplifies the development workflow.

**The `theme-crud6` repository will be retired.** All future development will occur in `sprinkle-crud6`.

## What Changed

### Before (with theme-crud6)

```json
{
  "dependencies": {
    "@ssnukala/sprinkle-crud6": "^0.4.3",
    "@ssnukala/theme-crud6": "github:ssnukala/theme-crud6#main"
  }
}
```

### After (with integrated frontend)

```json
{
  "dependencies": {
    "@ssnukala/sprinkle-crud6": "^0.5.0"
  }
}
```

## Migration Steps

### 1. Update package.json

Remove the `@ssnukala/theme-crud6` dependency:

```bash
npm uninstall @ssnukala/theme-crud6
```

Update sprinkle-crud6 to the latest version:

```bash
npm install @ssnukala/sprinkle-crud6@latest
```

### 2. Update Import Paths

Import paths remain the same! No code changes needed for imports:

```typescript
// Still works - no changes needed
import { useCRUD6Api, useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import { CRUD6CreateModal, CRUD6EditModal } from '@ssnukala/sprinkle-crud6/components'
```

### 3. Component Usage

Components are now automatically registered by the sprinkle plugin. No changes needed to your template code:

```vue
<template>
  <!-- These still work exactly the same -->
  <UFCRUD6ListPage />
  <UFCRUD6RowPage />
  <UFCRUD6CreateModal :model="'users'" :schema="schema" @saved="refresh" />
</template>
```

### 4. Routes

Routes are included and automatically registered. No configuration changes needed.

## Benefits of the Merge

### Simplified Dependency Management
- **Before**: Two packages to maintain and version
- **After**: Single package with all functionality

### Consistent with UserFrosting 6 Patterns
- Follows the same structure as `@userfrosting/sprinkle-admin`
- All frontend and backend code in one sprinkle
- Easier to understand and maintain

### Better Development Experience
- Single repository for issues and PRs
- Synchronized versioning between frontend and backend
- No dependency conflicts

### Reduced Installation Complexity
- One `npm install` command instead of two
- No need to manage separate theme repository
- Peer dependencies properly declared

## Component Structure

All components are now located in the sprinkle:

```
app/assets/
├── components/
│   └── Pages/
│       └── CRUD6/
│           └── Base/
│               ├── CreateModal.vue
│               ├── EditModal.vue
│               ├── DeleteModal.vue
│               ├── Form.vue
│               ├── Info.vue
│               └── Users.vue
├── views/
│   ├── PageList.vue
│   └── PageRow.vue
├── composables/
│   ├── useCRUD6Api.ts
│   ├── useCRUD6Schema.ts
│   └── useCRUD6sApi.ts
├── plugins/
│   └── crud6.ts
└── routes/
    └── CRUD6Routes.ts
```

## Breaking Changes

### None for Most Users

If you were using the packages normally, there are **no breaking changes**. The API remains the same:

- ✅ Import paths unchanged
- ✅ Component names unchanged
- ✅ Composable functions unchanged
- ✅ Props and events unchanged

### For Advanced Users

If you were directly importing from `@ssnukala/theme-crud6` internals, you may need to update import paths to use `@ssnukala/sprinkle-crud6` instead.

## Testing Your Migration

1. Remove theme-crud6 dependency
2. Update sprinkle-crud6
3. Run your application
4. Test CRUD operations:
   - Navigate to `/crud6/{your-model}`
   - Create a new record
   - Edit an existing record
   - Delete a record
5. Verify no console errors

## Need Help?

If you encounter issues during migration:

1. Check the [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
2. Review the [README](README.md) for updated documentation
3. Open a new issue if needed

## Timeline

- **Current**: Both packages work (theme-crud6 still available)
- **Next Release**: Frontend merged into sprinkle-crud6
- **Future**: theme-crud6 repository archived

## Version Support

| Package | Version | Status |
|---------|---------|--------|
| sprinkle-crud6 | 0.4.x | ⚠️ Requires theme-crud6 |
| sprinkle-crud6 | 0.5.x+ | ✅ All-in-one (recommended) |
| theme-crud6 | * | ⚠️ Deprecated |
