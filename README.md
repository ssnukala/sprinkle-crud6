# sprinkle-crud6
CRUD Sprinkle for UserFrosting 6 - Generic API CRUD Layer

A powerful and flexible CRUD (Create, Read, Update, Delete) API system for UserFrosting 6 that allows you to dynamically perform CRUD operations on any database table using JSON schema definitions. Designed for headless/API-first architecture with Vue.js frontend integration.

**Compatible with UserFrosting 6.0.4 beta and later, tested with 6.0.0-beta.8.**

## Features

- **JSON Schema-Driven**: Define your models using simple JSON configuration files
- **Generic Model System**: Dynamic Eloquent models for any database table without pre-defined model classes
- **RESTful API**: Full REST API support for all CRUD operations (`/api/crud6/{model}`)
- **Complete Frontend Integration**: Full-featured Vue.js components and views included
- **Unified Modal System**: Single modal component for all CRUD operations (create, edit, delete, custom actions)
  - Schema-driven modal configuration with multiple types (form, confirm, input, delete)
  - Configurable button combinations (yes_no, save_cancel, ok_cancel, confirm_cancel)
  - Full translation support with model and record context
  - Automatic default actions (create, edit, delete) from schema permissions
- **Scope-Based Action Filtering**: Control where actions appear (list view, detail view, or both)
- **Custom Action Buttons**: Schema-driven buttons for custom operations (field updates, API calls, navigation)
- **Multi-Column Form Layouts**: Configurable 1, 2, or 3 column form layouts with 2-column as default for better space utilization
- **AutoLookup Component**: Generic searchable auto-complete for selecting records from any model
- **Master-Detail Data Entry**: Create and edit master records with their detail records in a single form
- **Dynamic Detail Sections**: Configure one-to-many relationships declaratively in schemas
- **Multiple Detail Sections**: Display multiple related tables on a single detail page
- **Inline Editable Grids**: Edit detail records with add/edit/delete capabilities
- **Flexible Permissions**: Schema-based permission system with automatic enforcement
- **Data Validation**: Built-in validation based on field definitions with client and server-side checks
- **Sorting & Filtering**: Automatic sortable and filterable columns
- **Soft Delete Support**: Optional soft delete functionality
- **Type System**: Support for various field types (string, integer, boolean, date, json, password with automatic hashing, etc.)
- **Pagination**: Built-in pagination support with configurable page sizes
- **Eloquent ORM Integration**: Full Eloquent ORM support with dynamic model configuration
- **Responsive Design**: All forms and layouts are fully responsive and mobile-friendly
- **Translation Support**: Full i18n support following UserFrosting 6 standards

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

4. Configure Vite for optimal performance. In your `vite.config.ts`, add:
```typescript
export default defineConfig({
    // ... other config
    optimizeDeps: {
        include: [
            'limax',         // Used by CRUD6 for slug generation
            'lodash.deburr'  // Dependency of limax
        ]
    }
})
```

> **Why is this recommended?** CRUD6 uses the `limax` package for slug generation. Pre-bundling limax and its dependencies improves Vite cold-start performance and ensures consistent behavior across different Vite versions.

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
  "title_field": "user_name",
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
      "editable": false,
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
- **editable**: Whether the field can be edited in forms (defaults to `true` unless field is `auto_increment` or `computed`; set to `false` to make field non-editable)
- **auto_increment**: Whether the field is auto-incremented (automatically sets `editable: false`)
- **default**: Default value for the field
- **validation**: Validation rules for the field
- **filter_type**: Type of filter (`equals`, `like`, `starts_with`, `ends_with`, `in`, `between`, `greater_than`, `less_than`, `not_equals`)
- **field_template**: Custom Vue.js HTML template for rendering the field in list views (supports placeholders like `{{field_name}}`)

> **Security Note**: The `listable` field property defaults to `false` for security. Only fields explicitly marked as `listable: true` will be displayed in list views. This prevents sensitive data (such as passwords, API keys, or internal timestamps) from being accidentally exposed. Always review which fields should be visible in your application's list views.

