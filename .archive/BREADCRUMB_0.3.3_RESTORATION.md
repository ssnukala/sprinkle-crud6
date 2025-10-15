# Breadcrumb Fix - Restore 0.3.3 Working Configuration

## Date
October 15, 2025

## Issue
User reported: "the breadcrumb still does not show, https://github.com/ssnukala/sprinkle-crud6/tree/0.3.3 has a working version that shows the breadcrumbs but without the translation. please review release 0.3.3"

## Analysis

### Comparison: 0.3.3 (Working) vs Current (Broken)

**Version 0.3.3 (working breadcrumbs showing {{model}} placeholder):**
```typescript
{
    path: '',
    name: 'crud6.list',
    meta: {
        permission: {
            slug: 'uri_crud6'
        }
        // NO title or description here
    },
    component: () => import('../views/PageList.vue')
}
```

**Current version (breadcrumbs not showing):**
```typescript
{
    path: '',
    name: 'crud6.list',
    meta: {
        permission: {
            slug: 'uri_crud6'
        },
        title: 'CRUD6.PAGE',           // Added - causing issue
        description: 'CRUD6.PAGE_DESCRIPTION'  // Added - causing issue
    },
    component: () => import('../views/PageList.vue')
}
```

## Root Cause

The list route should NOT have `title` and `description` in its route meta. Here's why:

1. **PageList.vue dynamically sets page metadata** in `onMounted()`:
   ```typescript
   page.title = schema.value?.title || model.value.charAt(0).toUpperCase() + model.value.slice(1)
   page.description = schema.value.description || `A listing of...`
   ```

2. **When route meta has static values**, they conflict with dynamic updates
3. **Translation keys resolve at build time**, not runtime, so `CRUD6.PAGE` becomes `{{model}}` 
4. **Vue component can't override route meta** properly when breadcrumbs initialize

## Solution

Restore the 0.3.3 pattern:
1. **Remove** `title` and `description` from list route meta
2. **Keep** `title` and `description` in parent route meta (for route hierarchy)
3. **Keep** `title` and `description` in view route meta
4. Change view route description from `'CRUD6.PAGE_DESCRIPTION'` to `'CRUD6.INFO_PAGE'` (as in 0.3.3)

## Changes Made

### File: `app/assets/routes/CRUD6Routes.ts`

**List Route (crud6.list):**
```typescript
// Removed title and description from meta
meta: {
    permission: {
        slug: 'uri_crud6'
    }
}
```

**View Route (crud6.view):**
```typescript
// Restored order and description key from 0.3.3
meta: {
    title: 'CRUD6.PAGE',
    description: 'CRUD6.INFO_PAGE',  // Changed from CRUD6.PAGE_DESCRIPTION
    permission: {
        slug: 'uri_crud6'
    }
}
```

### File: `app/assets/tests/router/routes.test.ts`

Updated test expectations:
```typescript
// List route should NOT have title and description (dynamically set by Vue component)
const listRoute = mainRoute.children[0]
expect(listRoute.meta).not.toHaveProperty('title')
expect(listRoute.meta).not.toHaveProperty('description')

// View route should have title and description
const viewRoute = mainRoute.children[1]
expect(viewRoute.meta).toHaveProperty('title')
expect(viewRoute.meta).toHaveProperty('description')
```

## Expected Behavior

After this fix:
1. Breadcrumbs will **show** on list pages (not hidden)
2. Breadcrumbs will display `{{model}}` initially (translation placeholder)
3. Vue component will dynamically update breadcrumb with actual model name
4. Final result: `UserFrosting / Group Management` (or similar based on schema)

## Why This Works

1. **No route meta title** → breadcrumb initializes with empty/undefined
2. **Vue component sets page.title** → breadcrumb updates dynamically
3. **PageList.vue runs immediately** → sets capitalized model name first
4. **Schema loads asynchronously** → updates with schema.title when available
5. **Result**: Smooth transition from model name to schema title

## Testing

All tests pass:
```bash
npm test
# ✓ app/assets/tests/router/routes.test.ts (2 tests) 6ms
# ✓ app/assets/tests/components/imports.test.ts (3 tests) 3ms
```

## Related Documentation

- `.archive/BREADCRUMB_FIX.md` - Previous attempt using empty strings
- `.archive/BREADCRUMB_VISIBILITY_FIX.md` - Added title/description to all routes
- `.archive/BREADCRUMB_RESTORATION.md` - Restored translation keys
- Release 0.3.3: https://github.com/ssnukala/sprinkle-crud6/tree/0.3.3

## Notes

This fix restores the exact configuration that worked in release 0.3.3. The key insight is that **the list route must not have route-level title/description** to allow the Vue component to properly control breadcrumb display through dynamic page metadata updates.
