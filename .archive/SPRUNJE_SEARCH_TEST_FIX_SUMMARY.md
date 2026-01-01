# CRUD6Sprunje Search Test Fix Summary

## CI Failure Analysis
- **Workflow Run**: [#20633960995](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20633960995/job/59256237622)
- **Test**: `CRUD6SprunjeSearchTest.php:155` (testSearchCaseInsensitive)
- **Error**: `General error: 1 no such column: groups. (Connection: memory, SQL: select count(*) as aggregate from "groups" where "groups"."" is null)`

## Root Cause

The error shows an empty string column name being used: `"groups".""`. This was caused by Laravel's SoftDeletes trait attempting to apply a global scope with an invalid column name.

### Chain of Events
1. Test creates data using UserFrosting's `Group` model (from Account sprinkle with SoftDeletes)
2. Test gets `CRUD6Sprunje` from container, which uses `CRUD6Model` (also has SoftDeletes trait)
3. Test calls `setupSprunje('groups', ...)` which sets table to 'groups' but does NOT configure schema
4. When query executes, Laravel's `SoftDeletingScope` calls `getQualifiedDeletedAtColumn()`
5. CRUD6Model's `getDeletedAtColumn()` returns `null` (correctly, since no schema configured)
6. BUT `getQualifiedDeletedAtColumn()` was NOT overridden, so it used the trait's default implementation
7. The trait's implementation calls `qualifyColumn(null)` which somehow generates an empty string column name
8. SQL is generated as: `WHERE "groups"."" IS NULL` - causing the error

## Previous Fix Attempt (PR #346)

PR #346 added comprehensive checks to `getDeletedAtColumn()` to prevent empty strings from being returned. However, it did NOT override `getQualifiedDeletedAtColumn()`, which is the method actually called by Laravel's scope.

## Complete Fix

### Code Changes

**File**: `app/src/Database/Models/CRUD6Model.php`

Added override for `getQualifiedDeletedAtColumn()`:

```php
/**
 * Get the fully qualified "deleted at" column.
 * 
 * Overrides the SoftDeletes trait method to handle the case where
 * getDeletedAtColumn() returns null (soft deletes disabled).
 * 
 * CRITICAL: This prevents Laravel from generating SQL with empty column names
 * like: WHERE "table"."" IS NULL
 * 
 * @return string|null
 */
public function getQualifiedDeletedAtColumn(): ?string
{
    $column = $this->getDeletedAtColumn();
    
    // If soft deletes are disabled (no column name), return null
    // This tells Laravel's SoftDeletingScope to not apply the WHERE clause
    if ($column === null || $column === '') {
        return null;
    }
    
    // Otherwise, return the table-qualified column name
    return $this->qualifyColumn($column);
}
```

### Test Coverage

Added tests to `app/tests/Database/Models/CRUD6ModelTest.php`:

1. **testGetQualifiedDeletedAtColumnWithNullColumn**: Verifies null is returned when soft deletes are disabled
2. **testGetQualifiedDeletedAtColumnWithValidColumn**: Verifies qualified column name is returned when soft deletes are enabled
3. **testGetQualifiedDeletedAtColumnWithEmptyString**: Verifies edge case where empty string is handled properly

## Why This Fix Works

1. When CRUD6Model is used without schema configuration, `getDeletedAtColumn()` returns `null`
2. Now `getQualifiedDeletedAtColumn()` also returns `null` instead of trying to qualify a null column
3. Laravel's `SoftDeletingScope` checks if `getQualifiedDeletedAtColumn()` returns a value before applying the WHERE clause
4. With `null` returned, the scope doesn't apply any soft delete filtering
5. No invalid SQL is generated

## Impact

This fix resolves:
- ✅ CRUD6SprunjeSearchTest failure on line 155 (testSearchCaseInsensitive)
- ✅ Any other cases where CRUD6Model is used without schema configuration
- ✅ Prevents SQL errors when using CRUD6Model with UserFrosting tables (groups, users, roles, permissions)

## Related Issues

- PR #346: Added checks to `getDeletedAtColumn()` but missed `getQualifiedDeletedAtColumn()`
- This fix complements PR #346 by completing the SoftDeletes trait override

## Testing

To verify the fix:
```bash
# Run the specific failing test
vendor/bin/phpunit app/tests/Sprunje/CRUD6SprunjeSearchTest.php::testSearchCaseInsensitive

# Run all Sprunje tests
vendor/bin/phpunit app/tests/Sprunje/

# Run all tests to ensure no regressions
vendor/bin/phpunit
```

## Lessons Learned

1. When overriding trait behavior, check ALL methods that might be called, not just the primary one
2. Laravel's SoftDeletes trait uses multiple methods (`getDeletedAtColumn()` and `getQualifiedDeletedAtColumn()`)
3. Both methods need to be overridden to fully control soft delete behavior in dynamic models
4. Test coverage should include edge cases like null/empty string handling in all related methods

## Future Considerations

- Consider adding a check in CRUD6Sprunje to verify schema is configured before using CRUD6Model
- OR add a method to auto-configure CRUD6Model based on existing table structure
- Document that CRUD6Model should be configured with schema before use in production