> **Visibility Control**: Use `listable`, `viewable`, and `editable` together for fine-grained control:
> - `listable: true` - Shows field in list/table views
> - `viewable: true` - Shows field in detail/view pages (good for non-editable fields like timestamps)
> - `editable: false` - Makes field non-editable (not editable in forms)
> - Example: A `created_at` field might be `listable: false`, `viewable: true`, `editable: false` to show it in detail view but not allow editing

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
- **title_field**: Specifies which field to display in breadcrumbs and page titles for individual records (if not specified, the record ID is used)

This allows for cleaner, more concise schema definitions by only specifying these values when they differ from the defaults.

#### Breadcrumb Display Configuration

The `title_field` attribute controls which field is displayed in breadcrumbs and page titles when viewing individual records. This is essential for displaying meaningful identifiers instead of database IDs.

**Example**: For a users model accessed via `/crud6/users/8`, instead of showing "8" in the breadcrumb, you can configure it to show the user's name:

```json
{
  "model": "users",
  "title_field": "user_name",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "user_name": { "type": "string", "label": "Username" }
  }
}
```

With this configuration, the breadcrumb will show "john_doe" (the value of `user_name`) instead of "8" (the ID).

**Fallback Behavior**: If `title_field` is not specified or the specified field is empty, the system will display the record's ID.

**Common Examples**:
- Users model: `"title_field": "user_name"` or `"title_field": "email"`
- Products model: `"title_field": "name"` or `"title_field": "sku"`
- Contacts model: `"title_field": "last_name"` or `"title_field": "email"`
- Categories model: `"title_field": "name"`
- Orders model: `"title_field": "order_number"`

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

### Custom Action Buttons

Add schema-driven custom action buttons to list and detail pages for operations beyond standard CRUD. The unified modal system automatically handles all action types with configurable scope-based filtering.

#### Default Actions

CRUD6 automatically generates default actions based on schema permissions:

```json
{
  "model": "users",
  "permissions": {
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  }
}
```

This automatically generates:
- **Create** button in list view (`scope: ['list']`)
- **Edit** button in detail view (`scope: ['detail']`)
- **Delete** button in detail view (`scope: ['detail']`)

To disable default actions, add `"default_actions": false` to your schema.

#### Scope-Based Action Filtering

Control where actions appear using the `scope` property:

```json
{
  "model": "users",
  "actions": [
    {
      "key": "import_users",
      "label": "Import Users",
      "scope": ["list"],          // Only appears in list view
      "type": "route",
      "route": "users.import"
    },
    {
      "key": "reset_password",
      "label": "Reset Password",
      "scope": ["detail"],        // Only appears in detail view
      "type": "field_update",
      "field": "password"
    },
    {
      "key": "export_data",
      "label": "Export",
      "scope": ["list", "detail"] // Appears in both views
    }
  ]
}
```

**Scope Options:**
- `["list"]` - Action appears only in list view
- `["detail"]` - Action appears only in detail view  
- `["list", "detail"]` - Action appears in both views
- No scope property - Action appears in all views (backward compatible)

#### Custom Action Examples

**Toggle Field with Confirmation:**
```json
{
  "key": "toggle_enabled",
  "label": "Toggle Enabled",
  "icon": "power-off",
  "type": "field_update",
  "field": "flag_enabled",
  "toggle": true,
  "style": "default",
  "scope": ["detail"],
  "permission": "update_user_field",
  "confirm": "USER.TOGGLE_ENABLED_CONFIRM",
  "success_message": "User status updated successfully"
}
```

**API Call Action:**
```json
{
  "key": "reset_password",
  "label": "Reset Password",
  "icon": "envelope",
  "type": "api_call",
  "endpoint": "/api/users/{id}/password/reset",
  "method": "POST",
  "style": "secondary",
  "scope": ["detail"],
  "permission": "update_user_field",
  "confirm": "Send password reset email?",
  "success_message": "Password reset email sent"
}
```

**Route Navigation:**
```json
{
  "key": "change_password",
  "label": "Change Password",
  "icon": "key",
  "type": "route",
  "route": "user.password",
  "style": "primary",
  "scope": ["detail"],
  "permission": "update_user_field"
}
```

