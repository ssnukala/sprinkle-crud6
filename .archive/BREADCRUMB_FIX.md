# Breadcrumb {{model}} Placeholder Fix

## Issue
The breadcrumb in the page header was showing `{{model}}` instead of the actual model name (e.g., "Group Management"). 

Example: `UserFrosting / {{model}} / {{model}}`

## Root Cause
The route metadata in `app/assets/routes/CRUD6Routes.ts` was using static translation keys that contained `{{model}}` placeholders:
- `title: 'CRUD6.PAGE'` → resolved to `{{model}}` at route registration time
- `description: 'CRUD6.PAGE_DESCRIPTION'` → resolved to `A listing of the {{model}}...`

**Why this caused the issue:**
- Route metadata is evaluated when routes are registered (static/build time)
- Translation placeholders like `{{model}}` require runtime context to be populated
- The breadcrumb component uses the route metadata title directly
- Since the translation couldn't be populated at registration time, it showed the literal `{{model}}` placeholder

## Solution
Changed route metadata to use empty strings instead of translation keys:

```typescript
// Before
meta: {
    title: 'CRUD6.PAGE',
    description: 'CRUD6.PAGE_DESCRIPTION'
}

// After
meta: {
    title: '',
    description: ''
}
```

## Why This Works
The Vue components (PageList.vue and PageRow.vue) already dynamically update `page.title` and `page.description` after loading the schema:

**PageList.vue:**
```typescript
page.title = schema.value.title || model.value
page.description = schema.value.description || `A listing of the ${schema.value.title || model.value}...`
```

**PageRow.vue:**
```typescript
page.title = `Create ${schema.value.title || newModel}` // Create mode
page.title = `View ${schema.value.title || newModel}`   // View mode
```

By starting with empty route metadata, the breadcrumb will display:
1. Empty string initially (briefly during page load)
2. Actual model title once the Vue component loads the schema and updates page metadata
3. Example result: `UserFrosting / Group Management / Administrators`

## Files Changed
- `app/assets/routes/CRUD6Routes.ts` - Removed static translation keys from route metadata

## Benefits
- ✅ Breadcrumb displays the actual model name from schema
- ✅ Uses schema.title when available (e.g., "Group Management")
- ✅ Falls back to model name if schema not loaded (e.g., "groups")
- ✅ No flickering or placeholder text visible to users
- ✅ Consistent with how page titles and descriptions are already handled

## Related Issues
- Translation fix: All `{{model}}` placeholders in buttons, titles, and modals were fixed previously
- This fix completes the breadcrumb part of the translation system
