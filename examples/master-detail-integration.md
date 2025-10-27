# Master-Detail Integration Examples

This document provides complete, working examples of the two main use cases for master-detail data entry in CRUD6.

## Use Case 1: Order Entry (One-to-Many)

This example shows a complete order entry system where an order (master) has multiple order line items (details).

### Database Schema

```sql
-- Orders table (master)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    payment_status VARCHAR(50) DEFAULT 'pending',
    order_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order details table (detail)
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    line_number INT NOT NULL,
    sku VARCHAR(50) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Index for performance
CREATE INDEX idx_order_details_order_id ON order_details(order_id);
```

### JSON Schema Files

**app/schema/crud6/orders.json**:
```json
{
  "model": "orders",
  "title": "Order Management",
  "singular_title": "Order",
  "description": "Manage customer orders",
  "table": "orders",
  "permissions": {
    "read": "view_orders",
    "create": "create_order",
    "update": "edit_order",
    "delete": "delete_order"
  },
  "default_sort": {
    "created_at": "desc"
  },
  "detail_editable": {
    "model": "order_details",
    "foreign_key": "order_id",
    "fields": ["line_number", "sku", "product_name", "quantity", "unit_price", "line_total", "notes"],
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
      "readonly": true,
      "listable": true
    },
    "order_number": {
      "type": "string",
      "label": "Order Number",
      "required": true,
      "listable": true,
      "validation": {
        "required": true,
        "unique": true
      }
    },
    "customer_name": {
      "type": "string",
      "label": "Customer Name",
      "required": true,
      "listable": true
    },
    "customer_email": {
      "type": "string",
      "label": "Customer Email",
      "listable": true
    },
    "total_amount": {
      "type": "decimal",
      "label": "Total Amount",
      "required": true,
      "readonly": true,
      "listable": true
    },
    "payment_status": {
      "type": "string",
      "label": "Payment Status",
      "default": "pending",
      "listable": true
    },
    "order_date": {
      "type": "date",
      "label": "Order Date",
      "required": true,
      "listable": true
    },
    "notes": {
      "type": "text",
      "label": "Order Notes"
    }
  }
}
```

**app/schema/crud6/order_details.json**:
```json
{
  "model": "order_details",
  "title": "Order Detail Management",
  "singular_title": "Order Detail",
  "table": "order_details",
  "permissions": {
    "read": "view_orders",
    "create": "create_order",
    "update": "edit_order",
    "delete": "delete_order"
  },
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
      "required": true
    },
    "line_number": {
      "type": "integer",
      "label": "Line #",
      "required": true
    },
    "sku": {
      "type": "string",
      "label": "SKU",
      "required": true
    },
    "product_name": {
      "type": "string",
      "label": "Product Name",
      "required": true
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
    },
    "line_total": {
      "type": "decimal",
      "label": "Line Total",
      "readonly": true
    },
    "notes": {
      "type": "text",
      "label": "Notes"
    }
  }
}
```

### Vue Component (Complete Example)

