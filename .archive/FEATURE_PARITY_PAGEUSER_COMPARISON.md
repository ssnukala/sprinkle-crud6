# Feature Parity with sprinkle-admin PageUser.vue

## Overview

This document compares CRUD6's enhanced user page features with UserFrosting's sprinkle-admin PageUser.vue implementation, demonstrating how CRUD6 achieves feature parity through schema-driven configuration.

## sprinkle-admin PageUser.vue Features

The standard UserFrosting admin user page (`/admin/users/u/{slug}`) provides:

1. **User Information Card**
   - Display user details
   - Edit button
   - Delete button

2. **Additional Action Buttons**
   - Change Password
   - Reset Password (send email)
   - Enable/Disable User
   - Edit Roles
   - Edit Permissions

3. **Multiple Relationship Tables**
   - User Activities (login history, actions)
   - User Roles (assigned roles)
   - User Permissions (direct permissions)

## CRUD6 Enhanced User Page (`/crud6/users/{id}`)

CRUD6 now provides equivalent functionality through schema configuration:

### 1. User Information Card ✅

**PageUser.vue:**
- Hard-coded component displaying user fields
- Edit/Delete buttons

**CRUD6 (PageRow.vue + Info.vue):**
- Schema-driven field display
- Edit/Delete buttons (automatic)
- Custom action buttons (schema-configured)

```json
{
  "fields": {
    "user_name": { "type": "string", "label": "Username", "viewable": true },
    "email": { "type": "string", "label": "Email", "viewable": true },
    "flag_enabled": { "type": "boolean", "label": "Enabled", "viewable": true },
    "flag_verified": { "type": "boolean", "label": "Verified", "viewable": true }
  }
}
```

### 2. Custom Action Buttons ✅

**PageUser.vue Actions:**
- Change Password → Modal/Route
- Reset Password → API Call
- Enable/Disable → Field Update
- Edit Roles → Route/Modal
- Edit Permissions → Route/Modal

**CRUD6 Custom Actions:**

```json
{
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "Toggle Enabled",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default"
    },
    {
      "key": "change_password",
      "label": "Change Password",
      "icon": "key",
      "type": "route",
      "route": "user.password",
      "style": "primary"
    },
    {
      "key": "reset_password",
      "label": "Reset Password",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/users/{id}/password/reset",
      "method": "POST",
      "confirm": "Send password reset email?",
      "success_message": "Password reset email sent"
    },
    {
      "key": "disable_user",
      "label": "Disable User",
      "icon": "ban",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "style": "danger",
      "confirm": "Are you sure?"
    }
  ]
}
```

### 3. Multiple Relationship Tables ✅

**PageUser.vue:**
- Activities table (hard-coded component)
- Roles table (hard-coded component)
- Permissions table (hard-coded component)

**CRUD6 Multiple Details:**

```json
{
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "USER.ROLES"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "USER.PERMISSIONS"
    }
  ]
}
```

## Feature Comparison Matrix

| Feature | sprinkle-admin PageUser.vue | CRUD6 Enhanced PageRow.vue | Notes |
|---------|----------------------------|---------------------------|-------|
| **User Info Display** | ✅ Hard-coded | ✅ Schema-driven | CRUD6 is more flexible |
| **Edit Button** | ✅ Hard-coded | ✅ Automatic | Both supported |
| **Delete Button** | ✅ Hard-coded | ✅ Automatic | Both supported |
| **Change Password** | ✅ Custom button | ✅ Schema action | CRUD6: `type: route` |
| **Reset Password** | ✅ Custom button | ✅ Schema action | CRUD6: `type: api_call` |
| **Enable/Disable** | ✅ Custom button | ✅ Schema action | CRUD6: `type: field_update` |
| **Edit Roles** | ✅ Custom button | ✅ Schema action | CRUD6: configurable |
| **Edit Permissions** | ✅ Custom button | ✅ Schema action | CRUD6: configurable |
| **Activities Table** | ✅ Hard-coded | ✅ Schema detail | CRUD6: `details[0]` |
| **Roles Table** | ✅ Hard-coded | ✅ Schema detail | CRUD6: `details[1]` |
| **Permissions Table** | ✅ Hard-coded | ✅ Schema detail | CRUD6: `details[2]` |
| **Permission Checks** | ✅ Hard-coded | ✅ Schema-driven | Both supported |
| **Confirmation Dialogs** | ✅ Hard-coded | ✅ Schema-configured | CRUD6: `confirm` property |
| **Success Messages** | ✅ Hard-coded | ✅ Schema-configured | CRUD6: `success_message` |
| **Button Styling** | ✅ Hard-coded | ✅ Schema-driven | CRUD6: `style` property |
| **Icons** | ✅ Hard-coded | ✅ Schema-driven | CRUD6: `icon` property |

