# Relationship Pivot Table Actions - Design Proposal

## Overview

This document proposes a schema-driven approach to automatically manage pivot table entries when creating, updating, or deleting records with many-to-many relationships.

## Use Case

When creating a new user, automatically assign them to a default role by inserting a record in the `role_users` pivot table.

## Current State

The CRUD6 system already supports defining relationships in the schema:

```json
{
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "title": "ROLE.2"
    }
  ]
}
```

However, this only defines how to **read** relationships, not how to automatically manage them during create/update/delete operations.

## Proposed Solution

### Schema Extension

Add an `actions` property to relationship definitions:

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
        "on_create": {
          "attach": [
            {
              "related_id": 2,
              "description": "Assign default 'User' role"
            }
          ]
        },
        "on_update": {
          "sync": true,
          "field": "role_ids",
          "description": "Sync roles from form input"
        },
        "on_delete": {
          "detach": "all",
          "description": "Remove all role associations"
        }
      }
    }
  ]
}
```

### Action Types

#### 1. `on_create` Actions

Triggered when a new record is created.

**Options:**
- `attach`: Array of related records to attach
  - `related_id`: ID of the related record (e.g., role ID)
  - `pivot_data`: Optional additional pivot table data (e.g., timestamps)

**Example:**
```json
{
  "on_create": {
    "attach": [
      {
        "related_id": 2,
        "pivot_data": {
          "assigned_by": 1,
          "assigned_at": "now"
        }
      }
    ]
  }
}
```

#### 2. `on_update` Actions

Triggered when a record is updated.

**Options:**
- `sync`: Boolean or field name
  - `true`: Sync from a form field (default field name: `{relationship_name}_ids`)
  - String: Custom field name to read IDs from
- `attach`: Array of additional IDs to always attach
- `detach`: Array of IDs to always detach

**Example:**
```json
{
  "on_update": {
    "sync": "role_ids",
    "attach": [1],
    "description": "Sync user roles, always keep admin role"
  }
}
```

#### 3. `on_delete` Actions

Triggered when a record is deleted.

**Options:**
- `detach`: "all" or array of specific IDs
- `cascade`: Boolean - whether to cascade delete related records (use with caution)

**Example:**
```json
{
  "on_delete": {
    "detach": "all",
    "description": "Remove all role associations when user is deleted"
  }
}
```

### Implementation Approach

#### Phase 1: CreateAction Enhancement

Modify `CreateAction::handle()` to process relationship actions after record creation:

```php
protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
{
    // Existing code...
    $record = $this->db->transaction(function () use ($crudModel, $schema, $data, $currentUser) {
        // Insert the record (existing code)
        $insertId = $this->db->table($table)->insertGetId($insertData, $primaryKey);
        $crudModel = $crudModel->newQuery()->find($insertId);
        
        // NEW: Process relationship actions
        $this->processRelationshipActions($crudModel, $schema, $data, 'on_create');
        
        // Create activity record (existing code)
        $this->userActivityLogger->info("...");
        
        return $crudModel;
    });
    
    return $record;
}

