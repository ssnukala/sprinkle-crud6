# Custom Actions Feature

## Overview

The Custom Actions feature allows you to define schema-driven buttons and operations that appear on the detail/row page of CRUD6 models. This feature provides a flexible way to add custom functionality beyond the standard Edit and Delete actions, all configured through your JSON schema files.

## Features

- **Schema-Driven Configuration**: Define actions in your JSON schema file
- **Multiple Action Types**: Support for field updates, modal displays, route navigation, and API calls
- **Permission-Based**: Actions respect schema-defined permissions
- **Flexible Styling**: Customize button appearance with different styles
- **Confirmation Dialogs**: Optional confirmation prompts for destructive actions
- **Success Feedback**: Automatic success messages after action completion

## Action Types

### 1. Field Update (`field_update`)

Update a single field value directly, useful for toggling flags or updating status fields.

**Use Cases:**
- Enable/disable user accounts
- Toggle verified status
- Update boolean flags
- Set specific field values

**Configuration:**
```json
{
  "key": "toggle_enabled",
  "label": "Toggle Enabled",
  "icon": "power-off",
  "type": "field_update",
  "field": "flag_enabled",
  "toggle": true,
  "style": "default",
  "permission": "update_user_field",
  "success_message": "User status updated successfully"
}
```

**Properties:**
- `field` (required): The field name to update
- `toggle` (optional): Set to `true` to toggle boolean values
- `value` (optional): Specific value to set (alternative to toggle)

### 2. Route Navigation (`route`)

Navigate to a specific route/page when the action is clicked.

**Use Cases:**
- Navigate to password change form
- Open user profile page
- Go to settings page

**Configuration:**
```json
{
  "key": "change_password",
  "label": "Change Password",
  "icon": "key",
  "type": "route",
  "route": "user.password",
  "style": "primary",
  "permission": "update_user_field"
}
```

**Properties:**
- `route` (required): The route name to navigate to

### 3. API Call (`api_call`)

Make a custom API call when the action is clicked.

**Use Cases:**
- Send password reset email
- Trigger background processes
- Execute custom server-side logic

**Configuration:**
```json
{
  "key": "reset_password",
  "label": "Reset Password",
  "icon": "envelope",
  "type": "api_call",
  "endpoint": "/api/users/{id}/password/reset",
  "method": "POST",
  "style": "secondary",
  "permission": "update_user_field",
  "confirm": "Are you sure you want to send a password reset email?",
  "success_message": "Password reset email sent successfully"
}
```

**Properties:**
- `endpoint` (required): API endpoint URL (use `{id}` placeholder for record ID)
- `method` (optional): HTTP method (GET, POST, PUT, PATCH, DELETE). Default: POST

### 4. Modal Display (`modal`)

Display a custom modal/dialog for more complex actions (future implementation).

## Common Properties

All action types support these common properties:

- **`key`** (required, string): Unique identifier for the action
- **`label`** (required, string): Button text displayed to users
- **`type`** (required, string): Action type (`field_update`, `route`, `api_call`, `modal`)
- **`icon`** (optional, string): FontAwesome icon name (without 'fa-' prefix)
- **`style`** (optional, string): Button style class: `primary`, `secondary`, `default`, `danger`
- **`permission`** (optional, string): Required permission to see/use the action
- **`confirm`** (optional, string): Confirmation message before executing
- **`success_message`** (optional, string): Message shown on successful completion

## Complete Example

Here's a complete schema example with multiple custom actions:

```json
{
  "model": "users",
  "title": "User Management",
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "Toggle Enabled",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field",
      "success_message": "User status updated successfully"
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
      "confirm": "Are you sure you want to disable this user?",
      "success_message": "User disabled successfully"
    }
  ],
  "fields": {
    "flag_enabled": {
      "type": "boolean",
      "label": "Enabled",
      "viewable": true
    },
    "flag_verified": {
      "type": "boolean",
      "label": "Verified",
      "viewable": true
    }
  }
}
```

## Button Rendering Order

Actions appear on the detail page in this order:
1. Custom actions (in the order defined in the schema)
2. Edit button (if user has update permission)
3. Delete button (if user has delete permission)

## Permission Handling

- If an action has a `permission` property, it will only be visible to users with that permission
- Actions without a `permission` property are visible to all users who can view the record
- The permission system integrates with UserFrosting's built-in authorization system

## Usage in Components

The actions are automatically rendered by the `CRUD6Info` component when:
1. The schema includes an `actions` array
2. The user has permission to view the actions
3. The record is being viewed (not in edit mode)

No additional code is needed - just define the actions in your schema!

## Vue Composable

For custom implementations, you can use the `useCRUD6Actions` composable:

```typescript
import { useCRUD6Actions } from '@ssnukala/sprinkle-crud6/composables'

const { executeAction, loading, error } = useCRUD6Actions('users')

// Execute an action
const success = await executeAction(actionConfig, recordId, currentRecord)
```

## TypeScript Interface

```typescript
export interface ActionConfig {
  key: string
  label: string
  icon?: string
  type: 'field_update' | 'modal' | 'route' | 'api_call'
  permission?: string
  field?: string
  value?: any
  toggle?: boolean
  modal?: string
  route?: string
  endpoint?: string
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  style?: string
  confirm?: string
  success_message?: string
}
```

## Best Practices

1. **Use Meaningful Keys**: Choose descriptive key values for easy identification
2. **Add Confirmations**: Use `confirm` for destructive actions (delete, disable, etc.)
3. **Provide Feedback**: Set `success_message` to inform users of successful actions
4. **Set Permissions**: Always specify `permission` for sensitive actions
5. **Choose Appropriate Styles**: Use `danger` for destructive actions, `primary` for main actions
6. **Include Icons**: Icons improve UX - use FontAwesome icon names
7. **Field Updates for Simple Changes**: Use `field_update` for quick toggles instead of API calls

## See Also

- [Multiple Detail Sections](MULTIPLE_DETAILS_FEATURE.md) - Display multiple relationship tables
- [Detail Section Feature](DETAIL_SECTION_FEATURE.md) - Single detail section configuration
- [Schema API Quick Reference](SCHEMA_API_QUICK_REFERENCE.md) - Schema structure reference
