# UFTable Integration for CRUD6

The CRUD6 sprinkle now includes full UFTable integration for dynamic table rendering based on JSON schema configurations.

## Components

### UFTableCRUD6
A Vue component that provides a table interface for CRUD6 models:

```vue
<UFTableCRUD6
    :model="modelName"
    :schema="schema"
    :readonly="false"
    @edit="handleEdit"
    @delete="handleDelete"
    @row-click="handleRowClick"
/>
```

**Props:**
- `model`: The CRUD6 model name (string)
- `schema`: Optional schema object (will be loaded automatically if not provided)
- `readonly`: Disable edit/delete actions (boolean, default: false)

**Events:**
- `edit`: Emitted when edit button is clicked
- `delete`: Emitted when delete button is clicked
- `row-click`: Emitted when a table row is clicked

### Page Components

#### PageCRUD6s.vue
List view page that displays a table of records using UFTableCRUD6. Features:
- Schema-driven column configuration
- Automatic loading states
- Action buttons (Create, Edit, Delete)
- Permission-based access control

#### PageCRUD6.vue  
Detail/Edit view page for individual records. Features:
- Dynamic form generation from schema
- View/Edit mode toggle
- Field type-specific input controls
- Save/Cancel functionality

## Composables

### useCRUD6Schema
Manages schema loading and provides reactive access to schema data with automatic caching to prevent duplicate API calls:

```typescript
const { 
    schema, 
    loading, 
    error, 
    currentModel,
    loadSchema,
    setSchema,
    tableColumns,
    sortableFields
} = useCRUD6Schema('users')
```

**Key Features:**
- **Auto-caching**: Automatically caches schemas and prevents duplicate API calls for the same model
- **Direct setting**: Use `setSchema()` to set schema without API call when already available
- **Smart loading**: `loadSchema()` checks cache before making API requests

See [Preventing Duplicate Schema Calls](./Preventing-Duplicate-Schema-Calls.md) for detailed usage patterns.

### useCRUD6Api
Handles CRUD operations for a specific model:

```typescript
const {
    fetchRow,
    createRow,
    updateRow,
    deleteRow
} = useCRUD6Api('users')
```

## Schema-Driven Table Configuration

The table automatically configures columns based on schema field properties:

- `sortable`: Enables column sorting
- `searchable`: Includes field in search
- `readonly`: Makes field read-only in edit mode
- `label`: Sets column header text
- `type`: Determines display formatting

## Example Usage

```vue
<template>
    <div>
        <UFTableCRUD6
            model="products"
            @edit="editProduct"
            @delete="confirmDelete"
        />
    </div>
</template>

<script setup>
import { UFTableCRUD6 } from '@ssnukala/sprinkle-crud6'

function editProduct(product) {
    router.push(`/products/${product.id}/edit`)
}

function confirmDelete(product) {
    // Show confirmation modal
}
</script>
```

## Integration with UserFrosting Admin

When the full UserFrosting admin sprinkle is available, UFTableCRUD6 will automatically use the native UFTable component for enhanced functionality including:

- Advanced filtering and sorting
- Pagination controls
- Export capabilities
- Responsive design
- Accessibility features

The current implementation provides a compatible fallback table that maintains the same API and will seamlessly upgrade when UFTable becomes available.