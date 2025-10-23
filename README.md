# sprinkle-crud6
CRUD Sprinkle for UserFrosting 6 - Generic API CRUD Layer

A powerful and flexible CRUD (Create, Read, Update, Delete) API system for UserFrosting 6 that allows you to dynamically perform CRUD operations on any database table using JSON schema definitions. Designed for headless/API-first architecture with Vue.js frontend integration.

**Compatible with UserFrosting 6.0.4 beta and later.**

## Features

- **JSON Schema-Driven**: Define your models using simple JSON configuration files
- **Generic Model System**: Dynamic Eloquent models for any database table without pre-defined model classes
- **RESTful API**: Full REST API support for all CRUD operations (`/api/crud6/{model}`)
- **Complete Frontend Integration**: Full-featured Vue.js components and views included
- **Vue Components**: Pre-built modals, forms, and data tables for CRUD operations
- **Dynamic Detail Sections**: Configure one-to-many relationships declaratively in schemas
- **Flexible Permissions**: Schema-based permission system
- **Data Validation**: Built-in validation based on field definitions
- **Sorting & Filtering**: Automatic sortable and filterable columns
- **Soft Delete Support**: Optional soft delete functionality
- **Type System**: Support for various field types (string, integer, boolean, date, json, etc.)
- **Pagination**: Built-in pagination support
- **Eloquent ORM Integration**: Full Eloquent ORM support with dynamic model configuration

## Installation

> **Note**: This sprinkle requires UserFrosting 6.0.4 beta or later. Since UserFrosting 6 is still in beta, you'll need to configure your project to accept beta packages.

1. First, ensure your project accepts beta packages by setting minimum-stability in your `composer.json`:
```json
{
    "minimum-stability": "beta",
    "prefer-stable": true
}
```

2. Add to your UserFrosting 6 project via composer:
```bash
composer require ssnukala/sprinkle-crud6
```

3. Add the sprinkle to your sprinkles configuration in your app's main sprinkle class:
```php
use UserFrosting\Sprinkle\CRUD6\CRUD6;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class, // Add this line
        // ... your other sprinkles
    ];
}
```

## Configuration

### JSON Schema Format

Create JSON schema files in `app/schema/crud6/` directory. Each file should be named after your model (e.g., `users.json`, `groups.json`).

#### Example Schema (`app/schema/crud6/users.json`):

```json
{
  "model": "users",
  "title": "User Management",
  "description": "Manage system users", 
  "table": "users",
  "permissions": {
    "read": "uri_users",
    "create": "create_user", 
    "update": "update_user_field",
    "delete": "delete_user"
  },
  "default_sort": {
    "user_name": "asc"
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
    "user_name": {
      "type": "string", 
      "label": "Username",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "length": {
          "min": 3,
          "max": 50
        }
      }
    }
  }
}
```

> **Note**: The `primary_key`, `timestamps`, and `soft_delete` properties are optional and default to `"id"`, `true`, and `false` respectively.

### Schema Fields

Each field in the schema can have the following properties:

- **type**: Field data type (`string`, `integer`, `boolean`, `date`, `datetime`, `text`, `json`, `float`, `decimal`)
- **label**: Human-readable field name
- **required**: Whether the field is required for creation
- **sortable**: Whether the field can be sorted in lists
- **filterable**: Whether the field can be filtered
- **searchable**: Whether the field is included in global search
- **readonly**: Whether the field is read-only (not editable)
- **auto_increment**: Whether the field is auto-incremented
- **default**: Default value for the field
- **validation**: Validation rules for the field
- **filter_type**: Type of filter (`equals`, `like`, `starts_with`, `ends_with`, `in`, `between`, `greater_than`, `less_than`, `not_equals`)
- **field_template**: Custom Vue.js HTML template for rendering the field in list views (supports placeholders like `{{field_name}}`)
- **listable**: Whether the field should be displayed in list views (defaults based on field configuration)

### Schema Defaults

The following schema properties have default values and can be omitted from your schema files:

- **primary_key**: Defaults to `"id"` if not specified
- **timestamps**: Defaults to `true` if not specified
- **soft_delete**: Defaults to `false` if not specified

This allows for cleaner, more concise schema definitions by only specifying these values when they differ from the defaults.

### Field Templates

Field templates provide powerful customization for how fields are displayed in list views. Use the `field_template` attribute to define custom templates with access to all row data. Supports inline HTML, external HTML files, and Vue components.

**Inline Template Example:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "<div class='uk-card uk-card-small'><span class='uk-badge'>ID: {{id}}</span> | <span class='uk-badge'>SKU: {{sku}}</span><br/><p>{{description}}</p></div>"
  }
}
```

**External HTML Template File Example:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "product-card.html"
  }
}
```

