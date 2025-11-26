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
- **Multi-Column Form Layouts**: Configurable 1, 2, or 3 column form layouts with 2-column as default for better space utilization
- **AutoLookup Component**: Generic searchable auto-complete for selecting records from any model
- **Master-Detail Data Entry**: Create and edit master records with their detail records in a single form
- **Dynamic Detail Sections**: Configure one-to-many relationships declaratively in schemas
- **Multiple Detail Sections**: Display multiple related tables on a single detail page
- **Custom Action Buttons**: Schema-driven buttons for custom operations (field updates, API calls, navigation)
- **Inline Editable Grids**: Edit detail records with add/edit/delete capabilities
- **Flexible Permissions**: Schema-based permission system
- **Data Validation**: Built-in validation based on field definitions
- **Sorting & Filtering**: Automatic sortable and filterable columns
- **Soft Delete Support**: Optional soft delete functionality
- **Type System**: Support for various field types (string, integer, boolean, date, json, password with automatic hashing, etc.)
- **Pagination**: Built-in pagination support
- **Eloquent ORM Integration**: Full Eloquent ORM support with dynamic model configuration
- **Responsive Design**: All forms and layouts are fully responsive and mobile-friendly

## Installation

> **🚀 Quick Start with GitHub Codespaces**: Want to try CRUD6 instantly in a fully configured development environment? Click the "Code" button above, select "Codespaces", and create a new codespace. Everything will be automatically set up for you! See [.devcontainer/GITHUB_CODESPACES_GUIDE.md](.devcontainer/GITHUB_CODESPACES_GUIDE.md) for details.

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

4. Configure Vite to handle CommonJS dependencies. In your `vite.config.ts`, add:
```typescript
export default defineConfig({
    // ... other config
    optimizeDeps: {
        include: [
            'limax',         // Used by CRUD6 for slug generation
            'lodash.deburr'  // CommonJS dependency of limax
        ]
    }
})
```

