# Integration Test Paths Update - Based on SchemaBasedApiTest.php

**Date:** November 20, 2025  
**Related PR:** Update integration test paths to match SchemaBasedApiTest implementation

## Summary

Updated the integration test path configuration files to include all comprehensive API endpoints tested in `SchemaBasedApiTest.php` from the previous PR. The configuration now supports the full range of CRUD6 API operations including schema endpoints, CRUD operations, field updates, custom actions, and relationship management.

## Files Updated

### 1. `.github/config/integration-test-paths.json` (733 lines, +367 additions)

**Previous Coverage:**
- Basic GET endpoints (list, single)
- Limited to 5 models (users, groups, roles, permissions, activities)
- Only tested authentication/unauthenticated scenarios

**New Coverage (Authenticated API Endpoints):**

For each model (users, groups, roles, permissions, activities):

1. **Schema Endpoint**
   - `GET /api/crud6/{model}/schema`
   - Returns model schema definition with fields and actions

2. **CRUD Operations**
   - `GET /api/crud6/{model}` - List all records
   - `POST /api/crud6/{model}` - Create new record (with payload)
   - `GET /api/crud6/{model}/{id}` - Get single record
   - `PUT /api/crud6/{model}/{id}` - Update record (with payload)
   - `DELETE /api/crud6/{model}/{id}` - Delete record

3. **Field Update Endpoints** (for users model)
   - `PUT /api/crud6/users/{id}/{field}` - Update single field
   - Example: `PUT /api/crud6/users/1/flag_enabled` for boolean toggles

4. **Custom Action Endpoints** (for users model)
   - `POST /api/crud6/users/{id}/a/{actionKey}` - Execute custom action
   - Example: `POST /api/crud6/users/1/a/reset_password`

5. **Relationship Endpoints**
   - `POST /api/crud6/{model}/{id}/{relation}` - Attach related records
   - `DELETE /api/crud6/{model}/{id}/{relation}` - Detach related records
   - `GET /api/crud6/{model}/{id}/{relation}` - List related records (nested)

**Specific Nested Endpoints Added:**
- `GET /api/crud6/groups/{id}/users` - Users in a group
- `GET /api/crud6/roles/{id}/users` - Users with a role
- `GET /api/crud6/roles/{id}/permissions` - Permissions in a role
- `GET /api/crud6/permissions/{id}/roles` - Roles with a permission
- `GET /api/crud6/permissions/{id}/users` - Users with a permission (through roles)

**Unauthenticated Coverage:**
- Added schema endpoints to unauthenticated tests
- Added create, update, delete operations (expect 401)
- Maintained existing authentication checks for GET operations

**New Validation Types:**
- `status_any` - Accept multiple status codes (for partially implemented features)
- `acceptable_statuses` - List of valid status codes (e.g., [200, 403, 500])

**Request Payloads:**
Each create endpoint now includes example payload data:
- Users: user_name, first_name, last_name, email, password
- Groups: slug, name, description, icon
- Roles: slug, name, description
- Permissions: slug, name, description

**Permission Requirements:**
Documented required permissions for each endpoint:
- `create_user`, `create_group`, `create_role`, `create_permission`
- `update_user_field`, `update_group_field`, `update_role_field`
- `delete_user`, `delete_group`, `delete_role`, `delete_permission`

### 2. `.github/config/integration-test-models.json` (365 lines, +224 additions)

**Enhanced Model Definitions:**

Each model now includes:

1. **Relationship Metadata**
   ```json
   "relationships": [
     {
       "name": "roles",
       "type": "many_to_many",
       "nested_endpoint": true
     }
   ]
   ```

2. **Custom Actions** (users model)
   ```json
   "custom_actions": [
     "reset_password",
     "enable_user",
     "disable_user"
   ]
   ```

3. **Field Toggles** (users model)
   ```json
   "field_toggles": [
     "flag_enabled",
     "flag_verified"
   ]
   ```

4. **Create Payloads**
   ```json
   "create_payload": {
     "user_name": "apitest",
     "first_name": "API",
     "last_name": "Test",
     "email": "apitest@example.com",
     "password": "TestPassword123"
   }
   ```

**Relationship Types Defined:**
- `many_to_many` - Direct many-to-many relationship (users ↔ roles)
- `has_many` - One-to-many relationship (group → users)
- `many_to_many_through` - Indirect relationship (users → permissions through roles)

**Updated Path Templates:**

Added templates for all CRUD6 API operations:

**Authenticated:**
- `schema` - GET schema endpoint
- `create` - POST create endpoint
- `update` - PUT update endpoint
- `update_field` - PUT single field update
- `custom_action` - POST custom action
- `relationship_attach` - POST attach related records
- `relationship_detach` - DELETE detach related records
- `nested_relationship` - GET nested relationship list
- `delete` - DELETE record

