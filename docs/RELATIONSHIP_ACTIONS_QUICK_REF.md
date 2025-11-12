# Relationship Actions Quick Reference

## Basic Setup

Add `actions` to relationship in schema:

```json
{
  "relationships": [{
    "name": "roles",
    "type": "many_to_many",
    "pivot_table": "role_users",
    "foreign_key": "user_id",
    "related_key": "role_id",
    "actions": {
      "on_create": { ... },
      "on_update": { ... },
      "on_delete": { ... }
    }
  }]
}
```

## Quick Examples

### Assign Default Role on Create

```json
{
  "on_create": {
    "attach": [
      {
        "related_id": 2,
        "pivot_data": {
          "created_at": "now",
          "updated_at": "now"
        }
      }
    ]
  }
}
```

### Sync Roles on Update

```json
{
  "on_update": {
    "sync": "role_ids"
  }
}
```

### Clean Up on Delete

```json
{
  "on_delete": {
    "detach": "all"
  }
}
```

## Special Values

| Value | Replacement |
|-------|-------------|
| `"now"` | Current timestamp |
| `"current_user"` | Auth user ID |
| `"current_date"` | Today (Y-m-d) |

## Action Types

### `attach` - Add relationships
- Used in: `on_create`, `on_update`
- Format: Array of objects with `related_id` and optional `pivot_data`

### `sync` - Synchronize relationships
- Used in: `on_update`
- Format: Field name (string) or `true` for auto field name

### `detach` - Remove relationships
- Used in: `on_update`, `on_delete`
- Format: `"all"` or array of IDs to remove

## Complete Example

```json
{
  "model": "users",
  "relationships": [{
    "name": "roles",
    "type": "many_to_many",
    "pivot_table": "role_users",
    "foreign_key": "user_id",
    "related_key": "role_id",
    "actions": {
      "on_create": {
        "attach": [{"related_id": 2, "pivot_data": {"created_at": "now"}}]
      },
      "on_update": {
        "sync": "role_ids"
      },
      "on_delete": {
        "detach": "all"
      }
    }
  }],
  "fields": {
    "role_ids": {"type": "multiselect", "label": "Roles", "editable": true}
  }
}
```

## See Full Documentation

📖 [docs/RELATIONSHIP_ACTIONS.md](RELATIONSHIP_ACTIONS.md)
