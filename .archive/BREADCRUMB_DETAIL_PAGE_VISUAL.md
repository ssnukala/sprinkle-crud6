# Visual Comparison - Breadcrumb Detail Page Fix

## Before Fix

### List Page (`/crud6/groups`)
```
UserFrosting / Group Management  ✅ (Working correctly)
```

### Detail Page (`/crud6/groups/1`)
```
UserFrosting / CRUD6.PAGE / Hippos - Group  ❌ (Broken - showing literal translation key)
```

**Problem:** The literal text "CRUD6.PAGE" appears instead of the model name.

---

## After Fix

### List Page (`/crud6/groups`)
```
UserFrosting / Group Management  ✅ (Still working correctly)
```

### Detail Page (`/crud6/groups/1`)
```
UserFrosting / Groups / Hippos - Group  ✅ (Fixed - showing model name)
```

**Solution:** Removed static translation keys, using dynamic capitalized model name.

---

## Code Changes

### 1. Route Configuration (`app/assets/routes/CRUD6Routes.ts`)

#### Parent Route
```typescript
// Before
{
    path: '/crud6/:model',
    meta: {
        auth: {},
        title: 'CRUD6.PAGE',              // ❌ Static translation key
        description: 'CRUD6.PAGE_DESCRIPTION'
    }
}

// After
{
    path: '/crud6/:model',
    meta: {
        auth: {}  // ✅ No static title - allows dynamic updates
    }
}
```

#### Detail Route
```typescript
// Before
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        title: 'CRUD6.PAGE',        // ❌ Static translation key
        description: 'CRUD6.INFO_PAGE',
        permission: {
            slug: 'uri_crud6'
        }
    }
}

// After
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        permission: {
            slug: 'uri_crud6'
        }
        // ✅ No title/description - allows dynamic updates
    }
}
```

### 2. Vue Component Logic (`app/assets/views/PageRow.vue`)

```typescript
// Before (line 254)
page.title = schema.value.title || modelLabel.value
// Would set: "Group Management" (schema.title)

// After (lines 254-257)
const capitalizedModel = model.value.charAt(0).toUpperCase() + model.value.slice(1)
page.title = capitalizedModel
// Sets: "Groups" (capitalized model name)
```

---

## Data Flow Comparison

### List Page Flow (Unchanged)
```
1. Component mounts
   └─> page.title = "Groups" (initial)

2. Schema loads
   └─> page.title = "Group Management" (schema.title)

3. Breadcrumb renders
   └─> UserFrosting / Group Management ✅
```

### Detail Page Flow (Fixed)

#### Before Fix:
```
1. Route registers with static meta
   └─> meta.title = "CRUD6.PAGE" (translation key)

2. Breadcrumb reads route meta
   └─> Shows: "CRUD6.PAGE" (literal text) ❌

3. Component tries to update page.title
   └─> Doesn't affect breadcrumb (parent route uses static meta)
```

#### After Fix:
```
1. Route registers with NO static meta
   └─> meta = { permission: {...} }

2. Component mounts
   └─> page.title = "Groups" (capitalized model)

3. Schema loads
   └─> page.title = "Groups" (keeps capitalized model)

4. Record fetches
   └─> page.title = "Hippos - Group" (record + singular)

5. Breadcrumb renders
   └─> UserFrosting / Groups / Hippos - Group ✅
```

---

## Breadcrumb Hierarchy Breakdown

```
Level 1: "UserFrosting"
  │      (Root / Application name)
  │
Level 2: "Groups"
  │      (Model name - links to list page)
  │      - List page: "Group Management" (schema.title)
  │      - Detail page: "Groups" (capitalized model)
  │
Level 3: "Hippos - Group"
         (Current page - record name + singular title)
         - Only visible on detail page
```

---

## Why It Works

### Translation Resolution Timeline

**Before Fix:**
```
Build Time:
  - Route registers with title: 'CRUD6.PAGE'
  - Translation resolves: 'CRUD6.PAGE' → '{{model}}'
  - Stored in route meta: title = '{{model}}'

Runtime:
  - Breadcrumb reads: route.meta.title = '{{model}}'
  - Displays: "CRUD6.PAGE" or "{{model}}" literally ❌
```