**Input Form Action:**
```json
{
  "key": "set_password",
  "label": "Set Password",
  "type": "input",
  "scope": ["detail"],
  "modal_config": {
    "type": "input",
    "fields": ["password"],
    "buttons": "save_cancel"
  },
  "confirm": "Set new password for {{user_name}}?",
  "permission": "update_user_field"
}
```

#### Modal Configuration

All actions use the UnifiedModal component with configurable modal behavior:

**Modal Types:**
- `form` - Full CRUD form (auto-detected for create/edit actions)
- `confirm` - Simple Yes/No confirmation (auto-detected for delete/toggle actions)
- `input` - Input fields with validation
- `message` - Display message with OK button

**Button Presets:**
- `yes_no` - For confirmations (Yes/No buttons)
- `save_cancel` - For forms and inputs (Save/Cancel buttons)
- `ok_cancel` - For messages (OK/Cancel buttons)
- `confirm_cancel` - Customizable confirm action
- Custom array - Define your own button combinations

**Example with Full Modal Configuration:**
```json
{
  "key": "custom_action",
  "label": "Custom Action",
  "type": "input",
  "scope": ["detail"],
  "modal_config": {
    "type": "input",
    "title": "CUSTOM_ACTION.TITLE",
    "fields": ["field1", "field2"],
    "buttons": "save_cancel",
    "warning": "CUSTOM_ACTION.WARNING"
  }
}
```

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
    "total_amount": { "type": "decimal", "editable": false }
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

See [Master-Detail Usage Guide](examples/docs/master-detail-usage.md) for detailed examples and code samples.

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

## Schema-Driven Philosophy

**CRUD6 is completely schema-driven.** This means:

1. **No Hardcoded Models**: All models, fields, relationships, and permissions are defined in JSON schema files
2. **Dynamic Discovery**: The system automatically discovers and loads schemas from the `app/schema/crud6/` or `examples/schema/` directory
3. **Generic Operations**: Controllers, services, and tests work generically with any schema
4. **Extensible**: Add new models by simply creating a new schema file - no code changes needed

### Adding a New Model

To add a new model to your application:

1. **Create the schema file** in `app/schema/crud6/{model}.json`:
   ```json
   {
     "model": "products",
     "table": "products",
     "title": "Products",
     "fields": {
       "id": { "type": "integer", "label": "ID" },
       "name": { "type": "string", "label": "Product Name", "required": true },
       "price": { "type": "decimal", "label": "Price" }
     }
   }
   ```

2. **Create the database table** (via migration or direct SQL):
   ```php
   // In your migration file
   $schema->create('products', function (Blueprint $table) {
       $table->id();
       $table->string('name');
       $table->decimal('price', 10, 2);
       $table->timestamps();
   });
   ```

3. **That's it!** The following are automatically available:
   - ✅ RESTful API endpoints (`/api/crud6/products`)
   - ✅ Vue.js components for CRUD operations
   - ✅ Permissions (if defined in schema)
   - ✅ Validation (based on field definitions)
   - ✅ Relationships (if defined in schema)
   - ✅ Integration tests (via schema discovery)

### No Code Changes Required

When you add a new schema file:
- ❌ **DO NOT** modify controllers - they work generically
- ❌ **DO NOT** modify routes - they handle `{model}` parameter
- ❌ **DO NOT** update test files - tests auto-discover schemas
- ❌ **DO NOT** hardcode model names anywhere
- ✅ **DO** place schema in the correct directory
- ✅ **DO** ensure database table exists
- ✅ **DO** define permissions in the schema

### Example: Testing a New Model

Tests automatically discover and test new models:

```php
// SchemaBasedApiTest.php discovers schemas automatically
public static function schemaProvider(): array
{
    $schemaDir = __DIR__ . '/../../../examples/schema';
    $schemaFiles = glob($schemaDir . '/*.json');
    // Returns ALL schemas found - no hardcoding!
}
```

Add `orders.json` schema → Tests automatically include it!

