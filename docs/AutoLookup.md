# AutoLookup Component Documentation

## Overview

The `CRUD6AutoLookup` component is a generic, searchable auto-complete/lookup component designed for UserFrosting 6 CRUD applications. It provides a user-friendly way to search and select records from any CRUD6 model.

## Features

- **Generic Model Support**: Works with any CRUD6 model
- **Configurable Display**: Choose which field(s) to display
- **Real-time Search**: Searches as you type with debouncing
- **Keyboard Navigation**: Full keyboard support (arrows, enter, escape)
- **Loading States**: Visual feedback during API calls
- **Clear Selection**: Easy way to reset the selection
- **Customizable Format**: Use custom functions to format display text
- **Form Integration**: Works seamlessly with v-model and form validation

## Basic Usage

```vue
<script setup lang="ts">
import { ref } from 'vue'

const selectedProductId = ref<number | null>(null)
const selectedProduct = ref<any>(null)

function handleProductSelect(product: any) {
  selectedProduct.value = product
  console.log('Product selected:', product)
}
</script>

<template>
  <UFCRUD6AutoLookup
    model="products"
    id-field="id"
    display-field="name"
    placeholder="Search for a product..."
    v-model="selectedProductId"
    @select="handleProductSelect"
  />
</template>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `model` | `string` | **required** | The CRUD6 model name to search (e.g., 'products', 'categories') |
| `idField` | `string` | `'id'` | The field name to use as the ID/value |
| `displayField` | `string` | `'name'` | Single field name to display (use this OR displayFields) |
| `displayFields` | `string[]` | `undefined` | Array of field names to display (use this OR displayField) |
| `placeholder` | `string` | `'Search {model}...'` | Placeholder text for the input field |
| `modelValue` | `number \| string \| null` | `null` | v-model binding for the selected ID |
| `minSearchLength` | `number` | `1` | Minimum characters before triggering search |
| `debounceDelay` | `number` | `300` | Debounce delay in milliseconds |
| `required` | `boolean` | `false` | Whether the field is required |
| `disabled` | `boolean` | `false` | Whether the field is disabled |
| `displayFormat` | `function` | `undefined` | Custom function to format display text |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `update:modelValue` | `number \| string \| null` | Emitted when selection changes (v-model) |
| `select` | `object` | Emitted when an item is selected (contains full item data) |
| `clear` | - | Emitted when selection is cleared |

## Examples

### Example 1: Simple Lookup

Display a single field (name):

```vue
<UFCRUD6AutoLookup
  model="products"
  id-field="id"
  display-field="name"
  placeholder="Search products..."
  v-model="selectedProductId"
  @select="handleSelect"
/>
```

### Example 2: Multiple Display Fields

Display multiple fields separated by a dash:

```vue
<UFCRUD6AutoLookup
  model="products"
  id-field="id"
  :display-fields="['sku', 'name']"
  placeholder="Search by SKU or name..."
  v-model="selectedProductId"
/>
```

Result: `ABC123 - Widget Pro`

### Example 3: Custom Display Format

Use a custom function for complete control:

```vue
<script setup lang="ts">
function customFormat(item: any): string {
  return `${item.sku} - ${item.name} ($${item.price})`
}
</script>

<template>
  <UFCRUD6AutoLookup
    model="products"
    id-field="id"
    :display-format="customFormat"
    placeholder="Search products..."
    v-model="selectedProductId"
  />
</template>
```

Result: `ABC123 - Widget Pro ($29.99)`

### Example 4: Required Field with Validation

```vue
<template>
  <div class="uk-margin">
    <label class="uk-form-label">
      Product
      <span class="uk-text-danger">*</span>
    </label>
    <UFCRUD6AutoLookup
      model="products"
      id-field="id"
      display-field="name"
      :required="true"
      v-model="formData.product_id"
    />
  </div>
