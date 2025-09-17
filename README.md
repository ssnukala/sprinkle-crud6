# sprinkle-crud6
CRUD Sprinkle for UserFrosting 6 - Generic API CRUD Layer

A powerful and flexible CRUD (Create, Read, Update, Delete) system for UserFrosting 6 that allows you to dynamically perform CRUD operations on any database table using JSON schema definitions.

## Features

- **JSON Schema-Driven**: Define your models using simple JSON configuration files
- **Dynamic Routing**: Automatic routes for any model (`/crud6/{model}` and `/api/crud6/{model}`)
- **RESTful API**: Full REST API support for all CRUD operations
- **Flexible Permissions**: Schema-based permission system
- **Data Validation**: Built-in validation based on field definitions
- **Sorting & Filtering**: Automatic sortable and filterable columns
- **Soft Delete Support**: Optional soft delete functionality
- **Type System**: Support for various field types (string, integer, boolean, date, json, etc.)
- **Template System**: Customizable templates for different models

## Installation

1. Add to your UserFrosting 6 project via composer:
```bash
composer require ssnukala/sprinkle-crud6
```

2. Add the sprinkle to your sprinkles configuration in your app's main sprinkle class:
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
  "primary_key": "id",
  "timestamps": true,
  "soft_delete": false,
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

## Usage

### Routes

Once you have a schema file, the following routes are automatically available:

#### Web Interface
- `GET /crud6/{model}` - List view page for the model

#### API Endpoints
- `GET /api/crud6/{model}` - List records with pagination, sorting, and filtering
- `POST /api/crud6/{model}` - Create new record
- `GET /api/crud6/{model}/{id}` - Read single record
- `PUT /api/crud6/{model}/{id}` - Update record
- `DELETE /api/crud6/{model}/{id}` - Delete record

### Examples

With a `users.json` schema file, you can access:
- Web interface: `http://yoursite.com/crud6/users`
- API list: `http://yoursite.com/api/crud6/users`
- API create: `POST http://yoursite.com/api/crud6/users`
- API read: `GET http://yoursite.com/api/crud6/users/123`
- API update: `PUT http://yoursite.com/api/crud6/users/123`
- API delete: `DELETE http://yoursite.com/api/crud6/users/123`

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

### Custom Templates

You can override the default template by specifying a custom template in your schema:

```json
{
  "model": "users",
  "template": "pages/custom-user-list.html.twig",
  // ... rest of schema
}
```

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

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License.