### Schema-Driven Components

These components are already schema-driven:

- **Controllers**: Work with any model via `CRUD6Model` and schema configuration
- **Permissions**: Loaded dynamically from all schema files (see `DefaultPermissions.php`)
- **Tests**: Auto-discover schemas from directory (see `SchemaBasedApiTest.php`)
- **Frontend**: Uses schema to render forms, lists, and modals dynamically
- **Sprunje**: Dynamically configured from schema fields for sorting/filtering

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

#### Unified Modal Architecture

CRUD6 uses a **unified modal system** that consolidates all CRUD operations into a single, flexible component. This provides:

**Key Advantages:**
- **Single Component**: One modal handles create, edit, delete, and all custom actions
- **Schema-Driven**: Modal behavior configured entirely through JSON schemas
- **Automatic Actions**: Default create/edit/delete actions generated from permissions
- **Scope Filtering**: Actions automatically filtered for list or detail views
- **Consistent UX**: All operations follow the same patterns and translations
- **Reduced Code**: Eliminates duplicate modal components and logic

**How It Works:**
1. Schema defines permissions and custom actions
2. Backend automatically generates default actions (create, edit, delete)
3. Actions filtered by scope (list view vs detail view)
4. UnifiedModal renders appropriate form/confirmation based on action type
5. All translations use consistent model and record context

**Example Schema:**
```json
{
  "model": "users",
  "permissions": {
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  },
  "actions": [
    {
      "key": "toggle_enabled",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "scope": ["detail"]
    }
  ]
}
```

This automatically provides:
- Create button in list view
- Edit and Delete buttons in detail view
- Custom toggle action in detail view
- All with proper modals and confirmations

#### Included Components

**Views:**
- `PageList.vue` - List view with data table, filtering, and pagination
- `PageRow.vue` - Detail view with edit functionality

**Unified Modal:**
- `UnifiedModal.vue` - Schema-driven unified modal for all CRUD operations (create, edit, delete, custom actions)
  - Replaces separate CreateModal, EditModal, DeleteModal components
  - Supports multiple modal types: form, confirm, input, delete
  - Configurable button combinations: yes_no, save_cancel, ok_cancel, confirm_cancel
  - Full translation support with model and record context

**Form Components:**
- `Form.vue` - Dynamic form generation based on schema
- `Info.vue` - Display record information with edit/delete actions
- `Details.vue` - Display related records in detail sections

**Master-Detail Components:**
- `MasterDetailForm.vue` - Complete master-detail form for creating/editing records with their details
- `DetailGrid.vue` - Inline editable grid for managing detail records with add/edit/delete capabilities

**Lookup Components:**
- `AutoLookup.vue` - Generic searchable auto-complete component for selecting records from any model

#### Component Usage

Components are available for import and use in your Vue applications. Import them directly from the package:

```vue
<script setup>
import { 
  CRUD6UnifiedModal, 
  CRUD6Form, 
  CRUD6Info,
  CRUD6MasterDetailForm,
  CRUD6DetailGrid,
  CRUD6AutoLookup
} from '@ssnukala/sprinkle-crud6/components'
</script>

<template>
  <!-- Use the unified modal for all CRUD operations -->
  <CRUD6UnifiedModal
    :action="createAction"
    :model="'users'"
    :schema="schema"
    @saved="refresh"
  />
  
  <CRUD6UnifiedModal
    :action="editAction"
    :record="user"
    :model="'users'"
    :schema="schema"
    @saved="refresh"
  />
  
  <CRUD6UnifiedModal
    :action="deleteAction"
    :record="user"
    :model="'users'"
    @confirmed="handleDelete"
  />
  
  <!-- Use master-detail components -->
  <CRUD6MasterDetailForm 
    model="orders" 
    :record-id="orderId"
    :detail-config="detailConfig" 
    @saved="handleSaved"
  />
  
  <CRUD6DetailGrid
    v-model="detailRecords"
    :detail-schema="detailSchema"
    :fields="['line_number', 'sku', 'quantity', 'price']"
    :allow-add="true"
    :allow-edit="true"
    :allow-delete="true"
  />
  
  <!-- Use lookup components for searchable selection -->
  <CRUD6AutoLookup
    model="products"
    id-field="id"
    :display-fields="['sku', 'name']"
    placeholder="Search products..."
    v-model="selectedProductId"
    @select="handleProductSelect"
  />
</template>
```

