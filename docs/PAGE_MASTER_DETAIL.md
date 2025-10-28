# PageMasterDetail Component

## Overview

`PageMasterDetail` is an enhanced version of `PageRow` that extends it with master-detail capabilities. It allows editing a master record along with its associated detail records in a single integrated interface.

## Key Features

- **Extends PageRow**: Inherits all PageRow functionality (view, edit, create single records)
- **Master-Detail Editing**: Edit master records with inline detail grid
- **SmartLookup Support**: Full support for smartlookup field type in both master and detail forms
- **Single Transaction**: Saves master and all details together
- **Detail Grid**: Inline editable grid with add/edit/delete operations
- **Automatic Mode Detection**: Switches between standard mode and master-detail mode based on schema

## Usage

### Import

```typescript
import { CRUD6MasterDetailPage } from '@ssnukala/sprinkle-crud6/views'
```

### In Routes

```typescript
{
  path: '/crud6/:model/:id',
  name: 'crud6-master-detail-row',
  component: CRUD6MasterDetailPage,
  meta: {
    title: 'View Record',
    auth: true
  }
}
```

### Schema Configuration

The component automatically detects master-detail mode when the schema includes a `detail_editable` configuration:

```json
{
  "model": "order",
  "title": "Order Management",
  "table": "orders",
  "primary_key": "id",
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price"],
    "title": "Order Lines",
    "allow_add": true,
    "allow_edit": true,
    "allow_delete": true
  },
  "fields": {
    "customer_id": {
      "type": "smartlookup",
      "label": "Customer",
      "lookup_model": "customers",
      "lookup_id": "id",
      "lookup_desc": "name"
    },
    "order_number": {
      "type": "string",
      "label": "Order Number"
    }
  }
}
```

## Component Behavior

### View Mode (Default)

When not in edit mode, displays:
- **Master Info**: Uses `CRUD6Info` component to show master record details
- **Detail List**: Uses `CRUD6Details` component to show related detail records (read-only)

### Edit Mode

When in edit mode (create or update), displays:
- **Master Form**: Editable form for master record fields (supports all field types including smartlookup)
- **Detail Grid**: Inline editable grid for detail records with add/edit/delete operations

### Mode Detection

The component automatically detects its mode based on:
1. **Create Mode**: Route name includes 'create' OR no `recordId` parameter
2. **Master-Detail Mode**: Schema includes `detail_editable` configuration
3. **Standard Mode**: No `detail_editable` in schema (behaves like PageRow)

## Master-Detail Features

### Detail Configuration

The `detail_editable` schema property configures the detail behavior:

```typescript
interface DetailEditableConfig {
  model: string              // Detail model name
  foreign_key: string        // Foreign key field in detail records
  fields: string[]           // Fields to display/edit in detail grid
  title?: string            // Title for detail section
  allow_add?: boolean       // Allow adding new detail records (default: true)
  allow_edit?: boolean      // Allow editing detail records (default: true)
  allow_delete?: boolean    // Allow deleting detail records (default: true)
}
```

### Detail Actions

Detail records are tracked with internal action flags:

- `_action: 'create'`: New detail record to be created
- `_action: 'update'`: Existing detail record modified
- `_action: 'delete'`: Existing detail record marked for deletion

### Save Process

When saving in master-detail mode:

1. **Validate Form**: Check required fields in master and details
2. **Save Master**: Create or update the master record
3. **Process Details**: For each detail record:
   - **Create**: POST new records to `/api/crud6/{detail_model}`
   - **Update**: PUT modified records to `/api/crud6/{detail_model}/{id}`
   - **Delete**: DELETE marked records from `/api/crud6/{detail_model}/{id}`
4. **Show Result**: Display success/error messages
5. **Navigate**: Return to list page on success

## SmartLookup Integration

### In Master Form

SmartLookup fields in the master form work exactly as in PageRow:

```json
{
  "customer_id": {
    "type": "smartlookup",
    "label": "Customer",
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name"
  }
}
```

### In Detail Grid

SmartLookup fields are also supported in detail grids for inline lookup:

```json
{
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price"]
  },
  "fields": {
    "product_id": {
      "type": "smartlookup",
      "label": "Product",
      "lookup_model": "products",
      "lookup_id": "id",
      "lookup_desc": "name"
    }
  }
}
```

## Component Structure

### Props