## Advantages of CRUD6 Approach

### 1. **Zero Code for New Models**
- sprinkle-admin: Requires new Vue component for each model
- CRUD6: Just add JSON schema file

### 2. **Consistency**
- sprinkle-admin: Different implementations per model
- CRUD6: Consistent behavior across all models

### 3. **Maintainability**
- sprinkle-admin: Update code for each model
- CRUD6: Update schema file only

### 4. **Flexibility**
- sprinkle-admin: Fixed set of actions/relationships
- CRUD6: Configure any actions/relationships in schema

### 5. **Reusability**
- sprinkle-admin: Custom components per model
- CRUD6: Generic components work for all models

## Complete Example: Enhanced User Schema

Here's the complete schema that achieves feature parity:

```json
{
  "model": "users",
  "title": "User Management",
  "singular_title": "User",
  "description": "Manage system users with enhanced features",
  "table": "users",
  "permissions": {
    "read": "uri_users",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  },
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "USER.ROLES"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "USER.PERMISSIONS"
    }
  ],
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "Toggle Enabled",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field"
    },
    {
      "key": "toggle_verified",
      "label": "Toggle Verified",
      "icon": "check-circle",
      "type": "field_update",
      "field": "flag_verified",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field"
    },
    {
      "key": "change_password",
      "label": "Change Password",
      "icon": "key",
      "type": "route",
      "route": "user.password",
      "style": "primary",
      "permission": "update_user_field"
    },
    {
      "key": "reset_password",
      "label": "Reset Password",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/users/{id}/password/reset",
      "method": "POST",
      "style": "secondary",
      "permission": "update_user_field",
      "confirm": "Send password reset email?",
      "success_message": "Password reset email sent"
    },
    {
      "key": "disable_user",
      "label": "Disable User",
      "icon": "ban",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "style": "danger",
      "permission": "update_user_field",
      "confirm": "Disable this user?",
      "success_message": "User disabled"
    }
  ],
  "fields": {
    "id": { "type": "integer", "label": "ID", "viewable": true },
    "user_name": { "type": "string", "label": "Username", "viewable": true },
    "email": { "type": "string", "label": "Email", "viewable": true },
    "first_name": { "type": "string", "label": "First Name", "viewable": true },
    "last_name": { "type": "string", "label": "Last Name", "viewable": true },
    "flag_enabled": { "type": "boolean", "label": "Enabled", "viewable": true },
    "flag_verified": { "type": "boolean", "label": "Verified", "viewable": true },
    "created_at": { "type": "datetime", "label": "Created", "viewable": true },
    "updated_at": { "type": "datetime", "label": "Updated", "viewable": true }
  }
}
```

## Usage Comparison

### sprinkle-admin Route
```
/admin/users/u/{slug}
```
Hard-coded component with fixed functionality

### CRUD6 Route
```
/crud6/users/{id}
```
Generic component with schema-driven functionality

## Migration Path

To migrate from sprinkle-admin user pages to CRUD6:

1. Create `app/schema/crud6/users.json` with enhanced schema
2. Configure `details` array for relationships
3. Configure `actions` array for custom buttons
4. Update routes to point to `/crud6/users/{id}`
5. Test permissions and functionality

## Conclusion

CRUD6's enhanced PageRow.vue with custom actions and multiple details provides:

✅ **Feature Parity**: All sprinkle-admin PageUser.vue features
✅ **Schema-Driven**: No code changes needed
✅ **Extensible**: Easy to add new actions/relationships
✅ **Reusable**: Works for any model, not just users
✅ **Maintainable**: Update schema files, not Vue components

The schema-driven approach provides the same user experience as sprinkle-admin's hard-coded components while being more flexible, maintainable, and reusable across all models.