**Unauthenticated:**
- `schema` - GET schema (expect 401)
- `create` - POST create (expect 401)
- `update` - PUT update (expect 401)
- `delete` - DELETE (expect 401)

## Alignment with SchemaBasedApiTest.php

The updated configuration files now mirror the comprehensive testing approach in `SchemaBasedApiTest.php`:

### Test Method → Config Mapping

| SchemaBasedApiTest Method | Config Section |
|--------------------------|----------------|
| `testSecurityMiddlewareIsApplied()` | `unauthenticated.api.*` |
| `testUsersModelCompleteApiIntegration()` | `authenticated.api.users_*` |
| `testRolesModelCompleteApiIntegration()` | `authenticated.api.roles_*` |
| `testGroupsModelCompleteApiIntegration()` | `authenticated.api.groups_*` |
| `testPermissionsModelCompleteApiIntegration()` | `authenticated.api.permissions_*` |

### Helper Methods → Config Patterns

| Helper Method | Config Pattern |
|--------------|----------------|
| `testSchemaEndpointRequiresAuth()` | `{model}_schema` endpoints |
| `testListEndpointWithAuth()` | `{model}_list` endpoints |
| `testCreateEndpointWithValidation()` | `{model}_create` with payload |
| `testReadEndpoint()` | `{model}_single` endpoints |
| `testFieldUpdateEndpoints()` | `{model}_update_field` endpoints |
| `testCustomActionsFromSchema()` | `{model}_custom_action` endpoints |
| `testRelationshipEndpoints()` | `{model}_relationship_*` endpoints |
| `testFullUpdateEndpoint()` | `{model}_update` endpoints |
| `testDeleteEndpoint()` | `{model}_delete` endpoints |

## Benefits

1. **Comprehensive Coverage**: All API endpoints from SchemaBasedApiTest are now documented in configuration
2. **Automated Testing**: Can be used to generate automated integration tests
3. **Documentation**: Serves as API endpoint documentation for CRUD6
4. **Validation**: Includes expected status codes, response formats, and required permissions
5. **Reusability**: Template-based approach for adding new models
6. **CI/CD Integration**: Can be used in automated testing pipelines

## Usage

### For Manual Testing
Use the paths in `integration-test-paths.json` to test API endpoints with tools like curl or Postman.

### For Automated Testing
Use the model definitions in `integration-test-models.json` to generate test cases programmatically.

### For Documentation
Both files serve as comprehensive API documentation for the CRUD6 sprinkle.

## Security Considerations

### CSRF Protection

**UserFrosting 6 Security Mechanisms:**
- All POST, PUT, and DELETE endpoints are protected by `CsrfGuardMiddleware` in production
- CSRF tokens are required for all state-changing operations

**For PHPUnit Integration Tests:**
- CSRF is **automatically handled** by UserFrosting's testing framework
- The `createJsonRequest()` method includes necessary CSRF headers
- Tests use `WithTestUser` trait and `actAsUser()` for authentication
- No manual CSRF token management needed in tests
- See `SchemaBasedApiTest.php` lines 47-51 for implementation details

**For Manual Testing (curl/Postman):**
```bash
# 1. Get CSRF token
TOKEN=$(curl -c cookies.txt http://localhost:8080/csrf | jq -r '.csrf_token')

# 2. Use token in POST/PUT/DELETE requests
curl -b cookies.txt -X POST http://localhost:8080/api/crud6/users \
  -H "X-CSRF-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_name":"test",...}'
```

**Configuration Files:**
- Both `integration-test-paths.json` and `integration-test-models.json` now include `security` section
- Documents CSRF requirements for POST, PUT, DELETE operations
- Clarifies automatic handling in PHPUnit vs manual testing requirements

### Authentication

- All CRUD6 API endpoints require authentication via `AuthGuard` middleware
- Tests verify both authenticated and unauthenticated scenarios
- Unauthenticated requests return HTTP 401

### Permissions

- Many operations require specific permissions (documented in `requires_permission` field)
- Tests verify permission enforcement (HTTP 403 when permission missing)
- Permission requirements documented for each endpoint

## Notes

- All endpoints require authentication (AuthGuard middleware)
- CSRF protection is enforced on POST/PUT/DELETE operations (CsrfGuardMiddleware)
- Permission requirements are documented but not enforced in unauthenticated tests
- Custom actions and field toggles are model-specific (primarily users model)
- Relationship endpoints support both attach/detach and nested listing operations
- Some custom actions may not be fully implemented (status_any validation used)

## Related Files

- `app/tests/Integration/SchemaBasedApiTest.php` - Original comprehensive test implementation
- `examples/schema/c6admin-*.json` - Schema files for tested models
- `app/schema/crud6/*.json` - Runtime schema files
- `.github/scripts/test-paths.php` - Script to test paths from configuration
