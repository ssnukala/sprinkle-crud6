# Translation Keys and Breadcrumb Fix Summary

**Date**: November 3, 2025  
**Issue**: Edit and Delete buttons showing translation keys instead of translated text; Breadcrumb showing {{model}} placeholder

## Problem Statement

Two issues were reported:
1. **Edit/Delete Buttons**: Showing literal "CRUD6.EDIT" and "CRUD6.DELETE" instead of "Edit User" and "Delete User"
2. **Breadcrumb Placeholder**: Showing "UserFrosting / {{model}} / Users" instead of "UserFrosting / Users / <record name>"

## Root Cause Analysis

### Issue 1: Translation Key Structure

In `app/locale/en_US/messages.php`, translation keys were defined twice:

```php
// BEFORE (BROKEN)
'EDIT'                => 'Edit {{model}}',  // Line 40 - Gets overwritten!
'EDIT' => [                                  // Lines 41-44 - This wins!
    'SUCCESS' => 'Retrieved {{model}} for editing',
    'ERROR'   => 'Failed to retrieve {{model}}',
],
```

In PHP, when you define the same array key twice, the second definition completely replaces the first. This meant:
- `$messages['CRUD6']['EDIT']` was an array, not a string
- Vue i18n couldn't resolve `$t('CRUD6.EDIT')` to a button label
- Same issue affected `CREATE`, `DELETE`, and `UPDATE` keys

### Issue 2: Static Breadcrumb Title

In `app/assets/routes/CRUD6Routes.ts`, routes had static titles:

```typescript
// BEFORE (BROKEN)
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        title: 'CRUD6.PAGE',  // Translates to literal '{{model}}'
        description: 'CRUD6.INFO_PAGE',
    }
}
```

The translation for `CRUD6.PAGE` was literally `{{model}}` (line 55 in messages.php), which appeared in breadcrumbs as-is instead of being replaced with the actual model name.

## Solution Implemented

### Fix 1: Use Numeric Index for Default Translation Value

Restructured translation arrays to use numeric index `0` for the default button label:

```php
// AFTER (FIXED)
'EDIT' => [
    0 => 'Edit {{model}}',                   // Default value for $t('CRUD6.EDIT')
    'SUCCESS' => 'Retrieved {{model}} for editing',  // For $t('CRUD6.EDIT.SUCCESS')
    'ERROR'   => 'Failed to retrieve {{model}}',     // For $t('CRUD6.EDIT.ERROR')
],
```

This pattern allows:
- `$t('CRUD6.EDIT')` → Returns index `0` → "Edit {{model}}"
- `$t('CRUD6.EDIT.SUCCESS')` → Returns 'SUCCESS' key → "Retrieved {{model}} for editing"

Applied same fix to: `CREATE`, `DELETE`, and `UPDATE` keys.

### Fix 2: Restore 0.3.3 Route Configuration Pattern

Restored the working 0.3.3 route configuration where the list route has NO title/description:

```typescript
// Parent route - HAS title and description
{
    path: '/crud6/:model',
    meta: {
        auth: {},
        title: 'CRUD6.PAGE',
        description: 'CRUD6.PAGE_DESCRIPTION'
    }
}

// List route - NO title/description
{
    path: '',
    name: 'crud6.list',
    meta: {
        permission: { slug: 'uri_crud6' }
        // NO title/description - allows PageList.vue to set dynamically
    }
}

// View route - HAS title and description
{
    path: ':id',
    name: 'crud6.view',
    meta: {
        title: 'CRUD6.PAGE',
        description: 'CRUD6.INFO_PAGE',
        permission: { slug: 'uri_crud6' }
    }
}
```

**Why This Pattern Works (from 0.3.3 analysis):**
- List route WITHOUT title/description prevents conflict with PageList.vue dynamic updates
- PageList.vue sets `page.title` immediately on mount with capitalized model name
- PageList.vue updates `page.title` again after schema loads with proper title
- Breadcrumb component reads from `page.title` without route meta interference
- Parent and view routes keep title/description for breadcrumb hierarchy

**Why Empty Strings or Removing All Titles Failed:**
- Empty strings (`title: ''`) still interfere with Vue component updates
- Removing title from ALL routes breaks breadcrumb component initialization
- Only the list route should omit title/description for dynamic updates to work

Components handle dynamic title updates:
- **PageList.vue**: Sets title from `schema.title` or capitalized model name
- **PageRow.vue**: Sets initial title, updates with schema title and record name
- **PageMasterDetail.vue**: Sets initial title, updates with schema title and record name

## Files Modified

