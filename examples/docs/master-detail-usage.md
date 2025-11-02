# Master-Detail Data Entry - Usage Examples

This document provides comprehensive examples for using the master-detail data entry feature in CRUD6.

## Overview

The master-detail feature allows you to create and edit master records along with their associated detail records in a single form. This is useful for scenarios like:

1. **Order Entry**: Create an order with multiple order line items
2. **Product Categories**: Assign multiple categories to a product
3. **Invoice Management**: Create invoices with line items
4. **Any Master-Detail Relationship**: Where a parent record has multiple child records

## Components

### 1. MasterDetailForm

The main component for master-detail data entry. It combines a master record form with an inline editable grid for detail records.

**Props:**
- `model` (string, required): The master model name (e.g., 'orders')
- `recordId` (string|number, optional): ID for editing existing record
- `detailConfig` (DetailEditableConfig, required): Configuration for detail records

**Events:**
- `saved`: Emitted when the form is successfully saved
- `cancelled`: Emitted when the user cancels the form

### 2. DetailGrid

An inline editable grid component for managing detail records.

**Props:**
- `modelValue` (DetailRecord[], required): Array of detail records
- `detailSchema` (CRUD6Schema, required): Schema for the detail model
- `fields` (string[], required): Fields to display in the grid
- `allowAdd` (boolean, optional): Allow adding new rows (default: true)
- `allowEdit` (boolean, optional): Allow editing rows (default: true)
- `allowDelete` (boolean, optional): Allow deleting rows (default: true)
- `disabled` (boolean, optional): Disable all editing (default: false)

## Schema Configuration

### Detail Editable Configuration

Add a `detail_editable` section to your master schema to enable master-detail editing:

```json
{
  "model": "orders",
  "title": "Order Management",
  "detail_editable": {
    "model": "order_details",
    "foreign_key": "order_id",
    "fields": ["line_number", "sku", "product_name", "quantity", "unit_price", "notes"],
    "title": "Order Items",
    "allow_add": true,
    "allow_edit": true,
    "allow_delete": true
  },
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "order_number": { "type": "string", "required": true },
    "customer_name": { "type": "string", "required": true },
    "total_amount": { "type": "decimal", "readonly": true }
  }
}
```

**Configuration Properties:**
- `model`: The detail model name
- `foreign_key`: The foreign key field in detail records that references the master
- `fields`: Array of field names to display in the detail grid
- `title` (optional): Title for the detail section
- `allow_add` (optional): Allow adding new detail records (default: true)
- `allow_edit` (optional): Allow editing detail records (default: true)
- `allow_delete` (optional): Allow deleting detail records (default: true)

## Use Case 1: Order Entry (One-to-Many)

This example shows how to create an order with multiple order line items.

### Schema Files

**orders.json** (Master):
```json
{
  "model": "orders",
  "title": "Order Management",
  "singular_title": "Order",
  "table": "orders",
  "detail_editable": {
    "model": "order_details",
    "foreign_key": "order_id",
    "fields": ["line_number", "sku", "product_name", "quantity", "unit_price", "line_total", "notes"],
    "title": "Order Items"
  },
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "order_number": { "type": "string", "required": true },
    "customer_name": { "type": "string", "required": true },
    "customer_email": { "type": "string" },
    "total_amount": { "type": "decimal", "readonly": true },
    "payment_status": { "type": "string", "default": "pending" },
    "order_date": { "type": "date", "required": true },
    "notes": { "type": "text" }
  }
}
```

**order_details.json** (Detail):
```json
{
  "model": "order_details",
  "title": "Order Detail Management",
  "table": "order_details",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "order_id": { "type": "integer", "required": true },
    "line_number": { "type": "integer", "required": true },
    "sku": { "type": "string", "required": true },
    "product_name": { "type": "string", "required": true },
    "quantity": { "type": "integer", "required": true },
    "unit_price": { "type": "decimal", "required": true },
    "line_total": { "type": "decimal", "readonly": true },
    "notes": { "type": "text" }
  }
}
```

### Vue Template Usage

```vue
<template>
  <UFCRUD6MasterDetailForm
    model="orders"
    :record-id="orderId"
    :detail-config="detailConfig"
    @saved="handleSaved"
    @cancelled="handleCancelled"
  />
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const orderId = ref(route.params.id || null)

const detailConfig = {
  model: 'order_details',
  foreign_key: 'order_id',
  fields: ['line_number', 'sku', 'product_name', 'quantity', 'unit_price', 'line_total', 'notes'],
  title: 'Order Items',
  allow_add: true,
  allow_edit: true,
  allow_delete: true
}

function handleSaved() {
  console.log('Order saved successfully')
  router.push('/crud6/orders')
}

function handleCancelled() {
  router.push('/crud6/orders')
}
</script>
```

