# Authenticated API Path Testing

## Overview
This document describes the new authenticated API path testing feature added to the integration test workflow.

## Problem Solved
Previously, the integration test workflow only tested:
1. **Unauthenticated API paths** - Using `test-paths.php` with curl (expected 401 responses)
2. **Authenticated frontend paths** - Using Playwright to take screenshots

**Missing:** Authenticated API endpoint testing with real HTTP requests

The comment in the workflow said "Authenticated path tests require session management. Using Playwright for authenticated tests instead" but there was NO script actually testing the authenticated API endpoints!

## Solution
Created a new Playwright-based script that:
1. Logs in to get an authenticated session
2. Tests all authenticated API endpoints from the JSON configuration
3. Handles CSRF tokens automatically
4. Tests all HTTP methods (GET, POST, PUT, DELETE)
5. Validates responses and reports results

## New Script: `test-authenticated-api-paths.js`

### Location
`.github/scripts/test-authenticated-api-paths.js`

### Features
- **Session Management**: Logs in with admin credentials to establish session
- **CSRF Tokens**: Automatically retrieves and includes CSRF tokens in requests
- **HTTP Methods**: Supports GET, POST, PUT, DELETE
- **Payloads**: Handles request payloads for POST/PUT operations
- **Validation**: Validates JSON responses and required fields
- **Error Handling**: Differentiates between permission failures (403) and server errors (500+)
- **Reporting**: Same format as `test-paths.php` for consistency

### Usage
```bash
node test-authenticated-api-paths.js <config_file> [base_url] [username] [password]
```

Example:
```bash
node test-authenticated-api-paths.js integration-test-paths.json
# Uses base_url, username, password from config file

node test-authenticated-api-paths.js integration-test-paths.json http://localhost:8080 admin admin123
# Overrides config with command line parameters
```

### Configuration
Reads from `integration-test-paths.json`:
```json
{
  "paths": {
    "authenticated": {
      "api": {
        "users_schema": {
          "method": "GET",
          "path": "/api/crud6/users/schema",
          "expected_status": 200,
          "validation": {
            "type": "json",
            "contains": ["model", "fields"]
          }
        },
        "users_create": {
          "method": "POST",
          "path": "/api/crud6/users",
          "expected_status": 200,
          "requires_permission": "create_user",
          "payload": {
            "user_name": "apitest",
            "email": "apitest@example.com",
            "password": "TestPassword123"
          }
        }
      }
    }
  },
  "config": {
    "base_url": "http://localhost:8080",
    "auth": {
      "username": "admin",
      "password": "admin123"
    }
  }
}
```

## Workflow Integration

### Workflow Step
Added to `.github/workflows/integration-test.yml` in the "Test API and Frontend paths (Modular)" step:

```yaml
- name: Test API and Frontend paths (Modular)
  run: |
    cd userfrosting
    
    # Test unauthenticated paths
    php test-paths.php integration-test-paths.json unauth
    
    # NEW: Test authenticated API paths
    node test-authenticated-api-paths.js integration-test-paths.json
    
    # Frontend paths tested via screenshots
```

### Test Coverage
The authenticated API testing covers all c6admin schemas:

**Users** (`/api/crud6/users`)
- Schema endpoint: GET `/api/crud6/users/schema`
- List endpoint: GET `/api/crud6/users`
- Create endpoint: POST `/api/crud6/users`
- Read endpoint: GET `/api/crud6/users/{id}`
- Update endpoint: PUT `/api/crud6/users/{id}`
- Update field: PUT `/api/crud6/users/{id}/{field}`
- Delete endpoint: DELETE `/api/crud6/users/{id}`
- Custom actions: POST `/api/crud6/users/{id}/a/{action}`
- Relationships: POST/DELETE `/api/crud6/users/{id}/{relation}`

**Groups** (`/api/crud6/groups`)
- Schema, List, Create, Read, Update, Delete
- Nested endpoints: GET `/api/crud6/groups/{id}/users`

**Roles** (`/api/crud6/roles`)
- Schema, List, Create, Read, Update, Delete
- Nested endpoints: GET `/api/crud6/roles/{id}/users`
- Nested endpoints: GET `/api/crud6/roles/{id}/permissions`

**Permissions** (`/api/crud6/permissions`)
- Schema, List, Create, Read, Delete
- Nested endpoints: GET `/api/crud6/permissions/{id}/roles`
- Nested endpoints: GET `/api/crud6/permissions/{id}/users`

