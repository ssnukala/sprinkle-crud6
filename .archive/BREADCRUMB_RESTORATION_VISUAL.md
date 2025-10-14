# Visual Summary - Breadcrumb Translation Key Restoration

## What Changed

### Route Metadata Changes

**Before (Previous Fix):**
```typescript
// app/assets/routes/CRUD6Routes.ts
meta: {
    title: '',              // Empty string
    description: ''         // Empty string
}
```

**After (Current Restoration):**
```typescript
// app/assets/routes/CRUD6Routes.ts
meta: {
    title: 'CRUD6.PAGE',              // Translation key
    description: 'CRUD6.PAGE_DESCRIPTION'  // Translation key
}
```

## Translation Resolution

### Translation File
```php
// app/locale/en_US/messages.php
'CRUD6' => [
    'PAGE'             => '{{model}}',
    'PAGE_DESCRIPTION' => 'A listing of the {{model}} for your site. Provides management tools for editing and deleting {{model}}.',
]
```

### How It Resolves

1. **Route Registration** (Build/Static Time)
   - Route defines: `title: 'CRUD6.PAGE'`
   - Translation system looks up: `CRUD6.PAGE`
   - Finds: `{{model}}`
   - Result: `title = '{{model}}'` (literal string)

2. **Breadcrumb Display** (Runtime)
   - Breadcrumb reads route meta: `title = '{{model}}'`
   - Displays: **`UserFrosting / {{model}}`**
   - The `{{model}}` is shown as literal text because there's no runtime context to replace it

## Visual Comparison

### List Page (`/crud6/groups`)

**Current Restoration:**
```
UserFrosting / {{model}}
```

**Previous Fix (Empty Strings):**
```
UserFrosting / Group Management
```

### Detail Page (`/crud6/groups/1`)

**Current Restoration:**
```
UserFrosting / {{model}} / {{model}}
```

**Previous Fix (Empty Strings):**
```
UserFrosting / Group Management / [Record Name]
```

## Why This Happens

### Translation Key Issue
- Translation keys are evaluated at **route registration time** (static/build time)
- The `{{model}}` placeholder is a translation parameter that needs **runtime context**
- At route registration, there is no model context available
- Result: The literal `{{model}}` string is used as the title

### Previous Solution (Empty Strings)
- Used empty strings in route meta: `title: ''`
- Vue components dynamically set `page.title` after mounting
- Components have access to the model parameter at runtime
- Result: Actual model name is displayed

## Files Changed

1. **app/assets/routes/CRUD6Routes.ts**
   - Restored translation keys for title and description
   - Applied to 3 routes: parent, list, and view

2. **.archive/BREADCRUMB_RESTORATION.md**
   - Documents the restoration
   - Explains the behavior

## Purpose of Restoration

This restoration allows the user to:
1. ✅ See the original behavior where `{{model}}` was visible as literal text
2. ✅ Debug and understand where the breadcrumb issue is coming from
3. ✅ Compare with the previous fix to identify the problem
4. ✅ Understand the translation system's behavior

## Next Steps

Once the user debugs the issue, they may want to:
- Revert back to empty strings if they want dynamic model names
- Keep translation keys if they prefer a different approach
- Modify the translation system to support runtime model replacement
