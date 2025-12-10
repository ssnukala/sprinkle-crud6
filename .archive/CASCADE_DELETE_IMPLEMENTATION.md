# Cascade Delete Implementation

**Date:** 2024-12-10  
**Issue:** Foreign key constraint violations during user deletion  
**PR:** copilot/add-delete-cascade-functionality

## Problem Statement

Integration tests were failing with foreign key constraint violations when attempting to delete users that had related activity records:

```
SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: 
a foreign key constraint fails (`userfrosting_test`.`activities`, CONSTRAINT `activities_user_id_foreign` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`))
```

## Root Cause

The `DeleteAction` controller was attempting to delete parent records (users) without first deleting dependent child records (activities). The database enforced referential integrity through foreign key constraints, preventing orphaned child records.

## Solution Overview

Implemented **schema-driven cascade deletion** that:

1. Reads the `details` configuration from the schema to identify child relationships
2. Automatically deletes child records before deleting the parent
3. Supports both soft delete and hard delete modes
4. Uses transactions to ensure atomicity

## Implementation Details

### Files Modified

1. **`app/src/Controller/Traits/ProcessesRelationshipActions.php`**
   - Added `cascadeDeleteChildRecords()` method
   - Handles both soft and hard cascade deletion
   - Configurable per-child behavior

2. **`app/src/Controller/DeleteAction.php`**
   - Calls `cascadeDeleteChildRecords()` before deleting parent
   - Passes soft delete flag to cascade method
   - All within existing database transaction

3. **`app/tests/Controller/DeleteActionTest.php`**
   - Added `testCascadeDeleteChildRecords()` test
   - Verifies cascade deletion works correctly
   - Tests with activities as child records

### Cascade Delete Logic

The `cascadeDeleteChildRecords()` method:

```php
protected function cascadeDeleteChildRecords(
    CRUD6ModelInterface $model,
    array $schema,
    $schemaService,
    bool $softDelete = false
): void
```

**Process:**

1. Reads `details` array from parent schema
2. For each detail with a `foreign_key`:
   - Loads the child model schema
   - Configures a CRUD6Model instance for the child
   - Queries for all child records matching the parent ID
   - Deletes each child record (soft or hard based on strategy)

**Delete Strategy:**

| Parent Delete Type | Child Has Soft Delete | Result |
|-------------------|----------------------|---------|
| Hard Delete | Yes | Hard delete child |
| Hard Delete | No | Hard delete child |
| Soft Delete | Yes | Soft delete child |
| Soft Delete | No | Hard delete child |

### Schema Configuration

The cascade delete feature uses the existing `details` configuration:

```json
{
  "model": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "title": "CRUD6.ACTIVITY.2"
    }
  ]
}
```

**Optional Configuration:**

```json
{
  "model": "activities",
  "foreign_key": "user_id",
  "cascade_delete": false,           // Disable cascade for this child
  "cascade_delete_mode": "hard"      // Force hard delete (auto/hard/soft)
}
```

### Soft Delete Support

When the parent model uses soft deletes (`soft_delete: true` in schema):

1. Child records that support soft deletes are also soft deleted
2. Child records without soft delete support are hard deleted
3. This maintains data integrity while preserving audit trails

### Transaction Safety

All cascade deletion occurs within the existing database transaction in `DeleteAction`:

```php
$this->db->transaction(function () use (...) {
    // 1. Cascade delete child records
    $this->cascadeDeleteChildRecords($crudModel, $crudSchema, $this->schemaService, $isSoftDelete);
    
    // 2. Process relationship actions (detach pivot records)
    $this->processRelationshipActions($crudModel, $crudSchema, [], 'on_delete');
    
    // 3. Delete the parent record
    if ($isSoftDelete) {
        $crudModel->softDelete();
    } else {
        $crudModel->delete();
    }
    
    // 4. Log the activity
    $this->userActivityLogger->info(...);
});
```

If any step fails, the entire transaction is rolled back.

## Testing

### New Test: `testCascadeDeleteChildRecords()`

This test:

1. Creates a user with 3 activity records
2. Verifies activities exist in database
3. Deletes the user via API
4. Confirms user is soft deleted
5. Confirms all activities are hard deleted (cascade)

### Expected Behavior

- ✅ User deletion succeeds (no foreign key violations)
- ✅ Child activities are deleted before parent
- ✅ All operations are atomic (transaction)
- ✅ Soft delete parent → hard delete children (activities don't support soft delete)

## Benefits

1. **No Code Changes Required**: Works automatically based on schema
2. **Referential Integrity**: Prevents orphaned child records
3. **Audit Trail Preserved**: Soft delete support maintains history where applicable
4. **Configurable**: Per-child cascade behavior can be customized
5. **Transaction Safe**: All-or-nothing deletion ensures consistency

## Future Enhancements

Potential improvements for future versions:

1. **Cascade Restore**: When restoring a soft-deleted parent, restore children too
2. **Circular Reference Detection**: Prevent infinite loops in complex relationships
3. **Bulk Delete Optimization**: Batch delete child records for performance
4. **Cascade Depth Limit**: Prevent excessive cascade chains
5. **Pre/Post Delete Hooks**: Allow custom logic during cascade

## Migration Guide

Existing schemas automatically benefit from cascade deletion:

1. **No Schema Changes Required**: If `details` section exists with `foreign_key`, cascade works
2. **Opt-Out**: Add `cascade_delete: false` to disable for specific children
3. **Force Hard Delete**: Add `cascade_delete_mode: "hard"` to force hard delete even with soft parent

## Related Documentation

- Schema structure: `examples/schema/users.json`
- Relationship actions: `ProcessesRelationshipActions` trait
- Delete action: `DeleteAction` controller
- Integration tests: `SchemaBasedApiTest.php`
