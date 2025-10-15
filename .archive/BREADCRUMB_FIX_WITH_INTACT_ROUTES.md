# Breadcrumb Fix - Detail Page with Intact CRUD6Routes.ts

## Date
October 15, 2025

## Issue
After PR 108 reverted PR 106, the breadcrumb on detail pages was showing:
```
UserFrosting / Hippos - Group
```

Instead of the expected:
```
UserFrosting / Groups / Hippos - Group
```

The middle level (model name "Groups") was missing from the breadcrumb hierarchy.

## Background

### PR 106 (Merged, then reverted)
- Removed `title` and `description` from CRUD6Routes.ts meta
- Fixed breadcrumb display on detail pages
- Changed PageRow.vue to use capitalized model name

### PR 108 (Revert)
- Reverted PR 106 completely
- Restored `title` and `description` to CRUD6Routes.ts
- Reason: PR 106 removed breadcrumbs altogether

## Problem
With the revert (PR 108), CRUD6Routes.ts has the metadata restored, but PageRow.vue was also reverted to use `schema.value.title` which causes:
- List page: Shows "Group Management" ✅ (correct)
- Detail page: Shows "Group Management" initially, then "Hippos - Group" ❌ (missing "Groups" level)

## Root Cause
In PageRow.vue (line 254 before fix):
```typescript
page.title = schema.value.title || modelLabel.value
```

This sets the page title to "Group Management" (schema.title) instead of "Groups" (capitalized model name).

When the record loads, it updates to "Hippos - Group", but the parent breadcrumb level never gets set to "Groups".

## Solution
Applied only the PageRow.vue change from PR 106, while keeping CRUD6Routes.ts intact:

### Change Made
**File: `app/assets/views/PageRow.vue`** (Lines 252-257)

```typescript
// Before (line 254)
page.title = schema.value.title || modelLabel.value

// After (lines 255-256)
const capitalizedModel = model.value.charAt(0).toUpperCase() + model.value.slice(1)
page.title = capitalizedModel
```

### Why This Works
- CRUD6Routes.ts keeps its `title` and `description` metadata (no breadcrumb removal)
- PageRow.vue dynamically overrides the route metadata at runtime
- Uses capitalized model name ("Groups") for breadcrumb hierarchy
- After record loads, updates to "Hippos - Group" (record name)
- Breadcrumb properly shows: UserFrosting / Groups / Hippos - Group

## Results

### List Page (`/crud6/groups`)
```
UserFrosting / Group Management  ✅
```
Unchanged - still uses schema.title

### Detail Page (`/crud6/groups/1`)
```
Before: UserFrosting / Hippos - Group  ❌
After:  UserFrosting / Groups / Hippos - Group  ✅
```

## Data Flow

### Detail Page Flow (Fixed)
```
1. Component mounts
   └─> page.title = "Groups" (initial, capitalized model)

2. Schema loads
   └─> page.title = "Groups" (keeps capitalized model, not schema.title)

3. Record fetches
   └─> page.title = "Hippos - Group" (record name + singular)

4. Breadcrumb renders
   └─> UserFrosting / Groups / Hippos - Group ✅
```

## Files Changed
- **app/assets/views/PageRow.vue** - Use capitalized model name for breadcrumb hierarchy

## Files NOT Changed (as required)
- **app/assets/routes/CRUD6Routes.ts** - Remains intact with title and description

## Testing
All tests pass:
```
✓ app/assets/tests/router/routes.test.ts (2 tests)
✓ app/assets/tests/components/imports.test.ts (3 tests)
```

No test changes required because:
- Route structure unchanged
- Tests verify route has title and description ✅
- Vue component behavior tested separately

## Key Benefits
✅ Breadcrumb shows correct model name on detail pages
✅ CRUD6Routes.ts remains intact (no breaking changes)
✅ List page behavior unchanged
✅ Minimal code change (4 lines)
✅ All tests passing
✅ Consistent with PR 106 approach but without removing route metadata

## Comparison with PR 106

| Aspect | PR 106 | This Fix |
|--------|--------|----------|
| CRUD6Routes.ts | Removed title/description | Kept intact |
| PageRow.vue | Used capitalized model | Used capitalized model |
| Tests | Updated to expect no title | No changes needed |
| Breadcrumb | Fixed | Fixed |
| Impact | Breaking change (removed meta) | Non-breaking (override) |

## Related Documentation
- `.archive/BREADCRUMB_DETAIL_PAGE_FIX.md` - PR 106 documentation
- `.archive/BREADCRUMB_DETAIL_PAGE_VISUAL.md` - PR 106 visual comparison
- `app/locale/en_US/messages.php` - Translation keys (CRUD6.PAGE = "{{model}}")
- `app/schema/crud6/groups.json` - Schema structure example

## Translation Keys
From `app/locale/en_US/messages.php`:
```php
'CRUD6' => [
    'PAGE'             => '{{model}}',
    'PAGE_DESCRIPTION' => 'A listing of the {{model}} for your site...',
    'INFO_PAGE'        => 'View and edit {{model}} details.',
]
```

These translation keys remain in CRUD6Routes.ts but are overridden at runtime by Vue component.
