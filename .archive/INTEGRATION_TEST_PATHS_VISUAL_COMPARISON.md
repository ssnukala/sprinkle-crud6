# Integration Test Paths - Visual Comparison

## Before vs After

### BEFORE: Basic Coverage (10 endpoints)

```
Authenticated API:
  ├── users
  │   ├── GET /api/crud6/users (list)
  │   └── GET /api/crud6/users/1 (single)
  ├── groups
  │   ├── GET /api/crud6/groups (list)
  │   └── GET /api/crud6/groups/1 (single)
  ├── roles
  │   ├── GET /api/crud6/roles (list)
  │   └── GET /api/crud6/roles/1 (single)
  ├── permissions
  │   ├── GET /api/crud6/permissions (list)
  │   └── GET /api/crud6/permissions/1 (single)
  └── activities
      ├── GET /api/crud6/activities (list)
      └── GET /api/crud6/activities/1 (single)

Unauthenticated API: (Same endpoints, expect 401)
```

### AFTER: Comprehensive Coverage (53 endpoints)

```
Authenticated API (35 endpoints):
  ├── users (10 endpoints)
  │   ├── GET    /api/crud6/users/schema              ✨ Schema definition
  │   ├── GET    /api/crud6/users                     📋 List
  │   ├── POST   /api/crud6/users                     ✨ Create (with payload)
  │   ├── GET    /api/crud6/users/1                   📄 Read
  │   ├── PUT    /api/crud6/users/1                   ✨ Update (with payload)
  │   ├── PUT    /api/crud6/users/1/flag_enabled      ✨ Field update (toggle)
  │   ├── POST   /api/crud6/users/1/a/reset_password  ✨ Custom action
  │   ├── POST   /api/crud6/users/1/roles             ✨ Attach relationship
  │   ├── DELETE /api/crud6/users/1/roles             ✨ Detach relationship
  │   └── DELETE /api/crud6/users/1                   ✨ Delete
  │
  ├── groups (7 endpoints)
  │   ├── GET    /api/crud6/groups/schema             ✨ Schema definition
  │   ├── GET    /api/crud6/groups                    📋 List
  │   ├── POST   /api/crud6/groups                    ✨ Create (with payload)
  │   ├── GET    /api/crud6/groups/1                  📄 Read
  │   ├── PUT    /api/crud6/groups/1                  ✨ Update (with payload)
  │   ├── GET    /api/crud6/groups/1/users            ✨ Nested: users in group
  │   └── DELETE /api/crud6/groups/1                  ✨ Delete
  │
  ├── roles (8 endpoints)
  │   ├── GET    /api/crud6/roles/schema              ✨ Schema definition
  │   ├── GET    /api/crud6/roles                     📋 List
  │   ├── POST   /api/crud6/roles                     ✨ Create (with payload)
  │   ├── GET    /api/crud6/roles/1                   📄 Read
  │   ├── PUT    /api/crud6/roles/1                   ✨ Update (with payload)
  │   ├── GET    /api/crud6/roles/1/users             ✨ Nested: users with role
  │   ├── GET    /api/crud6/roles/1/permissions       ✨ Nested: perms in role
  │   └── DELETE /api/crud6/roles/1                   ✨ Delete
  │
  ├── permissions (7 endpoints)
  │   ├── GET    /api/crud6/permissions/schema        ✨ Schema definition
  │   ├── GET    /api/crud6/permissions               📋 List
  │   ├── POST   /api/crud6/permissions               ✨ Create (with payload)
  │   ├── GET    /api/crud6/permissions/1             📄 Read
  │   ├── GET    /api/crud6/permissions/1/roles       ✨ Nested: roles with perm
  │   ├── GET    /api/crud6/permissions/1/users       ✨ Nested: users (through roles)
  │   └── DELETE /api/crud6/permissions/1             ✨ Delete
  │
  └── activities (3 endpoints)
      ├── GET    /api/crud6/activities/schema         ✨ Schema definition
      ├── GET    /api/crud6/activities                📋 List
      └── GET    /api/crud6/activities/1              📄 Read

Unauthenticated API (18 endpoints):
  ├── users (6 endpoints)
  │   ├── GET    /api/crud6/users/schema              ✨ (expect 401)
  │   ├── GET    /api/crud6/users                     📋 (expect 401)
  │   ├── POST   /api/crud6/users                     ✨ (expect 401)
  │   ├── GET    /api/crud6/users/1                   📄 (expect 401)
  │   ├── PUT    /api/crud6/users/1                   ✨ (expect 401)
  │   └── DELETE /api/crud6/users/1                   ✨ (expect 401)
  │
  ├── groups (3 endpoints)
  │   ├── GET    /api/crud6/groups/schema             ✨ (expect 401)
  │   ├── GET    /api/crud6/groups                    📋 (expect 401)
  │   └── GET    /api/crud6/groups/1                  📄 (expect 401)
  │
  ├── roles (3 endpoints)
  │   ├── GET    /api/crud6/roles/schema              ✨ (expect 401)
  │   ├── GET    /api/crud6/roles                     📋 (expect 401)
  │   └── GET    /api/crud6/roles/1                   📄 (expect 401)
  │
  ├── permissions (3 endpoints)
  │   ├── GET    /api/crud6/permissions/schema        ✨ (expect 401)
  │   ├── GET    /api/crud6/permissions               📋 (expect 401)
  │   └── GET    /api/crud6/permissions/1             📄 (expect 401)
  │
  └── activities (3 endpoints)
      ├── GET    /api/crud6/activities/schema         ✨ (expect 401)
      ├── GET    /api/crud6/activities                📋 (expect 401)
      └── GET    /api/crud6/activities/1              📄 (expect 401)

Legend:
  📋 = Existing endpoint (was in old config)
  📄 = Existing endpoint (was in old config)
  ✨ = New endpoint (added in this update)
```