## Use Case 2: Standalone DetailGrid

You can use the DetailGrid component independently for inline editing of detail records.

```vue
<template>
  <div>
    <h3>Order Items</h3>
    <UFCRUD6DetailGrid
      v-model="orderItems"
      :detail-schema="orderDetailSchema"
      :fields="['line_number', 'sku', 'product_name', 'quantity', 'unit_price']"
      :allow-add="true"
      :allow-edit="true"
      :allow-delete="true"
    />
    
    <button @click="saveItems">Save Items</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'

const orderItems = ref([
  { line_number: 1, sku: 'PROD-001', product_name: 'Widget', quantity: 10, unit_price: 9.99 },
  { line_number: 2, sku: 'PROD-002', product_name: 'Gadget', quantity: 5, unit_price: 19.99 }
])

const { schema: orderDetailSchema, loadSchema } = useCRUD6Schema()

// Load the detail schema
await loadSchema('order_details')

function saveItems() {
  console.log('Saving items:', orderItems.value)
  // Your save logic here
}
</script>
```

## API Composable

The `useMasterDetail` composable provides methods for saving master-detail records:

```typescript
import { useMasterDetail } from '@ssnukala/sprinkle-crud6/composables'

const {
  saveMasterWithDetails,
  loadDetails,
  apiLoading,
  apiError
} = useMasterDetail('orders', 'order_details', 'order_id')

// Save master with details
const response = await saveMasterWithDetails(
  orderId,           // null for create, number for update
  {                  // Master data
    order_number: 'ORD-001',
    customer_name: 'John Doe',
    total_amount: 149.97
  },
  [                  // Detail records
    { 
      line_number: 1, 
      sku: 'PROD-001', 
      quantity: 10, 
      unit_price: 9.99,
      _action: 'create' 
    },
    { 
      id: 5,
      line_number: 2, 
      sku: 'PROD-002', 
      quantity: 5, 
      unit_price: 19.99,
      _action: 'update' 
    }
  ]
)

// Load details for an existing master record
const details = await loadDetails(orderId)
```

## Detail Record Actions

Detail records can have an internal `_action` flag to indicate what operation should be performed:

- `'create'`: Create a new detail record
- `'update'`: Update an existing detail record
- `'delete'`: Delete an existing detail record

The DetailGrid component automatically manages these flags based on user interactions.

## Best Practices

1. **Readonly Calculations**: Mark calculated fields like `line_total` as readonly in the schema
2. **Required Fields**: Use validation to ensure required fields are filled
3. **Foreign Keys**: The foreign key field is automatically populated when saving
4. **Line Numbers**: Consider adding a line number field for ordering
5. **Soft Deletes**: Use soft deletes for audit trails if needed

## Advanced Usage

### Custom Validation

Add custom validation logic before saving:

```vue
<script setup>
import { ref, computed } from 'vue'

const detailRecords = ref([])

const isValid = computed(() => {
  // Check if all details have required fields
  return detailRecords.value.every(record => 
    record.sku && record.quantity > 0 && record.unit_price > 0
  )
})

async function handleSave() {
  if (!isValid.value) {
    alert('Please fill all required fields')
    return
  }
  // Proceed with save
}
</script>
```

### Calculating Totals

Update master total when details change:

```vue
<script setup>
import { ref, computed, watch } from 'vue'

const masterFormData = ref({ total_amount: 0 })
const detailRecords = ref([])

const calculatedTotal = computed(() => {
  return detailRecords.value
    .filter(r => r._action !== 'delete')
    .reduce((sum, record) => {
      const lineTotal = (record.quantity || 0) * (record.unit_price || 0)
      return sum + lineTotal
    }, 0)
})

// Update master total when details change
watch(calculatedTotal, (newTotal) => {
  masterFormData.value.total_amount = newTotal
})
</script>
```

## Troubleshooting

### Issue: Detail records not saving

**Solution**: Ensure the foreign key field name in `detailConfig` matches the actual foreign key column in your database.

### Issue: Detail grid not showing

**Solution**: Make sure the detail schema is loaded correctly. Check browser console for errors.

### Issue: Permission errors

**Solution**: Verify that the user has the required permissions for both master and detail models in the schema configuration.

## API Endpoints Used

The master-detail feature uses the following CRUD6 API endpoints:

- `POST /api/crud6/{model}` - Create master record
- `PUT /api/crud6/{model}/{id}` - Update master record
- `POST /api/crud6/{detail_model}` - Create detail record
- `PUT /api/crud6/{detail_model}/{id}` - Update detail record
- `DELETE /api/crud6/{detail_model}/{id}` - Delete detail record
- `GET /api/crud6/{model}/{id}/{detail_model}` - Load detail records

All operations are performed sequentially with proper error handling.
