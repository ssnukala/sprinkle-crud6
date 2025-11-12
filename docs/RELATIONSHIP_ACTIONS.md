# Relationship Actions Implementation Guide

## Overview

The schema-based relationship actions feature has been **fully implemented** in CRUD6. This allows you to automatically manage pivot table entries when creating, updating, or deleting records with many-to-many relationships.

## Features Implemented

✅ **Automatic pivot table management** through schema configuration  
✅ **Three event types**: `on_create`, `on_update`, `on_delete`  
✅ **Three action types**: `attach`, `sync`, `detach`  
✅ **Special pivot data values**: `now`, `current_user`, `current_date`  
✅ **Transaction safety**: All actions execute within database transactions  
✅ **Error handling**: Failures rollback entire operation  

## Schema Configuration

### Basic Structure

Add an `actions` property to relationship definitions in your schema:

```json
{
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "title": "ROLE.2",
      "actions": {
        "on_create": { ... },
        "on_update": { ... },
        "on_delete": { ... }
      }
    }
  ]
}
```

## Action Types

### 1. `on_create` - Triggered After Record Creation

Automatically attach related records when creating a new record.

**Example: Assign Default Role to New Users**

```json
{
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "actions": {
        "on_create": {
          "attach": [
            {
              "related_id": 2,
              "description": "Assign default 'User' role",
              "pivot_data": {
                "created_at": "now",
                "updated_at": "now"
              }
            }
          ]
        }
      }
    }
  ]
}
```

**What happens:**
1. User record is created
2. Entry is automatically inserted into `role_users` table
3. User is assigned role ID 2 (the default "User" role)

### 2. `on_update` - Triggered After Record Update

Synchronize related records based on form input.

**Example: Sync User Roles from Form**

```json
{
  "fields": {
    "role_ids": {
      "type": "multiselect",
      "label": "Roles",
      "editable": true
    }
  },
  "relationships": [
    {
      "name": "roles",
      "actions": {
        "on_update": {
          "sync": "role_ids",
          "description": "Sync user roles from form input"
        }
      }
    }
  ]
}
```

**What happens:**
1. User updates the form with selected role IDs
2. Eloquent `sync()` is called automatically
3. New roles are attached, unchanged ones kept, removed ones detached

**Sync Options:**
- `"sync": true` - Uses field name `{relationship_name}_ids` (e.g., `roles_ids`)
- `"sync": "custom_field"` - Uses specified field name from form data

### 3. `on_delete` - Triggered Before Record Deletion

Clean up related records when deleting a record.

**Example: Remove All Role Associations**

```json
{
  "relationships": [
    {
      "name": "roles",
      "actions": {
        "on_delete": {
          "detach": "all",
          "description": "Remove all role associations"
        }
      }
    }
  ]
}
```

**What happens:**
1. Before user deletion, all entries in `role_users` are removed
2. Then the user record is deleted
3. No orphaned pivot table entries remain

**Detach Options:**
- `"detach": "all"` - Removes all related records
- `"detach": [1, 2, 3]` - Removes only specified IDs

## Special Pivot Data Values

The system processes special placeholder values in `pivot_data`:

| Value | Replaced With | Example Use Case |
|-------|--------------|------------------|
| `"now"` | Current timestamp | `created_at`, `updated_at` |
| `"current_user"` | Authenticated user's ID | `assigned_by`, `created_by` |
| `"current_date"` | Current date (Y-m-d) | `assigned_date` |

**Example with All Special Values:**

```json
{
  "on_create": {
    "attach": [
      {
        "related_id": 1,
        "pivot_data": {
          "assigned_at": "now",
          "assigned_by": "current_user",
          "assignment_date": "current_date",
          "custom_field": "static value"
        }
      }
    ]
  }
}
```

## Complete Example

### User Schema with Relationship Actions

**File:** `examples/schema/users-relationship-actions.json`

```json
{
  "model": "users",
  "table": "users",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "actions": {
        "on_create": {
          "attach": [
            {
              "related_id": 2,
              "description": "Assign default 'User' role",
              "pivot_data": {
                "created_at": "now",
                "updated_at": "now"
              }
            }
          ]
        },
        "on_update": {
          "sync": "role_ids",
          "description": "Sync user roles from form"
        },
        "on_delete": {
          "detach": "all",
          "description": "Clean up role associations"
        }
      }
    }
  ],
  "fields": {
    "role_ids": {
      "type": "multiselect",
      "label": "Roles",
      "editable": true
    }
  }
}
```

## Implementation Details

### Controllers Modified

1. **CreateAction** - Processes `on_create` actions after record creation
2. **EditAction** - Processes `on_update` actions after record update
3. **DeleteAction** - Processes `on_delete` actions before record deletion

### ProcessesRelationshipActions Trait

**Location:** `app/src/Controller/Traits/ProcessesRelationshipActions.php`

**Methods:**
- `processRelationshipActions()` - Main orchestrator
- `processAttachAction()` - Handles attach operations
- `processSyncAction()` - Handles sync operations
- `processDetachAction()` - Handles detach operations
- `processPivotData()` - Processes special values

### Transaction Safety

All relationship actions execute within the same database transaction as the main operation:

```php
$this->db->transaction(function () use (...) {
    // 1. Insert/Update/Delete main record
    // 2. Process relationship actions
    // 3. Log activity
    
    // If ANY step fails, entire transaction rolls back
});
```

## Error Handling

### Validation

The system validates:
- ✅ Relationship has a `name` property
- ✅ Action configuration is properly formatted
- ✅ Special values are correctly processed

### Logging

All relationship actions are logged:

