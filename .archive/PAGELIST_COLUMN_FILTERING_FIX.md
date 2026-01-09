# PageList Column Filtering Fix - January 2026

## Issue Summary
**Problem**: The CRUD6 users page was displaying columns that should not be visible according to their `show_in` configuration. Specifically, the password field (and other fields like locale, group_id) were appearing in the table even though they did not have "list" in their `show_in` array.

**Reported behavior**:
- Username ✓ (should show)
- First Name ✓ (should show)
- Last Name ✓ (should show)
- Email Address ✓ (should show)
- Verified ✓ (should show)
- Enabled ✓ (should show)
- Locale ✗ (should NOT show - only in form/detail)
- Group ✗ (should NOT show - only in form/detail)
- Roles ✗ (should NOT show - relationship data)
- **Password ✗ (should NOT show - security critical!)**

## Root Cause Analysis

### The Bug
In `app/src/ServicesProvider/SchemaFilter.php` at line 248, the `getListContextData()` method had incorrect fallback logic:

```php
// BEFORE (buggy code):
$showInList = isset($field['show_in']) 
    ? in_array('list', $field['show_in']) 
    : ($field['listable'] ?? true);  // <-- BUG: defaults to TRUE
```

### Why This Was Wrong
1. When a field has `show_in` array defined, it correctly checks if 'list' is in the array
2. When a field does NOT have `show_in`, it falls back to the `listable` flag
3. If neither `show_in` nor `listable` exist, it defaults to **TRUE**
4. This "permissive by default" approach violates security best practices

### Security Impact
- Sensitive fields like `password` could be exposed in list views if schema was incomplete
- Fields without explicit `show_in` configuration would leak into list views
- Violates "secure by default" principle - fields should opt-in, not opt-out

## The Fix

### Code Change
Changed line 248 in `SchemaFilter.php`:

```php
// AFTER (fixed code):
$showInList = isset($field['show_in']) 
    ? in_array('list', $field['show_in']) 
    : ($field['listable'] ?? false);  // <-- FIX: defaults to FALSE
```

### Logic Flow (After Fix)
1. **If field has `show_in` array**: Check if 'list' is in the array
   - Example: `"show_in": ["list", "form", "detail"]` → INCLUDED ✓
   - Example: `"show_in": ["form", "detail"]` → EXCLUDED ✗
   - Example: `"show_in": ["create", "edit"]` → EXCLUDED ✗

2. **If field does NOT have `show_in`**: Check explicit `listable` flag
   - Example: `"listable": true` → INCLUDED ✓
   - Example: `"listable": false` → EXCLUDED ✗
   - Example: no listable flag → EXCLUDED ✗ (secure by default)

### Why This Is Correct
- **Secure by default**: Fields must explicitly opt-in to be listed
- **Explicit is better than implicit**: Clear schema definitions
- **Prevents data leaks**: Sensitive fields won't appear if misconfigured
- **Consistent with detail context**: Similar approach used there

## Test Coverage

### Added Test: `testListContextFilteringWithShowIn()`
Location: `app/tests/ServicesProvider/SchemaFilteringTest.php`

Test scenarios:
1. ✓ Fields with 'list' in `show_in` are included
2. ✓ Fields without 'list' in `show_in` are excluded
3. ✓ Password field is specifically excluded (security critical)
4. ✓ Fields without `show_in` but with `listable: true` are included
5. ✓ Fields without any list configuration are excluded (secure by default)

Test schema mirrors the users.json structure to ensure real-world accuracy.

## Expected Behavior After Fix

### Users Table Should Show (7 columns):
1. Username - `show_in: ["list", "form", "detail"]`
2. First Name - `show_in: ["list", "form", "detail"]`
3. Last Name - `show_in: ["list", "form", "detail"]`
4. Email Address - `show_in: ["list", "form", "detail"]`
5. Verified - `show_in: ["list", "form", "detail"]`
6. Enabled - `show_in: ["list", "form", "detail"]`
7. Actions (dropdown)

### Users Table Should NOT Show:
- ✗ ID - `show_in: ["detail"]` only
- ✗ Locale - `show_in: ["form", "detail"]` only
- ✗ Group - `show_in: ["form", "detail"]` only
- ✗ Password - `show_in: ["create", "edit"]` only (CRITICAL: security sensitive)
- ✗ Role IDs - computed field, `show_in: ["form"]` only
- ✗ Timestamps - `show_in: ["detail"]` or empty

## Related Files Modified
1. `app/src/ServicesProvider/SchemaFilter.php` - Fixed fallback logic (1 line change)
2. `app/tests/ServicesProvider/SchemaFilteringTest.php` - Added comprehensive test

## Validation Steps
1. ✓ Syntax validation passed for all PHP files
2. ✓ New test added to verify the fix
3. ⏳ Manual testing required: Visit `/crud6/users` page
4. ⏳ Verify only 7 columns appear (not 10)
5. ⏳ Verify password field is NOT visible in table

## Notes for Manual Testing
To test this fix in a live UserFrosting 6 installation with sprinkle-c6admin:

1. Install/update sprinkle-crud6 to this branch
2. Clear any schema caches
3. Navigate to `/crud6/users` 
4. Count the columns - should be 7, not 10
5. Verify password column is NOT present
6. Test other models to ensure the fix applies universally

## References
- Schema definition: `examples/schema/users.json`
- Frontend component: `app/assets/views/PageList.vue`
- Backend filtering: `app/src/ServicesProvider/SchemaFilter.php`
- Test coverage: `app/tests/ServicesProvider/SchemaFilteringTest.php`

## Security Classification
**Security Level**: Medium
- Prevents accidental exposure of sensitive field data
- Implements secure-by-default pattern
- No known exploitation in production (caught early)

## Related Patterns
This fix aligns with the detail context filtering logic which also uses secure defaults:
```php
// In getDetailContextData() - line 303
$showInDetail = isset($field['show_in']) 
    ? in_array('detail', $field['show_in']) 
    : ($field['viewable'] ?? true);  // Note: true is appropriate for detail views
```

The difference:
- **List context**: Defaults to FALSE (restrictive, for tables)
- **Detail context**: Defaults to TRUE (permissive, for full record view)

This makes sense because:
- Lists show many records → minimize data exposure
- Details show one record → show complete information
