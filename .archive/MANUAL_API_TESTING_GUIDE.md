# Manual API Testing Guide for CRUD6

This guide provides curl commands and test procedures for manually testing all CRUD6 API endpoints with both authenticated and unauthenticated requests.

## Prerequisites

1. CRUD6 sprinkle deployed in a UserFrosting 6 application
2. Database migrated and seeded with test data
3. A test user account with appropriate permissions
4. `curl` and `jq` installed for command-line testing

## Authentication Setup

First, obtain an authentication token:

```bash
# Login and get authentication token
TOKEN=$(curl -s -X POST http://localhost/api/account/login \
  -H "Content-Type: application/json" \
  -d '{"user_name":"admin","password":"your_password"}' \
  | jq -r '.token')

echo "Auth Token: $TOKEN"
```

Or use cookie-based authentication:

```bash
# Login and save cookies
curl -X POST http://localhost/api/account/login \
  -H "Content-Type: application/json" \
  -d '{"user_name":"admin","password":"your_password"}' \
  -c cookies.txt

# Use cookies in subsequent requests with -b cookies.txt
```

## Base URL

```bash
BASE_URL="http://localhost"
```

## Test Endpoints

### 1. Config Endpoint (Public - No Auth Required)

**Endpoint**: `GET /api/crud6/config`

```bash
# Test without authentication (should work)
curl -v "${BASE_URL}/api/crud6/config"

# Expected: 200 OK with config data
```

### 2. Schema Endpoint

**Endpoint**: `GET /api/crud6/{model}/schema`

```bash
# Test without authentication (should fail with 401)
curl -v "${BASE_URL}/api/crud6/users/schema"

# Test with authentication but no permission (should fail with 403)
curl -v "${BASE_URL}/api/crud6/users/schema" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (should succeed with 200)
curl -v "${BASE_URL}/api/crud6/users/schema" \
  -H "Authorization: Bearer $TOKEN"

# Expected responses:
# - No auth: 401 "Login Required"
# - No permission: 403 "Access Denied"
# - With permission: 200 with schema JSON
```

### 3. List Endpoint (Sprunje)

**Endpoint**: `GET /api/crud6/{model}`

```bash
# Test without authentication (should fail with 401)
curl -v "${BASE_URL}/api/crud6/users"

# Test with authentication but no permission (should fail with 403)
curl -v "${BASE_URL}/api/crud6/users" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (should succeed with 200)
curl -v "${BASE_URL}/api/crud6/users?size=10&page=0" \
  -H "Authorization: Bearer $TOKEN"

# Test with filters
curl -v "${BASE_URL}/api/crud6/users?filters[user_name]=admin" \
  -H "Authorization: Bearer $TOKEN"

# Test with sorting
curl -v "${BASE_URL}/api/crud6/users?sorts[user_name]=asc" \
  -H "Authorization: Bearer $TOKEN"

# Test with search
curl -v "${BASE_URL}/api/crud6/users?search=admin" \
  -H "Authorization: Bearer $TOKEN"
```

### 4. Create Endpoint

**Endpoint**: `POST /api/crud6/{model}`

```bash
# Test without authentication (should fail with 401)
curl -v -X POST "${BASE_URL}/api/crud6/users" \
  -H "Content-Type: application/json" \
  -d '{
    "user_name": "newuser",
    "first_name": "New",
    "last_name": "User",
    "email": "newuser@example.com",
    "password": "Password123"
  }'

# Test with authentication but no permission (should fail with 403)
curl -v -X POST "${BASE_URL}/api/crud6/users" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS" \
  -d '{
    "user_name": "newuser",
    "first_name": "New",
    "last_name": "User",
    "email": "newuser@example.com",
    "password": "Password123"
  }'

# Test with authentication and permission (should succeed with 200)
curl -v -X POST "${BASE_URL}/api/crud6/users" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "user_name": "newuser",
    "first_name": "New",
    "last_name": "User",
    "email": "newuser@example.com",
    "password": "Password123"
  }'

# Test validation errors
curl -v -X POST "${BASE_URL}/api/crud6/users" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "user_name": "",
    "email": "invalid-email"
  }'
```

### 5. Read Single Record Endpoint

**Endpoint**: `GET /api/crud6/{model}/{id}`

```bash
# Test without authentication (should fail with 401)
curl -v "${BASE_URL}/api/crud6/users/1"

# Test with authentication but no permission (should fail with 403)
curl -v "${BASE_URL}/api/crud6/users/1" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (should succeed with 200)
curl -v "${BASE_URL}/api/crud6/users/1" \
  -H "Authorization: Bearer $TOKEN"

# Test non-existent ID (should return 404)
curl -v "${BASE_URL}/api/crud6/users/999999" \
  -H "Authorization: Bearer $TOKEN"
```

