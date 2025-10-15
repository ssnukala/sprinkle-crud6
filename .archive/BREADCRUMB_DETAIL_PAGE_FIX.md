# Breadcrumb Detail Page Fix - Model Name Display

## Date
October 15, 2025

## Issue
On the detail page (`/crud6/groups/1`), the breadcrumb was showing:
```
UserFrosting / CRUD6.PAGE / Hippos - Group
```

The 2nd component should show "Groups" (the model name) instead of "CRUD6.PAGE".

The list page (`/crud6/groups`) was working correctly, showing:
```
UserFrosting / Group Management
```

## Root Cause

### Static Route Metadata Problem
The route configuration in `app/assets/routes/CRUD6Routes.ts` had static translation keys:

```typescript
// Parent route
{
    path: '/crud6/:model',
    meta: {
        title: 'CRUD6.PAGE',  // Resolves to "{{model}}" at build time
        description: 'CRUD6.PAGE_DESCRIPTION'
    }
}

// Detail route
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        title: 'CRUD6.PAGE',  // Also resolves to "{{model}}"
        description: 'CRUD6.INFO_PAGE'
    }
}
```

### Why This Caused the Issue

1. **Translation Resolution at Build Time:**
   - Route metadata is evaluated when routes are registered (static/build time)
   - Translation key `CRUD6.PAGE` resolves to `{{model}}` from messages.php
   - The literal string `"{{model}}"` gets stored in route metadata
   - No runtime context available to replace placeholder

2. **Breadcrumb Display:**
   - Breadcrumb component reads parent route's meta.title
   - Displays literal text: "CRUD6.PAGE" (or "{{model}}" if translation applied)
   - Vue component's dynamic `page.title` updates don't affect parent route metadata

3. **List Page vs Detail Page:**
   - List route had NO title in meta → allowed PageList.vue to set dynamic title
   - Detail route HAD title in meta → prevented dynamic updates, showed static value

## Solution

### 1. Remove Static Titles from Routes
**File: `app/assets/routes/CRUD6Routes.ts`**

```typescript
// Before
{
    path: '/crud6/:model',
    meta: {
        auth: {},
        title: 'CRUD6.PAGE',              // ❌ Removed
        description: 'CRUD6.PAGE_DESCRIPTION'  // ❌ Removed
    }
}

// After
{
    path: '/crud6/:model',
    meta: {
        auth: {}  // ✅ Only auth metadata remains
    }
}
```

Applied to:
- Parent route (`/crud6/:model`)
- Detail route (`:id` / `crud6.view`)

### 2. Update Detail Page Title Logic
**File: `app/assets/views/PageRow.vue`**

Changed line 252-257 to use capitalized model name instead of schema.title:

```typescript
// Before
page.title = schema.value.title || modelLabel.value
// Would set "Group Management" from schema

// After
const capitalizedModel = model.value.charAt(0).toUpperCase() + model.value.slice(1)
page.title = capitalizedModel
// Sets "Groups" (capitalized model name)
```

**Reasoning:**
- List page uses `schema.title` ("Group Management") for descriptive title
- Detail page needs model name ("Groups") for breadcrumb hierarchy
- Record name gets appended later: "Hippos - Group"

### 3. Update Tests
**File: `app/assets/tests/router/routes.test.ts`**

Changed test expectations to verify NO static titles exist:

```typescript
// Parent route should NOT have title and description
expect(mainRoute.meta).not.toHaveProperty('title')
expect(mainRoute.meta).not.toHaveProperty('description')

// List route should NOT have title and description
expect(listRoute.meta).not.toHaveProperty('title')
expect(listRoute.meta).not.toHaveProperty('description')

// View route should NOT have title and description
expect(viewRoute.meta).not.toHaveProperty('title')
expect(viewRoute.meta).not.toHaveProperty('description')
```

## How It Works Now

### List Page Flow (`/crud6/groups`)
1. Component mounts → sets `page.title = "Groups"` (capitalized model)
2. Schema loads → updates `page.title = "Group Management"` (schema.title)
3. Breadcrumb displays: **`UserFrosting / Group Management`** ✅

### Detail Page Flow (`/crud6/groups/1`)
1. Component mounts → sets `page.title = "Groups"` (capitalized model)
2. Schema loads → keeps `page.title = "Groups"` (capitalized model)
3. Record fetches → updates `page.title = "Hippos - Group"` (record name + singular)
4. Breadcrumb displays: **`UserFrosting / Groups / Hippos - Group`** ✅

## Breadcrumb Hierarchy

The breadcrumb component builds hierarchy from:

1. **Root:** "UserFrosting" (application name)
2. **Parent route:** Uses `page.title` set by active child component
   - List page: "Group Management" (from schema.title)
   - Detail page: "Groups" (from capitalized model name)
3. **Current page:** Uses `page.title` from current component
   - Detail page: "Hippos - Group" (record name + singular title)

## Benefits

- ✅ Breadcrumb shows model name ("Groups") on detail pages
- ✅ Maintains descriptive title ("Group Management") on list pages  
- ✅ No more "CRUD6.PAGE" or "{{model}}" placeholder text
- ✅ Proper hierarchical navigation: UserFrosting > Groups > Record
- ✅ Consistent pattern: All routes use dynamic titles from Vue components
- ✅ All tests passing

## Files Changed
- `app/assets/routes/CRUD6Routes.ts` - Removed static translation keys
- `app/assets/views/PageRow.vue` - Use model name for breadcrumb hierarchy
- `app/assets/tests/router/routes.test.ts` - Updated test expectations

## Related Documentation
- `.archive/BREADCRUMB_FIX.md` - Original fix that changed list route to empty strings
- `.archive/BREADCRUMB_RESTORATION.md` - Previous restoration of translation keys
- `.archive/BREADCRUMB_VISIBILITY_FIX.md` - Ensured meta fields exist
- `app/locale/en_US/messages.php` - Contains CRUD6 translation keys

## Translation Keys Reference

From `app/locale/en_US/messages.php`:
```php
'CRUD6' => [
    'PAGE'             => '{{model}}',
    'PAGE_DESCRIPTION' => 'A listing of the {{model}} for your site...',
    'INFO_PAGE'        => 'View and edit {{model}} details.',
]
```

These translations are no longer used in route metadata, but may still be used elsewhere in the application.

## Schema Reference

From `app/schema/crud6/groups.json`:
```json
{
  "model": "groups",
  "title": "Group Management",      // Used by list page
  "singular_title": "Group",         // Used for record labels
  "description": "Manage user groups and roles"
}
```

- **List page** uses `title` for descriptive heading
- **Detail page** uses capitalized `model` for breadcrumb, `singular_title` for labels
- **Record name** comes from `title_field` (default: "name")