</template>
```

### Example 5: Category Lookup

```vue
<UFCRUD6AutoLookup
  model="categories"
  id-field="id"
  display-field="name"
  placeholder="Select category..."
  v-model="selectedCategoryId"
  @select="handleCategorySelect"
/>
```

### Example 6: Custom ID Field

For models with non-standard ID fields:

```vue
<UFCRUD6AutoLookup
  model="users"
  id-field="user_id"
  display-field="user_name"
  placeholder="Search users..."
  v-model="selectedUserId"
/>
```

### Example 7: In a Form Context

```vue
<template>
  <form @submit.prevent="submitOrder">
    <div class="uk-margin">
      <label class="uk-form-label">Product</label>
      <UFCRUD6AutoLookup
        model="products"
        id-field="id"
        :display-fields="['sku', 'name']"
        v-model="orderForm.product_id"
        :required="true"
        @select="handleProductSelect"
      />
    </div>
    
    <div class="uk-margin">
      <label class="uk-form-label">Quantity</label>
      <input 
        type="number" 
        class="uk-input" 
        v-model="orderForm.quantity"
        required
      />
    </div>
    
    <button class="uk-button uk-button-primary" type="submit">
      Add to Order
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const orderForm = ref({
  product_id: null,
  quantity: 1,
  unit_price: 0
})

function handleProductSelect(product: any) {
  // Auto-fill the unit price when product is selected
  orderForm.value.unit_price = product.price
}

function submitOrder() {
  console.log('Submitting order:', orderForm.value)
}
</script>
```

## Integration with Schemas

The AutoLookup component works with any CRUD6 model that has a proper schema defined. Ensure your model schema has searchable fields configured:

```json
{
  "model": "products",
  "table": "products",
  "fields": {
    "id": {
      "type": "integer",
      "searchable": false
    },
    "sku": {
      "type": "string",
      "searchable": true
    },
    "name": {
      "type": "string",
      "searchable": true
    },
    "description": {
      "type": "text",
      "searchable": true
    }
  }
}
```

The component uses the CRUD6 Sprunje API which respects the `searchable` flag in your schema.

## Keyboard Navigation

- **Arrow Down**: Move to next result
- **Arrow Up**: Move to previous result
- **Enter**: Select highlighted result
- **Escape**: Close dropdown

## Styling

The component uses UIkit styles by default but can be customized:

```vue
<style scoped>
/* Custom dropdown styling */
.crud6-auto-lookup .uk-dropdown {
  max-height: 400px; /* Increase max height */
}

/* Custom active item styling */
.crud6-auto-lookup .uk-dropdown-nav > li.uk-active > a {
  background-color: #32d296; /* Custom color */
}
</style>
```

## API Endpoints Used

The component makes calls to:
- `GET /api/crud6/{model}?search={query}&size=20`

This endpoint is provided by the CRUD6 SprunjeAction controller.

## Best Practices

1. **Use displayFields for better UX**: Show multiple fields (e.g., SKU + Name) so users can identify items easily
2. **Set appropriate minSearchLength**: For large datasets, set `min-search-length="3"` to reduce API calls
3. **Customize debounceDelay**: Adjust based on your API response time
4. **Handle the select event**: Use it to populate related fields automatically
5. **Show selected item details**: Display additional info about the selected item for confirmation

## Troubleshooting

### No results showing
- Ensure your model schema has fields with `searchable: true`
- Check that the model exists in `app/schema/crud6/{model}.json`
- Verify the API endpoint is accessible at `/api/crud6/{model}`

### Dropdown not closing
- This is usually a z-index issue. Check that parent containers don't have conflicting z-index values

### Custom format not working
- Ensure your format function returns a string
- Check that the fields you're accessing exist in the API response

## See Also

- [Form.vue](./Form.vue) - CRUD6 form component
- [useCRUD6Api](../composables/useCRUD6Api.ts) - CRUD6 API composable
- [CRUD6 Schema Documentation](../../README.md#json-schema-format)
