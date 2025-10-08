# Integration Test Enhancement Summary

## Overview

This document summarizes the enhancements made to the sprinkle-crud6 integration tests to test API routes and frontend routes with screenshot capture.

## Changes Made

### 1. GitHub Actions Workflow (`.github/workflows/integration-test.yml`)

#### New Steps Added

**Before (Original):**
- Basic PHP server test with simple curl check
- No screenshot capture
- No specific API endpoint testing
- No frontend route testing

**After (Enhanced):**

1. **Install Playwright browsers** (Line 167-170)
   - Installs Chromium with dependencies for screenshots
   - Uses `npx playwright install chromium --with-deps`

2. **Build frontend assets** (Line 172-175)
   - Builds frontend assets with npm
   - Continues on failure to allow testing without assets

3. **Start PHP development server** (Line 177-188)
   - Starts server in background with logging
   - Saves PID for cleanup
   - Verifies server is running

4. **Test API endpoint - Groups List** (Line 190-204)
   - Tests `GET /api/crud6/groups`
   - Verifies 401 response for unauthenticated requests
   - Saves response for debugging

5. **Test API endpoint - Single Group** (Line 206-220)
   - Tests `GET /api/crud6/groups/1`
   - Verifies 401 response for unauthenticated requests
   - Saves response for debugging

6. **Test Frontend route - Groups List Page** (Line 222-236)
   - Tests `/crud6/groups` route
   - Follows redirects
   - Verifies 200 response
   - Reports page size

7. **Test Frontend route - Single Group Page** (Line 238-252)
   - Tests `/crud6/groups/1` route
   - Follows redirects
   - Verifies 200 response
   - Reports page size

8. **Take screenshots of frontend pages** (Line 254-307)
   - Creates Node.js script with Playwright
   - Takes full-page screenshot of `/crud6/groups`
   - Takes full-page screenshot of `/crud6/groups/1`
   - Uses 1280x720 viewport
   - Handles errors gracefully

9. **Upload screenshots as artifacts** (Line 309-316)
   - Uploads screenshots to GitHub Actions artifacts
   - 30-day retention period
   - Available for download from workflow run

10. **Stop PHP server** (Line 318-323)
    - Cleanup step to stop server
    - Runs even if tests fail

11. **Enhanced Summary** (Line 325-341)
    - Documents all new tests
    - Notes authentication behavior
    - Explains screenshot availability

### 2. PHP Unit Test Suite (`app/tests/Controller/CRUD6GroupsIntegrationTest.php`)

**New comprehensive test class with 10 test methods:**

#### Authentication Tests (401 for guests)
- `testGroupsListApiRequiresAuthentication()` - List endpoint
- `testSingleGroupApiRequiresAuthentication()` - Single record endpoint

#### Authorization Tests (403 without permission)
- `testGroupsListApiRequiresPermission()` - List endpoint
- `testSingleGroupApiRequiresPermission()` - Single record endpoint

#### Data Retrieval Tests
- `testGroupsListApiReturnsGroups()` - Verifies list returns data
- `testSingleGroupApiReturnsGroup()` - Verifies single record structure
  - Validates: id, slug, name fields
  - Checks data matches factory values

#### Error Handling Tests
- `testSingleGroupApiReturns404ForNonExistent()` - Non-existent record

#### Frontend Route Tests
- `testFrontendGroupsListRouteExists()` - List page route
- `testFrontendSingleGroupRouteExists()` - Detail page route

### 3. Documentation Updates

#### INTEGRATION_TESTING.md
**Added Section:** "Automated Integration Tests"
- Documents API endpoint tests
- Documents frontend route tests
- Documents screenshot capture process
- Instructions for running tests locally
- Instructions for viewing CI results
- Information about screenshot artifacts

#### TESTING_GUIDE.md
**Enhanced Section:** "Integration Tests"
- Documents new CRUD6 integration tests
- Lists all test endpoints
- Instructions for running specific tests
- Instructions for viewing screenshots
- Notes about 30-day artifact retention

#### CRUD6_INTEGRATION_TEST_README.md (New File)
**Comprehensive test documentation including:**
- Test suite overview
- Description of all test methods
- Expected API responses with examples
- Expected frontend behavior
- Screenshot artifact instructions
- Troubleshooting guide
- Contributing guidelines

## Test Coverage Matrix

| Endpoint/Route | Authentication | Authorization | Data Retrieval | Error Handling | Screenshot |
|----------------|---------------|---------------|----------------|----------------|------------|
| GET /api/crud6/groups | ✅ 401 | ✅ 403 | ✅ Returns data | - | - |
| GET /api/crud6/groups/1 | ✅ 401 | ✅ 403 | ✅ Returns record | ✅ 404 | - |
| /crud6/groups | - | - | ✅ Loads page | - | ✅ Full page |
| /crud6/groups/1 | - | - | ✅ Loads page | - | ✅ Full page |

## Artifacts Generated

### GitHub Actions Artifacts
1. **integration-test-screenshots.zip**
   - `screenshot_groups_list.png` - Full page screenshot of groups list
   - `screenshot_group_detail.png` - Full page screenshot of group detail
   - Available for 30 days after workflow run
   - Download from Actions → Workflow Run → Artifacts section

### Test Reports
- PHPUnit test results in CI logs
- API response samples in workflow logs
- Page size reports in workflow logs

## How to Use

### Running Tests Locally

```bash
# Run all CRUD6 integration tests
vendor/bin/phpunit app/tests/Controller/CRUD6GroupsIntegrationTest.php

# Run specific test
vendor/bin/phpunit --filter testGroupsListApiReturnsGroups

# Run with detailed output
vendor/bin/phpunit --testdox app/tests/Controller/CRUD6GroupsIntegrationTest.php
```

### Viewing CI Results

1. Go to GitHub repository
2. Navigate to **Actions** tab
3. Select latest "Integration Test with UserFrosting 6" run
4. View test results in workflow logs
5. Download screenshot artifacts from **Artifacts** section

### Understanding Test Results

**Expected Behaviors:**
- ✅ API returns 401 for unauthenticated requests
- ✅ API returns 403 for unauthorized users
- ✅ API returns 200 with data for authorized users
- ✅ Frontend routes load (may redirect to login)
- ✅ Screenshots capture visual state of pages

## Benefits

### For Developers
- Automated verification of API endpoints
- Visual confirmation of frontend routes
- Catches authentication/authorization issues
- Validates data structure and content

### For QA/Testing
- Screenshots provide visual evidence
- No manual testing required for basic checks
- Quick verification of changes
- Historical artifact retention

### For CI/CD
- Automated testing on every push/PR
- Prevents regressions
- Validates integration with UserFrosting 6
- Fast feedback loop (full test ~5 minutes)

## Next Steps

To run these tests on your changes:

1. **Push changes to GitHub** - Workflow runs automatically
2. **Check Actions tab** - View test progress
3. **Review logs** - See detailed test results
4. **Download screenshots** - Visual verification
5. **Fix issues** - If tests fail, review logs and fix

## Related Files

- `.github/workflows/integration-test.yml` - CI workflow
- `app/tests/Controller/CRUD6GroupsIntegrationTest.php` - PHP unit tests
- `app/tests/Controller/CRUD6_INTEGRATION_TEST_README.md` - Detailed test docs
- `INTEGRATION_TESTING.md` - Full integration guide
- `TESTING_GUIDE.md` - General testing guide