> **Note**: The unified modal automatically handles create, edit, delete, and custom action operations based on the action configuration. Default actions are automatically generated from schema permissions.

##### AutoLookup Component

The AutoLookup component provides a searchable auto-complete interface for selecting records from any CRUD6 model. Perfect for product lookups, category selection, or any scenario where you need to search and select from a large dataset.

**Basic Usage:**
```vue
<CRUD6AutoLookup
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
<CRUD6AutoLookup
  model="products"
  id-field="id"
  :display-fields="['sku', 'name']"
  placeholder="Search by SKU or name..."
  v-model="selectedProductId"
/>
```

**With Custom Display Format:**
```vue
<CRUD6AutoLookup
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

// Execute a custom action (the unified modal uses this internally)
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

> **Note**: The `useCRUD6Actions` composable is used internally by UnifiedModal to execute custom actions. You can use it directly for programmatic action execution.

#### Routes

Frontend routes are automatically registered:
- `/crud6/{model}` - List view for any model
- `/crud6/{model}/{id}` - Detail view for a specific record

These routes follow UserFrosting 6 patterns and integrate with the authentication and authorization system. The unified modal system handles all CRUD operations (create, edit, delete) without requiring separate route configurations.

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

## Translation Support

CRUD6 follows **UserFrosting 6 standards** for translations, ensuring consistency with sprinkle-admin and theme-pink-cupcake.

### UserFrosting 6 Pattern

**Locale file** (use specific field placeholders, no embedded warnings):
```php
// app/locale/en_US/messages.php
'USER' => [
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
    'TOGGLE_ENABLED_CONFIRM' => 'Toggle enabled status for {{user_name}}?',
],
// WARNING_CANNOT_UNDONE is from UF6 core - no need to define it
```

**Schema** (warnings handled separately):
```json
{
    "key": "disable_user",
    "confirm": "USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm"
        // Defaults to "WARNING_CANNOT_UNDONE" from UF6 core
    }
}
```

### UnifiedModal Translation Context

The unified modal system provides rich translation context including both model and record data:

```javascript
{
  model: 'User',           // Model label (singular_title or capitalized model name)
  user_name: 'john_doe',   // All record fields available
  first_name: 'John',
  last_name: 'Doe',
  id: 8,
  // ... all other record fields
}
```

This allows translations to use specific field placeholders:
```
"Are you sure you want to delete {{model}} <strong>{{user_name}}</strong>?"
"Toggle {{field}} for {{first_name}} {{last_name}}?"
```

### Key Principles

1. **Use specific field placeholders**: `{{first_name}}`, `{{last_name}}`, `{{user_name}}` (not generic `{{name}}`)
2. **Use `WARNING_CANNOT_UNDONE`**: Standard warning key from UserFrosting 6 core
3. **Separate warnings from messages**: Warnings handled by modal component, not locale strings
4. **Follow UF6 patterns**: Matches sprinkle-admin and theme-pink-cupcake conventions
5. **Model context**: `{{model}}` placeholder available in all translations

### Warning Configuration

Control warning display via `modal_config.warning`:

```json
// Default warning (from UF6 core)
{"modal_config": {"type": "confirm"}}  // Uses WARNING_CANNOT_UNDONE

// Custom warning
{"modal_config": {"type": "confirm", "warning": "MY_CUSTOM_WARNING"}}

