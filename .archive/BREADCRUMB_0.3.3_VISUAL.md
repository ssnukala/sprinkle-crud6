# Visual Comparison - Breadcrumb 0.3.3 Restoration

## What Changed

### List Route (crud6.list)

**Before (Current/Broken - breadcrumbs not showing):**
```typescript
{
    path: '',
    name: 'crud6.list',
    meta: {
        permission: {
            slug: 'uri_crud6'
        },
        title: 'CRUD6.PAGE',              // ❌ Removed
        description: 'CRUD6.PAGE_DESCRIPTION'  // ❌ Removed
    },
    component: () => import('../views/PageList.vue')
}
```

**After (Restored from 0.3.3 - breadcrumbs showing):**
```typescript
{
    path: '',
    name: 'crud6.list',
    meta: {
        permission: {
            slug: 'uri_crud6'
        }
        // ✅ No title or description - allows dynamic updates
    },
    component: () => import('../views/PageList.vue')
}
```

### View Route (crud6.view)

**Before (Current):**
```typescript
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        permission: {
            slug: 'uri_crud6'
        },
        title: 'CRUD6.PAGE',
        description: 'CRUD6.PAGE_DESCRIPTION'  // ❌ Wrong key
    },
    component: () => import('../views/PageRow.vue')
}
```

**After (Restored from 0.3.3):**
```typescript
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        title: 'CRUD6.PAGE',              // ✅ Moved before permission
        description: 'CRUD6.INFO_PAGE',   // ✅ Restored correct key
        permission: {
            slug: 'uri_crud6'
        }
    },
    component: () => import('../views/PageRow.vue')
}
```

## Breadcrumb Behavior

### Before Fix (Broken)

```
List Page: [No breadcrumb showing]
```

**Why:** Route meta has static `title: 'CRUD6.PAGE'` which resolves to `{{model}}` at build time. Vue component's dynamic updates conflict with static route meta.

### After Fix (Working - 0.3.3 Pattern)

```
List Page: UserFrosting / {{model}}
           ↓ (after schema loads)
           UserFrosting / Group Management
```

**Why:** No route meta title allows Vue component to set page.title dynamically without conflict.

## How Dynamic Updates Work

### PageList.vue onMounted() Flow

1. **Component mounts**
   ```typescript
   // Set initial page title immediately for breadcrumbs
   page.title = model.value.charAt(0).toUpperCase() + model.value.slice(1)
   // Result: "Groups"
   ```

2. **Schema loads asynchronously**
   ```typescript
   loadSchema(model.value).then(() => {
       page.title = schema.value.title || model.value
       // Result: "Group Management" (from schema)
   })
   ```

3. **Breadcrumb updates reactively**
   - Initial: `UserFrosting / Groups`
   - After schema: `UserFrosting / Group Management`

## Test Changes

### Before
```typescript
// Expected all child routes to have title and description
const listRoute = mainRoute.children[0]
expect(listRoute.meta).toHaveProperty('title')         // ❌ Wrong expectation
expect(listRoute.meta).toHaveProperty('description')   // ❌ Wrong expectation
```

### After
```typescript
// List route should NOT have title and description (dynamically set by Vue component)
const listRoute = mainRoute.children[0]
expect(listRoute.meta).not.toHaveProperty('title')         // ✅ Correct
expect(listRoute.meta).not.toHaveProperty('description')   // ✅ Correct
```

## Key Differences from 0.3.3

The restored configuration is **identical to 0.3.3** except for:
1. Path has leading slash: `'/crud6/:model'` (current) vs `'crud6/:model'` (0.3.3)
   - This is intentional and doesn't affect breadcrumbs

All other aspects match 0.3.3 exactly:
- ✅ List route has no title/description in meta
- ✅ View route uses `CRUD6.INFO_PAGE` for description
- ✅ View route has title/description before permission in meta

## Translation Keys Reference

From `app/locale/en_US/messages.php`:
- `CRUD6.PAGE` → `{{model}}`
- `CRUD6.INFO_PAGE` → `View and edit {{model}} details.`
- `CRUD6.PAGE_DESCRIPTION` → `A listing of the {{model}} for your site...`

## Expected Results

After deploying this fix:
1. ✅ Breadcrumbs will be visible on all CRUD6 pages
2. ✅ List page will show model name (capitalized) then schema title
3. ✅ View page will show `{{model}}` placeholder (from translation key)
4. ✅ Smooth dynamic updates without flickering
5. ✅ Consistent with UserFrosting 6 patterns

## Files Changed
- `app/assets/routes/CRUD6Routes.ts` - Restored 0.3.3 route configuration
- `app/assets/tests/router/routes.test.ts` - Updated test expectations
- `.archive/BREADCRUMB_0.3.3_RESTORATION.md` - Detailed documentation
