# Schema-Driven Permission Check (Simplified)

## Change Summary
Custom actions now use the existing `update` permission from the schema, which is already schema-driven.

## Implementation
```php
// CustomActionController.php line 112
$this->validateAccess($crudSchema, 'update');  // ✅ Schema-driven
```

## How It Works

### Schema Definition (Already Exists)
Schemas define standard CRUD permissions:
```json
{
  "permissions": {
    "read": "uri_crud6",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  }
}
```

### Permission Resolution
When `validateAccess($crudSchema, 'update')` is called (Base.php line 171):
```php
$permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
```

This resolves to:
- **From schema**: Uses `$schema['permissions']['update']` (e.g., `update_user_field`)
- **Fallback**: If not in schema, uses `crud6.{model}.update`

This is **already schema-driven** - no need to add a new `custom_action` field!

## Benefits

1. **Simpler**: Uses existing schema structure
2. **Schema-Driven**: Permission comes from `$schema['permissions']['update']`
3. **Consistent**: Custom actions are updates, so they use update permission
4. **No Schema Changes**: Works with existing schemas

## Why This Works

Custom actions modify records (similar to PUT/UPDATE operations), so they logically use the `update` permission that's already defined in the schema. The `validateAccess` method automatically looks up the permission from the schema, making it schema-driven without requiring any new fields.

## Example Flow

1. User requests custom action on user record
2. Controller calls: `validateAccess($crudSchema, 'update')`
3. Base.php looks up: `$schema['permissions']['update']`
4. Returns: `update_user_field`
5. Checks if user has `update_user_field` permission

If the action defines its own permission in the schema, that takes precedence:
```json
{
  "actions": [
    {
      "key": "reset_password",
      "permission": "reset_user_password",  // ✅ Uses this specific permission
      ...
    }
  ]
}
```
