# Schema-Driven Custom Action Permission

## Change Summary
Made custom action permission checks schema-driven instead of hardcoded.

## Previous Implementation (Hardcoded)
```php
// CustomActionController.php line 111
$this->validateAccess($crudSchema, 'update');  // ❌ Hardcoded 'update'
```

This hardcoded the permission check to use 'update' action, which violated the principle of being schema-driven.

## New Implementation (Schema-Driven)
```php
// CustomActionController.php line 112
$this->validateAccess($crudSchema, 'custom_action');  // ✅ Uses schema
```

Now uses 'custom_action' as the action name, which is resolved from the schema's `permissions` section.

## How It Works

### 1. Schema Definition
Schemas now define a `custom_action` permission:
```json
{
  "permissions": {
    "read": "uri_crud6",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user",
    "custom_action": "update_user_field"
  }
}
```

### 2. Permission Resolution
When `validateAccess($crudSchema, 'custom_action')` is called (Base.php line 171):
```php
$permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
```

This resolves to:
- **If defined in schema**: Uses `$schema['permissions']['custom_action']` (e.g., `update_user_field`)
- **If not in schema**: Falls back to `crud6.{model}.custom_action` (e.g., `crud6.users.custom_action`)

### 3. Default Permissions
Added `custom_action` to default permissions seed (DefaultPermissions.php line 88):
```php
$actions = ['read', 'create', 'edit', 'delete', 'custom_action'];
```

This ensures `crud6.{model}.custom_action` permissions exist in the database.

## Benefits

1. **Schema-Driven**: Aligns with CRUD6's principle of driving functionality from schema
2. **Flexible**: Each model can map `custom_action` to different permissions
3. **Backward Compatible**: Falls back to default permission if not in schema
4. **Consistent**: Follows same pattern as other CRUD operations

## Updated Files
- `app/src/Controller/CustomActionController.php` - Changed hardcoded 'update' to schema-driven 'custom_action'
- `app/src/Database/Seeds/DefaultPermissions.php` - Added 'custom_action' to default actions
- `examples/schema/users.json` - Added `"custom_action": "update_user_field"`
- `examples/schema/groups.json` - Added `"custom_action": "update_group_field"`
- `examples/schema/roles.json` - Added `"custom_action": "update_role_field"`
- `examples/schema/permissions.json` - Added `"custom_action": "update_permission"`

## Test Compatibility
Tests remain compatible because:
- Tests give users `update_user_field` permission
- Schema maps `custom_action` → `update_user_field`
- Controller now checks `custom_action` which resolves to `update_user_field`
- Tests pass as before

## Example Usage

### Action with Specific Permission
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

### Action without Specific Permission
```json
{
  "actions": [
    {
      "key": "enable_user",
      // No permission defined
      ...
    }
  ]
}
```
Falls back to `custom_action` permission from schema:
- Looks up `$schema['permissions']['custom_action']`
- Resolves to `update_user_field`
- User needs `update_user_field` permission

## Comparison with Other Controllers

| Controller | Action | Hardcoded? | Schema Permission |
|------------|--------|-----------|-------------------|
| ApiAction | `read` | ✅ Yes | `permissions.read` |
| CreateAction | `create` | ✅ Yes | `permissions.create` |
| DeleteAction | `delete` | ✅ Yes | `permissions.delete` |
| EditAction | `edit` | ✅ Yes | `permissions.edit` |
| UpdateFieldAction | `update` | ✅ Yes | `permissions.update` |
| CustomActionController | `custom_action` | ✅ Yes | `permissions.custom_action` |

All controllers use schema-driven permission resolution. The action name is standardized, but the actual permission slug comes from the schema.
