# Leveraging UserFrosting's Relationship Components

## Overview
This document describes how CRUD6 now leverages UserFrosting's built-in relationship handling components instead of manually building JOIN queries.

## Changes Made

### 1. Added Dynamic Relationship Method to CRUD6Model

Added `dynamicRelationship()` method to CRUD6Model that creates relationships using UserFrosting's built-in methods:

```php
public function dynamicRelationship(
    string $relationName, 
    array $config, 
    string $relatedClass
): \Illuminate\Database\Eloquent\Relations\Relation
```

This method supports:
- **`many_to_many`**: Uses `belongsToMany()` for standard many-to-many relationships
- **`belongs_to_many_through`**: Uses `belongsToManyThrough()` for nested relationships

### 2. Updated SprunjeAction to Use Framework Components

SprunjeAction now:
1. **Attempts to use UserFrosting's relationship methods first** (via `dynamicRelationship()`)
2. **Falls back to manual JOINs** if needed for backward compatibility

### Advantages

**Before (Manual Approach)**:
```php
$query->join($pivotTable, ...)
    ->where($pivotForeignKey, $id)
```

**After (Framework Approach)**:
```php
$relationship = $crudModel->dynamicRelationship($name, $config, $relatedClass);
return $relationship->getQuery();
```

**Benefits**:
- ✅ Leverages UserFrosting's tested relationship handling
- ✅ Automatically handles pivot table queries
- ✅ Supports eager loading and relationship methods
- ✅ Cleaner, more maintainable code
- ✅ Compatible with UserFrosting's `BelongsToManyThrough` for nested relationships

## Schema Configuration

### Standard Many-to-Many (e.g., roles)

```json
{
  "relationships": [{
    "name": "roles",
    "type": "many_to_many",
    "pivot_table": "role_user",
    "foreign_key": "user_id",
    "related_key": "role_id"
  }]
}
```

Uses: `belongsToMany()` → Handled by UserFrosting's relationship system

### Nested Many-to-Many (e.g., permissions through roles)

**Option 1: Explicit belongsToManyThrough configuration**
```json
{
  "details": [{
    "model": "permissions",
    "through": "UserFrosting\\Sprinkle\\Account\\Database\\Models\\Role",
    "type": "belongs_to_many_through",
    "first_pivot_table": "role_user",
    "first_foreign_key": "user_id",
    "first_related_key": "role_id",
    "second_pivot_table": "role_permission",
    "second_foreign_key": "role_id",
    "second_related_key": "permission_id"
  }]
}
```

Uses: `belongsToManyThrough()` → UserFrosting's nested relationship handling

**Option 2: Fallback to manual JOINs (backward compatible)**
```json
{
  "details": [{
    "model": "permissions",
    "foreign_key": "user_id"
  }]
}
```

Falls back to manual JOIN approach (current implementation)

## Implementation Details

### Dynamic Models Support

The key insight is that UserFrosting's `belongsToMany()` and `belongsToManyThrough()` methods don't require hard-coded model classes. They accept:
- Class strings (e.g., `get_class($relatedModel)`)
- Table names
- Column names

This makes them perfect for CRUD6's dynamic model approach.

### Backward Compatibility

The implementation maintains backward compatibility:
1. Checks if relationship configuration exists
2. Attempts to use UserFrosting's methods if possible
3. Falls back to manual JOINs if needed
4. Existing schemas continue to work unchanged

## Future Enhancements

### Potential Improvements
1. **Eager Loading**: Support `->with()` for loading relationships
2. **Relationship Caching**: Cache created relationship instances
3. **Custom Relationship Types**: Support more relationship types (hasMany, hasOne, etc.)
4. **Schema Validation**: Validate relationship configurations at load time

### Migration Path

To fully leverage UserFrosting's components:
1. Update schema to include full `belongsToManyThrough` configuration
2. Test with `->withVia()` for intermediate models
3. Consider eager loading for performance optimization

## References

- UserFrosting's `BelongsToManyThrough`: `/tmp/sprinkle-core/app/src/Database/Relations/BelongsToManyThrough.php`
- Test Example: `/tmp/sprinkle-core/app/tests/Integration/Sprunje/SprunjeBelongsToManyThroughTest.php`
- HasRelationships Trait: `/tmp/sprinkle-core/app/src/Database/Models/Concerns/HasRelationships.php`