**Vue Component Template Example:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "ProductCard.vue"
  }
}
```

For external templates, create your template file in `app/assets/templates/crud6/` with the referenced filename.

**Features:**
- **Inline HTML**: Use `{{field_name}}` placeholders for simple templating
- **External HTML files**: Better organization for complex templates
- **Vue components**: Full Vue 3 features (directives, computed properties, TypeScript)
- All row data is available to templates
- Supports standard HTML and CSS classes (UIkit classes recommended)
- Ideal for creating consolidated column displays with multiple field values

**Use Cases:**
- Combine multiple fields into a single consolidated column
- Add badges, labels, or icons to field displays
- Create rich card-style layouts within table cells
- Format complex data presentations with conditional logic
- Use reactive computed properties for dynamic displays
- Keep complex templates in separate files for better maintainability

See `examples/categories.json`, `examples/products.json`, `examples/products-template-file.json`, and `examples/products-vue-template.json` for working examples.

### Detail Section Configuration

Define one-to-many relationships declaratively in your schemas to display related data on detail pages:

```json
{
  "model": "groups",
  "title": "Group Management",
  "table": "groups",
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
- **model**: The name of the related model to display
- **foreign_key**: The foreign key field in the related table that references this model
- **list_fields**: Array of field names to display in the detail list
- **title** (optional): Title for the detail section (supports i18n keys)

When viewing a group detail page, the users belonging to that group will be automatically displayed in a data table. The detail section is optional - if not configured, no related data section will be shown.

See [Detail Section Feature Documentation](docs/DETAIL_SECTION_FEATURE.md) for more information.

### Many-to-Many Relationship Configuration

Define many-to-many relationships for managing associations through pivot tables:

```json
{
  "model": "users",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "user_roles",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "title": "USER.ROLES"
    }
  ]
}
```

**Relationship Configuration Properties:**
- **name**: The relationship name (used in API endpoints)
- **type**: Must be "many_to_many"
- **pivot_table**: The junction/pivot table name
- **foreign_key**: Column in pivot table referencing this model (defaults to `{model}_id`)
- **related_key**: Column in pivot table referencing related model (defaults to `{relation}_id`)
- **title** (optional): Display title for the relationship (supports i18n keys)

Once configured, you can manage relationships via API:

```bash
# Attach roles to a user
POST /api/crud6/users/5/roles
{ "ids": [1, 2, 3] }

# Detach a role from a user
DELETE /api/crud6/users/5/roles
{ "ids": [2] }
```

This enables managing user roles, role permissions, and any other many-to-many relationships through a consistent API.

### Database Connection Configuration

You can specify a database connection in your schema file to use a non-default database:

```json
{
  "model": "products",
  "table": "products",
  "connection": "mysql_analytics",
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true
    },
    "name": {
      "type": "string",
      "required": true
    }
  }
}
```

The `connection` field specifies which database connection to use. If omitted, the default database connection will be used. This connection can be overridden at runtime using the URL syntax (`/api/crud6/products@db2`).

### Path-Based Connection Configuration

You can also organize schemas by database connection using folder structure:

```
app/schema/crud6/
├── users.json              # Default connection
├── products.json           # Default connection
├── db1/
│   └── users.json         # Implicitly uses db1 connection
└── analytics/
    └── page_views.json    # Implicitly uses analytics connection
```

When accessing `/api/crud6/users@db1`:
1. First looks for schema at `app/schema/crud6/db1/users.json`
2. If found, automatically applies `db1` connection
3. If not found, falls back to `app/schema/crud6/users.json` and applies `db1` connection from URL

This approach provides flexibility to either:
- Use explicit `"connection"` field in schema
- Organize schemas in connection-specific folders
- Override connection via URL parameter

## Usage

### API Endpoints

Once you have a schema file, the following API routes are automatically available:

**Basic CRUD:**
- `GET /api/crud6/{model}/schema` - Get schema definition for the model
- `GET /api/crud6/{model}` - List records with pagination, sorting, and filtering
- `POST /api/crud6/{model}` - Create new record
- `GET /api/crud6/{model}/{id}` - Read single record
- `PUT /api/crud6/{model}/{id}` - Update record (full)
- `PUT /api/crud6/{model}/{id}/{field}` - Update single field (partial)
- `DELETE /api/crud6/{model}/{id}` - Delete record

**Relationships:**
- `GET /api/crud6/{model}/{id}/{relation}` - Get related records (one-to-many)
- `POST /api/crud6/{model}/{id}/{relation}` - Attach relationships (many-to-many)
- `DELETE /api/crud6/{model}/{id}/{relation}` - Detach relationships (many-to-many)

### Database Connection Selection

You can specify which database connection to use for a model by using the `@` syntax in the URL:

- `GET /api/crud6/users` - Uses the default database connection (or connection defined in schema)
- `GET /api/crud6/users@db1` - Uses the `db1` database connection
- `POST /api/crud6/products@analytics` - Creates a product record using the `analytics` database connection

The connection name specified in the URL (e.g., `@db1`) will override any connection defined in the model's JSON schema. This allows you to:
- Access the same model from different databases
- Implement multi-tenant applications
- Query replicas or analytics databases
- Perform operations across multiple database servers

**Note**: Database connections must be configured in your UserFrosting application's database configuration file.

### Examples

With a `users.json` schema file, you can access:

**Basic CRUD:**
- API schema: `GET http://yoursite.com/api/crud6/users/schema`
- API list: `GET http://yoursite.com/api/crud6/users`
- API create: `POST http://yoursite.com/api/crud6/users`
- API read: `GET http://yoursite.com/api/crud6/users/123`
- API update (full): `PUT http://yoursite.com/api/crud6/users/123`
- API update (field): `PUT http://yoursite.com/api/crud6/users/123/flag_enabled`
- API delete: `DELETE http://yoursite.com/api/crud6/users/123`

**Relationships:**
- Get user's roles: `GET http://yoursite.com/api/crud6/users/123/roles`
- Attach roles to user: `POST http://yoursite.com/api/crud6/users/123/roles`
- Detach roles from user: `DELETE http://yoursite.com/api/crud6/users/123/roles`

### API Parameters

The list endpoint supports the following query parameters:

- **size**: Number of records per page (default: 25)
- **page**: Page number (default: 1)
- **sorts[field]**: Sort direction (`asc` or `desc`)
- **filters[field]**: Filter value for specific field
- **search**: Global search term

Example:
```
GET /api/crud6/users?size=50&page=2&sorts[user_name]=asc&filters[group_id]=1&search=john
```

## Advanced Features

### Vue.js Integration

This sprinkle includes a complete set of Vue.js components and views for building CRUD interfaces. The components follow UserFrosting 6 patterns and integrate seamlessly with the framework.

#### Included Components

**Views:**
- `PageList.vue` - List view with data table, filtering, and pagination
- `PageRow.vue` - Detail view with edit functionality

**Modals:**
- `CreateModal.vue` - Create new records
- `EditModal.vue` - Edit existing records
- `DeleteModal.vue` - Delete confirmation

**Form Components:**
- `Form.vue` - Dynamic form generation based on schema
- `Info.vue` - Display record information with edit/delete actions
- `Users.vue` - Related users display (for group/role management)

#### Component Registration

Components are automatically registered globally when the sprinkle is installed. You can use them directly in your templates:

```vue
<template>
  <!-- Use the pre-built page components -->
  <UFCRUD6ListPage />
  <UFCRUD6RowPage />
  
  <!-- Or use individual components -->
  <UFCRUD6CreateModal :model="'users'" :schema="schema" @saved="refresh" />
  <UFCRUD6EditModal :crud6="record" :model="'users'" :schema="schema" @saved="refresh" />
</template>
```

#### Composables

The sprinkle provides Vue composables for API interactions:

```typescript
import { 
  useCRUD6Api, 
  useCRUD6Schema, 
  useCRUD6Relationships 
} from '@ssnukala/sprinkle-crud6/composables'

// CRUD operations
const { 
  fetchRow, 
  createRow, 
  updateRow, 
  updateField,  // Update single field
  deleteRow, 
  apiLoading, 
  apiError 
} = useCRUD6Api()

// Update a single field
await updateField('5', 'flag_enabled', true)

// Schema loading and permissions
const { schema, loading, error, loadSchema, hasPermission } = useCRUD6Schema()

// Many-to-many relationship management
const { 
  attachRelationships, 
  detachRelationships, 
  apiLoading, 
  apiError 
} = useCRUD6Relationships()

// Manage user roles
await attachRelationships('users', '5', 'roles', [1, 2, 3])
await detachRelationships('users', '5', 'roles', [2])
```

#### Routes

Frontend routes are automatically registered:
- `/crud6/{model}` - List view for any model
- `/crud6/{model}/{id}` - Detail view for a specific record

These routes follow UserFrosting 6 patterns and integrate with the authentication and authorization system.

### Soft Delete

Enable soft delete in your schema:

```json
{
  "model": "users",
  "soft_delete": true,
  // ... rest of schema
}
```

This will update records with a `deleted_at` timestamp instead of physically deleting them.

### Timestamps

Enable automatic timestamp management:

```json
{
  "model": "users", 
  "timestamps": true,
  // ... rest of schema
}
```

This will automatically set `created_at` and `updated_at` fields.

### Generic Model System

CRUD6 includes a powerful generic model system that allows you to work with any database table using Eloquent ORM without requiring pre-defined model classes.

#### Using the Generic Model

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

// Get a configured model instance for any table
$schemaService = $container->get(SchemaService::class);
$userModel = $schemaService->getModelInstance('users');

// Now use it like any Eloquent model
$users = $userModel->where('is_active', true)->get();
$newUser = $userModel->create([
    'user_name' => 'john_doe',
    'email' => 'john@example.com'
]);
```

#### Manual Model Configuration

```php
use UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model;

$model = new CRUD6Model();
$model->setTable('products')
      ->setFillable(['name', 'price', 'description']);

// Or configure from schema
$model->configureFromSchema($schema);
```

#### Features

- **Dynamic Configuration**: Models are configured at runtime based on JSON schemas
- **Eloquent ORM**: Full support for all Eloquent features (relationships, scopes, etc.)
- **Type Casting**: Automatic field casting based on schema field types
- **Soft Deletes**: Built-in soft delete support when enabled in schema
- **Mass Assignment Protection**: Fillable attributes automatically set from schema
- **Timestamp Management**: Automatic handling of created_at/updated_at fields

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License.