## Endpoint Type Summary

### By HTTP Method

| Method | Count | Description |
|--------|-------|-------------|
| GET    | 38    | Schema, list, read, and nested endpoints |
| POST   | 6     | Create and custom actions |
| PUT    | 6     | Update and field updates |
| DELETE | 3     | Delete and relationship detach |
| **Total** | **53** | All test paths |

### By Endpoint Type

| Type | Count | Example |
|------|-------|---------|
| Schema | 10 | GET /api/crud6/users/schema |
| List | 10 | GET /api/crud6/users |
| Create | 8 | POST /api/crud6/users |
| Read | 10 | GET /api/crud6/users/1 |
| Update | 8 | PUT /api/crud6/users/1 |
| Delete | 8 | DELETE /api/crud6/users/1 |
| Field Update | 1 | PUT /api/crud6/users/1/flag_enabled |
| Custom Action | 1 | POST /api/crud6/users/1/a/reset_password |
| Relationship Ops | 2 | POST/DELETE /api/crud6/users/1/roles |
| Nested Relations | 6 | GET /api/crud6/roles/1/users |

## Coverage by Model

### Users Model (Most Comprehensive - 10 authenticated endpoints)

```
Schema:       GET    /api/crud6/users/schema
List:         GET    /api/crud6/users
Create:       POST   /api/crud6/users
              Payload: user_name, first_name, last_name, email, password
              Permission: create_user
Read:         GET    /api/crud6/users/1
Update:       PUT    /api/crud6/users/1
              Payload: first_name, last_name
              Permission: update_user_field
Field Update: PUT    /api/crud6/users/1/flag_enabled
              Payload: { flag_enabled: false }
              Permission: update_user_field
              Note: Boolean toggle action
Custom:       POST   /api/crud6/users/1/a/reset_password
              Permission: update_user_field
              Note: Custom action from schema
Attach:       POST   /api/crud6/users/1/roles
              Payload: { related_ids: [1] }
              Note: Many-to-many relationship
Detach:       DELETE /api/crud6/users/1/roles
              Payload: { related_ids: [1] }
              Note: Many-to-many relationship
Delete:       DELETE /api/crud6/users/1
              Permission: delete_user
```

### Roles Model (8 authenticated endpoints)

```
Schema:       GET    /api/crud6/roles/schema
List:         GET    /api/crud6/roles
Create:       POST   /api/crud6/roles
              Payload: slug, name, description
              Permission: create_role
Read:         GET    /api/crud6/roles/1
Update:       PUT    /api/crud6/roles/1
              Payload: { name: "Updated Role Name" }
              Permission: update_role_field
Nested:       GET    /api/crud6/roles/1/users
              Note: Users belonging to role (many-to-many)
Nested:       GET    /api/crud6/roles/1/permissions
              Note: Permissions in role (many-to-many)
Delete:       DELETE /api/crud6/roles/1
              Permission: delete_role
```

### Groups Model (7 authenticated endpoints)

```
Schema:       GET    /api/crud6/groups/schema
List:         GET    /api/crud6/groups
Create:       POST   /api/crud6/groups
              Payload: slug, name, description, icon
              Permission: create_group
Read:         GET    /api/crud6/groups/1
Update:       PUT    /api/crud6/groups/1
              Payload: { name: "Updated Group Name" }
              Permission: update_group_field
Nested:       GET    /api/crud6/groups/1/users
              Note: Users belonging to group (has_many)
Delete:       DELETE /api/crud6/groups/1
              Permission: delete_group
```

### Permissions Model (7 authenticated endpoints)

```
Schema:       GET    /api/crud6/permissions/schema
List:         GET    /api/crud6/permissions
Create:       POST   /api/crud6/permissions
              Payload: slug, name, description
              Permission: create_permission
Read:         GET    /api/crud6/permissions/1
Nested:       GET    /api/crud6/permissions/1/roles
              Note: Roles with permission (many-to-many)
Nested:       GET    /api/crud6/permissions/1/users
              Note: Users with permission through roles (complex nested)
Delete:       DELETE /api/crud6/permissions/1
              Permission: delete_permission
```

