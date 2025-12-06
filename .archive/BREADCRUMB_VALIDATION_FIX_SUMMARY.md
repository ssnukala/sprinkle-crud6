# Breadcrumb Duplication and VALIDATION Translation Fix Summary

**Date:** 2025-12-05  
**Issue:** Breadcrumb duplication on page refresh and untranslated VALIDATION keys  
**PR/Branch:** copilot/fix-breadcrumb-translation-issues

## Problem Statement

When viewing a CRUD6 detail page (e.g., `crud6/users/8`):

1. **Breadcrumb Issue**: 
   - On initial load: "UserFrosting / Admin Panel / User /   /" (with empty trailing elements)
   - On page refresh: "UserFrosting / Admin Panel / Users /   User / user01" (duplicate entry with "Users" inserted before "User")

2. **VALIDATION Translation Issue**:
   - Translation keys like `VALIDATION.ENTER_VALUE`, `VALIDATION.CONFIRM`, etc. showing as literal keys
   - UFAlerts showing translation keys instead of translated messages
   - Particularly visible in password change modal and other forms

## Root Causes

### Breadcrumb Duplication

The issue was caused by inconsistent use of model labels in `PageRow.vue`:

1. **First call** (line 374, in model watcher):
   ```typescript
   await setDetailBreadcrumbs(schemaTitle, '', listPath)
   ```
   - `schemaTitle` = translated `schema.title` = "Users" (plural, from `CRUD6.USER.PAGE`)

2. **Second call** (line 232, in fetch):
   ```typescript
   await setDetailBreadcrumbs(modelLabel.value, recordName, listPath)
   ```
   - `modelLabel.value` = translated `schema.singular_title` = "User" (singular, from `CRUD6.USER.1`)

When `setDetailBreadcrumbs` was called the second time:
- It looked for placeholders like `CRUD6.PAGE` or `{{model}}` - not found (already replaced with "Users")
- It didn't find the existing breadcrumb pointing to `/crud6/users` with label "Users"
- It added a new breadcrumb with label "User" and path `/crud6/users`
- Result: Two breadcrumbs for the list page ("Users" and "User")

Additionally, the function didn't properly handle empty `recordTitle`:
- First call with empty recordTitle created an empty breadcrumb for current path
- This interfered with the second call's attempt to add the proper record breadcrumb

### VALIDATION Translation Issue

VALIDATION translation keys were defined at the root level of `messages.php`:

```php
return [
    'CRUD6' => [...],
    'VALIDATION' => [...],  // At root level
];
```

This potentially conflicted with UserFrosting core translations or wasn't properly scoped to the CRUD6 sprinkle, causing the translations to not be found or overridden.

## Solutions Implemented

### 1. Consistent Model Label in Breadcrumbs

**File:** `app/assets/views/PageRow.vue`

Changed line 374 to use `modelLabel.value` (singular) instead of `schemaTitle` (plural):

```typescript
// Before
await setDetailBreadcrumbs(schemaTitle, '', listPath)

// After  
await setDetailBreadcrumbs(modelLabel.value, '', listPath)
```

This ensures both calls use the same label ("User"), preventing duplication.

### 2. Improved Breadcrumb Detection Logic

**File:** `app/assets/composables/useCRUD6Breadcrumbs.ts`

Added detection for existing model breadcrumbs by path, not just by placeholder:

```typescript
// Check if this breadcrumb points to the list path (existing model breadcrumb)
// This handles cases where setDetailBreadcrumbs is called multiple times
// and the placeholder was already replaced with the model title
else if (listPath && crumb.to === listPath) {
    debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Found existing model breadcrumb by path:', crumb)
    // Update with current model title (in case it changed, e.g., from plural to singular)
    updatedCrumbs.push({ label: modelTitle, to: listPath })
    foundModelCrumb = true
}
```

### 3. Proper Handling of Empty Record Title

**File:** `app/assets/composables/useCRUD6Breadcrumbs.ts`

Modified the current path breadcrumb logic to skip when recordTitle is empty:

```typescript
// Check if this is already the current path (detail page)
else if (crumb.to === currentPath) {
    debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Found existing current path breadcrumb:', crumb)
    // Update it with record title (only if recordTitle is provided)
    if (recordTitle) {
        updatedCrumbs.push({ label: recordTitle, to: currentPath })
        foundRecordCrumb = true
    } else {
        // If no recordTitle provided, skip this breadcrumb (it will be added later when record is loaded)
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Skipping current path breadcrumb (no recordTitle yet)')
    }
}
```

This prevents empty breadcrumbs from being created on the first call.

### 4. Scoped VALIDATION Translations

**Files:** 
- `app/locale/en_US/messages.php`
- `app/locale/fr_FR/messages.php`

Moved VALIDATION keys under the CRUD6 namespace:

