# Breadcrumb Translation Key Restoration

## Date
October 14, 2025

## Issue
User reported: "breadcrumbs still not working, it used work in the initial versions when it used to show {{model}} can you look at the commit history that was used to update to show the dynamic translated model name, lets restore that for now so I can see where the change is coming from"

## Action Taken
Restored the original route metadata that uses translation keys instead of empty strings.

## Changes Made

### File: `app/assets/routes/CRUD6Routes.ts`

**Before (empty strings):**
```typescript
meta: {
    auth: {},
    title: '',
    description: ''
}
```

**After (translation keys restored):**
```typescript
meta: {
    auth: {},
    title: 'CRUD6.PAGE',
    description: 'CRUD6.PAGE_DESCRIPTION'
}
```

Applied to:
- Parent route (`/crud6/:model`)
- List route (`crud6.list`)
- View route (`crud6.view`)

## Translation Keys

From `app/locale/en_US/messages.php`:
- `CRUD6.PAGE` → `{{model}}`
- `CRUD6.PAGE_DESCRIPTION` → `A listing of the {{model}} for your site. Provides management tools for editing and deleting {{model}}.`

## Expected Behavior

With this restoration, breadcrumbs will now show:
- `UserFrosting / {{model}}` on list pages
- `UserFrosting / {{model}} / {{model}}` on detail pages

The `{{model}}` placeholder appears because:
1. Translation keys are evaluated at route registration time (static/build time)
2. The `{{model}}` placeholder requires runtime context to be populated
3. Without runtime context, the literal placeholder text is displayed

## Purpose

This restoration allows the user to:
1. See the original behavior where `{{model}}` was visible
2. Debug and understand where the change is coming from
3. Compare with the previous fix that used empty strings

## Related Documentation

- `.archive/BREADCRUMB_FIX.md` - Documents the change from translation keys to empty strings
- `.archive/BREADCRUMB_VISIBILITY_FIX.md` - Documents ensuring meta fields exist
- `app/locale/en_US/messages.php` - Contains the CRUD6 translation keys

## Notes

This is a temporary restoration for debugging purposes. The previous solution (using empty strings and dynamically updating page.title in Vue components) was designed to show the actual model name instead of the placeholder text.
