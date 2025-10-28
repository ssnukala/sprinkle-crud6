# SmartLookup Field Type Documentation

## Overview

The `smartlookup` field type provides an auto-complete lookup field that searches records from any CRUD6 model using the standard `/api/crud6/{model}` endpoint. It allows users to type and search for records, displaying matched results in a dropdown.

## Field Configuration

### Required Parameters

- `type`: Must be set to `"smartlookup"`
- `lookup_model` (or `model`): The CRUD6 model to search (e.g., "customers", "products", "categories")
- `lookup_id` (or `id`): The field name to use as the ID/value (default: "id")
- `lookup_desc` (or `desc`): The field name to display in the dropdown (default: "name")

### Optional Parameters

- `label`: Display label for the field
- `placeholder`: Placeholder text in the search input
- `required`: Whether the field is required (boolean)
- `readonly`: Whether the field is readonly (boolean)
- `description`: Help text displayed below the field
- `default`: Default value for the field

## Example Usage

### Basic SmartLookup Field

```json
{
  "customer_id": {
    "type": "smartlookup",
    "label": "Customer",
    "required": true,
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name",
    "placeholder": "Search for a customer...",
    "description": "Type to search for customers by name"
  }
}
```

### SmartLookup in Master-Detail Schema

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
    "title": "Order Lines"
  },
  "fields": {
    "customer_id": {
      "type": "smartlookup",
      "label": "Customer",
      "required": true,
      "lookup_model": "customers",
      "lookup_id": "id",
      "lookup_desc": "name"
    },
    "order_number": {
      "type": "string",
      "label": "Order Number",
      "required": true
    }
  }
}
```

### SmartLookup in Detail Grid

Detail grids also support smartlookup fields for inline editing:

```json
{
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price"],
    "title": "Order Lines"
  },
  "fields": {
    "product_id": {
      "type": "smartlookup",
      "label": "Product",
      "required": true,
      "lookup_model": "products",
      "lookup_id": "id",
      "lookup_desc": "name"
    },
    "quantity": {
      "type": "integer",
      "label": "Quantity",
      "required": true
    },
    "unit_price": {
      "type": "decimal",
      "label": "Unit Price",
      "required": true
    }
  }
}
```

## How It Works

### API Integration

The smartlookup field uses the standard CRUD6 Sprunje endpoint:

```
GET /api/crud6/{model}?search={query}&size=20
```

**Parameters:**
- `model`: The lookup model name (from `lookup_model`)
- `search`: User's search query (searches across searchable fields in the model schema)
- `size`: Maximum number of results to return (default: 20)

**Response Format:**
```json
{
  "count": 2,
  "count_filtered": 2,
  "rows": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  ]
}
```

### User Experience

1. **Initial State**: Empty input field with placeholder text
2. **User Types**: After typing 1+ characters (configurable), a debounced search is triggered
3. **Results Display**: Matching records appear in a dropdown below the input
4. **Selection**: User can use mouse or keyboard (arrow keys + Enter) to select a record
5. **Value Storage**: The selected record's ID is stored in the field value
6. **Display**: The selected record's description field is displayed in the input

### Search Behavior

The search query is sent to the backend where the Sprunje handler searches across all fields marked as `searchable: true` in the model's schema. For example, if searching for "customers":

```json
{
  "fields": {
    "name": {
      "type": "string",
      "searchable": true
    },
    "email": {
      "type": "string",
      "searchable": true
    }
  }
}
```

Typing "john" would match both:
- Records with "john" in the name field
- Records with "john" in the email field

## Component Support

The smartlookup field type is supported in:

- ✅ **PageRow**: Single record view/edit page
- ✅ **PageMasterDetail**: Master-detail record view/edit page with detail grid
- ✅ **Form**: Standalone CRUD6 form component
- ✅ **DetailGrid**: Inline editable detail grid for master-detail relationships

## Implementation Details

### Components Involved

1. **AutoLookup.vue**: Core component that handles search, display, and selection
2. **PageRow.vue**: Uses AutoLookup for smartlookup fields
3. **PageMasterDetail.vue**: Uses AutoLookup for smartlookup fields in master forms
4. **Form.vue**: Uses AutoLookup for smartlookup fields
5. **DetailGrid.vue**: Uses AutoLookup for smartlookup fields in detail rows

### Props (AutoLookup Component)

```typescript
interface AutoLookupProps {
  model: string              // The CRUD6 model to search
  idField?: string           // ID field name (default: 'id')
  displayField?: string      // Display field name (default: 'name')
  displayFields?: string[]   // Multiple display fields
  placeholder?: string       // Placeholder text
  modelValue?: number | string | null  // Selected value (ID)
  minSearchLength?: number   // Min characters before search (default: 1)
  debounceDelay?: number     // Debounce delay in ms (default: 300)
  required?: boolean         // Required field
  disabled?: boolean         // Disabled state
  displayFormat?: (item: any) => string  // Custom display formatter
}
```

### Events

```typescript
// Emitted when value changes
emit('update:modelValue', value: number | string | null)

// Emitted when item is selected
emit('select', item: any)

// Emitted when selection is cleared
emit('clear')
```

## Advanced Features

### Multiple Display Fields

Display multiple fields in the dropdown:

```json
{
  "customer_id": {
    "type": "smartlookup",
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name",
    "display_fields": ["name", "email", "phone"]
  }
}
```

This will display results as: "John Doe - john@example.com - 555-1234"

### Custom Display Format

For more complex display formats, extend the AutoLookup component with a custom `displayFormat` function.

## Best Practices

1. **Mark Fields as Searchable**: Ensure the lookup model's schema has appropriate fields marked as `searchable: true`
2. **Use Meaningful Descriptions**: Choose description fields that help users identify the correct record
3. **Consider Performance**: Limit the size parameter (default: 20) for large datasets
4. **Provide Clear Labels**: Use descriptive labels and placeholders to guide users
5. **Validate Required Fields**: Mark lookup fields as required when appropriate

## Comparison with Other Field Types

| Feature | String | Integer | SmartLookup |
|---------|--------|---------|-------------|
| User Input | Free text | Numbers only | Searchable dropdown |
| Validation | Pattern/length | Range | Model existence |
| Storage | Text value | Numeric value | Foreign key ID |
| Display | As entered | As entered | Looked up description |
| Search | Simple | Simple | Dynamic API search |

## See Also

- [AutoLookup Component](../app/assets/components/CRUD6/AutoLookup.vue)
- [PageMasterDetail Component](../app/assets/views/PageMasterDetail.vue)
- [Example Schema](./smartlookup-example.json)
