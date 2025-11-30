# CRUD6 API Reference

This document provides a comprehensive reference for all CRUD6 REST API endpoints.

## Base URL

All endpoints are relative to `/api/crud6`.

## Authentication

All endpoints require authentication via UserFrosting's AuthGuard middleware. Ensure you have a valid session before making API calls.

---

## Schema Endpoints

### Get Schema
```
GET /api/crud6/{model}/schema
```

Retrieve the schema definition for a model.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name (e.g., `users`, `products`) |
| `context` | string | query | Optional. Filter schema for context: `list`, `form`, `detail`, `meta`, or comma-separated |
| `include_related` | boolean | query | Optional. Include related model schemas |

**Response:**
```json
{
  "model": "users",
  "title": "Users",
  "singular_title": "User",
  "primary_key": "id",
  "fields": {
    "name": {
      "type": "string",
      "label": "Name",
      "required": true,
      "sortable": true,
      "filterable": true
    },
    "email": {
      "type": "email",
      "label": "Email",
      "required": true,
      "validation": {
        "email": {}
      }
    }
  },
  "permissions": {
    "read": "uri_users",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  }
}
```

**Example Requests:**
```bash
# Get full schema
curl /api/crud6/users/schema

# Get list-only fields
curl "/api/crud6/users/schema?context=list"

# Get multiple contexts
curl "/api/crud6/users/schema?context=list,form"

# Include related schemas
curl "/api/crud6/users/schema?context=detail&include_related=true"
```

---

## CRUD Endpoints

### List Records (Sprunje)
```
GET /api/crud6/{model}
```

Retrieve a paginated list of records with sorting and filtering.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `page` | integer | query | Page number (default: 1) |
| `size` | integer | query | Records per page (default: 10, max: 100) |
| `sorts` | object | query | Sort configuration (e.g., `{"name": "asc"}`) |
| `filters` | object | query | Filter configuration (e.g., `{"status": "active"}`) |

**Response:**
```json
{
  "count": 100,
  "count_filtered": 25,
  "rows": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "status": "active"
    }
  ],
  "listable": ["id", "name", "email", "status"]
}
```

**Example Request:**
```bash
curl "/api/crud6/users?page=1&size=25&sorts[name]=asc&filters[status]=active"
```

---

### Get Single Record
```
GET /api/crud6/{model}/{id}
```

Retrieve a single record by ID with optional relationship data.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `id` | string/integer | path | Record ID |

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2024-01-15T10:30:00Z"
  },
  "details": {
    "roles": [
      {"id": 1, "name": "Admin"},
      {"id": 2, "name": "Editor"}
    ]
  }
}
```

**Example Request:**
```bash
curl /api/crud6/users/123
```

---

### Create Record
```
POST /api/crud6/{model}
```

Create a new record.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| (fields) | various | body | Record data matching schema fields |

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "status": "active"
}
```

**Response:**
```json
{
  "title": "User Created",
  "description": "User 'Jane Smith' was created successfully.",
  "data": {
    "id": 124,
    "name": "Jane Smith",
    "email": "jane@example.com"
  }
}
```

**Example Request:**
```bash
curl -X POST /api/crud6/users \
  -H "Content-Type: application/json" \
  -d '{"name": "Jane Smith", "email": "jane@example.com"}'
```

---

### Update Record (Full)
```
PUT /api/crud6/{model}/{id}
```

Update all fields of a record.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `id` | string/integer | path | Record ID |
| (fields) | various | body | Updated record data |

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "status": "inactive"
}
```

**Response:**
```json
{
  "title": "User Updated",
  "description": "User 'Jane Doe' was updated successfully.",
  "data": {
    "id": 124,
    "name": "Jane Doe",
    "email": "jane.doe@example.com"
  }
}
```

---

### Update Single Field
```
PUT /api/crud6/{model}/{id}/{field}
```

Update a single field of a record.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `id` | string/integer | path | Record ID |
| `field` | string | path | Field name to update |
| (field) | various | body | New field value |

**Request Body:**
```json
{
  "status": "active"
}
```

**Response:**
```json
{
  "title": "Field Updated",
  "description": "Field 'status' was updated successfully.",
  "data": {
    "id": 124,
    "status": "active"
  }
}
```

**Example Request:**
```bash
curl -X PUT /api/crud6/users/124/status \
  -H "Content-Type: application/json" \
  -d '{"status": "active"}'