```php
$this->debugLog("CRUD6 [RelationshipActions] Attached relationship", [
    'event' => 'on_create',
    'model' => 'users',
    'relationship' => 'roles',
    'related_id' => 2,
    'pivot_data' => ['created_at' => '2025-01-15 10:30:00']
]);
```

### Error Recovery

If a relationship action fails:
1. Error is logged with full context
2. Exception is re-thrown
3. Transaction is rolled back
4. No partial changes are committed

## Usage Examples

### Example 1: Simple Default Assignment

Assign a default role when creating users:

```json
{
  "on_create": {
    "attach": [{"related_id": 2}]
  }
}
```

### Example 2: Multiple Defaults

Assign multiple default roles:

```json
{
  "on_create": {
    "attach": [
      {"related_id": 2, "description": "User role"},
      {"related_id": 5, "description": "Guest role"}
    ]
  }
}
```

### Example 3: Audit Trail

Track who assigned the relationship:

```json
{
  "on_create": {
    "attach": [
      {
        "related_id": 2,
        "pivot_data": {
          "assigned_by": "current_user",
          "assigned_at": "now",
          "notes": "Auto-assigned on registration"
        }
      }
    ]
  }
}
```

### Example 4: Conditional Sync

Only sync if field is present:

```json
{
  "on_update": {
    "sync": "role_ids"
  }
}
```

If `role_ids` is not in the request, sync is skipped.

### Example 5: Partial Cleanup

Remove specific relationships on delete:

```json
{
  "on_delete": {
    "detach": [2, 3, 5]
  }
}
```

## Migration from Manual Approach

### Before (Manual Custom Controller)

```php
class UserCreateAction extends CreateAction {
    protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface {
        $user = parent::handle($crudModel, $schema, $request);
        $user->roles()->attach(2);
        return $user;
    }
}
```

### After (Schema-Based)

**Remove custom controller, add to schema:**

```json
{
  "relationships": [{
    "name": "roles",
    "actions": {
      "on_create": {
        "attach": [{"related_id": 2}]
      }
    }
  }]
}
```

**Benefits:**
- ✅ No PHP code changes needed
- ✅ Configuration in one place (schema)
- ✅ Same pattern across all models
- ✅ Easier to maintain and modify

## Testing

### Manual Testing Steps

1. **Test on_create:**
   - Create a new user via API
   - Check `role_users` table for automatic entry
   - Verify role ID 2 is assigned

2. **Test on_update:**
   - Update user with `role_ids: [1, 3, 5]`
   - Verify `role_users` table is synced
   - Check old roles removed, new ones added

3. **Test on_delete:**
   - Delete a user
   - Verify all `role_users` entries removed
   - Check no orphaned pivot records

### Debugging

Enable debug logging to see relationship actions:

```php
$this->logger->debug("CRUD6 [RelationshipActions] ...", [
    'event' => 'on_create',
    'model' => 'users',
    'relationship' => 'roles'
]);
```

## Best Practices

### 1. Use Descriptions

Always add descriptions for documentation:

```json
{
  "on_create": {
    "attach": [
      {
        "related_id": 2,
        "description": "Assign default 'User' role - required for basic access"
      }
    ]
  }
}
```

### 2. Include Timestamps

Always include timestamps in pivot data:

```json
{
  "pivot_data": {
    "created_at": "now",
    "updated_at": "now"
  }
}
```

### 3. Handle Form Fields

For `on_update` with `sync`, ensure the field exists in schema:

```json
{
  "fields": {
    "role_ids": {
      "type": "multiselect",
      "label": "Roles",
      "editable": true
    }
  }
}
```

### 4. Clean Up on Delete

Always detach relationships on delete to avoid orphaned records:

```json
{
  "on_delete": {
    "detach": "all"
  }
}
```

## Troubleshooting

### Issue: Relationships not attaching

**Check:**
1. Schema has correct relationship definition
2. `actions` property is at correct level (inside relationship)
3. `related_id` actually exists in related table
4. Pivot table exists and has correct columns

### Issue: Sync not working

**Check:**
1. Field name matches sync configuration
2. Field is included in update request data
3. Field data is array format: `[1, 2, 3]`

### Issue: Transaction rollback

**Check:**
1. Debug logs for error messages
2. Related IDs exist in database
3. Pivot table constraints are satisfied
4. No database-level errors

## Security Considerations

### Permission Checks

Relationship actions respect existing permissions:
- Only execute if user has permission for the main operation
- No separate permission checks needed for relationship actions

### Validation

Always validate related IDs:
- The system attempts the operation
- Database foreign key constraints provide safety
- Transaction rollback on failure

### Audit Logging

All actions are logged:
- User who performed the action
- Event type (create/update/delete)
- Relationship details
- Affected records

## Performance Considerations

### Batch Operations

Each `attach` in the array is a separate operation:

```json
{
  "attach": [
    {"related_id": 1},
    {"related_id": 2},
    {"related_id": 3}
  ]
}
```

For many relationships, consider:
- Using `sync` instead of multiple `attach` calls
- Limiting number of default assignments

### Query Optimization

The system uses Eloquent's optimized methods:
- `attach()` - Single query per relationship
- `sync()` - Optimized diff and bulk operations
- `detach()` - Bulk delete operations

## Future Enhancements

Potential future additions:
1. Conditional actions based on field values
2. Custom pivot data from request fields
3. Cascade delete for related records
4. Validation of related IDs before attaching
5. Hooks for custom processing logic

## Summary

Schema-based relationship actions provide a **declarative, maintainable** way to manage pivot table entries automatically. The implementation is:

✅ **Production-ready**  
✅ **Transaction-safe**  
✅ **Backward compatible**  
✅ **Well-documented**  
✅ **Easy to use**  

No custom controllers needed—just configure your schema and let CRUD6 handle the rest!
