# Fix Summary: Ambiguous Column 'id' in WHERE Clause

**Date**: 2025-11-19  
**Issue**: MySQL error "Column 'id' in where clause is ambiguous"  
**PR**: copilot/fix-sprunje-request-error

## Problem Description

When querying relationships in CRUD6 (specifically roles -> users), a MySQL error occurred:

```
SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'id' in where clause is ambiguous
```

The SQL query generated was:
```sql
select count(*) as aggregate from (
  select `users`.*, MAX(activities.occurred_at) as last_activity 
  from `users` 
  inner join `activities` on `activities`.`user_id` = `users`.`id` 
  where `id` = 1  -- ❌ Ambiguous: which table's id?
  and `users`.`deleted_at` is null 
  group by `users`.`id`
) c
```

## Root Cause

The issue occurred because:

1. **Schema Configuration**: The `roles.json` schema defines a many-to-many relationship with users but the `details` array entry for users didn't specify a `foreign_key`:
   ```json
   "details": [
       {
           "model": "users",
           "list_fields": ["user_name", "first_name", "last_name", "email"],
           "title": "ROLE.USERS"
           // No foreign_key specified!
       }
   ]
   ```

2. **Code Default**: The code defaulted to `'id'` when no `foreign_key` was specified (line 175 of SprunjeAction.php):
   ```php
   $foreignKey = $detailConfig['foreign_key'] ?? 'id';
   ```

3. **Unqualified WHERE Clause**: When `UserSprunje` was used (which joins with `activities` table), the unqualified `id` column became ambiguous:
   ```php
   $this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
       return $query->where($foreignKey, $crudModel->id); // ❌ Ambiguous 'id'
   });
   ```

## Solution

The fix involves qualifying all column names with table names to prevent ambiguity:

### 1. SprunjeAction.php - UserSprunje Users Relation

**Before**:
```php
$this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
    return $query->where($foreignKey, $crudModel->id);
});
```

**After**:
```php
// Handle many-to-many relationship with proper JOIN
if ($relationshipConfig !== null && $relationshipConfig['type'] === 'many_to_many') {
    $this->userSprunje->extendQuery(function ($query) use ($crudModel, $relationshipConfig) {
        $pivotTable = $relationshipConfig['pivot_table'];
        $foreignKey = $relationshipConfig['foreign_key'];
        $relatedKey = $relationshipConfig['related_key'];
        
        return $query->join(
            $pivotTable,
            "users.id",  // ✅ Qualified
            '=',
            "{$pivotTable}.{$relatedKey}"
        )->where("{$pivotTable}.{$foreignKey}", $crudModel->id);  // ✅ Qualified
    });
} else {
    // Direct relationship - qualify the column with table name
    $this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey, $relatedSchema) {
        $relatedTable = $relatedSchema['table'] ?? 'users';
        $qualifiedForeignKey = strpos($foreignKey, '.') !== false 
            ? $foreignKey 
            : "{$relatedTable}.{$foreignKey}";  // ✅ Qualified
        return $query->where($qualifiedForeignKey, $crudModel->id);
    });
}
```

### 2. SprunjeAction.php - One-to-Many Fallback

**Before**:
```php
$this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
    return $query->where($foreignKey, $crudModel->id);
});
```

**After**:
```php
$this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey, $relatedModel) {
    $relatedTable = $relatedModel->getTable();
    $qualifiedForeignKey = strpos($foreignKey, '.') !== false 
        ? $foreignKey 
        : "{$relatedTable}.{$foreignKey}";  // ✅ Qualified
    return $query->where($qualifiedForeignKey, $crudModel->id);
});
```

### 3. CRUD6Sprunje.php - filterSearch Method

**Before**:
```php
return $query->where(function ($subQuery) use ($value) {
    foreach ($this->filterable as $field) {
        $subQuery->where($field, 'LIKE', "%{$value}%");  // ❌ Unqualified
    }
});
```

**After**:
```php
return $query->where(function ($subQuery) use ($value, $tableName) {
    foreach ($this->filterable as $field) {
        $qualifiedField = strpos($field, '.') !== false 
            ? $field 
            : "{$tableName}.{$field}";  // ✅ Qualified
        $subQuery->where($qualifiedField, 'LIKE', "%{$value}%");
    }
});
```

## Testing

Created comprehensive test suite in `app/tests/Integration/RoleUsersRelationshipTest.php`:

1. **testRoleUsersNestedEndpointHandlesAmbiguousColumn**: Tests the exact scenario that caused the error
2. **testRoleUsersNestedEndpointWithNoUsers**: Tests empty result handling
3. **testRoleUsersNestedEndpointWithPagination**: Tests pagination with qualified columns

All tests use the actual c6admin schemas (roles.json) for validation.

## Files Modified

1. `app/src/Controller/SprunjeAction.php` - Added table qualification for UserSprunje and one-to-many queries
2. `app/src/Sprunje/CRUD6Sprunje.php` - Added table qualification for filterSearch method
3. `app/tests/Integration/RoleUsersRelationshipTest.php` - New comprehensive test suite

## Prevention

To prevent similar issues in the future:

1. **Always qualify column names** in WHERE clauses when the query might have joins
2. **Check for existing qualifiers** using `strpos($field, '.') !== false`
3. **Use relationship configurations** from schema when available (many-to-many, belongs-to-many-through)
4. **Test with UserSprunje** which has built-in joins and is more likely to expose ambiguity issues

## Verification

The fix can be verified by:

1. Running the new test suite: `vendor/bin/phpunit app/tests/Integration/RoleUsersRelationshipTest.php`
2. Testing the endpoint manually: `GET /api/crud6/roles/{id}/users`
3. Checking logs for the absence of the "Column 'id' in where clause is ambiguous" error

## References

- Issue reported in: [MySQL error in userfrosting.log]
- Schema used: `app/schema/crud6/roles.json`
- UserFrosting Sprunje pattern: sprinkle-admin UserSprunje
- SQL error: SQLSTATE[23000]: Integrity constraint violation: 1052