// No warning
{"modal_config": {"type": "confirm", "warning": ""}}
```

### Documentation

- **Complete Guide**: [docs/NESTED_TRANSLATION_USAGE_GUIDE.md](docs/NESTED_TRANSLATION_USAGE_GUIDE.md)
- **Example Schema**: [examples/schema/products-unified-modal.json](examples/schema/products-unified-modal.json)
- **Example Locale**: [examples/locale/translation-example-messages.php](examples/locale/translation-example-messages.php)

## Migration Guide

### Upgrading to UnifiedModal

If you're upgrading from an earlier version of CRUD6 that used separate modal components (`CreateModal`, `EditModal`, `DeleteModal`), the unified modal system provides:

**Benefits:**
- Single component for all CRUD operations
- Automatic default actions from schema permissions
- Scope-based action filtering
- Consistent translations and UX
- Reduced code duplication

**Migration Steps:**
1. **Schemas**: No changes required - existing schemas work automatically
2. **Components**: Import `CRUD6UnifiedModal` instead of separate modal components
3. **Actions**: Optionally add `scope` property to control where actions appear
4. **Default Actions**: Automatically generated from permissions (can disable with `default_actions: false`)

**Old Approach:**
```vue
<UFCRUD6CreateModal :model="'users'" :schema="schema" @saved="refresh" />
<UFCRUD6EditModal :crud6="record" :model="'users'" :schema="schema" @saved="refresh" />
<UFCRUD6DeleteModal :crud6="record" :model="'users'" @confirmed="handleDelete" />
```

**New Approach:**
```vue
<CRUD6UnifiedModal
  :action="createAction"
  :model="'users'"
  :schema="schema"
  @saved="refresh"
/>
<CRUD6UnifiedModal
  :action="editAction"
  :record="record"
  :model="'users'"
  :schema="schema"
  @saved="refresh"
/>
<CRUD6UnifiedModal
  :action="deleteAction"
  :record="record"
  :model="'users'"
  @confirmed="handleDelete"
/>
```

> **Note**: Actions are automatically filtered by scope and provided in the schema contexts. See [.archive/UNIFIED_MODAL_MIGRATION_GUIDE.md](.archive/UNIFIED_MODAL_MIGRATION_GUIDE.md) for detailed migration instructions.

## Integration Testing Framework

CRUD6 includes a **reusable, JSON-driven integration testing framework** that makes testing **as simple as CRUD6 itself**. Just like CRUD6 lets you define models with JSON schemas, this framework lets you define complete integration tests with a single JSON configuration file.

### ✨ Philosophy: Testing as Simple as CRUD6

```json
// CRUD6: Define models with JSON
{ "model": "Product", "fields": { "name": "string" } }

// Testing: Define tests with JSON (NEW!)
{ "sprinkle": { "name": "my-sprinkle" }, "routes": { "pattern": "simple" } }
```

**Same simplicity. Same power. Zero custom code required.**

### 🎯 Quick Start (3 Steps!)

#### Option 1: JSON-Driven Approach (Recommended - 5 minutes)

```bash
# Step 1: Copy configuration template
cp .github/testing-framework/config/integration-test-config.json .

# Step 2: Edit JSON (~50 lines) - customize for your sprinkle

# Step 3: Generate workflow
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

**Done!** Complete integration testing configured with zero custom code.

#### Option 2: Traditional Installer (10 minutes)