### 6. Update Record Endpoint

**Endpoint**: `PUT /api/crud6/{model}/{id}`

```bash
# Test without authentication (should fail with 401)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1" \
  -H "Content-Type: application/json" \
  -d '{"first_name": "Updated"}'

# Test with authentication but no permission (should fail with 403)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS" \
  -d '{"first_name": "Updated"}'

# Test with authentication and permission (should succeed with 200)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"first_name": "Updated", "last_name": "Name"}'

# Test partial update
curl -v -X PUT "${BASE_URL}/api/crud6/users/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"first_name": "OnlyFirstName"}'

# Test readonly field (should fail)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"id": 999}'
```

### 7. Update Single Field Endpoint

**Endpoint**: `PUT /api/crud6/{model}/{id}/{field}`

```bash
# Test without authentication (should fail with 401)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/first_name" \
  -H "Content-Type: application/json" \
  -d '{"first_name": "Updated"}'

# Test with authentication but no permission (should fail with 403)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/first_name" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS" \
  -d '{"first_name": "Updated"}'

# Test with authentication and permission (should succeed with 200)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/first_name" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"first_name": "Updated"}'

# Test boolean toggle (flag_enabled)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/flag_enabled" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"flag_enabled": false}'

# Test boolean toggle (flag_verified)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/flag_verified" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"flag_verified": true}'

# Test non-existent field (should fail)
curl -v -X PUT "${BASE_URL}/api/crud6/users/1/nonexistent_field" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"nonexistent_field": "value"}'
```

### 8. Delete Endpoint

**Endpoint**: `DELETE /api/crud6/{model}/{id}`

```bash
# Test without authentication (should fail with 401)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/10"

# Test with authentication but no permission (should fail with 403)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/10" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (should succeed with 200)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/10" \
  -H "Authorization: Bearer $TOKEN"

# Test non-existent ID (should return 404)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/999999" \
  -H "Authorization: Bearer $TOKEN"

# Verify soft delete in database
mysql -u root -p -e "SELECT id, user_name, deleted_at FROM uf_users WHERE id = 10;"
```

### 9. Custom Action Endpoint

**Endpoint**: `POST /api/crud6/{model}/{id}/a/{actionKey}`

```bash
# Test without authentication (should fail with 401)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/a/enable_user"

# Test with authentication but no permission (should fail with 403)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/a/enable_user" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (behavior depends on action)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/a/enable_user" \
  -H "Authorization: Bearer $TOKEN"

# Test other custom actions
curl -v -X POST "${BASE_URL}/api/crud6/users/1/a/disable_user" \
  -H "Authorization: Bearer $TOKEN"

curl -v -X POST "${BASE_URL}/api/crud6/users/1/a/reset_password" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"new_password": "NewPassword123"}'
```

### 10. Get Related Data Endpoint (Nested)

**Endpoint**: `GET /api/crud6/{model}/{id}/{relation}`

```bash
# Test without authentication (should fail with 401)
curl -v "${BASE_URL}/api/crud6/users/1/roles"

# Test with authentication but no permission (should fail with 403)
curl -v "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS"

# Test with authentication and permission (should succeed with 200)
curl -v "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Authorization: Bearer $TOKEN"

# Test other relationships
curl -v "${BASE_URL}/api/crud6/roles/1/permissions" \
  -H "Authorization: Bearer $TOKEN"

curl -v "${BASE_URL}/api/crud6/roles/1/users" \
  -H "Authorization: Bearer $TOKEN"

curl -v "${BASE_URL}/api/crud6/permissions/1/roles" \
  -H "Authorization: Bearer $TOKEN"
```

### 11. Attach Relationship Endpoint

**Endpoint**: `POST /api/crud6/{model}/{id}/{relation}`

```bash
# Test without authentication (should fail with 401)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -d '{"related_ids": [2, 3]}'

# Test with authentication but no permission (should fail with 403)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS" \
  -d '{"related_ids": [2, 3]}'

# Test with authentication and permission (should succeed with 200)
curl -v -X POST "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"related_ids": [2, 3]}'

# Verify in database
mysql -u root -p -e "SELECT * FROM uf_role_users WHERE user_id = 1;"

# Test with pivot data
curl -v -X POST "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "related_ids": [4],
    "pivot_data": {
      "created_at": "2024-01-01 00:00:00"
    }
  }'
```

### 12. Detach Relationship Endpoint

**Endpoint**: `DELETE /api/crud6/{model}/{id}/{relation}`

