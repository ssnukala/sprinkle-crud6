# CRUD6 Sprinkle Examples

This directory contains example configurations and usage patterns for the CRUD6 sprinkle.

## Basic Usage Example

### 1. Schema Definition

Create a schema file `app/schema/crud6/products.json`:

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
      "filterable": false,
      "searchable": false
    },
    "name": {
      "type": "string",
      "label": "Product Name",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "length": {
          "min": 2,
          "max": 255
        }
      }
    },
    "sku": {
      "type": "string",
      "label": "SKU",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "unique": true
      }
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": false,
      "validation": {
        "required": true,
        "numeric": true,
        "min": 0
      }
    },
    "description": {
      "type": "text",
      "label": "Description",
      "required": false,
      "sortable": false,
      "filterable": false,
      "searchable": true
    },
    "category_id": {
      "type": "integer",
      "label": "Category",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": false,
      "filter_type": "equals"
    },
    "is_active": {
      "type": "boolean",
      "label": "Active",
      "default": true,
      "sortable": true,
      "filterable": true,
      "searchable": false
    },
    "metadata": {
      "type": "json",
      "label": "Metadata",
      "required": false,
      "sortable": false,
      "filterable": false,
      "searchable": false
    },
    "created_at": {
      "type": "datetime",
      "label": "Created At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "searchable": false,
      "date_format": "Y-m-d H:i:s"
    },
    "updated_at": {
      "type": "datetime",
      "label": "Updated At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "searchable": false,
      "date_format": "Y-m-d H:i:s"
    }
  }
}
```

### 2. Database Table

Create the corresponding database table:

```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Access the API

Once the schema is created, you can access the API endpoints:

- **API Endpoints**:
  - List: `GET /api/crud6/products`
  - Create: `POST /api/crud6/products`
  - Read: `GET /api/crud6/products/{id}`
  - Update: `PUT /api/crud6/products/{id}`
  - Delete: `DELETE /api/crud6/products/{id}`

### 4. API Usage Examples

#### List products with filtering and sorting:
```bash
GET /api/crud6/products?size=25&page=1&sorts[name]=asc&filters[category_id]=1&search=laptop
```

#### Create a new product:
```bash
POST /api/crud6/products
Content-Type: application/json

{
    "name": "Gaming Laptop",
    "sku": "LAPTOP001",
    "price": 1299.99,
    "description": "High-performance gaming laptop",
    "category_id": 1,
    "is_active": true,
    "metadata": {
        "specs": {
            "cpu": "Intel i7",
            "ram": "16GB",
            "storage": "1TB SSD"
        }
    }
}
```

#### Update a product:
```bash
PUT /api/crud6/products/123
Content-Type: application/json

{
    "name": "Updated Gaming Laptop",
    "price": 1199.99
}
```

#### Delete a product:
```bash
DELETE /api/crud6/products/123
```

## Advanced Configuration

### Vue.js Integration

This API is designed to work seamlessly with Vue.js frontends. Use the endpoints with libraries like `userfrosting/pink-cup-cake` for rich frontend interfaces.

**Schema Optimization**: The `useCRUD6Schema` composable now includes automatic caching to prevent duplicate API calls. See [Preventing Duplicate Schema Calls](../docs/Preventing-Duplicate-Schema-Calls.md) for optimization patterns.

Example usage in Vue components:

```typescript
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'

// Automatically loads and caches schema
const { schema, loadSchema, setSchema } = useCRUD6Schema('products')

// First call - makes API request
await loadSchema('products')

// Subsequent calls - uses cached schema (no API call)
await loadSchema('products')
```

### Soft Delete

Enable soft delete for your model:

```json
{
    "model": "products",
    "soft_delete": true,
    ...
}
```

### Complex Validation

Add complex validation rules:

```json
{
    "fields": {
        "email": {
            "type": "string",
            "validation": {
                "required": true,
                "email": true,
                "unique": {
                    "table": "users",
                    "column": "email"
                }
            }
        }
    }
}
```

### Filter Types

Different filter types for fields:

```json
{
    "fields": {
        "name": {
            "filter_type": "like"
        },
        "price": {
            "filter_type": "between"
        },
        "category_id": {
            "filter_type": "in"
        },
        "created_at": {
            "filter_type": "greater_than"
        }
    }
}
```