# Custom Action Permission Fix

## Issue
CustomActionTest had 2 failing tests:
- `testCustomActionWithAuthentication` 
- `testMultipleCustomActionsWithAuth`

Both tests were receiving 403 Forbidden errors when they should have received 404 (action not found) or 200 (action executed).

## Root Cause

### Before the Fix
`CustomActionController.php` line 90 had:
```php
$this->validateAccess($crudSchema, 'edit');  // Checks for 'edit' permission
```

This checked for 'edit' permission (`crud6.users.edit`) BEFORE checking if the action even exists or has its own permission requirements.

### The Problem
1. Test user is given `update_user_field` permission
2. Controller checks for `crud6.users.edit` permission first (doesn't exist)
3. User doesn't have `crud6.users.edit` → Returns 403 Forbidden
4. Never reaches the proper permission checks at lines 107-114

## CRUD6 Permission Model

### Standard Permissions
In CRUD6, the standard permissions are:
- `read` - View records (GET /api/crud6/{model})
- `create` - Create records (POST /api/crud6/{model})
- `update` - Modify records (PUT /api/crud6/{model}/{id})
- `delete` - Remove records (DELETE /api/crud6/{model}/{id})

### Edit vs Update
- `edit` permission - Used by EditAction for BOTH GET (view form) and PUT (update) operations
- `update` permission - Mapped in schema to specific field-level update permissions like `update_user_field`

Custom actions are record modifications similar to PUT updates, so they should use `update` permission.

## The Fix

Removed the premature 'edit' permission check from CustomActionController (line 90).

### New Flow (Lines 88-112)
```php
try {
    // Find the action configuration in the schema
    $actionConfig = $this->findActionConfig($crudSchema, $actionKey);
    
    if ($actionConfig === null) {
        throw new NotFoundException("Action '{$actionKey}' not found...");
    }
    
    // Check permission for the action
    if (isset($actionConfig['permission'])) {
        $this->validateActionPermission($actionConfig['permission']);
    } else {
        // If no specific permission, check update permission
        $this->validateAccess($crudSchema, 'update');
    }
```

### Why This Is Correct

1. **Action-specific permissions**: If an action defines its own permission in the schema, that's checked first
2. **Falls back to 'update'**: Otherwise checks 'update' permission (maps to `update_user_field` for users)
3. **Proper error handling**: Returns 404 for missing actions, not 403
4. **Consistent with UpdateFieldAction**: UpdateFieldAction also uses 'update' permission
5. **Schema-driven**: Honors the schema's permission mapping (`"update": "update_user_field"`)

## Test Results

### testCustomActionWithAuthentication
- User with `update_user_field` permission
- Requests action `test_action` (doesn't exist in schema)
- **Before**: 403 Forbidden ❌
- **After**: 404 Not Found ✅

### testMultipleCustomActionsWithAuth
- User with `update_user_field` permission  
- Requests actions `enable_user` and `disable_user` (don't exist in schema)
- **Before**: 403 Forbidden for both ❌
- **After**: 404 Not Found for both ✅

## Related Files
- `app/src/Controller/CustomActionController.php` - Fixed permission check
- `examples/schema/users.json` - Defines `"update": "update_user_field"`
- `app/src/Database/Seeds/DefaultPermissions.php` - Seeds permissions from schemas
- `app/src/Controller/UpdateFieldAction.php` - Uses 'update' permission (consistent)
- `app/src/Controller/EditAction.php` - Uses 'edit' permission (for view+update combined)

## Summary
Custom actions should check 'update' permission by default (unless action specifies otherwise), not 'edit' permission. This aligns with the schema permission model and allows tests with `update_user_field` permission to execute custom actions.