**Activities** (`/api/crud6/activities`)
- Schema, List, Read

## Test Behavior

### Success Scenarios
```
Testing: users_schema
   Description: Get users schema definition
   Method: GET
   Path: /api/crud6/users/schema
   ✅ Status: 200 (expected 200)
   ✅ Validation: JSON contains expected keys
   ✅ PASSED
```

### Permission Failure (Warning)
```
Testing: users_create
   Description: Create new user via CRUD6 API
   Method: POST
   Path: /api/crud6/users
   ⚠️  Status: 403 (expected 200)
   ⚠️  WARNING: Permission failure (403) - user may lack required permission
   ⚠️  Required permission: create_user
   ⚠️  WARNED (continuing tests)
```

### Server Error (Failure)
```
Testing: users_custom_action
   Description: Execute custom action on user
   Method: POST
   Path: /api/crud6/users/1/a/broken_action
   ❌ Status: 500 (expected 200)
   ❌ FAILED: Server error detected - possible code/SQL failure
   ❌ Error: Call to undefined function now()
```

### Summary Output
```
=========================================
Test Summary
=========================================
Total tests: 25
Passed: 20
Warnings: 3
Failed: 2
Skipped: 0

❌ Some tests failed (actual code/SQL errors detected)
   Note: Permission failures (403) are warnings, not failures
```

## Exit Codes
- **Exit 0**: All tests passed (warnings allowed)
- **Exit 1**: Some tests failed (server errors detected)

## Benefits

### 1. Complete API Coverage
- Unauthenticated API endpoints: 401 expected (warnings)
- **Authenticated API endpoints: 200 expected** (NEW!)
- All CRUD operations tested with real HTTP requests

### 2. Real-World Testing
- Uses actual HTTP requests via Playwright
- Tests the same endpoints that frontend code calls
- Validates session management and CSRF protection
- Catches issues that unit tests might miss

### 3. Comprehensive Schema Testing
- Tests all models from c6admin schemas
- Covers all endpoint types (schema, list, CRUD, relationships, actions)
- Validates response formats and required fields

### 4. Clear Error Reporting
- Distinguishes permission failures (403) from code errors (500)
- Shows required permissions for failed operations
- Continues testing even after warnings
- Exit code properly reflects test status

### 5. CI/CD Integration
- Runs automatically in GitHub Actions workflow
- Uses same config file as other tests (integration-test-paths.json)
- Consistent reporting format with test-paths.php
- Easy to understand results in workflow logs

## Comparison with PHPUnit Tests

### PHPUnit Integration Tests
- Test authentication scenarios (401, 403, 200)
- Test business logic and database interactions
- Use test database with factories
- Focus on unit/integration testing

### Authenticated API Path Testing
- Tests real HTTP endpoints
- Uses actual application server
- Tests with real database (seeded data)
- Focus on end-to-end API testing

**Both are valuable** - PHPUnit tests the code logic, while API path testing validates the actual HTTP interface.

## Future Enhancements

### Possible Improvements
1. **Parallel Testing**: Run multiple API tests in parallel
2. **Response Time Tracking**: Measure and report API response times
3. **Data Validation**: More detailed validation of response data
4. **Coverage Metrics**: Track which endpoints are covered by tests
5. **Custom Assertions**: Add model-specific validation rules

### Configuration Extensions
1. **Test Data Setup**: Allow specifying test data in config
2. **Cleanup Actions**: Add cleanup steps after tests
3. **Conditional Tests**: Skip tests based on environment
4. **Retry Logic**: Retry failed tests before marking as failed

## Troubleshooting

### Common Issues

**Issue: Login fails**
- Check username/password in config
- Verify admin user was created in workflow
- Check for CSRF token issues

**Issue: 403 errors for all endpoints**
- Check user permissions in database
- Verify seeds ran correctly
- Check permission slugs in config

**Issue: 500 errors**
- Check server logs for actual error
- Verify database schema is correct
- Check for missing dependencies

**Issue: Script times out**
- Increase timeouts in script
- Check if server is responding
- Verify network connectivity

## References
- Main script: `.github/scripts/test-authenticated-api-paths.js`
- Workflow: `.github/workflows/integration-test.yml`
- Config: `.github/config/integration-test-paths.json`
- Related: `test-paths.php` (unauthenticated testing)
- Related: `take-screenshots-with-tracking.js` (frontend testing)
