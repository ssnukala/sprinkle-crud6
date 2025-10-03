# Database Connection Configuration Feature

## Overview

This document describes the implementation of configurable database connections for the CRUD6 sprinkle. The feature allows models to specify database connections both in JSON schema files and dynamically through URL parameters.

## Changes Made

### 1. CRUD6ModelInterface (`app/src/Database/Models/Interfaces/CRUD6ModelInterface.php`)

**Added:**
- `setConnection(?string $connection): static` method signature

This method allows dynamic database connection configuration on model instances.

### 2. CRUD6Model (`app/src/Database/Models/CRUD6Model.php`)

**Modified:**
- `configureFromSchema()` method now always sets the connection (uses schema value or null for default)
- Added explicit `setConnection(?string $connection): static` method override for interface compatibility
- Updated class documentation to mention dynamic database connection selection

The model overrides the parent Eloquent Model's `setConnection()` method to ensure compatibility with the CRUD6ModelInterface signature requirements (explicit `static` return type). The implementation calls the parent method and returns `$this`.

**Example:**
```php
$model = new CRUD6Model();
$model->configureFromSchema($schema); // Sets connection from schema or null for default
$model->setConnection('mysql_secondary'); // Override connection
```

### 3. CRUD6Injector Middleware (`app/src/Middlewares/CRUD6Injector.php`)

**Added:**
- `currentConnectionName` property to store parsed connection name
- `parseModelAndConnection()` method to parse model and connection from URL parameter
- Support for `model@connection` URL syntax
- Path-based schema lookup with connection-specific folders

**Modified:**
- `process()` method now parses both model name and optional connection from URL
- `getInstance()` method applies connection if specified in URL (overrides schema connection)
- Updated middleware documentation to explain connection syntax and path-based lookup

**URL Syntax:**
- `/api/crud6/users` - Uses default or schema-configured connection
- `/api/crud6/users@db1` - Uses `db1` connection (overrides schema)

**Path-Based Lookup:**
When accessing `/api/crud6/users@db1`:
1. First looks for schema at `schema://crud6/db1/users.json`
2. If found, automatically applies `db1` connection
3. If not found, falls back to `schema://crud6/users.json` and applies connection from URL

### 4. SchemaService (`app/src/ServicesProvider/SchemaService.php`)

**Modified:**
- `getSchema()` method now accepts optional `$connection` parameter
- `getSchemaFilePath()` method now accepts optional `$connection` parameter
- Implements fallback logic: connection-based path → default path
- Automatically sets connection in schema when loaded from connection-specific folder

**Examples:**
```php
// Load from default path
$schema = $schemaService->getSchema('users');

// Try connection-based path first, fallback to default
$schema = $schemaService->getSchema('users', 'db1');
// Looks for: schema://crud6/db1/users.json
// Falls back to: schema://crud6/users.json
```

### 4. Route Documentation (`app/src/Routes/CRUD6Routes.php`)

**Updated:**
- Added documentation for database connection selection via URL syntax
- Examples of using `@connection` in route paths

### 5. Test Files

**Created:**
- `app/tests/Middlewares/CRUD6InjectorTest.php` - Tests for connection parsing logic
  - Tests parsing model name without connection
  - Tests parsing model name with connection
  - Tests parsing with multiple @ symbols
  - Tests model name validation

**Modified:**
- `app/tests/Database/Models/CRUD6ModelTest.php` - Added connection configuration tests
  - Tests connection configuration from schema
  - Tests manual connection configuration
  - Tests connection override
  - Tests null connection (use default)

### 6. Documentation

**Modified:**
- `README.md` - Added comprehensive documentation for:
  - Database connection selection via URL syntax
  - Schema-based connection configuration
  - Use cases (multi-tenancy, analytics, read replicas, etc.)

- `examples/README.md` - Added detailed section on:
  - Schema-based connection configuration
  - URL-based connection override
  - Real-world use cases

**Created:**
- `examples/analytics.json` - Example schema demonstrating connection configuration
- `app/schema/crud6/db1/users.json` - Example schema demonstrating folder-based connection