protected function processRelationshipActions(
    CRUD6ModelInterface $model, 
    array $schema, 
    array $data, 
    string $event
): void {
    if (!isset($schema['relationships'])) {
        return;
    }
    
    foreach ($schema['relationships'] as $relationship) {
        if (!isset($relationship['actions'][$event])) {
            continue;
        }
        
        $action = $relationship['actions'][$event];
        $relationName = $relationship['name'];
        
        // Handle attach action
        if (isset($action['attach'])) {
            foreach ($action['attach'] as $attachConfig) {
                $relatedId = $attachConfig['related_id'];
                $pivotData = $attachConfig['pivot_data'] ?? [];
                
                // Process special values like "now" for timestamps
                $pivotData = $this->processPivotData($pivotData);
                
                // Attach the related record
                $model->{$relationName}()->attach($relatedId, $pivotData);
                
                $this->debugLog("CRUD6 [CreateAction] Attached relationship", [
                    'model' => $schema['model'],
                    'relationship' => $relationName,
                    'related_id' => $relatedId,
                ]);
            }
        }
    }
}
```

#### Phase 2: UpdateAction Enhancement

Similar modifications to handle `on_update` actions:

```php
protected function processRelationshipActions(
    CRUD6ModelInterface $model, 
    array $schema, 
    array $data, 
    string $event
): void {
    // ... existing on_create handling ...
    
    // Handle sync action (on_update)
    if ($event === 'on_update' && isset($action['sync'])) {
        $fieldName = is_string($action['sync']) 
            ? $action['sync'] 
            : $relationName . '_ids';
        
        if (isset($data[$fieldName])) {
            $relatedIds = is_array($data[$fieldName]) 
                ? $data[$fieldName] 
                : [$data[$fieldName]];
            
            $model->{$relationName}()->sync($relatedIds);
            
            $this->debugLog("CRUD6 [UpdateAction] Synced relationship", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'related_ids' => $relatedIds,
            ]);
        }
    }
}
```

#### Phase 3: DeleteAction Enhancement

Handle `on_delete` actions:

```php
protected function processRelationshipActions(
    CRUD6ModelInterface $model, 
    array $schema, 
    array $data, 
    string $event
): void {
    // ... existing handlers ...
    
    // Handle detach action (on_delete)
    if ($event === 'on_delete' && isset($action['detach'])) {
        if ($action['detach'] === 'all') {
            $model->{$relationName}()->detach();
            
            $this->debugLog("CRUD6 [DeleteAction] Detached all relationships", [
                'model' => $schema['model'],
                'relationship' => $relationName,
            ]);
        } elseif (is_array($action['detach'])) {
            $model->{$relationName}()->detach($action['detach']);
            
            $this->debugLog("CRUD6 [DeleteAction] Detached specific relationships", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'related_ids' => $action['detach'],
            ]);
        }
    }
}
```

### Configuration Examples

#### Example 1: Default Role Assignment

```json
{
  "model": "users",
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
              "description": "Assign default 'Member' role to new users"
            }
          ]
        },
        "on_delete": {
          "detach": "all"
        }
      }
    }
  ]
}
```

#### Example 2: Dynamic Role Management

```json
{
  "fields": {
    "role_ids": {
      "type": "multiselect",
      "label": "Roles",
      "lookup_model": "roles",
      "editable": true
    }
  },
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "actions": {
        "on_create": {
          "attach": [{"related_id": 2}]
        },
        "on_update": {
          "sync": "role_ids"
        },
        "on_delete": {
          "detach": "all"
        }
      }
    }
  ]
}
```

#### Example 3: Audit Trail with Pivot Data

```json
{
  "relationships": [
    {
      "name": "projects",
      "type": "many_to_many",
      "pivot_table": "user_projects",
      "foreign_key": "user_id",
      "related_key": "project_id",
      "actions": {
        "on_create": {
          "attach": [
            {
              "related_id": 1,
              "pivot_data": {
                "role": "member",
                "assigned_at": "now",
                "assigned_by": "current_user"
              }
            }
          ]
        }
      }
    }
  ]
}
```

### Special Values

The implementation should support special values in `pivot_data`:

- `"now"`: Current timestamp
- `"current_user"`: ID of the currently authenticated user
- `"current_date"`: Current date (Y-m-d format)

### Security Considerations

1. **Permission Checks**: Relationship actions should respect existing permissions
   - Only create pivot entries if user has permission to manage the relationship
   - Add optional `permission` field to relationship actions

2. **Validation**: Validate that related IDs exist before attaching
   - Check foreign key constraints
   - Verify related records exist in the database

3. **Configuration Validation**: Validate relationship action configuration on schema load
   - Ensure required fields are present
   - Check for valid action types

### Benefits

1. **Declarative Configuration**: Define relationship behavior in schema, not code
2. **Consistency**: Same pattern across all models
3. **Maintainability**: Easy to modify default behaviors
4. **Auditable**: Clear documentation of automatic relationship management
5. **Flexible**: Supports various use cases (defaults, sync, cleanup)

### Migration Path

1. **Optional Feature**: Make relationship actions completely optional
   - Existing schemas continue to work without changes
   - Opt-in by adding `actions` to relationships

2. **Backward Compatibility**: 
   - No breaking changes to existing API
   - Existing relationship definitions continue to work for read operations

3. **Gradual Adoption**:
   - Start with simple use cases (default role assignment)
   - Expand to more complex scenarios (syncing, cascading)

### Testing Strategy

1. **Unit Tests**: Test individual action processors
2. **Integration Tests**: Test full create/update/delete flows
3. **Schema Validation Tests**: Test various schema configurations
4. **Edge Cases**: Test error handling, missing related IDs, permission failures

### Documentation Requirements

1. Update schema documentation with relationship actions
2. Add examples for common use cases
3. Document special values and their behavior
4. Add troubleshooting guide for common issues

### Future Enhancements

1. **Conditional Actions**: Execute actions based on conditions
   ```json
   {
     "on_create": {
       "attach": [
         {
           "related_id": 2,
           "condition": {
             "field": "account_type",
             "equals": "premium"
           }
         }
       ]
     }
   }
   ```

2. **Custom Pivot Table Data**: Support complex pivot data from form inputs
   ```json
   {
     "on_update": {
       "sync": "role_ids",
       "pivot_fields": ["assigned_at", "assigned_by"]
     }
   }
   ```

3. **Cascade Actions**: Define cascading behavior for related records
   ```json
   {
     "on_delete": {
       "cascade": true,
       "models": ["permissions"]
     }
   }
   ```

## Conclusion

This proposal provides a comprehensive, schema-driven approach to managing pivot table entries automatically. It maintains backward compatibility while adding powerful new capabilities for relationship management in CRUD6.

The implementation is straightforward, building on existing UserFrosting/Eloquent relationship methods, and provides a clear upgrade path for existing schemas.
