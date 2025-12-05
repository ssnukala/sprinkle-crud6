# UnifiedModal Migration Guide

## Overview

The CRUD6 sprinkle has been enhanced with a new **UnifiedModal** component that consolidates all modal types (create, edit, delete, custom actions) into a single, schema-driven component with automatic default action support.

## Key Features

### 1. Automatic Default Actions
The `SchemaService` now automatically adds default CRUD actions to schemas:
- **Create Action** (scope: `list`) - Appears in list view
- **Edit Action** (scope: `detail`) - Appears in detail view  
- **Delete Action** (scope: `detail`) - Appears in detail view

### 2. Scope-Based Filtering
Actions are filtered by scope automatically:
- `list` scope: Actions shown in list/table view (e.g., create button)
- `detail` scope: Actions shown in detail/info view (e.g., edit, delete buttons)

### 3. UnifiedModal Component
One component handles all modal types:
- `form`: Full CRUD forms (create/edit) using CRUD6Form
- `delete`: Delete confirmations
- `confirm`: Simple confirmations
- `input`: Input fields with validation

### 4. Translation Consistency
All translations now use the same pattern with both model and record context:
```javascript
translator.translate('CRUD6.DELETE', { model: 'User', user_name: 'john_doe' })
```

## Migration Steps

### Backend Changes (Automatic)

**No changes required!** The `SchemaService` automatically:
1. Adds default actions if not present in schema
2. Filters actions by scope when requested
3. Supports schema override with `default_actions: false`

### Frontend Changes

#### Before (Old Modals)

```vue
<!-- PageList.vue -->
<CRUD6CreateModal 
  :model="model" 
  :schema="schema" 
  @saved="refresh()" />

<!-- Info.vue -->
<CRUD6EditModal 
  :crud6="record" 
  :model="model" 
  :schema="schema" 
  @saved="refresh()" />

<CRUD6DeleteModal 
  :crud6="record" 
  :model="model" 
  :schema="schema" 
  @deleted="navigateToList()" />
```

#### After (UnifiedModal)

```vue
<!-- PageList.vue - List actions from schema -->
<CRUD6UnifiedModal
  v-for="action in listActions"
  :key="action.key"
  :action="action"
  :model="model"
  :schema="schema"
  @saved="refresh()"
  @confirmed="refresh()" />

<!-- Info.vue - Detail actions from schema -->
<CRUD6UnifiedModal
  v-for="action in detailActions"
  :key="action.key"
  :action="action"
  :record="record"
  :model="model"
  :schema="schema"
  @saved="refresh()"
  @confirmed="handleAction(action)" />
```

## Schema Configuration

### Default Actions (Automatic)

If your schema has permissions defined, default actions are added automatically:

```json
{
  "model": "users",
  "permissions": {
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  }
}
```

Automatically generates:
- Create action with `scope: ["list"]`
- Edit action with `scope: ["detail"]`
- Delete action with `scope: ["detail"]`

### Disable Default Actions

```json
{
  "model": "users",
  "default_actions": false,
  "actions": [
    // Your custom actions only
  ]
}
```

### Override Default Actions

Define an action with a matching key to override the default:

```json
{
  "model": "users",
  "actions": [
    {
      "key": "edit_action",
      "label": "CUSTOM.EDIT_LABEL",
      "icon": "pencil",
      "type": "form",
      "scope": ["detail"],
      "permission": "update_user_field"
    }
  ]
}
```

### Custom Actions with Scope

```json
{
  "model": "users",
  "actions": [
    {
      "key": "bulk_import",
      "label": "Import Users",
      "icon": "upload",
      "type": "modal",
      "scope": ["list"],
      "permission": "create_user"
    },
    {
      "key": "reset_password",
      "label": "Reset Password",
      "icon": "key",
      "type": "field_update",
      "field": "password",
      "scope": ["detail"],
      "permission": "update_user_field",
      "modal_config": {
        "type": "input",
        "fields": ["password"]
      }
    }
  ]
}
```

## Action Types

### 1. Form Actions (Create/Edit)

```json
{
  "key": "create_action",
  "label": "CRUD6.CREATE",
  "icon": "plus",
  "type": "form",
  "scope": ["list"],
  "modal_config": {
    "type": "form",
    "title": "CRUD6.CREATE"
  }
}
```

### 2. Delete Actions

```json
{
  "key": "delete_action",
  "label": "CRUD6.DELETE",
  "icon": "trash",
  "type": "delete",
  "scope": ["detail"],
  "confirm": "CRUD6.DELETE_CONFIRM",
  "modal_config": {
    "type": "confirm",
    "buttons": "yes_no",
    "warning": "WARNING_CANNOT_UNDONE"
  }
}
```

### 3. Field Update Actions

```json
{
  "key": "toggle_enabled",
  "label": "Toggle Enabled",
  "icon": "toggle-on",
  "type": "field_update",
  "field": "flag_enabled",
  "toggle": true,
  "scope": ["detail"],
  "permission": "update_user_field"
}
```

### 4. Custom Modal Actions