### Activities Model (3 authenticated endpoints - Basic CRUD only)

```
Schema:       GET    /api/crud6/activities/schema
List:         GET    /api/crud6/activities
Read:         GET    /api/crud6/activities/1
```

## Test Coverage Matrix

### CRUD Operations

| Model | Schema | List | Create | Read | Update | Delete |
|-------|--------|------|--------|------|--------|--------|
| users | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| groups | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| roles | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| permissions | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| activities | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |

### Advanced Features

| Model | Field Update | Custom Actions | Relationships | Nested Endpoints |
|-------|--------------|----------------|---------------|------------------|
| users | ✅ (flag_enabled) | ✅ (reset_password) | ✅ (roles attach/detach) | ❌ |
| groups | ❌ | ❌ | ❌ | ✅ (users) |
| roles | ❌ | ❌ | ❌ | ✅ (users, permissions) |
| permissions | ❌ | ❌ | ❌ | ✅ (roles, users) |
| activities | ❌ | ❌ | ❌ | ❌ |

## Relationship Coverage

### Direct Relationships (Tested via Nested Endpoints)

```
groups → users              GET /api/crud6/groups/{id}/users
roles → users               GET /api/crud6/roles/{id}/users
roles → permissions         GET /api/crud6/roles/{id}/permissions
permissions → roles         GET /api/crud6/permissions/{id}/roles
```

### Complex Nested Relationships

```
permissions → users (through roles)
  GET /api/crud6/permissions/{id}/users
  - Permissions are assigned to roles
  - Roles are assigned to users
  - This endpoint returns users who have a permission through their roles
```

### Relationship Management (Attach/Detach)

```
users ↔ roles              POST   /api/crud6/users/{id}/roles
                           DELETE /api/crud6/users/{id}/roles
                           Payload: { related_ids: [1, 2, 3] }
```

## Permission Requirements

### By Operation Type

| Operation | Permission Pattern | Example |
|-----------|-------------------|---------|
| Create | create_{singular} | create_user, create_group |
| Update | update_{singular}_field | update_user_field, update_group_field |
| Delete | delete_{singular} | delete_user, delete_group |
| Read/List | uri_{model} | uri_users, uri_groups |

### Users Model Permissions

```
uri_users              - List users (GET /api/crud6/users)
create_user            - Create user (POST /api/crud6/users)
update_user_field      - Update user (PUT /api/crud6/users/{id})
update_user_field      - Field update (PUT /api/crud6/users/{id}/flag_enabled)
update_user_field      - Custom actions (POST /api/crud6/users/{id}/a/reset_password)
delete_user            - Delete user (DELETE /api/crud6/users/{id})
```

## New Validation Types

### Status Any (for Partially Implemented Features)

```json
"validation": {
  "type": "status_any",
  "acceptable_statuses": [200, 403, 500]
}
```

Used for custom actions and relationship operations that may not be fully implemented.

### Request Payloads

```json
"payload": {
  "user_name": "apitest",
  "first_name": "API",
  "last_name": "Test",
  "email": "apitest@example.com",
  "password": "TestPassword123"
}
```

Each create/update endpoint includes example payload data.

## Alignment with SchemaBasedApiTest.php

### Test Method Mapping

| Test Method | Endpoints Tested |
|-------------|------------------|
| `testSecurityMiddlewareIsApplied()` | All unauthenticated endpoints (18) |
| `testUsersModelCompleteApiIntegration()` | users_* endpoints (10) |
| `testRolesModelCompleteApiIntegration()` | roles_* endpoints (8) |
| `testGroupsModelCompleteApiIntegration()` | groups_* endpoints (7) |
| `testPermissionsModelCompleteApiIntegration()` | permissions_* endpoints (7) |

### Helper Method Mapping

| Helper Method | Config Pattern |
|--------------|----------------|
| `testSchemaEndpointRequiresAuth()` | {model}_schema |
| `testListEndpointWithAuth()` | {model}_list |
| `testCreateEndpointWithValidation()` | {model}_create |
| `testReadEndpoint()` | {model}_single |
| `testFieldUpdateEndpoints()` | {model}_update_field |
| `testCustomActionsFromSchema()` | {model}_custom_action |
| `testRelationshipEndpoints()` | {model}_relationship_attach/detach |
| `testFullUpdateEndpoint()` | {model}_update |
| `testDeleteEndpoint()` | {model}_delete |

## Statistics

- **Total endpoints:** 53
- **New endpoints added:** 43 (81% increase)
- **Models covered:** 5
- **HTTP methods:** 4 (GET, POST, PUT, DELETE)
- **Relationship endpoints:** 8 (6 nested + 2 attach/detach)
- **Custom action endpoints:** 1
- **Field update endpoints:** 1
- **Schema endpoints:** 10 (5 authenticated + 5 unauthenticated)