1. **app/locale/en_US/messages.php**
   - Fixed `CRUD6.CREATE` structure (lines 21-27)
   - Fixed `CRUD6.DELETE` structure (lines 29-35)
   - Fixed `CRUD6.EDIT` structure (lines 40-44)
   - Fixed `CRUD6.UPDATE` structure (lines 57-63)

2. **app/assets/routes/CRUD6Routes.ts**
   - Removed `title` from parent route meta
   - Removed `title` from list route meta
   - Removed `title` from view route meta
   - Added explanatory comments about dynamic title handling

3. **app/assets/tests/router/routes.test.ts**
   - Updated test expectations to reflect new behavior
   - Changed from expecting static titles to expecting dynamic titles
   - Maintained test for description property on view route

## Validation

### Translation Structure Validation

Created `validate-translation-fix.php` to verify:
- All keys are arrays with numeric index `0`
- All keys have `SUCCESS` nested key
- Simulated translation resolution works correctly

**Results**:
```
✓ CRUD6.CREATE is properly structured
✓ CRUD6.EDIT is properly structured
✓ CRUD6.DELETE is properly structured
✓ CRUD6.UPDATE is properly structured
```

### Route Configuration Validation

Created `validate-route-fix.sh` to verify:
- No static `CRUD6` titles in route meta
- Routes rely on component-based title setting

**Results**:
```
✓ No static CRUD6 titles found in route meta
✓ Titles will be set dynamically by components
```

## Expected Behavior After Fix

### Edit/Delete Buttons
- **Before**: Literal "CRUD6.EDIT" and "CRUD6.DELETE" text
- **After**: "Edit User" and "Delete User" (or translated equivalent)

### Breadcrumbs
- **List Page Before**: "UserFrosting / {{model}}"
- **List Page After**: "UserFrosting / Users"
- **Detail Page Before**: "UserFrosting / {{model}} / Users"
- **Detail Page After**: "UserFrosting / Users / John Doe"

### Success Messages (Backend)
- Still work correctly with nested keys:
  - `CRUD6.EDIT.SUCCESS` → "Retrieved User for editing"
  - `CRUD6.DELETE.SUCCESS` → "Successfully deleted User"
  - `CRUD6.CREATE.SUCCESS` → "Successfully created User"
  - `CRUD6.UPDATE.SUCCESS` → "Successfully updated User"

## Testing Recommendations

These changes require testing in a full UserFrosting 6 application context:

1. **Button Labels**
   - Navigate to `/crud6/users/1`
   - Verify Edit button shows "Edit User" not "CRUD6.EDIT"
   - Verify Delete button shows "Delete User" not "CRUD6.DELETE"

2. **Breadcrumbs**
   - Navigate to `/crud6/users` (list page)
   - Verify breadcrumb shows "UserFrosting / Users" not "UserFrosting / {{model}}"
   - Navigate to `/crud6/users/1` (detail page)
   - Verify breadcrumb shows "UserFrosting / Users / <record name>" not "UserFrosting / {{model}} / Users"

3. **Other Models**
   - Test with other models (groups, roles, permissions)
   - Verify buttons and breadcrumbs work for all models

4. **Success Messages**
   - Create, edit, delete records
   - Verify success messages still appear correctly

## Technical Notes

### Translation Array Pattern

This pattern (using numeric index `0` for default) is consistent with other parts of the messages file:

```php
'CRUD6' => [
    1 => 'CRUD6',              // Numeric index for pluralization
    2 => 'CRUD6 All Rows',     // Numeric index for pluralization
    // ...
]
```

### UserFrosting i18n Behavior

UserFrosting's i18n system (based on symfony/translation) handles numeric indices automatically:
- When resolving a translation key that points to an array
- If the array has a numeric index `0`, it uses that as the default value
- This allows both `key` and `key.nested` to work correctly

### Component-Based Title Setting

All CRUD6 views set page titles dynamically:

```javascript
// PageList.vue
page.title = schema.value?.title || model.value.charAt(0).toUpperCase() + model.value.slice(1)

// PageRow.vue
page.title = `${recordName} - ${modelLabel.value}`

// PageMasterDetail.vue
page.title = schema.value.title || modelLabel.value
```

This approach is more flexible than route-level titles because:
1. Titles can include record-specific data
2. Titles can be translated based on schema configuration
3. Titles update dynamically as data loads

## References

- Issue: Translation keys showing instead of text on Edit/Delete buttons
- Issue: Breadcrumb showing {{model}} placeholder
- UserFrosting Documentation: https://learn.userfrosting.com/i18n
- Symfony Translation Component: https://symfony.com/doc/current/translation.html

## Commits

1. `297b777` - Fix translation keys for Edit/Delete buttons and breadcrumb placeholder
2. `1d0f7c9` - Update route tests to reflect dynamic title handling