```bash
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

This will:
- ✅ Create `.github/config/` and `.github/scripts/` directories
- ✅ Copy template configuration files with your sprinkle name
- ✅ Install all reusable testing scripts
- ✅ Create documentation

### 📦 What's Automated (100%)

Everything is automated via JSON configuration - no custom code needed:

- ✅ **Infrastructure** - PHP, MySQL, Node.js, UserFrosting installation
- ✅ **Dependencies** - Composer, NPM package management (including local paths)
- ✅ **Application Config** - MyApp.php, main.ts, **router/index.ts**, **vite.config.ts**
- ✅ **Database** - Migrations, schema-driven SQL generation, seed execution
- ✅ **Testing** - API endpoints, frontend routes, Playwright screenshots
- ✅ **Route Patterns** - Supports simple array, factory function, and custom patterns

### 📚 Documentation

Complete documentation for the testing framework:

- **[Framework README](.github/testing-framework/README.md)** - Overview and quick start
- **[JSON-Driven Testing Guide](.github/testing-framework/docs/JSON_DRIVEN_TESTING.md)** - **NEW!** Complete JSON approach
- **[Installation Guide](.github/testing-framework/docs/INSTALLATION.md)** - Detailed installation instructions
- **[Configuration Guide](.github/testing-framework/docs/CONFIGURATION.md)** - How to customize for your sprinkle
- **[Workflow Templates](.github/testing-framework/docs/WORKFLOW_TEMPLATES.md)** - GitHub Actions integration
- **[Frontend Patterns](.github/testing-framework/docs/FRONTEND_INTEGRATION_PATTERNS.md)** - Route configuration patterns

### 🎓 Example: JSON Configuration

Create a single `integration-test-config.json` file (~50 lines):

```json
{
  "sprinkle": {
    "name": "my-sprinkle",
    "composer_package": "vendor/my-sprinkle",
    "npm_package": "@vendor/my-sprinkle"
  },
  "schemas": {
    "path": ""  // Default: app/schema/crud6/
  },
  "routes": {
    "pattern": "simple",  // or "factory" for C6Admin-style
    "import": {
      "module": "@vendor/my-sprinkle/routes",
      "name": "MyRoutes"
    }
  },
  "testing": {
    "php_version": "8.4",
    "node_version": "20"
  }
}
```

**Generate workflow:**
```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

**Everything automated:**
- PHP, MySQL, Node.js setup
- UserFrosting installation
- Composer & NPM dependencies
- Route configuration (simple/factory/custom patterns)
- Vite configuration
- Database migrations & seeds
- API & frontend testing
- Screenshot capture

**CRUD6 itself uses this approach!** See [integration-test-config.json](integration-test-config.json) for a real-world example.

### 🆚 Benefits Over Manual Testing

| Aspect | Manual Approach | JSON-Driven Framework |
|--------|----------------|----------------------|
| Setup Time | Hours to days | **5 minutes** |
| Configuration | Scattered YAML (500+ lines) | **One JSON file (~50 lines)** |
| Custom Code | High - write tests manually | **Zero - pure configuration** |
| Route Setup | Manual code editing | **Auto-generated from JSON** |
| Vite Config | Manual setup | **Auto-configured** |
| Maintenance | Update each sprinkle separately | **Update JSON config only** |
| Consistency | Varies by developer | **Standardized** |
| CI/CD Integration | Custom workflow per sprinkle | **Auto-generated** |

### 🔬 Real-World Usage

**CRUD6 Migration Results:**
- **Before:** 460 lines of custom workflow YAML
- **After:** 50 lines of JSON + auto-generated workflow (265 lines)
- **Reduction:** 42% less code to maintain
- **Result:** Same functionality, zero custom code

This framework is production-tested and used by:
- ✅ **[sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6)** - Using JSON-driven approach (see [integration-test-config.json](integration-test-config.json))
- 🔜 **[sprinkle-c6admin](https://github.com/ssnukala/sprinkle-c6admin)** - Migration in progress

**Want to use it?** See the [JSON-Driven Testing Guide](.github/testing-framework/docs/JSON_DRIVEN_TESTING.md) for complete documentation.

### 🔧 Testing Multiple Sprinkles

When you have multiple sprinkles using CRUD6, each with their own schemas, you can configure the testing framework to test schemas from all sprinkles simultaneously:

**Configure in phpunit.xml:**
```xml
<env name="TEST_SCHEMA_DIRS" value="examples/schema,vendor/mysprinkle/schema,app/schema/crud6"/>
```

**Or override in your test class:**
```php
class MyAppTestCase extends CRUD6TestCase
{
    protected function getTestSchemaDirs(): array
    {
        return [
            __DIR__ . '/../../vendor/sprinkle-inventory/schema',
            __DIR__ . '/../../vendor/sprinkle-crm/schema',
            __DIR__ . '/../../../examples/schema',
        ];
    }
}
```

The testing framework will automatically discover and test schemas from all configured directories. See [Multi-Sprinkle Testing Documentation](docs/TESTING_MULTI_SPRINKLE.md) for detailed examples and configuration options.

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