## Usage Examples

### 1. Schema-Based Configuration (Explicit Connection Field)

```json
{
  "model": "analytics",
  "table": "page_views",
  "connection": "mysql_analytics",
  "fields": {
    "id": { "type": "integer" },
    "page_url": { "type": "string" }
  }
}
```

### 2. Folder-Based Configuration (Implicit Connection)

Organize schemas by database connection:

```
app/schema/crud6/
├── users.json              # Default connection
├── products.json           # Default connection
├── db1/
│   └── users.json         # Automatically uses db1 connection
├── db2/
│   └── orders.json        # Automatically uses db2 connection
└── analytics/
    └── page_views.json    # Automatically uses analytics connection
```

With this structure:
- `/api/crud6/users` → uses `app/schema/crud6/users.json` (default connection)
- `/api/crud6/users@db1` → uses `app/schema/crud6/db1/users.json` (db1 connection)
- `/api/crud6/orders@db2` → uses `app/schema/crud6/db2/orders.json` (db2 connection)

### 3. URL-Based Override

```bash
# Uses default connection
GET /api/crud6/users

# Uses db1 connection (overrides schema)
GET /api/crud6/users@db1

# Create product on secondary database
POST /api/crud6/products@db_secondary
```

### Programmatic Usage

```php
use UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model;

$model = new CRUD6Model();

// Configure from schema (may include connection)
$model->configureFromSchema($schema);

// Override connection programmatically
$model->setConnection('mysql_replica');

// Use the model
$users = $model->where('is_active', true)->get();
```

## Technical Details

### Connection Resolution Flow

When processing `/api/crud6/users@db1`:

1. **Parse URL**: Extract model name (`users`) and connection (`db1`)
2. **Schema Lookup**:
   - Try: `schema://crud6/db1/users.json`
   - If not found, try: `schema://crud6/users.json`
3. **Connection Application**:
   - If schema found in connection folder: automatically set connection from folder name
   - If explicit `connection` field in schema: use that connection
   - URL connection always overrides schema connection
4. **Model Configuration**: Apply all settings to CRUD6Model instance

### Connection Priority

1. **URL-specified connection** (highest priority) - `@connection` in URL
2. **Folder-based connection** - Schema location in connection-specific folder
3. **Schema-specified connection** - `connection` field in JSON schema
4. **Default connection** - UserFrosting's default database connection

### Model Name Validation

Model names must match the pattern: `/^[a-zA-Z0-9_]+$/`

This means:
- ✅ Valid: `users`, `user_profiles`, `Users123`, `table_2024`
- ❌ Invalid: `users@db1`, `users-table`, `users.table`, `users table`

The `@` symbol is used only for connection specification and is parsed before validation.

### Connection Name Format

Connection names can include any characters after the `@` symbol. If multiple `@` symbols are present, only the first one is used as the delimiter.

Example: `users@db1@backup` → model: `users`, connection: `db1@backup`

## Use Cases

1. **Multi-tenancy**: Route different tenants to different database connections
2. **Analytics**: Keep analytics data in a separate database
3. **Read Replicas**: Query read replicas for heavy read operations
4. **Data Migration**: Access legacy databases alongside new ones
5. **Microservices**: Access different databases per service boundary

## Backward Compatibility

This feature is fully backward compatible:
- Existing schemas without `connection` field continue to use the default connection
- Existing URLs without `@connection` syntax work as before
- No breaking changes to existing code

## Testing

All changes include comprehensive unit tests:
- Connection parsing logic
- Model connection configuration
- Schema-based connection setup
- Connection override scenarios

Run tests with:
```bash
vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php
vendor/bin/phpunit app/tests/Middlewares/CRUD6InjectorTest.php
```

## Security Considerations

- Connection names must be pre-configured in UserFrosting's database configuration
- Invalid connection names will cause Eloquent to throw an exception
- Model name validation prevents injection attacks
- No user input is directly used in SQL queries
