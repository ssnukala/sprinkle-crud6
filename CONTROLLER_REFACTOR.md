# CRUD6 Controller Refactor

This document describes the refactored controller structure that follows UserFrosting sprinkle-admin patterns.

## New Controller Structure

The controllers have been moved from `app/src/Controller/Base/` to `app/src/Controller/` to follow UserFrosting conventions. The new structure includes:

### Main Controllers

- **Api** - Handles API listing endpoints with pagination, sorting, and filtering
- **Create** - Handles creating new records 
- **Delete** - Handles deleting existing records
- **Edit** - Handles updating existing records
- **SprunjeAction** - Handles reading single records
- **ModelBasedController** - Demonstrates using the generic CRUD6Model with Eloquent ORM

### Naming Convention

Following UserFrosting sprinkle-admin patterns:
- `Api.php` - For data API endpoints (like GroupApi.php in sprinkle-admin)
- `Create.php` - For create actions (like GroupCreateAction.php)
- `Delete.php` - For delete actions (like GroupDeleteAction.php)  
- `Edit.php` - For edit actions (like GroupEditAction.php)
- `SprunjeAction.php` - For sprunje/single record actions

## API Endpoints

The routes have been updated to use the new controllers:

### Traditional Query Builder API
- `GET /api/crud6/{model}` - List data (Api controller)
- `POST /api/crud6/{model}` - Create record (Create controller)
- `GET /api/crud6/{model}/{id}` - Read single record (SprunjeAction controller)
- `PUT /api/crud6/{model}/{id}` - Update record (Edit controller)
- `DELETE /api/crud6/{model}/{id}` - Delete record (Delete controller)

### Generic Model API (Eloquent ORM)
- `GET /api/crud6-model/{model}` - List using generic model
- `POST /api/crud6-model/{model}` - Create using generic model
- `GET /api/crud6-model/{model}/{id}` - Read using generic model

## Key Features

1. **Dependency Injection** - All controllers use constructor injection following UserFrosting patterns
2. **Permission Validation** - Each controller validates access based on schema permissions
3. **Error Handling** - Proper exception handling and logging
4. **Schema-Based** - All operations are driven by JSON schema configuration
5. **Type Safety** - Strong typing throughout with declared types

## Backward Compatibility

The old Base controllers are preserved in `app/src/Controller/Base/` for backward compatibility, but the new controllers in the root Controller namespace should be used going forward.

## Usage Example

```php
// Schema-driven CRUD operations
GET /api/crud6/products              // List products with pagination
POST /api/crud6/products             // Create new product
GET /api/crud6/products/123          // Get product with ID 123
PUT /api/crud6/products/123          // Update product with ID 123
DELETE /api/crud6/products/123       // Delete product with ID 123

// Model-based operations (using Eloquent ORM)
GET /api/crud6-model/products        // List products using generic model
POST /api/crud6-model/products       // Create product using generic model
GET /api/crud6-model/products/123    // Get product using generic model
```

All operations require proper authentication and authorization based on the schema permissions configuration.