**app/assets/views/OrderEntry.vue**:
```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useMasterDetail, useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { DetailRecord, DetailEditableConfig } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6DetailGrid from '../components/CRUD6/DetailGrid.vue'

const route = useRoute()
const router = useRouter()
const page = usePageMeta()

// Set page title
page.title = route.params.id ? 'Edit Order' : 'Create Order'

// Order ID (null for create mode)
const orderId = computed(() => route.params.id ? String(route.params.id) : null)

// Load schemas
const { schema: orderSchema, loadSchema: loadOrderSchema } = useCRUD6Schema()
const { schema: detailSchema, loadSchema: loadDetailSchema } = useCRUD6Schema()

// Master-detail composable
const {
  saveMasterWithDetails,
  loadDetails,
  apiLoading,
  apiError
} = useMasterDetail('orders', 'order_details', 'order_id')

// Form data
const orderData = ref({
  order_number: '',
  customer_name: '',
  customer_email: '',
  total_amount: 0,
  payment_status: 'pending',
  order_date: new Date().toISOString().split('T')[0],
  notes: ''
})

const orderItems = ref<DetailRecord[]>([])

// Detail configuration
const detailConfig: DetailEditableConfig = {
  model: 'order_details',
  foreign_key: 'order_id',
  fields: ['line_number', 'sku', 'product_name', 'quantity', 'unit_price', 'line_total', 'notes'],
  title: 'Order Items',
  allow_add: true,
  allow_edit: true,
  allow_delete: true
}

// Calculate total from items
const calculatedTotal = computed(() => {
  return orderItems.value
    .filter(item => item._action !== 'delete')
    .reduce((sum, item) => {
      const lineTotal = (item.quantity || 0) * (item.unit_price || 0)
      return sum + lineTotal
    }, 0)
})

// Watch for changes in items and update total
watch(calculatedTotal, (newTotal) => {
  orderData.value.total_amount = newTotal
})

// Load data if editing
if (orderId.value) {
  Promise.all([
    loadOrderSchema('orders'),
    loadDetailSchema('order_details')
  ]).then(async () => {
    // Load existing order data (you would use fetchRow here)
    // For demo, using mock data
    orderData.value = {
      order_number: 'ORD-001',
      customer_name: 'John Doe',
      customer_email: 'john@example.com',
      total_amount: 199.95,
      payment_status: 'paid',
      order_date: '2024-01-15',
      notes: 'Rush order'
    }

    // Load order items
    const details = await loadDetails(Number(orderId.value))
    orderItems.value = details.map(d => ({ ...d, _action: 'update' as const }))
  })
} else {
  // Load schemas for create mode
  loadOrderSchema('orders')
  loadDetailSchema('order_details')
}

// Form validation
const isValid = computed(() => {
  return orderData.value.order_number && 
         orderData.value.customer_name && 
         orderItems.value.filter(i => i._action !== 'delete').length > 0
})

// Save order
async function saveOrder() {
  if (!isValid.value) {
    alert('Please fill all required fields and add at least one item')
    return
  }

  try {
    await saveMasterWithDetails(
      orderId.value ? Number(orderId.value) : null,
      orderData.value,
      orderItems.value
    )
    router.push('/crud6/orders')
  } catch (error) {
    console.error('Failed to save order:', error)
  }
}

// Cancel
function cancel() {
  router.push('/crud6/orders')
}
</script>

<template>
  <div class="order-entry">
    <div class="uk-container">
      <h1>{{ orderId ? 'Edit Order' : 'Create New Order' }}</h1>

      <form @submit.prevent="saveOrder">
        <!-- Order Information -->
        <UFCardBox title="Order Information">
          <div class="uk-grid-small" uk-grid>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Order Number *</label>
              <input 
                v-model="orderData.order_number" 
                type="text" 
                class="uk-input" 
                required
              />
            </div>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Order Date *</label>
              <input 
                v-model="orderData.order_date" 
                type="date" 
                class="uk-input" 
                required
              />
            </div>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Customer Name *</label>
              <input 
                v-model="orderData.customer_name" 
                type="text" 
                class="uk-input" 
                required
              />
            </div>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Customer Email</label>
              <input 
                v-model="orderData.customer_email" 
                type="email" 
                class="uk-input"
              />
            </div>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Payment Status</label>
              <select v-model="orderData.payment_status" class="uk-select">
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="uk-width-1-2@s">
              <label class="uk-form-label">Total Amount (Calculated)</label>
              <input 
                :value="calculatedTotal.toFixed(2)" 
                type="text" 
                class="uk-input" 
                readonly
              />
            </div>
            <div class="uk-width-1-1">
              <label class="uk-form-label">Notes</label>
              <textarea 
                v-model="orderData.notes" 
                class="uk-textarea" 
                rows="3"
              ></textarea>
            </div>
          </div>
        </UFCardBox>

        <!-- Order Items -->
        <UFCardBox title="Order Items" class="uk-margin-top">
          <CRUD6DetailGrid
            v-if="detailSchema"
            v-model="orderItems"
            :detail-schema="detailSchema"
            :fields="detailConfig.fields"
            :allow-add="true"
            :allow-edit="true"
            :allow-delete="true"
            :disabled="apiLoading"
          />
        </UFCardBox>

        <!-- Actions -->
        <div class="uk-margin-top uk-text-right">
          <button 
            type="button" 
            class="uk-button uk-button-default uk-margin-small-right"
            @click="cancel"
            :disabled="apiLoading"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="uk-button uk-button-primary"
            :disabled="apiLoading || !isValid"
          >
            <span v-if="apiLoading" uk-spinner="ratio: 0.5"></span>
            {{ orderId ? 'Update Order' : 'Create Order' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
```

## Use Case 2: Product Categories (Many-to-Many)

This example demonstrates managing many-to-many relationships between products and categories through a pivot table.

### Database Schema

```sql
-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pivot table for many-to-many relationship
CREATE TABLE product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_category (product_id, category_id)
);

-- Indexes for performance
CREATE INDEX idx_product_categories_product ON product_categories(product_id);
CREATE INDEX idx_product_categories_category ON product_categories(category_id);
```

### API Usage

For many-to-many relationships, use the relationships API rather than the master-detail form:

```typescript
import { useCRUD6Relationships } from '@ssnukala/sprinkle-crud6/composables'

const { attachRelationships, detachRelationships } = useCRUD6Relationships()

// Assign categories to a product
await attachRelationships('products', productId, 'categories', [1, 2, 3])

// Remove categories from a product
await detachRelationships('products', productId, 'categories', [2])
```

See `examples/ProductCategoryPage.vue` for a complete Vue component example.

## Summary

- **Use Case 1 (Order Entry)**: Use `MasterDetailForm` component for one-to-many relationships where details need to be created/edited alongside the master
- **Use Case 2 (Product Categories)**: Use `useCRUD6Relationships` composable for many-to-many relationships through pivot tables

Both approaches provide clean, intuitive interfaces for managing complex data relationships in CRUD6.
