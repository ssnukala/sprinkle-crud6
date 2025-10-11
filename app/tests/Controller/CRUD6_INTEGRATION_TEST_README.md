# CRUD6 Groups Integration Test Documentation

## Overview

This document describes the automated integration tests for CRUD6 groups endpoints. These tests verify that the CRUD6 sprinkle correctly implements API and frontend routes for managing groups.

## Test Files

### PHP Unit Tests

**File**: `app/tests/Controller/CRUD6GroupsIntegrationTest.php`

This test suite includes:

1. **API Authentication Tests**
   - `testGroupsListApiRequiresAuthentication()` - Verifies GET /api/crud6/groups returns 401 for guests
   - `testSingleGroupApiRequiresAuthentication()` - Verifies GET /api/crud6/groups/1 returns 401 for guests

2. **API Authorization Tests**
   - `testGroupsListApiRequiresPermission()` - Verifies 403 without `uri_crud6` permission
   - `testSingleGroupApiRequiresPermission()` - Verifies 403 without `uri_crud6` permission

3. **API Data Tests**
   - `testGroupsListApiReturnsGroups()` - Verifies groups list returns data for authorized users
   - `testSingleGroupApiReturnsGroup()` - Verifies single group returns correct data structure
   - `testSingleGroupApiReturns404ForNonExistent()` - Verifies 404 for non-existent groups

4. **Frontend Route Tests**
   - `testFrontendGroupsListRouteExists()` - Verifies /crud6/groups route is accessible
   - `testFrontendSingleGroupRouteExists()` - Verifies /crud6/groups/1 route is accessible

### GitHub Actions Integration Tests

**File**: `.github/workflows/integration-test.yml`

The CI workflow includes:

1. **Environment Setup**
   - Installs UserFrosting 6.0.0-beta.5
   - Installs sprinkle-crud6 package
   - Configures database and environment
   - Runs migrations and seeds data
   - Builds frontend assets

2. **API Endpoint Tests**
   - Tests GET /api/crud6/groups (list endpoint)
   - Tests GET /api/crud6/groups/1 (single record endpoint)
   - Verifies authentication requirements (401 responses)

3. **Frontend Route Tests**
   - Tests /crud6/groups (list page)
   - Tests /crud6/groups/1 (detail page)
   - Verifies pages load correctly

4. **Screenshot Capture**
   - Uses Playwright to capture screenshots
   - Takes full-page screenshots of both routes
   - Uploads screenshots as GitHub Actions artifacts
   - 30-day retention for visual verification

## Running Tests Locally

### Prerequisites

```bash
# Install PHP dependencies
composer install

# Install Node dependencies (for Playwright screenshots)
npm install
```

### Run PHP Unit Tests

```bash
# Run all CRUD6 integration tests
vendor/bin/phpunit app/tests/Controller/CRUD6GroupsIntegrationTest.php

# Run specific test
vendor/bin/phpunit --filter testGroupsListApiReturnsGroups

# Run with verbose output
vendor/bin/phpunit --testdox app/tests/Controller/CRUD6GroupsIntegrationTest.php
```

### Run Manual Integration Tests

To manually test the endpoints in your local environment:

```bash
# Start UserFrosting development server
cd your-userfrosting-app
php bakery serve

# In another terminal, test API endpoints
curl -v http://localhost:8080/api/crud6/groups
curl -v http://localhost:8080/api/crud6/groups/1

# Test frontend routes in browser
# Navigate to: http://localhost:8080/crud6/groups
# Navigate to: http://localhost:8080/crud6/groups/1
```

## Expected Results

### API Endpoints

#### GET /api/crud6/groups

**Unauthenticated Request:**
```
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "title": "Login Required",
  "description": "Please login to access this resource"
}
```

**Authenticated Request (with uri_crud6 permission):**
```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "rows": [
    {
      "id": 1,
      "slug": "users",
      "name": "Users",
      "description": "Default group for users",
      ...
    },
    ...
  ],
  "count": 5,
  "count_filtered": 5
}
```

#### GET /api/crud6/groups/1

**Unauthenticated Request:**
```
HTTP/1.1 401 Unauthorized
```

**Authenticated Request (with uri_crud6 permission):**
```
HTTP/1.1 200 OK
Content-Type: application/json

{
  "id": 1,
  "slug": "users",
  "name": "Users",
  "description": "Default group for users",
  "icon": "fa fa-user",
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "users_count": 5
}
```

**Non-existent Group:**
```
HTTP/1.1 404 Not Found
```

### Frontend Routes

#### /crud6/groups

- Displays list of all groups in a data table
- Shows search, filter, and sort controls
- Includes pagination
- May redirect to login if not authenticated

#### /crud6/groups/1

- Displays details of group with ID 1
- Shows all fields from schema
- Includes edit/delete buttons (if user has permissions)
- May redirect to login if not authenticated

## Screenshot Artifacts

After each CI run, screenshots are uploaded as artifacts:

1. `screenshot_groups_list.png` - Full page screenshot of /crud6/groups
2. `screenshot_group_detail.png` - Full page screenshot of /crud6/groups/1

**To View Screenshots:**

1. **Quick Access**: 
   - Go to: https://github.com/ssnukala/sprinkle-crud6/actions/workflows/integration-test.yml
   - Click on the latest workflow run
   - Check the workflow summary at the top for a direct link to the artifacts

2. **Alternative Method**:
   - Go to GitHub repository → Actions tab
   - Click on the latest "Integration Test with UserFrosting 6" workflow run
   - Scroll to "Artifacts" section at bottom of page
   - Download "integration-test-screenshots.zip"
   - Extract and view PNG files

> **💡 Tip**: The workflow summary includes a direct link with format: `https://github.com/ssnukala/sprinkle-crud6/actions/runs/{RUN_ID}`

> **Note**: Screenshots are retained for 30 days after the workflow run.

## Troubleshooting

### Tests Fail with "Login Required"

This is expected behavior for unauthenticated requests. The tests verify:
- API returns 401 for guests (correct)
- API returns 403 for users without permission (correct)
- API returns 200 with data for authorized users (correct)

### Frontend Routes Return 302 Redirect

This is expected if user is not logged in. The routes will redirect to the login page.

### Screenshots Not Generated

Screenshots require:
- Playwright to be installed: `npx playwright install chromium --with-deps`
- PHP server to be running
- Frontend assets to be built

If screenshots fail, the workflow will continue without failing.

### Database Connection Errors

Ensure:
- MySQL service is running
- Database credentials are correct in `.env`
- Migrations have been run: `php bakery migrate`
- Seeds have been run (see workflow for seed commands)

## Contributing

When adding new tests:

1. Follow existing test patterns
2. Use `AdminTestCase` as base class
3. Use `RefreshDatabase` trait for clean database state
4. Use `WithTestUser` trait for authentication testing
5. Document expected behavior clearly
6. Update this README with new test descriptions

## Related Documentation

- [INTEGRATION_TESTING.md](../../INTEGRATION_TESTING.md) - Full integration testing guide
- [TESTING_GUIDE.md](../../TESTING_GUIDE.md) - General testing guide
- [examples/frontend-usage.md](../../examples/frontend-usage.md) - Frontend route documentation
