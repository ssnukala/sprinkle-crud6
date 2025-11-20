# Integration Test Paths - Quick Reference Guide

## Overview

The integration test path configuration provides a comprehensive mapping of all CRUD6 API endpoints for testing and documentation purposes.

## Files

- **`integration-test-paths.json`** - Complete endpoint definitions with payloads and validation
- **`integration-test-models.json`** - Model metadata and path templates for code generation

## Quick Examples

### 1. Testing Authentication

**Unauthenticated requests should return 401:**

```bash
# Test without auth token
curl -X GET http://localhost:8080/api/crud6/users/schema
# Expected: 401 Unauthorized

curl -X GET http://localhost:8080/api/crud6/users
# Expected: 401 Unauthorized
```

### 2. Schema Endpoint

**Get model schema definition:**

```bash
# With authentication
curl -X GET http://localhost:8080/api/crud6/users/schema \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK
# Response: { "model": "users", "fields": [...], "actions": [...] }
```

### 3. CRUD Operations

**Create:**

```bash
curl -X POST http://localhost:8080/api/crud6/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "user_name": "apitest",
    "first_name": "API",
    "last_name": "Test",
    "email": "apitest@example.com",
    "password": "TestPassword123"
  }'
# Expected: 200 OK
# Response: { "data": { "id": 123, ... } }
```

**Read:**

```bash
curl -X GET http://localhost:8080/api/crud6/users/1 \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK
# Response: { "id": 1, "user_name": "admin", "email": "admin@example.com", ... }
```

**Update:**

```bash
curl -X PUT http://localhost:8080/api/crud6/users/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Updated",
    "last_name": "Name"
  }'
# Expected: 200 OK
```

**Delete:**

```bash
curl -X DELETE http://localhost:8080/api/crud6/users/1 \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK
```

### 4. Field Updates (Boolean Toggles)

**Update single field:**

```bash
curl -X PUT http://localhost:8080/api/crud6/users/1/flag_enabled \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "flag_enabled": false }'
# Expected: 200 OK
```

### 5. Custom Actions

**Execute custom action:**

```bash
curl -X POST http://localhost:8080/api/crud6/users/1/a/reset_password \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
# Expected: 200, 404, or 500 (depending on implementation)
```

### 6. Relationship Operations

**Attach related records (many-to-many):**

```bash
curl -X POST http://localhost:8080/api/crud6/users/1/roles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "related_ids": [1, 2, 3] }'
# Expected: 200 OK or 403 Forbidden
```

**Detach related records:**

```bash
curl -X DELETE http://localhost:8080/api/crud6/users/1/roles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{ "related_ids": [2] }'
# Expected: 200 OK or 403 Forbidden
```

### 7. Nested Relationship Endpoints

**Get related records:**

```bash
# Get users in a role
curl -X GET http://localhost:8080/api/crud6/roles/1/users \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK
# Response: Array of users

# Get permissions for a role
curl -X GET http://localhost:8080/api/crud6/roles/1/permissions \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK

# Get users with a permission (through roles)
curl -X GET http://localhost:8080/api/crud6/permissions/1/users \
  -H "Authorization: Bearer $TOKEN"
# Expected: 200 OK
```

## Using the Configuration Files

### For Manual Testing

Extract endpoint paths from configuration:

```bash
# List all authenticated API endpoints
jq '.paths.authenticated.api | to_entries[] | .value.path' \
  .github/config/integration-test-paths.json

# List endpoints for a specific model
jq '.paths.authenticated.api | to_entries[] | select(.key | startswith("users_")) | .value.path' \
  .github/config/integration-test-paths.json
```

### For Automated Testing

Generate test cases from model templates:

```javascript
// Read model configuration
const models = require('.github/config/integration-test-models.json');

// Generate test for each model
Object.entries(models.models).forEach(([modelName, modelConfig]) => {
  if (!modelConfig.enabled) return;
  
  // Generate schema test
  const schemaPath = `${modelConfig.api_prefix}/${modelName}/schema`;
  console.log(`Testing ${schemaPath}`);
  
  // Generate CRUD tests
  const listPath = `${modelConfig.api_prefix}/${modelName}`;
  const readPath = `${modelConfig.api_prefix}/${modelName}/${modelConfig.test_id}`;
  // ... etc
});
```

### For Documentation Generation

Create API documentation from configuration:

```python
import json

# Load configuration
with open('.github/config/integration-test-paths.json') as f:
    config = json.load(f)

# Generate markdown documentation
for endpoint_name, endpoint_config in config['paths']['authenticated']['api'].items():
    method = endpoint_config['method']
    path = endpoint_config['path']
    description = endpoint_config['description']
    
    print(f"### {endpoint_name}")
    print(f"**{method}** `{path}`")
    print(f"\n{description}\n")
    
    if 'payload' in endpoint_config:
        print("**Payload:**")
        print(f"```json\n{json.dumps(endpoint_config['payload'], indent=2)}\n```\n")
```

## Endpoint Categories

### By Model

```bash
# Users (10 endpoints)
users_schema, users_list, users_create, users_single, users_update,
users_update_field, users_custom_action, users_relationship_attach,
users_relationship_detach, users_delete

# Groups (7 endpoints)
groups_schema, groups_list, groups_create, groups_single,
groups_update, groups_nested_users, groups_delete

# Roles (8 endpoints)
roles_schema, roles_list, roles_create, roles_single, roles_update,
roles_nested_users, roles_nested_permissions, roles_delete

# Permissions (7 endpoints)
permissions_schema, permissions_list, permissions_create,
permissions_single, permissions_nested_roles, permissions_nested_users,
permissions_delete

# Activities (3 endpoints)
activities_schema, activities_list, activities_single
```