```json
{
  "key": "send_email",
  "label": "Send Email",
  "icon": "envelope",
  "type": "api_call",
  "method": "POST",
  "scope": ["detail"],
  "confirm": "Send email to {{user_name}}?",
  "modal_config": {
    "type": "confirm"
  }
}
```

## Component API

### UnifiedModal Props

```typescript
interface Props {
  /** Action configuration from schema */
  action: ActionConfig
  /** Record data for interpolation and edit forms */
  record?: any
  /** Schema fields for input rendering */
  schemaFields?: Record<string, SchemaField>
  /** Model name for CRUD6Form */
  model?: string
  /** Complete schema for CRUD6Form */
  schema?: any
}
```

### UnifiedModal Events

```typescript
// Form saved (for type: 'form')
emit('saved')

// Action confirmed (for all other types)
emit('confirmed', data?)

// Action cancelled
emit('cancelled')
```

### UnifiedModal Slots

```vue
<!-- Custom trigger button -->
<CRUD6UnifiedModal :action="action">
  <template #trigger="{ modalId }">
    <a :href="`#${modalId}`" uk-toggle>
      Custom Trigger
    </a>
  </template>
</CRUD6UnifiedModal>
```

## Translation Keys

### CRUD Actions

```php
'CRUD6' => [
    'CREATE' => 'Create {{model}}',
    'EDIT' => 'Edit {{model}}',
    'DELETE' => 'Delete {{model}}',
    'DELETE_CONFIRM' => 'Are you sure you want to delete the {{model}}?',
    'DELETE_YES' => 'Yes, delete {{model}}',
]
```

### Button Labels

```php
'YES' => 'Yes',
'NO' => 'No',
'SAVE' => 'Save',
'CANCEL' => 'Cancel',
'OK' => 'OK',
'CONFIRM' => 'Confirm',
```

## Backward Compatibility

The old modal components are **deprecated but still functional**:
- `CRUD6CreateModal`
- `CRUD6EditModal`
- `CRUD6DeleteModal`
- `CRUD6ActionModal`

They will be removed in a future major version. Migrate to `CRUD6UnifiedModal` when convenient.

## Examples

### Complete Schema Example

```json
{
  "model": "products",
  "title": "Products",
  "singular_title": "Product",
  "table": "products",
  "permissions": {
    "read": "view_products",
    "create": "create_product",
    "update": "update_product",
    "delete": "delete_product"
  },
  "actions": [
    {
      "key": "duplicate_product",
      "label": "Duplicate",
      "icon": "copy",
      "type": "api_call",
      "method": "POST",
      "endpoint": "/api/crud6/products/{id}/duplicate",
      "scope": ["detail"],
      "permission": "create_product",
      "confirm": "Create a copy of {{name}}?"
    },
    {
      "key": "publish_product",
      "label": "Publish",
      "icon": "check",
      "type": "field_update",
      "field": "status",
      "value": "published",
      "scope": ["detail"],
      "permission": "update_product",
      "confirm": "Publish {{name}}?",
      "visible_when": { "status": "draft" }
    }
  ],
  "fields": {
    // ... field definitions
  }
}
```

### Component Usage Example

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6UnifiedModal from '@ssnukala/sprinkle-crud6/components'

const { schema } = useCRUD6Schema()

// Get list-scoped actions (includes default create)
const listActions = computed(() => {
  return schema.value?.contexts?.list?.actions || []
})

// Get detail-scoped actions (includes default edit, delete)
const detailActions = computed(() => {
  return schema.value?.contexts?.detail?.actions || []
})
</script>

<template>
  <!-- List view -->
  <div>
    <CRUD6UnifiedModal
      v-for="action in listActions"
      :key="action.key"
      :action="action"
      :model="model"
      :schema="schema"
      @saved="refresh()" />
  </div>

  <!-- Detail view -->
  <div>
    <CRUD6UnifiedModal
      v-for="action in detailActions"
      :key="action.key"
      :action="action"
      :record="record"
      :model="model"
      :schema="schema"
      @saved="refresh()"
      @confirmed="handleAction(action)" />
  </div>
</template>
```

## Benefits

1. **Consistency**: All modals use the same component and patterns
2. **Schema-Driven**: Actions defined in schema, not hardcoded in components
3. **Automatic Defaults**: CRUD operations work out of the box
4. **Scope Filtering**: Actions appear in the right context automatically
5. **Translation**: Proper model and record context for all translations
6. **Extensibility**: Easy to add custom actions via schema
7. **Maintainability**: One component to maintain instead of four

## Testing

After migration, test:
1. ✅ Create operations in list view
2. ✅ Edit operations in detail view
3. ✅ Delete operations in detail view
4. ✅ Custom actions appear in correct scopes
5. ✅ Translations work with model and record context
6. ✅ Permissions are enforced
7. ✅ Modal submissions refresh data correctly

## Support

If you encounter issues during migration:
1. Check schema has proper permissions defined
2. Verify action scopes are set correctly
3. Ensure translations include `{{model}}` placeholder
4. Confirm event handlers (`@saved`, `@confirmed`) are wired up
5. Review browser console for deprecation warnings