> **Why is this needed?** CRUD6 uses the `limax` package for slug generation, which depends on `lodash.deburr` (a CommonJS module). Vite needs to pre-bundle these CommonJS modules for proper ES module compatibility.

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
    },
    "user_name": {
      "type": "string", 
      "label": "Username",
      "required": true,
      "sortable": true,
      "filterable": true,
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

- **type**: Field data type (`string`, `integer`, `boolean`, `boolean-tgl`, `boolean-yn`, `date`, `datetime`, `text`, `json`, `float`, `decimal`, `password`, `email`, `url`, `phone`, `zip`, `address`, `textarea-rXcY`)
- **ui**: UI widget type for boolean fields (`checkbox`, `toggle`, `select`) - use with `type: boolean` as alternative to legacy `boolean-tgl`/`boolean-yn` types
- **label**: Human-readable field name
- **required**: Whether the field is required for creation
- **sortable**: Whether the field can be sorted in lists
- **filterable**: Whether the field is included in global search and filtering
- **listable**: Whether the field should be displayed in list views (must be explicitly set to `true` to show; defaults to `false` for security)
- **viewable**: Whether the field should be displayed in detail/view pages (defaults to `true`; set to `false` to hide sensitive fields from detail views)
- **editable**: Whether the field can be edited in forms (defaults to `true` unless field is `auto_increment` or `computed`; set to `false` to make field readonly)
- **auto_increment**: Whether the field is auto-incremented (automatically sets `editable: false`)
- **default**: Default value for the field
- **validation**: Validation rules for the field
- **filter_type**: Type of filter (`equals`, `like`, `starts_with`, `ends_with`, `in`, `between`, `greater_than`, `less_than`, `not_equals`)
- **field_template**: Custom Vue.js HTML template for rendering the field in list views (supports placeholders like `{{field_name}}`)
- **readonly**: ⚠️ **DEPRECATED** - Use `editable: false` instead. This attribute is kept for backward compatibility but will be removed in future versions.

> **Security Note**: The `listable` field property defaults to `false` for security. Only fields explicitly marked as `listable: true` will be displayed in list views. This prevents sensitive data (such as passwords, API keys, or internal timestamps) from being accidentally exposed. Always review which fields should be visible in your application's list views.

> **Visibility Control**: Use `listable`, `viewable`, and `editable` together for fine-grained control:
> - `listable: true` - Shows field in list/table views
> - `viewable: true` - Shows field in detail/view pages (good for readonly fields like timestamps)
> - `editable: false` - Makes field readonly (not editable in forms)
> - Example: A `created_at` field might be `listable: false`, `viewable: true`, `editable: false` to show it in detail view but not allow editing
> 
> **Note**: Setting `editable: false` automatically makes a field readonly - there's no need to specify both `editable: false` and `readonly: true`.

> **Boolean Field Types**: CRUD6 supports multiple boolean field rendering options:
> - `type: "boolean"` (default) or `type: "boolean", ui: "checkbox"` - Standard checkbox
> - `type: "boolean-tgl"` (legacy) or `type: "boolean", ui: "toggle"` - Modern toggle switch with Enabled/Disabled label
> - `type: "boolean-yn"` (legacy) or `type: "boolean", ui: "select"` - Yes/No dropdown select
> - The new `ui` property format is recommended for clarity. See [examples/schema/README.md](examples/schema/README.md#boolean-field-types) for detailed examples.

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

See `examples/schema/categories.json`, `examples/schema/products.json`, `examples/schema/products-template-file.json`, and `examples/schema/products-vue-template.json` for working examples.

### Detail Section Configuration

Define one-to-many relationships declaratively in your schemas to display related data on detail pages.

#### Single Detail Section

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

#### Multiple Detail Sections (NEW!)

Display multiple related tables on a single detail page:

```json
{
  "model": "users",
  "title": "User Management",
  "table": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "USER.ROLES"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "USER.PERMISSIONS"
    }
  ],
  "fields": {
    "id": { "type": "integer", "label": "ID" },
    "user_name": { "type": "string", "label": "Username" }
  }
}
```

**Detail Configuration Properties:**
- **model**: The name of the related model to display
- **foreign_key**: The foreign key field in the related table that references this model
- **list_fields**: Array of field names to display in the detail list
- **title** (optional): Title for the detail section (supports i18n keys)

When viewing a detail page, related records will be automatically displayed in data tables. Both `detail` (single) and `details` (multiple) configurations are supported for backward compatibility.

See [Detail Section Feature Documentation](docs/DETAIL_SECTION_FEATURE.md) and [Multiple Details Feature](docs/MULTIPLE_DETAILS_FEATURE.md) for more information.

### Custom Action Buttons (NEW!)

Add schema-driven custom action buttons to detail pages for operations beyond standard Edit/Delete:

```json
{
  "model": "users",
  "title": "User Management",
  "table": "users",
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "Toggle Enabled",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field",
      "success_message": "User status updated successfully"
    },
    {
      "key": "reset_password",
      "label": "Reset Password",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/users/{id}/password/reset",
      "method": "POST",
      "style": "secondary",
      "permission": "update_user_field",
      "confirm": "Send password reset email?",
      "success_message": "Password reset email sent"
    },
    {
      "key": "change_password",
      "label": "Change Password",
      "icon": "key",
      "type": "route",
      "route": "user.password",
      "style": "primary",
      "permission": "update_user_field"
    }
  ],
  "fields": {
    "flag_enabled": { "type": "boolean", "label": "Enabled" }
  }
}
```

**Action Types:**
- **`field_update`**: Update a single field value (toggle boolean or set specific value)
- **`route`**: Navigate to a specific route/page
- **`api_call`**: Make a custom API call to a backend endpoint
- **`modal`**: Display a custom modal (future implementation)

**Common Action Properties:**
- **key** (required): Unique identifier for the action
- **label** (required): Button text displayed to users
- **type** (required): Action type (field_update, route, api_call, modal)
- **icon** (optional): FontAwesome icon name
- **style** (optional): Button style (primary, secondary, default, danger)
- **permission** (optional): Required permission to see/use the action
- **confirm** (optional): Confirmation message before executing
- **success_message** (optional): Message shown on successful completion

See [Custom Actions Feature Documentation](docs/CUSTOM_ACTIONS_FEATURE.md) for complete reference and examples.

### Master-Detail Data Entry Configuration

Configure editable master-detail relationships to allow creating/editing master records with their detail records in a single form:

```json
{
  "model": "orders",
  "title": "Order Management",
  "table": "orders",
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

**Detail Editable Configuration Properties:**
- **model**: The detail model name
- **foreign_key**: The foreign key field in detail records that references the master
- **fields**: Array of field names to display in the editable grid
- **title** (optional): Title for the detail section
- **allow_add** (optional): Allow adding new detail records (default: true)
- **allow_edit** (optional): Allow editing detail records (default: true)
- **allow_delete** (optional): Allow deleting detail records (default: true)

**Use Cases:**
- **Order Entry**: Create orders with line items in a single form
- **Invoice Management**: Create invoices with detail lines
- **Bill of Materials**: Define products with component lists
- **Any One-to-Many Relationship**: Where child records need to be managed with the parent

The master-detail form includes:
- Standard form fields for the master record
- Inline editable grid for detail records with add/edit/delete
- Single save operation that processes both master and details
- Automatic foreign key population for detail records

See [Master-Detail Usage Guide](examples/master-detail-usage.md) for detailed examples and code samples.

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

**Master-Detail Components:**
- `MasterDetailForm.vue` - Complete master-detail form for creating/editing records with their details
- `DetailGrid.vue` - Inline editable grid for managing detail records with add/edit/delete capabilities

**Lookup Components:**
- `AutoLookup.vue` - Generic searchable auto-complete component for selecting records from any model

#### Component Registration

Components are automatically registered globally when the sprinkle is installed. You can use them directly in your templates:

```vue
<template>
  <!-- Use the pre-built page components -->
  <UFCRUD6ListPage />
  <UFCRUD6RowPage />
  
  <!-- Use individual components -->
  <UFCRUD6CreateModal :model="'users'" :schema="schema" @saved="refresh" />
  <UFCRUD6EditModal :crud6="record" :model="'users'" :schema="schema" @saved="refresh" />
  
  <!-- Use master-detail components -->
  <UFCRUD6MasterDetailForm 
    model="orders" 
    :record-id="orderId"
    :detail-config="detailConfig" 
    @saved="handleSaved"
  />
  
  <UFCRUD6DetailGrid
    v-model="detailRecords"
    :detail-schema="detailSchema"
    :fields="['line_number', 'sku', 'quantity', 'price']"
    :allow-add="true"
    :allow-edit="true"
    :allow-delete="true"
  />
  
  <!-- Use lookup components for searchable selection -->
  <UFCRUD6AutoLookup
    model="products"
    id-field="id"
    :display-fields="['sku', 'name']"
    placeholder="Search products..."
    v-model="selectedProductId"
    @select="handleProductSelect"
  />
</template>
```

##### AutoLookup Component

The AutoLookup component provides a searchable auto-complete interface for selecting records from any CRUD6 model. Perfect for product lookups, category selection, or any scenario where you need to search and select from a large dataset.

**Basic Usage:**
```vue
<UFCRUD6AutoLookup
  model="products"
  id-field="id"
  display-field="name"
  placeholder="Search for a product..."
  v-model="selectedProductId"
  @select="handleProductSelect"
/>
```

**With Multiple Display Fields:**
```vue
<UFCRUD6AutoLookup
  model="products"
  id-field="id"
  :display-fields="['sku', 'name']"
  placeholder="Search by SKU or name..."
  v-model="selectedProductId"
/>
```

**With Custom Display Format:**
```vue
<UFCRUD6AutoLookup
  model="products"
  id-field="id"
  :display-format="(item) => `${item.sku} - ${item.name} ($${item.price})`"
  placeholder="Search products..."
  v-model="selectedProductId"
/>
```

**Key Features:**
- Works with any CRUD6 model
- Real-time search with debouncing
- Keyboard navigation (arrows, enter, escape)
- Configurable display fields
- Custom format functions
- Loading states and clear button
- v-model support
- Form validation support

**Props:**
- `model` - CRUD6 model name (required)
- `id-field` - ID field name (default: 'id')
- `display-field` - Single field to display (default: 'name')
- `display-fields` - Array of fields to display (alternative to display-field)
- `display-format` - Custom format function
- `placeholder` - Input placeholder text
- `min-search-length` - Minimum characters before search (default: 1)
- `debounce-delay` - Debounce delay in ms (default: 300)
- `required` - Field is required
- `disabled` - Field is disabled

**Events:**
- `@select` - Fired when item is selected (includes full item data)
- `@clear` - Fired when selection is cleared
- `@update:modelValue` - v-model update (selected ID)

For detailed documentation and more examples, see [AutoLookup Documentation](docs/AutoLookup.md).

#### Composables

The sprinkle provides Vue composables for API interactions:

```typescript
import { 
  useCRUD6Api, 
  useCRUD6Schema, 
  useCRUD6Relationships,
  useMasterDetail,
  useCRUD6Actions
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

// Custom actions
const { executeAction, loading, error } = useCRUD6Actions('users')

// Execute a custom action
const success = await executeAction(actionConfig, recordId, currentRecord)

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

// Master-detail operations
const {
  saveMasterWithDetails,
  loadDetails,
  apiLoading,
  apiError
} = useMasterDetail('orders', 'order_details', 'order_id')

// Save order with line items
await saveMasterWithDetails(
  orderId,  // null for create
  { order_number: 'ORD-001', customer_name: 'John Doe' },  // Master data
  [  // Detail records
    { line_number: 1, sku: 'PROD-001', quantity: 10, unit_price: 9.99, _action: 'create' }
  ]
)
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

## Architecture & Code Analysis

For a comprehensive analysis of the codebase, including optimization recommendations, documentation improvements, and comparative analysis with similar packages, see the [Comprehensive Review](docs/COMPREHENSIVE_REVIEW.md) document.

### Key Architectural Highlights

- **Controller Pattern**: Action-based controllers following UserFrosting 6 patterns from `sprinkle-admin`
- **Service Layer**: `SchemaService` with in-memory caching for optimal performance
- **Model System**: Dynamic Eloquent model (`CRUD6Model`) configured from JSON schemas
- **Frontend**: Vue 3 composables and components with Pinia store for schema caching
- **Middleware**: `CRUD6Injector` for automatic model and schema injection

### Comparable Tools

| Tool | Approach | Best For |
|------|----------|----------|
| **CRUD6** | JSON Schema-driven, full-stack | UserFrosting 6 applications |
| **Laravel Nova** | Admin panel | Laravel enterprise apps |
| **Filament** | Livewire-based admin | Laravel rapid development |
| **Strapi** | Headless CMS | JavaScript/Node.js projects |

See the [Comparative Analysis](docs/COMPREHENSIVE_REVIEW.md#comparative-analysis) for detailed comparison.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

### Development Guidelines

- Follow [UserFrosting 6 patterns](https://learn.userfrosting.com/sprinkles/) for code organization
- Maintain PSR-12 coding standards
- Add PHPDoc/JSDoc comments for public methods
- Update documentation when adding features
- Run syntax validation: `find app/src -name "*.php" -exec php -l {} \;`

## License

This project is licensed under the MIT License.
