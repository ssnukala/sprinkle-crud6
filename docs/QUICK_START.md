# CRUD6 Quick Start Guide

Get up and running with CRUD6 in 5 minutes!

## Prerequisites

- UserFrosting 6.0.4+ installed
- Composer and npm available
- A database table you want to expose via API

## Step 1: Install CRUD6

```bash
composer require ssnukala/sprinkle-crud6
```

Add to your sprinkle configuration:

```php
use UserFrosting\Sprinkle\CRUD6\CRUD6;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class, // Add this
    ];
}
```

## Step 2: Create Your First Schema

Create a file at `app/schema/crud6/products.json`:

```json
{
  "model": "products",
  "title": "Product Management",
  "table": "products",
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "listable": true
    },
    "name": {
      "type": "string",
      "label": "Product Name",
      "required": true,
      "listable": true,
      "filterable": true,
      "sortable": true
    },
    "description": {
      "type": "text",
      "label": "Description"
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "listable": true,
      "sortable": true
    },
    "is_active": {
      "type": "boolean",
      "label": "Active",
      "default": true,
      "listable": true
    }
  },
  "permissions": {
    "read": "uri_products",
    "create": "create_product",
    "update": "update_product",
    "delete": "delete_product"
  }
}
```

## Step 3: Access Your API

Your API endpoints are now available:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/crud6/products` | List all products |
| POST | `/api/crud6/products` | Create a product |
| GET | `/api/crud6/products/1` | Get product by ID |
| PUT | `/api/crud6/products/1` | Update product |
| DELETE | `/api/crud6/products/1` | Delete product |
| GET | `/api/crud6/products/schema` | Get schema definition |

### Example API Calls

**List products with pagination:**
```bash
curl -X GET "http://localhost/api/crud6/products?size=10&page=1&sorts[name]=asc"
```

**Create a product:**
```bash
curl -X POST "http://localhost/api/crud6/products" \
  -H "Content-Type: application/json" \
  -d '{"name": "Widget", "price": 9.99, "is_active": true}'
```

**Search products:**
```bash
curl -X GET "http://localhost/api/crud6/products?search=widget"
```

## Step 4: Use Vue Components

In your Vue application:

```vue
<template>
  <div>
    <!-- List View -->
    <UFCRUD6ListPage />
    
    <!-- Or use individual components -->
    <UFCRUD6CreateModal 
      :model="'products'" 
      :schema="schema" 
      @saved="refreshList" 
    />
  </div>
</template>

<script setup lang="ts">
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'

const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
  await loadSchema('products')
})
</script>
```

## Step 5: Navigate to Your CRUD Pages

Frontend routes are automatically available:

- `/crud6/products` - List view
- `/crud6/products/1` - Detail view for product ID 1

## What's Next?

### Add Relationships

Define many-to-many relationships:

```json
{
  "relationships": [
    {
      "name": "categories",
      "type": "many_to_many",
      "pivot_table": "product_categories",
      "foreign_key": "product_id",
      "related_key": "category_id"
    }
  ]
}
```

### Add Detail Sections

Show related data on detail pages:

```json
{
  "details": [
    {
      "model": "reviews",
      "foreign_key": "product_id",
      "list_fields": ["rating", "comment", "created_at"],
      "title": "Product Reviews"
    }
  ]
}
```

### Add Custom Actions

Add buttons for custom operations:

```json
{
  "actions": [
    {
      "key": "toggle_active",
      "label": "Toggle Active",
      "icon": "power-off",
      "type": "field_update",
      "field": "is_active",
      "toggle": true
    }
  ]
}
```

## Complete Example Schema

Here's a full-featured schema example:

```json
{
  "model": "products",
  "title": "Product Management",
  "singular_title": "Product",
  "table": "products",
  "timestamps": true,
  "soft_delete": true,
  "default_sort": {
    "name": "asc"
  },
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "listable": true,
      "sortable": true
    },
    "name": {
      "type": "string",
      "label": "Name",
      "required": true,
      "listable": true,
      "filterable": true,
      "sortable": true,
      "validation": {
        "required": true,
        "length": { "min": 2, "max": 100 }
      }
    },
    "sku": {
      "type": "string",
      "label": "SKU",
      "listable": true,
      "filterable": true
    },
    "description": {
      "type": "textarea-r5c60",
      "label": "Description",
      "viewable": true
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "listable": true,
      "sortable": true,
      "validation": {
        "range": { "min": 0 }
      }
    },
    "category_id": {
      "type": "smartlookup",
      "label": "Category",
      "lookup_model": "categories",
      "lookup_id": "id",
      "lookup_desc": "name",
      "listable": true
    },
    "is_active": {
      "type": "boolean",
      "ui": "toggle",
      "label": "Active",
      "default": true,
      "listable": true
    },
    "created_at": {
      "type": "datetime",
      "label": "Created",
      "editable": false,
      "viewable": true
    }
  },
  "relationships": [
    {
      "name": "tags",
      "type": "many_to_many",
      "pivot_table": "product_tags",
      "foreign_key": "product_id",
      "related_key": "tag_id"
    }
  ],
  "details": [
    {
      "model": "reviews",
      "foreign_key": "product_id",
      "list_fields": ["rating", "comment", "user_name"],
      "title": "Customer Reviews"
    }
  ],
  "actions": [
    {
      "key": "toggle_active",
      "label": "Toggle Active",
      "icon": "power-off",
      "type": "field_update",
      "field": "is_active",
      "toggle": true,
      "style": "default"
    },
    {
      "key": "duplicate",
      "label": "Duplicate",
      "icon": "copy",
      "type": "api_call",
      "endpoint": "/api/products/{id}/duplicate",
      "method": "POST",
      "style": "secondary"
    }
  ],
  "permissions": {
    "read": "view_products",
    "create": "create_product",
    "update": "update_product",
    "delete": "delete_product"
  }
}
```

## Troubleshooting

### Schema Not Found
- Ensure your JSON file is in `app/schema/crud6/`
- Check the file name matches your model name (e.g., `products.json` for model `products`)
- Validate JSON syntax: `php -r "echo json_decode(file_get_contents('app/schema/crud6/products.json')) ? 'valid' : 'invalid';"`

### 403 Forbidden
- Check that permissions defined in schema are assigned to your user
- Verify AuthGuard middleware is working

### 500 Server Error
- Enable debug mode: Add `'crud6' => ['debug_mode' => true]` to your config
- Check logs at `app/logs/debug.log`

## Resources

- [Full Documentation](../README.md)
- [Schema Examples](../examples/schema/)
- [Comprehensive Review](./COMPREHENSIVE_REVIEW.md)
- [Field Types Reference](./FIELD_TYPES_REFERENCE.md)
- [Custom Actions Feature](./CUSTOM_ACTIONS_FEATURE.md)

---

*You now have a fully functional CRUD API and frontend in under 5 minutes!*
