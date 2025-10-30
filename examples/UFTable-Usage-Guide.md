# Example: Using CRUD6 with UFTable Integration

This example demonstrates how to use the new UFTable integration in a UserFrosting 6 application.

## Basic Setup

### 1. Add CRUD6 Sprinkle to your UserFrosting App

In your main sprinkle class:

```php
<?php

namespace MyApp\Sprinkle\MyApp;

use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Sprinkle\SprinkleRecipe;

class MyApp implements SprinkleRecipe
{
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
            CRUD6::class,  // Add CRUD6 sprinkle
        ];
    }
    
    // ... other methods
}
```

### 2. Create a Schema File

Create `app/schema/crud6/products.json`:

```json
{
  "model": "products",
  "title": "Product Management",
  "description": "Manage your product catalog",
  "table": "products",
  "primary_key": "id",
  "timestamps": true,
  "soft_delete": false,
  "permissions": {
    "read": "view_products",
    "create": "create_product", 
    "update": "edit_product",
    "delete": "delete_product"
  },
  "default_sort": {
    "name": "asc"
  },
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "readonly": true,
      "sortable": true,
      "searchable": false
    },
    "name": {
      "type": "string",
      "label": "Product Name",
      "required": true,
      "sortable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "length": {
          "min": 2,
          "max": 255
        }
      }
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "required": true,
      "sortable": true,
      "searchable": false,
      "filter_type": "between"
    },
    "is_active": {
      "type": "boolean",
      "label": "Active",
      "default": true,
      "sortable": true,
      "searchable": false
    }
  }
}
```

### 3. Create Database Migration

Create the corresponding database table:

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Core\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        if (!$this->schema->hasTable('products')) {
            $this->schema->create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $this->schema->dropIfExists('products');
    }
}
```

## Frontend Usage

### Vue Component Example

```vue
<template>
    <div class="products-page">
        <h1>Product Management</h1>
        
        <!-- List View -->
        <UFTableCRUD6
            model="products"
            :readonly="!canEdit"
            @edit="editProduct"
            @delete="deleteProduct"
            @row-click="viewProduct"
        />
        
        <!-- Or use the full page component -->
        <PageCRUD6s />
    </div>
</template>

<script setup lang="ts">
import { UFTableCRUD6, PageCRUD6s } from '@ssnukala/sprinkle-crud6'

const canEdit = true // Check user permissions

function editProduct(product: any) {
    console.log('Editing product:', product)
    // Navigate to edit page or open modal
}

function deleteProduct(product: any) {
    console.log('Deleting product:', product)
    // Show confirmation dialog
}

function viewProduct(product: any) {
    console.log('Viewing product:', product)
    // Navigate to detail page
}
</script>
```

### Using Composables

```vue
<script setup lang="ts">
import { useCRUD6Schema, useCRUD6Api } from '@ssnukala/sprinkle-crud6'

// Load schema and data
const { schema, loading, error, tableColumns } = useCRUD6Schema('products')
const { fetchRow, createRow, updateRow, deleteRow } = useCRUD6Api('products')

// Use the reactive data
console.log('Table columns:', tableColumns.value)
console.log('Schema:', schema.value)
</script>
```

## API Endpoints

Once set up, the following endpoints become available:

- `GET /api/crud6/products` - List products (with filtering/sorting)
- `POST /api/crud6/products` - Create new product
- `GET /api/crud6/products/{id}` - Get specific product
- `PUT /api/crud6/products/{id}` - Update product
- `DELETE /api/crud6/products/{id}` - Delete product
- `GET /api/crud6/products/schema` - Get products schema

## Key Benefits

1. **No Additional Code Required**: Just define the schema and create the database table
2. **Automatic UI Generation**: Tables and forms are generated from schema
3. **Permission Integration**: Respects UserFrosting's permission system
4. **Responsive Design**: Works on all devices
5. **Type Safety**: Full TypeScript support

## Advanced Features

### Custom Field Types
Support for various field types with appropriate input controls:
- `string` → Text input
- `integer`, `decimal`, `float` → Number input
- `boolean` → Checkbox
- `date` → Date picker
- `datetime` → DateTime picker
- `text` → Textarea
- `json` → JSON editor

### Filtering and Sorting
Automatic filtering and sorting based on schema configuration:
- Set `sortable: true` for sortable columns
- Set `searchable: true` for global search inclusion

### Validation
Schema-based validation with support for:
- Required fields
- Length constraints
- Email validation
- Custom validation rules

This integration makes CRUD6 a powerful tool for rapid application development while maintaining the flexibility and robustness of UserFrosting 6.