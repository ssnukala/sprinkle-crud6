# Additional Fix: Removed Unused Variable Causing Null Pointer Error

## Problem
After removing the invalid import from `CRUD6.php`, the integration tests were still failing with seed validation errors.

**Reference**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18334202688/job/52215051954

## Root Cause
The `DefaultPermissions.php` file contained an unused variable that was causing a null pointer error:

```php
protected function getPermissions(): array
{
    $defaultRoleIds = [
        'crud6-admin' => Role::where('slug', 'crud6-admin')->first()->id, // ❌ NULL POINTER!
    ];

    return [
        // ... permissions
    ];
}
```

### Why This Failed
1. The variable `$defaultRoleIds` was defined but **never used** anywhere in the code
2. It attempted to access `->id` on the result of `first()`, which could be `null`
3. If the role query failed or returned null for any reason, this caused a fatal error
4. Even though seeds were run in order (DefaultRoles before DefaultPermissions), any timing or loading issue would cause this to fail

## Solution
Removed the unused variable declaration (lines 43-45):

```diff
  protected function getPermissions(): array
  {
-     $defaultRoleIds = [
-         'crud6-admin' => Role::where('slug', 'crud6-admin')->first()->id,
-     ];
-
      return [
```

## Verification

### Before Fix ❌
```php
// Lines 43-45
$defaultRoleIds = [
    'crud6-admin' => Role::where('slug', 'crud6-admin')->first()->id,
];
// Variable defined but never used
// Causes null pointer if first() returns null
```

### After Fix ✅
```php
// Lines 43-45 removed
// getPermissions() now starts directly with return statement
// No dangerous null pointer access
```

## Impact
- **Lines removed**: 4
- **Risk**: None (removed dead code)
- **Benefit**: Eliminates potential null pointer errors
- **Performance**: Slight improvement (no unnecessary database query)

## Why This Wasn't Caught Earlier
1. The code has a PHPStan ignore comment, suggesting it was known to be problematic
2. The variable was never used, making it easy to overlook
3. It only fails when the role doesn't exist or can't be loaded
4. Works fine in development when seeds are run in perfect order

## Files Changed
- **app/src/Database/Seeds/DefaultPermissions.php** (-4 lines)

## Commit
- Hash: `8d1c35b`
- Message: "Remove unused variable that causes null pointer error in DefaultPermissions"

## Related Issues
This was the second issue preventing seeds from working:
1. **First issue**: Invalid import in CRUD6.php (fixed in dc77f74)
2. **Second issue**: Unused variable causing null pointer in DefaultPermissions.php (fixed in 8d1c35b)

Both issues are now resolved.