```

---

### Delete Record
```
DELETE /api/crud6/{model}/{id}
```

Delete a record (soft delete if enabled in schema).

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `id` | string/integer | path | Record ID |

**Response:**
```json
{
  "title": "User Deleted",
  "description": "User 'Jane Doe' was deleted successfully."
}
```

**Example Request:**
```bash
curl -X DELETE /api/crud6/users/124
```

---

## Relationship Endpoints

### Attach Relationships
```
POST /api/crud6/{model}/{id}/{relation}
```

Attach related records in a many-to-many relationship.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Parent model name |
| `id` | string/integer | path | Parent record ID |
| `relation` | string | path | Relationship name |
| `ids` | array | body | IDs of records to attach |

**Request Body:**
```json
{
  "ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "title": "Relationships Updated",
  "description": "3 roles were attached to user."
}
```

**Example Request:**
```bash
curl -X POST /api/crud6/users/123/roles \
  -H "Content-Type: application/json" \
  -d '{"ids": [1, 2, 3]}'
```

---

### Detach Relationships
```
DELETE /api/crud6/{model}/{id}/{relation}
```

Detach related records from a many-to-many relationship.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Parent model name |
| `id` | string/integer | path | Parent record ID |
| `relation` | string | path | Relationship name |
| `ids` | array | body | IDs of records to detach |

**Request Body:**
```json
{
  "ids": [2]
}
```

**Response:**
```json
{
  "title": "Relationships Updated",
  "description": "1 role was detached from user."
}
```

---

## Custom Action Endpoints

### Execute Custom Action
```
POST /api/crud6/{model}/{id}/a/{action}
```

Execute a custom action defined in the schema.

**Parameters:**
| Name | Type | Location | Description |
|------|------|----------|-------------|
| `model` | string | path | Model name |
| `id` | string/integer | path | Record ID |
| `action` | string | path | Action key from schema |
| (payload) | various | body | Action-specific data |

**Response:**
```json
{
  "title": "Action Completed",
  "description": "Email was sent successfully.",
  "data": {
    "sent_at": "2024-01-15T10:30:00Z"
  }
}
```

---

## Error Responses

All endpoints return consistent error responses:

### 400 Bad Request
```json
{
  "title": "Validation Error",
  "description": "The submitted data is invalid.",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name must be at least 3 characters."]
  }
}
```

### 403 Forbidden
```json
{
  "title": "Access Denied",
  "description": "You do not have permission to perform this action."
}
```

### 404 Not Found
```json
{
  "title": "Not Found",
  "description": "The requested user was not found."
}
```

### 500 Internal Server Error
```json
{
  "title": "Server Error",
  "description": "An unexpected error occurred. Please try again later."
}
```

---

## Rate Limiting

API endpoints are subject to UserFrosting's rate limiting configuration. Default limits:

- **List endpoints**: 60 requests/minute
- **Create/Update/Delete**: 30 requests/minute

---

## Batch Operations (Bulk)

### Bulk Create
```
POST /api/crud6/{model}/bulk
```

Create multiple records in a single request.

**Request Body:**
```json
{
  "records": [
    {"name": "User 1", "email": "user1@example.com"},
    {"name": "User 2", "email": "user2@example.com"}
  ]
}
```

**Response:**
```json
{
  "title": "Bulk Create Complete",
  "description": "2 records were created successfully.",
  "data": {
    "created": 2,
    "failed": 0,
    "ids": [125, 126]
  }
}
```

---

### Bulk Update
```
PUT /api/crud6/{model}/bulk
```

Update multiple records in a single request.

**Request Body:**
```json
{
  "records": [
    {"id": 1, "status": "active"},
    {"id": 2, "status": "active"}
  ]
}
```

---

### Bulk Delete
```
DELETE /api/crud6/{model}/bulk
```

Delete multiple records in a single request.

**Request Body:**
```json
{
  "ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "title": "Bulk Delete Complete",
  "description": "3 records were deleted successfully.",
  "data": {
    "deleted": 3,
    "failed": 0
  }
}
```

---

*Document generated: November 2025*
*API Version: CRUD6 0.6.x*