**After Fix:**
```
Build Time:
  - Route registers with NO title
  - No translation resolution needed
  - Stored in route meta: (no title field)

Runtime:
  - Component executes: page.title = 'Groups'
  - Breadcrumb reads: page.title = 'Groups'
  - Displays: "Groups" ✅
  - Record loads: page.title = 'Hippos - Group'
  - Breadcrumb updates: "Groups" → "Hippos - Group"
```

---

## Schema Fields Used

From `app/schema/crud6/groups.json`:
```json
{
  "model": "groups",          // ← Used for capitalized name: "Groups"
  "title": "Group Management", // ← Used by list page only
  "singular_title": "Group",   // ← Used in record display: "Hippos - Group"
  "title_field": "name"        // ← Field to use for record name: "Hippos"
}
```

### Field Usage by Page Type:

| Field           | List Page            | Detail Page (Breadcrumb) | Detail Page (Record) |
|-----------------|----------------------|--------------------------|----------------------|
| `model`         | -                    | "Groups" ✅              | -                    |
| `title`         | "Group Management" ✅ | -                        | -                    |
| `singular_title`| -                    | -                        | "Group" ✅           |
| `title_field`   | -                    | -                        | "Hippos" ✅          |

---

## Test Changes

### Route Test Update (`app/assets/tests/router/routes.test.ts`)

```typescript
// Before
test('CRUD6Routes should have title and description in meta for breadcrumbs', () => {
    // Parent route should have title and description
    expect(mainRoute.meta).toHaveProperty('title')          // ❌ Was required
    expect(mainRoute.meta).toHaveProperty('description')
    
    // View route should have title and description
    expect(viewRoute.meta).toHaveProperty('title')          // ❌ Was required
    expect(viewRoute.meta).toHaveProperty('description')
})

// After
test('CRUD6Routes should have correct meta for breadcrumbs', () => {
    // Parent route should NOT have title and description
    expect(mainRoute.meta).not.toHaveProperty('title')      // ✅ Not required
    expect(mainRoute.meta).not.toHaveProperty('description')
    
    // View route should NOT have title and description  
    expect(viewRoute.meta).not.toHaveProperty('title')      // ✅ Not required
    expect(viewRoute.meta).not.toHaveProperty('description')
})
```

**All tests passing:** ✅

---

## Files Changed Summary

1. **`app/assets/routes/CRUD6Routes.ts`**
   - Removed `title` and `description` from parent route meta
   - Removed `title` and `description` from detail route meta
   - Clean meta objects with only required fields (auth, permission)

2. **`app/assets/views/PageRow.vue`**
   - Changed detail page title from `schema.title` to capitalized model name
   - Maintains proper breadcrumb hierarchy
   - Added explanatory comments

3. **`app/assets/tests/router/routes.test.ts`**
   - Updated test expectations to verify NO static titles
   - Changed test name to reflect new behavior
   - All assertions now expect dynamic title handling

4. **`.archive/BREADCRUMB_DETAIL_PAGE_FIX.md`** (Documentation)
   - Comprehensive documentation of the issue and fix
   - Code examples and data flow diagrams
   - Related documentation references

---

## Benefits of This Fix

✅ **Correct breadcrumb display** - Shows "Groups" instead of "CRUD6.PAGE"

✅ **Consistent pattern** - All routes use dynamic titles from Vue components

✅ **Better UX** - Clear hierarchical navigation without placeholder text

✅ **Maintainable** - No static translation keys that need runtime context

✅ **Tested** - All route tests updated and passing

✅ **Documented** - Comprehensive documentation for future reference

---

## Related Documentation

- `.archive/BREADCRUMB_DETAIL_PAGE_FIX.md` - Detailed technical documentation
- `.archive/BREADCRUMB_FIX.md` - Original breadcrumb fix for list pages
- `.archive/BREADCRUMB_VISIBILITY_FIX.md` - Previous breadcrumb visibility work
- `app/locale/en_US/messages.php` - Translation keys reference
- `app/schema/crud6/groups.json` - Schema structure example
