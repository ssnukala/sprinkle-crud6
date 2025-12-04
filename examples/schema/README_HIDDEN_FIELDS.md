# Schema-Driven Hidden Username Field - Implementation Example

## Overview

This document explains how to implement hidden username fields for password forms using CRUD6's schema-driven approach, as demonstrated in `examples/schema/users.json`.

## Problem

Browser DevTools shows this accessibility warning on password change forms:
```
[DOM] Password forms should have (optionally hidden) username fields for accessibility
```

## Schema-Driven Solution

Instead of hardcoding hidden username fields in Vue templates, define them in the JSON schema. This maintains CRUD6's core design principle: **Vue templates are generic renderers; all field-specific behavior comes from schema configuration.**

## Implementation Example

### 1. Define Hidden Username Field

Add a hidden field definition in the `fields` section of your schema:

```json
{
  "fields": {
    "username_hidden": {
      "type": "string",
      "label": "",
      "required": false,
      "editable": false,
      "hidden": true,
      "show_in": [],
      "value_source": "user_name",
      "autocomplete": "username",
      "comment": "Hidden username field for password manager accessibility"
    }
  }
}
```

**Field Properties Explained:**
- `type`: "string" - Standard text input
- `label`: "" - Empty label (field is hidden)
- `required`: false - Not required for form submission
- `readonly`: true - User cannot edit this field
- `hidden`: true - **Key property** - Tells renderer to hide this field visually
- `show_in`: [] - Don't show in any standard views (list, form, detail)
- `value_source`: "user_name" - **Important** - Automatically populate from the record's user_name field
- `autocomplete`: "username" - Tell browsers/password managers this is a username

### 2. Include Field in Password Action

Add the hidden username field to the modal's field list:

```json
{
  "actions": [
    {
      "key": "password_action",
      "label": "CRUD6.USER.CHANGE_PASSWORD",
      "type": "field_update",
      "field": "password",
      "modal_config": {
        "type": "input",
        "fields": [
          "username_hidden",
          "password"
        ]
      }
    }
  ]
}
```

**Important**: List `username_hidden` **before** `password` so it appears first in the DOM (browser requirement for password manager recognition).

## How It Works

1. **Schema Definition**: The `username_hidden` field is defined with `hidden: true` and `value_source: "user_name"`
2. **Action Configuration**: The password change action includes `username_hidden` in its field list
3. **Dynamic Rendering**: The Vue template (ActionModal/FieldEditModal) renders all fields from the schema
4. **Hidden Field Rendering**: When the renderer sees `hidden: true`, it applies hidden styles:
   ```html
   <input 
     type="text"
     name="username_hidden"
     value="john.doe"
     autocomplete="username"
     style="position: absolute; left: -9999px; width: 1px; height: 1px;"
     tabindex="-1"
     aria-hidden="true"
     readonly />
   ```
5. **Value Population**: The `value_source: "user_name"` tells the renderer to populate the field from `record.user_name`

## Benefits of Schema-Driven Approach

### ✅ Correct (Schema-Driven)
- Field behavior defined in schema
- Reusable across different actions
- Easy to modify without changing code
- Maintains separation of concerns
- Follows CRUD6 design principles

### ❌ Incorrect (Template Hardcoding)
```vue
<!-- DON'T DO THIS -->
<input v-if="hasPasswordField" type="text" :value="usernameValue" ... />
```
- Violates schema-driven principle
- Hardcoded in template
- Not configurable per action
- Difficult to maintain

## Required Vue Template Support

For this to work, the Vue form renderer needs to support these field properties:

1. **`hidden` property**: When true, apply hidden styles
2. **`value_source` property**: Populate field value from `record[value_source]`
3. **`autocomplete` property**: Already supported ✅

### Example Renderer Logic

```typescript
// In Vue template renderer
if (field.hidden) {
  // Apply hidden styles
  style = "position: absolute; left: -9999px; width: 1px; height: 1px;"
  tabindex = "-1"
  ariaHidden = "true"
}

if (field.value_source && record) {
  // Populate from record
  fieldValue = record[field.value_source]
}
```

## Other Use Cases

This pattern can be used for any hidden fields, not just usernames:

### Hidden Current Email (for email change)
```json
{
  "email_hidden": {
    "type": "string",
    "hidden": true,
    "value_source": "email",
    "autocomplete": "email"
  }
}
```

### Hidden User ID (for tracking)
```json
{
  "user_id_hidden": {
    "type": "integer",
    "hidden": true,
    "value_source": "id",
    "editable": false
  }
}
```

## Complete Example

See `examples/schema/users.json` for the complete working example showing:
- Hidden username field definition
- Password change action configuration
- Proper field ordering
- Inline comments explaining the pattern

## Migration from Hardcoded Approach

If you previously had hardcoded hidden username fields in templates:

1. **Remove** the hardcoded template logic
2. **Add** hidden field definition to your schema
3. **Include** the hidden field in relevant action's field list
4. **Ensure** your renderer supports `hidden` and `value_source` properties

This maintains the same functionality while following CRUD6's schema-driven design principles.