### By Operation Type

```bash
# Schema endpoints (5 models)
{model}_schema

# CRUD endpoints (varies by model)
{model}_list       # All models
{model}_create     # users, groups, roles, permissions
{model}_single     # All models
{model}_update     # users, groups, roles
{model}_delete     # users, groups, roles, permissions

# Advanced endpoints (users only)
users_update_field           # Field updates
users_custom_action          # Custom actions
users_relationship_attach    # Attach relationships
users_relationship_detach    # Detach relationships

# Nested endpoints (groups, roles, permissions)
{model}_nested_{relation}    # Get related records
```

## Permission Reference

### Required Permissions by Endpoint

| Endpoint | Permission | Notes |
|----------|-----------|-------|
| `users_create` | `create_user` | |
| `users_update` | `update_user_field` | |
| `users_update_field` | `update_user_field` | |
| `users_custom_action` | `update_user_field` | |
| `users_delete` | `delete_user` | |
| `groups_create` | `create_group` | |
| `groups_update` | `update_group_field` | |
| `groups_delete` | `delete_group` | |
| `roles_create` | `create_role` | |
| `roles_update` | `update_role_field` | |
| `roles_delete` | `delete_role` | |
| `permissions_create` | `create_permission` | |
| `permissions_delete` | `delete_permission` | |

### Read Operations

All list and single record endpoints require the model-specific URI permission:
- `uri_users` for users endpoints
- `uri_groups` for groups endpoints
- `uri_roles` for roles endpoints
- `uri_permissions` for permissions endpoints

## Testing Checklist

### Basic CRUD (All Models)

- [ ] GET /api/crud6/{model}/schema (authenticated)
- [ ] GET /api/crud6/{model}/schema (unauthenticated - expect 401)
- [ ] GET /api/crud6/{model} (authenticated)
- [ ] GET /api/crud6/{model} (unauthenticated - expect 401)
- [ ] GET /api/crud6/{model}/{id} (authenticated)
- [ ] GET /api/crud6/{model}/{id} (unauthenticated - expect 401)

### Create/Update/Delete (Most Models)

- [ ] POST /api/crud6/{model} (authenticated with permission)
- [ ] POST /api/crud6/{model} (authenticated without permission - expect 403)
- [ ] POST /api/crud6/{model} (unauthenticated - expect 401)
- [ ] PUT /api/crud6/{model}/{id} (authenticated with permission)
- [ ] PUT /api/crud6/{model}/{id} (unauthenticated - expect 401)
- [ ] DELETE /api/crud6/{model}/{id} (authenticated with permission)
- [ ] DELETE /api/crud6/{model}/{id} (authenticated without permission - expect 403)
- [ ] DELETE /api/crud6/{model}/{id} (unauthenticated - expect 401)

### Advanced Features (Users Model)

- [ ] PUT /api/crud6/users/{id}/flag_enabled (toggle boolean)
- [ ] POST /api/crud6/users/{id}/a/reset_password (custom action)
- [ ] POST /api/crud6/users/{id}/roles (attach relationship)
- [ ] DELETE /api/crud6/users/{id}/roles (detach relationship)

### Nested Relationships

- [ ] GET /api/crud6/groups/{id}/users
- [ ] GET /api/crud6/roles/{id}/users
- [ ] GET /api/crud6/roles/{id}/permissions
- [ ] GET /api/crud6/permissions/{id}/roles
- [ ] GET /api/crud6/permissions/{id}/users (through roles)

## Common Testing Scenarios

### Scenario 1: New User Registration Flow

```bash
# 1. Create user
USER_ID=$(curl -X POST http://localhost:8080/api/crud6/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_name":"newuser","email":"new@example.com",...}' \
  | jq -r '.data.id')

# 2. Assign role
curl -X POST http://localhost:8080/api/crud6/users/$USER_ID/roles \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"related_ids":[2]}'

# 3. Enable user
curl -X PUT http://localhost:8080/api/crud6/users/$USER_ID/flag_enabled \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"flag_enabled":true}'
```

### Scenario 2: Role Management

```bash
# 1. Create role
ROLE_ID=$(curl -X POST http://localhost:8080/api/crud6/roles \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"slug":"editor","name":"Editor"}' \
  | jq -r '.data.id')

# 2. Get users with this role
curl -X GET http://localhost:8080/api/crud6/roles/$ROLE_ID/users \
  -H "Authorization: Bearer $TOKEN"

# 3. Get permissions for this role
curl -X GET http://localhost:8080/api/crud6/roles/$ROLE_ID/permissions \
  -H "Authorization: Bearer $TOKEN"
```

### Scenario 3: Permission Audit

```bash
# 1. Get all permissions
curl -X GET http://localhost:8080/api/crud6/permissions \
  -H "Authorization: Bearer $TOKEN"

# 2. For each permission, check which users have it
for PERM_ID in 1 2 3; do
  echo "Permission $PERM_ID users:"
  curl -X GET http://localhost:8080/api/crud6/permissions/$PERM_ID/users \
    -H "Authorization: Bearer $TOKEN"
done
```

## Tips

1. **Always authenticate** - All endpoints require authentication
2. **Check permissions** - Many operations require specific permissions
3. **Use schema endpoints** - Get field definitions and available actions
4. **Test error cases** - Try without auth, without permissions, with invalid data
5. **Verify relationships** - Test both attach/detach and nested listing
6. **Check nested endpoints** - Some relationships are read-only (nested GET only)

## Additional Resources

- Schema files: `app/schema/crud6/*.json`
- Example schemas: `examples/schema/c6admin-*.json`
- Test implementation: `app/tests/Integration/SchemaBasedApiTest.php`
- API routes: `app/src/Routes/CRUD6Routes.php`