```bash
# Test without authentication (should fail with 401)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -d '{"related_ids": [2]}'

# Test with authentication but no permission (should fail with 403)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_NO_PERMISSIONS" \
  -d '{"related_ids": [2]}'

# Test with authentication and permission (should succeed with 200)
curl -v -X DELETE "${BASE_URL}/api/crud6/users/1/roles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"related_ids": [2, 3]}'

# Verify in database
mysql -u root -p -e "SELECT * FROM uf_role_users WHERE user_id = 1;"
```

## Testing Different Models

Repeat the above tests for all models in your schema:

```bash
# Users
MODEL="users"

# Roles
MODEL="roles"

# Groups
MODEL="groups"

# Permissions
MODEL="permissions"

# Activities
MODEL="activities"
```

## Expected Response Codes

| Scenario | Expected HTTP Code | Expected Response |
|----------|-------------------|------------------|
| No authentication | 401 | `{"title": "Login Required", ...}` |
| No permission | 403 | `{"title": "Access Denied", ...}` |
| Success (list/read) | 200 | Data in response body |
| Success (create) | 200 | `{"data": {...}, ...}` |
| Success (update) | 200 | Updated record data |
| Success (delete) | 200 | Success message |
| Not found | 404 | `{"title": "Not Found", ...}` |
| Validation error | 400/422 | Validation error details |
| Server error | 500 | Error message |

## Automated Test Script

Save this as `test-all-endpoints.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost"
TOKEN="your_auth_token_here"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

test_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_code=$3
    local auth_header=$4
    local data=$5
    
    echo -n "Testing $method $endpoint... "
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method "${BASE_URL}${endpoint}" $auth_header)
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "${BASE_URL}${endpoint}" \
            -H "Content-Type: application/json" \
            $auth_header \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    
    if [ "$http_code" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} (got $http_code)"
    else
        echo -e "${RED}✗ FAIL${NC} (expected $expected_code, got $http_code)"
    fi
}

echo "=== Testing CRUD6 API Endpoints ==="
echo ""

# Schema endpoint
test_endpoint "GET" "/api/crud6/users/schema" "401" ""
test_endpoint "GET" "/api/crud6/users/schema" "200" "-H 'Authorization: Bearer $TOKEN'"

# List endpoint
test_endpoint "GET" "/api/crud6/users" "401" ""
test_endpoint "GET" "/api/crud6/users" "200" "-H 'Authorization: Bearer $TOKEN'"

# Create endpoint
test_endpoint "POST" "/api/crud6/users" "401" "" '{"user_name":"test"}'
test_endpoint "POST" "/api/crud6/users" "200" "-H 'Authorization: Bearer $TOKEN'" \
    '{"user_name":"testuser","email":"test@example.com","password":"Test123"}'

# Read endpoint
test_endpoint "GET" "/api/crud6/users/1" "401" ""
test_endpoint "GET" "/api/crud6/users/1" "200" "-H 'Authorization: Bearer $TOKEN'"

# Update endpoint
test_endpoint "PUT" "/api/crud6/users/1" "401" "" '{"first_name":"Updated"}'
test_endpoint "PUT" "/api/crud6/users/1" "200" "-H 'Authorization: Bearer $TOKEN'" \
    '{"first_name":"Updated"}'

# Delete endpoint
test_endpoint "DELETE" "/api/crud6/users/999" "401" ""
test_endpoint "DELETE" "/api/crud6/users/999" "404" "-H 'Authorization: Bearer $TOKEN'"

echo ""
echo "=== Test Summary ==="
```

Make it executable:
```bash
chmod +x test-all-endpoints.sh
./test-all-endpoints.sh
```

## Database Verification

After each operation, verify the changes in the database:

```bash
# Check users table
mysql -u root -p -e "SELECT id, user_name, first_name, last_name, flag_enabled, flag_verified, deleted_at FROM uf_users LIMIT 10;"

# Check relationships
mysql -u root -p -e "SELECT * FROM uf_role_users WHERE user_id = 1;"

# Check permissions
mysql -u root -p -e "SELECT r.name, p.name FROM uf_roles r JOIN uf_permission_role pr ON r.id = pr.role_id JOIN uf_permissions p ON pr.permission_id = p.id WHERE r.id = 1;"
```

## Troubleshooting

If you get unexpected results:

1. **Enable debug mode** in config: `crud6.debug_mode: true`
2. **Check application logs**: `storage/logs/userfrosting.log`
3. **Verify schema files** exist and are valid JSON
4. **Check permissions** are correctly assigned
5. **Verify database** migrations and seeds are up to date
6. **Check middleware** is properly configured

## Integration with Browser Testing

These curl commands can be adapted for browser automation tools:

- **Playwright**: Use in Node.js scripts
- **Selenium**: Convert to browser automation scripts
- **Postman**: Import as Postman collection
- **Bruno**: Import as Bruno collection