```php
return [
    'CRUD6' => [
        // ... existing CRUD6 translations
        
        // Validation translations (nested under CRUD6 for proper scoping)
        'VALIDATION' => [
            'ENTER_VALUE'         => 'Enter value',
            'CONFIRM'             => 'Confirm',
            'CONFIRM_PLACEHOLDER' => 'Confirm value',
            'MIN_LENGTH_HINT'     => 'Minimum {{min}} characters',
            'MATCH_HINT'          => 'Values must match',
            'FIELDS_MUST_MATCH'   => 'Fields must match',
            'MIN_LENGTH'          => 'Minimum {{min}} characters required',
        ],
    ],
];
```

### 5. Updated Translation Key References

**File:** `app/assets/components/CRUD6/UnifiedModal.vue`

Updated all VALIDATION key references to use the CRUD6 namespace:

```typescript
// Before
translator.translate('VALIDATION.ENTER_VALUE')
translator.translate('VALIDATION.CONFIRM')
// etc.

// After
translator.translate('CRUD6.VALIDATION.ENTER_VALUE')
translator.translate('CRUD6.VALIDATION.CONFIRM')
// etc.
```

## Expected Behavior After Fix

### Breadcrumbs

**On initial page load** (`/crud6/users/8`):
1. Model watcher triggers, calls `setDetailBreadcrumbs("User", "", "/crud6/users")`
   - Creates breadcrumb: "UserFrosting / Admin Panel / User"
2. Fetch completes, calls `setDetailBreadcrumbs("User", "user01", "/crud6/users")`
   - Finds existing breadcrumb with path `/crud6/users`, updates label (still "User")
   - Adds record breadcrumb: "user01"
   - Final: "UserFrosting / Admin Panel / User / user01" ✓

**On page refresh** (`/crud6/users/8`):
1. Model might not reload (cached), but if it does, same as initial load
2. Fetch runs, calls `setDetailBreadcrumbs("User", "user01", "/crud6/users")`
   - Finds existing breadcrumb by path `/crud6/users`, updates label
   - Updates or adds record breadcrumb
   - Final: "UserFrosting / Admin Panel / User / user01" ✓

**No more duplicate breadcrumbs!**

### VALIDATION Translations

All VALIDATION keys will now be properly translated:
- Password change modal: "Enter value", "Confirm", "Minimum 8 characters", etc.
- UFAlerts: Proper translated messages instead of keys
- Form validation: Translated error messages

## Files Changed

1. `app/assets/views/PageRow.vue` - Use singular model label consistently
2. `app/assets/composables/useCRUD6Breadcrumbs.ts` - Improved breadcrumb detection and empty title handling
3. `app/assets/components/CRUD6/UnifiedModal.vue` - Updated to use CRUD6.VALIDATION namespace
4. `app/locale/en_US/messages.php` - Moved VALIDATION under CRUD6 namespace
5. `app/locale/fr_FR/messages.php` - Moved VALIDATION under CRUD6 namespace

## Testing Recommendations

1. **Breadcrumb Testing**:
   - Navigate to `/crud6/users/8` from list page
   - Verify breadcrumb shows: "UserFrosting / Admin Panel / User / user01"
   - Refresh the page (F5)
   - Verify breadcrumb still shows: "UserFrosting / Admin Panel / User / user01"
   - Navigate to different user (e.g., `/crud6/users/9`)
   - Verify breadcrumb updates correctly

2. **VALIDATION Translation Testing**:
   - Open password change modal for a user
   - Verify all field labels and hints are translated
   - Submit invalid password (too short, doesn't match)
   - Verify error messages are translated
   - Check UFAlerts for proper translated messages

3. **Multi-language Testing**:
   - Switch locale to French (fr_FR)
   - Verify breadcrumbs use translated model names
   - Verify VALIDATION messages appear in French
   - Switch back to English and verify translations

## Technical Notes

### Why Singular vs Plural?

The schema has two title fields:
- `title`: Plural form for list pages (e.g., "Users", "CRUD6.USER.PAGE")
- `singular_title`: Singular form for detail pages (e.g., "User", "CRUD6.USER.1")

For breadcrumbs leading TO the list page (from detail pages), we should use singular because:
1. The breadcrumb represents "go back to Users list"
2. Using singular is more consistent with detail page context
3. Avoids confusion between list title and breadcrumb label

### Translation Namespacing Best Practices

Nested translations under sprinkle namespace:
- ✓ Prevents conflicts with core UserFrosting translations
- ✓ Makes it clear which sprinkle owns the translations
- ✓ Allows proper scoping in multi-sprinkle applications
- ✓ Follows UserFrosting 6 patterns (e.g., `CRUD6.CREATE`, `CRUD6.UPDATE`)

## Related Issues

- Original issue: "crud6/users/8 - when the page loads the breadcrumb is 'UserFrosting / Admin Panel / User /   /', after i refresh the same page now I see 'UserFrosting / Admin Panel / Users /   User / user01'"
- Translation keys not working in password change screen
- UFAlerts showing untranslated keys

## Commit

Commit: fab0055
Message: "Fix breadcrumb duplication and VALIDATION translation issues"
