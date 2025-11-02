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
    },
    "name": {
      "type": "string",
      "label": "Product Name",
      "required": true,
      "sortable": true,
      "filterable": true,
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
    },
    "category_id": {
      "type": "integer",
      "label": "Category",
      "required": true,
      "sortable": true,
      "filterable": true,
      "filter_type": "equals"
    },
    "is_active": {
      "type": "boolean",
      "label": "Active",
      "default": true,
      "sortable": true,
      "filterable": true,
    },
    "metadata": {
      "type": "json",
      "label": "Metadata",
      "required": false,
      "sortable": false,
      "filterable": false,
    },
    "created_at": {
      "type": "datetime",
      "label": "Created At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "date_format": "Y-m-d H:i:s"
    },
    "updated_at": {
      "type": "datetime",
      "label": "Updated At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
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

### Detail Section (One-to-Many Relationships)

Define related data that should be displayed on a record's detail page. This allows you to show one-to-many relationships declaratively without writing custom code.

#### Example: Categories with Products

```json
{
  "model": "categories",
  "title": "Category Management",
  "table": "categories",
  "primary_key": "id",
  "detail": {
    "model": "products",
    "foreign_key": "category_id",
    "list_fields": ["name", "sku", "price", "is_active"],
    "title": "Products in this Category"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID" },
    "name": { "type": "string", "label": "Category Name" },
    "slug": { "type": "string", "label": "Slug" }
  }
}
```

#### Example: Groups with Users

```json
{
  "model": "groups",
  "title": "Group Management",
  "table": "groups",
  "primary_key": "id",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name", "last_name", "flag_enabled"],
    "title": "GROUP.USERS"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID" },
    "name": { "type": "string", "label": "Group Name" }
  }
}
```

**Detail Configuration Properties:**
- `model`: The related model to display
- `foreign_key`: Foreign key field in the related table
- `list_fields`: Array of field names to show in the detail list
- `title` (optional): Section title (supports i18n keys)

**API Endpoint:** When viewing a category with ID 1, the products will be fetched from:
```
GET /api/crud6/categories/1/products
```

The detail section automatically:
- Filters related records by the foreign key
- Displays fields based on the related model's schema
- Supports sorting, searching, and pagination
- Formats fields according to their type (boolean, date, etc.)

See the complete [Detail Section Feature Documentation](../docs/DETAIL_SECTION_FEATURE.md) for more details.

### Database Connection Selection

You can configure models to use different database connections, allowing for multi-database architectures, read replicas, or analytics databases.

#### Schema-Based Connection Configuration

Define the connection in the schema file:

```json
{
  "model": "analytics",
  "table": "page_views",
  "connection": "mysql_analytics",
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true
    },
    "page_url": {
      "type": "string",
      "required": true
    },
    "viewed_at": {
      "type": "datetime",
      "required": true
    }
  }
}
```

#### URL-Based Connection Override

Override the schema connection or specify a connection at runtime:

```bash
# Uses default connection (or schema connection if specified)
GET /api/crud6/analytics

# Uses mysql_analytics connection (overrides schema)
GET /api/crud6/analytics@mysql_analytics

# Uses mysql_replica connection
GET /api/crud6/users@mysql_replica

# Create product on secondary database
POST /api/crud6/products@db_secondary
```

#### Folder-Based Connection Configuration

Organize schemas by database connection using folder structure:

```
app/schema/crud6/
├── users.json              # Default connection
├── products.json           # Default connection
├── db1/
│   └── users.json         # Implicitly uses db1 connection
├── db2/
│   └── orders.json        # Implicitly uses db2 connection
└── analytics/
    └── page_views.json    # Implicitly uses analytics connection
```

With this structure:
```bash
# Uses app/schema/crud6/users.json (default connection)
GET /api/crud6/users

# Uses app/schema/crud6/db1/users.json (db1 connection)
GET /api/crud6/users@db1

# Uses app/schema/crud6/analytics/page_views.json (analytics connection)
GET /api/crud6/page_views@analytics
```

**Benefits of folder-based approach:**
- Clean separation of schemas per database
- No need for explicit `connection` field in schema
- Easy to see which models belong to which database
- Automatic connection detection from folder name

#### Use Cases

- **Multi-tenancy**: Route requests to different database connections per tenant
- **Analytics**: Keep analytics data in a separate database
- **Read Replicas**: Query read replicas for heavy read operations
- **Data Migration**: Access legacy databases alongside new ones
- **Microservices**: Access different databases per service boundary

**Note**: Connections must be configured in your UserFrosting application's database configuration.

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