# Breadcrumb Debugging Guide

This document explains how to enable and use debug logging to diagnose breadcrumb issues in CRUD6.

## Enabling Debug Mode

### Backend Configuration

Add the following to your `app/config/default.php` file:

```php
return [
    // ... other config ...
    
    'crud6' => [
        'debug_mode' => true,  // Enable CRUD6 debug logging
    ],
];
```

### What Debug Mode Does

When `crud6.debug_mode` is enabled:

1. **Backend**: No specific backend logging for breadcrumbs (breadcrumbs are frontend-only)
2. **Frontend**: Detailed console logging for breadcrumb operations

## Debug Output

With debug mode enabled, open your browser's developer console (F12) and you'll see detailed logs like:

### PageList.vue Logs

```
[PageList.onMounted] Starting - model: users
[PageList.onMounted] Current page.breadcrumbs: [...]
[PageList.onMounted] Initial title: Users
[PageList.onMounted] Calling setListBreadcrumb with initial title
[PageList.onMounted] After setListBreadcrumb, page.breadcrumbs: [...]
```

### useCRUD6Breadcrumbs Logs

```
[useCRUD6Breadcrumbs.setListBreadcrumb] Called with modelTitle: Users
[useCRUD6Breadcrumbs.updateBreadcrumbs] Called with: {title: "Users", path: undefined}
[useCRUD6Breadcrumbs.updateBreadcrumbs] After nextTick
[useCRUD6Breadcrumbs.updateBreadcrumbs] Existing breadcrumbs: [{label: "UserFrosting", to: "/"}, {label: "C6ADMIN_PANEL", to: "/c6/admin"}, {label: "{{model}}", to: "/crud6/users"}]
[useCRUD6Breadcrumbs.updateBreadcrumbs] Current path: /crud6/users
[useCRUD6Breadcrumbs.updateBreadcrumbs] Found {{model}} placeholder in breadcrumb: {label: "{{model}}", to: "/crud6/users"}
[useCRUD6Breadcrumbs.updateBreadcrumbs] Breadcrumbs updated, applying deduplication
[useCRUD6Breadcrumbs.updateBreadcrumbs] Final breadcrumbs after deduplication: [...]
[useCRUD6Breadcrumbs.updateBreadcrumbs] Completed. Final page.breadcrumbs: [...]
```

## What to Look For

When diagnosing breadcrumb issues, check:

1. **Does {{model}} appear in "Existing breadcrumbs"?**
   - YES: The breadcrumb composable should replace it
   - NO: The route meta.title might not be set correctly

2. **Is "Found {{model}} placeholder" logged?**
   - YES: The replacement logic is working
   - NO: The placeholder detection might be failing

3. **Are breadcrumbs updated after the replacement?**
   - Compare "Existing breadcrumbs" with "Final breadcrumbs after deduplication"
   - Check if {{model}} was replaced with the actual model name

4. **Does usePageMeta.refresh() run after our update?**
   - If breadcrumbs revert to {{model}} after our update, there's a timing issue
   - Look for patterns where breadcrumbs change multiple times

## Common Issues

### Issue: Breadcrumbs revert to {{model}} after update

**Symptoms**: Debug logs show successful replacement, but breadcrumbs still show {{model}}

**Cause**: usePageMeta.refresh() is being called after our update, overwriting our changes

**Solution**: The composable uses nextTick() to wait for usePageMeta.refresh() to complete. If this still happens, there may be multiple refresh() calls.

### Issue: {{model}} not found in breadcrumbs

**Symptoms**: Debug logs show "No {{model}} placeholder found"

**Cause**: The route meta.title is not set to 'CRUD6.PAGE', or the translation doesn't produce '{{model}}'

**Solution**: Check the route definition and translation files

### Issue: Breadcrumbs update but then disappear

**Symptoms**: Breadcrumbs show correctly briefly, then disappear

**Cause**: The route might be missing required meta fields

**Solution**: Ensure route meta has both `title` and proper structure

## Disabling Debug Mode

Remove or set to false in `app/config/default.php`:

```php
'crud6' => [
    'debug_mode' => false,
],
```

Then refresh the page to stop seeing debug logs.