None - component reads from route parameters:
- `model`: From `route.params.model`
- `id`: From `route.params.id`

### Composables Used

1. **useCRUD6Schema**: Load and manage master schema
2. **useCRUD6Schema** (second instance): Load detail schema
3. **useCRUD6Api**: Master record CRUD operations
4. **useMasterDetail**: Master-detail transaction handling

### Child Components

- **CRUD6Info**: Display master record information (view mode)
- **CRUD6Details**: Display related detail records (view mode)
- **CRUD6AutoLookup**: SmartLookup field rendering (edit mode)
- **CRUD6DetailGrid**: Inline editable detail grid (edit mode)

## Differences from PageRow

| Feature | PageRow | PageMasterDetail |
|---------|---------|------------------|
| Master Record | ✅ View/Edit | ✅ View/Edit |
| Detail Records | ✅ View only | ✅ View + Edit |
| SmartLookup | ✅ Supported | ✅ Supported |
| Detail Grid | ❌ No | ✅ Inline editable |
| Transaction | Single record | Master + Details |
| Save Endpoint | Single API call | Multiple API calls |
| Use Case | Simple CRUD | Complex relationships |

## Complete Example

### Order Schema with SmartLookup and Master-Detail

```json
{
  "model": "order",
  "title": "Order Management",
  "singular_title": "Order",
  "table": "orders",
  "primary_key": "id",
  "timestamps": true,
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price", "line_total"],
    "title": "Order Items",
    "allow_add": true,
    "allow_edit": true,
    "allow_delete": true
  },
  "fields": {
    "id": {
      "type": "integer",
      "label": "Order ID",
      "auto_increment": true,
      "readonly": true
    },
    "customer_id": {
      "type": "smartlookup",
      "label": "Customer",
      "required": true,
      "lookup_model": "customers",
      "lookup_id": "id",
      "lookup_desc": "name",
      "placeholder": "Search customers..."
    },
    "order_number": {
      "type": "string",
      "label": "Order Number",
      "required": true
    },
    "order_date": {
      "type": "date",
      "label": "Order Date",
      "required": true
    },
    "total_amount": {
      "type": "decimal",
      "label": "Total Amount",
      "readonly": true
    }
  }
}
```

### Order Lines Schema

```json
{
  "model": "order_lines",
  "title": "Order Lines",
  "table": "order_lines",
  "primary_key": "id",
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "readonly": true
    },
    "order_id": {
      "type": "integer",
      "label": "Order ID",
      "required": true,
      "readonly": true
    },
    "product_id": {
      "type": "smartlookup",
      "label": "Product",
      "required": true,
      "lookup_model": "products",
      "lookup_id": "id",
      "lookup_desc": "name",
      "placeholder": "Search products..."
    },
    "quantity": {
      "type": "integer",
      "label": "Quantity",
      "required": true,
      "default": 1
    },
    "unit_price": {
      "type": "decimal",
      "label": "Unit Price",
      "required": true
    },
    "line_total": {
      "type": "decimal",
      "label": "Line Total",
      "readonly": true
    }
  }
}
```

## Best Practices

1. **Use for Related Data**: Use PageMasterDetail when you have one-to-many or many-to-many relationships
2. **Keep Details Simple**: Limit detail fields to essential information for inline editing
3. **Mark Readonly Fields**: Mark calculated fields (like totals) as readonly
4. **Provide Defaults**: Set sensible default values for detail fields
5. **Validation**: Ensure both master and detail schemas have proper validation rules
6. **Foreign Keys**: Always mark foreign key fields in details as readonly
7. **SmartLookup in Details**: Use smartlookup for foreign key relationships in detail grids

## Migration from PageRow

To migrate from PageRow to PageMasterDetail:

1. **No Code Changes Required**: Simply add `detail_editable` to your schema
2. **Test Detail Schema**: Ensure detail model schema exists and is accessible
3. **Update Routes**: Optionally update route names to indicate master-detail mode
4. **Test Thoroughly**: Verify all CRUD operations work correctly

## See Also

- [PageRow Component](../app/assets/views/PageRow.vue)
- [SmartLookup Field Type](./SMARTLOOKUP_FIELD_TYPE.md)
- [DetailGrid Component](../app/assets/components/CRUD6/DetailGrid.vue)
- [useMasterDetail Composable](../app/assets/composables/useMasterDetail.ts)
