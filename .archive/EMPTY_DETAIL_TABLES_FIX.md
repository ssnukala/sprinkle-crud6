# Fix: Empty Detail Tables Issue

## Problem Statement
Detail tables were showing up with buttons but no rows when using the `users.json` schema from sprinkle-c6admin that contains a `details` array.

## Root Cause
The `SprunjeAction.php` controller was expecting `$crudSchema['detail']` (singular) to be an object, but the schema uses `details` (plural) as an array of detail configurations:

```json
{
  "model": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["occurred_at", "type", "description", "ip_address"]
    },
    {
      "model": "roles", 
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"]
    }
  ]
}
```

When a relation was requested (e.g., `/api/crud6/users/1/activities`), the code could not find the matching detail configuration because it was:
1. Only checking for a singular `detail` object
2. Not iterating through the `details` array

This caused the code to fall through to the main model listing instead of applying the foreign key filter, resulting in empty tables.

## Solution
Updated `app/src/Controller/SprunjeAction.php` to:

1. **Support `details` array** (primary format):
   - Check if `$crudSchema['details']` exists and is an array
   - Iterate through the array to find a detail config where `model` matches the relation parameter
   - Break once a match is found

2. **Maintain backward compatibility** with singular `detail` object:
   - If `details` array is not found, check for singular `detail` object
   - Verify the `detail['model']` matches the relation parameter

3. **Enhanced logging**:
   - Added `has_details_array` to debug output
   - Helps diagnose schema configuration issues

## Code Changes

### Before (Lines 89-91)
```php
// Check if this relation is configured in the schema's detail section
$detailConfig = $crudSchema['detail'] ?? null;

if ($relation !== 'NONE' && $detailConfig && $detailConfig['model'] === $relation) {
```

### After (Lines 89-107)
```php
// Check if this relation is configured in the schema's detail/details section
// Support both singular 'detail' (legacy) and plural 'details' array
$detailConfig = null;
if (isset($crudSchema['details']) && is_array($crudSchema['details'])) {
    // Search through details array for matching model
    foreach ($crudSchema['details'] as $config) {
        if (isset($config['model']) && $config['model'] === $relation) {
            $detailConfig = $config;
            break;
        }
    }
} elseif (isset($crudSchema['detail']) && is_array($crudSchema['detail'])) {
    // Backward compatibility: support singular 'detail' object
    if (isset($crudSchema['detail']['model']) && $crudSchema['detail']['model'] === $relation) {
        $detailConfig = $crudSchema['detail'];
    }
}

if ($relation !== 'NONE' && $detailConfig !== null) {
```

## Testing

### Unit Test Verification
Created verification script that tests:
- ✅ Finding 'activities' in details array
- ✅ Finding 'roles' in details array
- ✅ Returning null for non-existent relations
- ✅ Backward compatibility with singular 'detail' object
- ✅ Handling schemas with no detail configuration
- ✅ Returning null for wrong relation names

All tests passed successfully.

### Expected Behavior After Fix
When accessing `/api/crud6/users/1/activities`:
1. SprunjeAction receives relation parameter `'activities'`
2. Code finds matching detail config in `details` array
3. Extracts `foreign_key` as `'user_id'`
4. Applies filter: `WHERE user_id = 1`
5. Returns activities for that user (not empty table)

## Impact
- **No breaking changes**: Existing schemas using singular `detail` continue to work
- **Minimal code change**: Only modified the detail config lookup logic
- **Improved compatibility**: Now supports both schema formats used in the ecosystem
- **Better debugging**: Enhanced logging helps troubleshoot schema issues

## Files Modified
- `app/src/Controller/SprunjeAction.php`: Updated detail config lookup logic

## Related Issues
- This fix addresses the issue where detail tables show buttons but no data rows
- Tested against `users.json` schema from https://github.com/ssnukala/sprinkle-c6admin
