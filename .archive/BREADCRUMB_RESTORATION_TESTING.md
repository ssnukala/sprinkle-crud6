# Breadcrumb Restoration - Testing Guide

## What Was Done

The breadcrumb route metadata was restored to use translation keys instead of empty strings, per user request to debug breadcrumb behavior.

## Changes Summary

### Modified Files
1. **app/assets/routes/CRUD6Routes.ts** - Restored translation keys in route metadata

### New Documentation Files
1. **.archive/BREADCRUMB_RESTORATION.md** - Explains the restoration and purpose
2. **.archive/BREADCRUMB_RESTORATION_VISUAL.md** - Visual before/after comparison

## Expected Behavior

### With This Restoration (Current State)
Breadcrumbs will show the literal `{{model}}` placeholder:
- List page: `UserFrosting / {{model}}`
- Detail page: `UserFrosting / {{model}} / {{model}}`

### With Previous Fix (Empty Strings)
Breadcrumbs showed the actual model name:
- List page: `UserFrosting / Group Management`
- Detail page: `UserFrosting / Group Management / [Record Name]`

## How to Test

### Prerequisites
This sprinkle requires a UserFrosting 6 application context to test properly.

### Testing Steps

1. **Build the frontend assets:**
   ```bash
   npm install
   npm run build
   ```

2. **Navigate to a CRUD6 list page:**
   - Go to: `/crud6/groups`
   - Expected breadcrumb: `UserFrosting / {{model}}`

3. **Navigate to a CRUD6 detail page:**
   - Go to: `/crud6/groups/1`
   - Expected breadcrumb: `UserFrosting / {{model}} / {{model}}`

4. **Verify the translation keys:**
   - Open browser console
   - Check route metadata: `this.$route.meta.title`
   - Should show: `CRUD6.PAGE`
   - Check translated value (if translation system is working)
   - Should show: `{{model}}`

## Why This Shows {{model}}

The `{{model}}` appears as literal text because:

1. **Route Registration (Build Time):**
   - Routes define: `meta: { title: 'CRUD6.PAGE' }`
   - Translation system resolves: `CRUD6.PAGE` → `{{model}}`
   - Route metadata stored as: `title = '{{model}}'`

2. **Breadcrumb Display (Runtime):**
   - Breadcrumb reads: `route.meta.title = '{{model}}'`
   - Displays literal string: `{{model}}`
   - No runtime replacement because model context not available to translation system

## To Restore Dynamic Behavior

If you want breadcrumbs to show actual model names again:

1. **Revert to empty strings:**
   ```typescript
   meta: {
       title: '',
       description: ''
   }
   ```

2. **Vue components will handle dynamically:**
   - PageList.vue sets `page.title` after loading schema
   - PageRow.vue sets `page.title` based on mode (create/view)
   - Breadcrumb updates reactively with actual model name

## Related Files

- **Translation keys:** `app/locale/en_US/messages.php`
- **Vue components:** 
  - `app/assets/views/PageList.vue`
  - `app/assets/views/PageRow.vue`
- **Route definition:** `app/assets/routes/CRUD6Routes.ts`

## Related Documentation

- `.archive/BREADCRUMB_FIX.md` - Original fix that changed to empty strings
- `.archive/BREADCRUMB_VISIBILITY_FIX.md` - Ensured meta fields exist
- `.archive/ISSUE_73_FINAL_SUMMARY.md` - Previous breadcrumb issues and fixes

## Debug Information

To debug breadcrumb behavior, check:

1. **Route metadata at runtime:**
   ```javascript
   console.log(this.$route.meta.title)
   ```

2. **Page metadata in Vue:**
   ```javascript
   console.log(page.title)
   ```

3. **Schema loading:**
   ```javascript
   console.log(schema.value)
   ```

4. **Translation resolution:**
   ```javascript
   console.log(this.$t('CRUD6.PAGE'))
   ```

## Notes

- This is a restoration for debugging purposes
- The user wanted to see the original `{{model}}` behavior
- The previous solution (empty strings + dynamic updates) was designed to show actual model names
- This restoration helps understand where the breadcrumb change comes from
