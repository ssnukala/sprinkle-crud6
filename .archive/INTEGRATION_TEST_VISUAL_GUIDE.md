# CRUD6 Integration Test Visual Guide

## Test Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                   GitHub Actions CI Workflow                     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  1. Setup Environment                                            │
│  ├─ Install PHP 8.1                                             │
│  ├─ Install Node.js 20                                          │
│  ├─ Setup MySQL 8.0                                             │
│  └─ Install UserFrosting 6.0.0-beta.5                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  2. Install sprinkle-crud6                                       │
│  ├─ Composer: ssnukala/sprinkle-crud6                          │
│  ├─ NPM: @ssnukala/sprinkle-crud6                              │
│  └─ Configure MyApp.php with CRUD6::class                       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  3. Database Setup                                               │
│  ├─ Run migrations                                              │
│  ├─ Seed default groups                                         │
│  ├─ Seed default permissions                                    │
│  ├─ Seed default roles                                          │
│  └─ Create groups.json schema                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  4. Prepare Testing Environment                                  │
│  ├─ Install Playwright browsers                                 │
│  ├─ Build frontend assets                                       │
│  └─ Start PHP development server (localhost:8080)              │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  5. Test API Endpoints                                           │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ GET /api/crud6/groups                                 │      │
│  │ ├─ Test unauthenticated → Expect 401                │      │
│  │ ├─ Verify error message                             │      │
│  │ └─ Save response for debugging                      │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ GET /api/crud6/groups/1                              │      │
│  │ ├─ Test unauthenticated → Expect 401                │      │
│  │ ├─ Verify error message                             │      │
│  │ └─ Save response for debugging                      │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  6. Test Frontend Routes                                         │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ /crud6/groups                                         │      │
│  │ ├─ Load page (with redirects)                       │      │
│  │ ├─ Verify 200 status                                │      │
│  │ └─ Report page size                                 │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ /crud6/groups/1                                       │      │
│  │ ├─ Load page (with redirects)                       │      │
│  │ ├─ Verify 200 status                                │      │
│  │ └─ Report page size                                 │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  7. Capture Screenshots with Playwright                          │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ Screenshot: /crud6/groups                            │      │
│  │ ├─ Open page in Chromium                            │      │
│  │ ├─ Wait for network idle                            │      │
│  │ ├─ Capture full page screenshot                     │      │
│  │ └─ Save as screenshot_groups_list.png               │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ Screenshot: /crud6/groups/1                          │      │
│  │ ├─ Open page in Chromium                            │      │
│  │ ├─ Wait for network idle                            │      │
│  │ ├─ Capture full page screenshot                     │      │
│  │ └─ Save as screenshot_group_detail.png              │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  8. Upload Artifacts                                             │
│  ├─ Package screenshots as zip                                  │
│  ├─ Upload to GitHub Actions artifacts                          │
│  └─ Set 30-day retention                                        │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  9. Cleanup & Summary                                            │
│  ├─ Stop PHP server                                             │
│  └─ Display test summary with results                           │
└─────────────────────────────────────────────────────────────────┘
```

## PHP Unit Tests Flow

```
┌─────────────────────────────────────────────────────────────────┐
│         CRUD6GroupsIntegrationTest Test Suite                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Setup                                                           │
│  ├─ Refresh database (clean slate)                             │
│  ├─ Load factories                                              │
│  └─ Initialize test environment                                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Authentication Tests (No User)                                  │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testGroupsListApiRequiresAuthentication              │      │
│  │ ├─ No user logged in                                │      │
│  │ ├─ Request: GET /api/crud6/groups                   │      │
│  │ └─ Assert: 401 Unauthorized                         │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testSingleGroupApiRequiresAuthentication             │      │
│  │ ├─ Create test group                                │      │
│  │ ├─ No user logged in                                │      │
│  │ ├─ Request: GET /api/crud6/groups/{id}              │      │
│  │ └─ Assert: 401 Unauthorized                         │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Authorization Tests (User Without Permission)                   │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testGroupsListApiRequiresPermission                  │      │
│  │ ├─ Create user (no permissions)                     │      │
│  │ ├─ Act as user                                       │      │
│  │ ├─ Request: GET /api/crud6/groups                   │      │
│  │ └─ Assert: 403 Forbidden                            │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testSingleGroupApiRequiresPermission                 │      │
│  │ ├─ Create user (no permissions)                     │      │
│  │ ├─ Create test group                                │      │
│  │ ├─ Act as user                                       │      │
│  │ ├─ Request: GET /api/crud6/groups/{id}              │      │
│  │ └─ Assert: 403 Forbidden                            │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Data Retrieval Tests (Authorized User)                         │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testGroupsListApiReturnsGroups                       │      │
│  │ ├─ Create user with uri_crud6 permission            │      │
│  │ ├─ Create 3 test groups                             │      │
│  │ ├─ Act as user                                       │      │
│  │ ├─ Request: GET /api/crud6/groups                   │      │
│  │ ├─ Assert: 200 OK                                   │      │
│  │ └─ Assert: Response contains groups data            │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testSingleGroupApiReturnsGroup                       │      │
│  │ ├─ Create user with uri_crud6 permission            │      │
│  │ ├─ Create test group (slug: test-group)             │      │
│  │ ├─ Act as user                                       │      │
│  │ ├─ Request: GET /api/crud6/groups/{id}              │      │
│  │ ├─ Assert: 200 OK                                   │      │
│  │ ├─ Assert: Response has id, slug, name fields       │      │
│  │ └─ Assert: Data matches created group                │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Error Handling Tests                                            │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testSingleGroupApiReturns404ForNonExistent          │      │
│  │ ├─ Create user with uri_crud6 permission            │      │
│  │ ├─ Act as user                                       │      │
│  │ ├─ Request: GET /api/crud6/groups/999999            │      │
│  │ └─ Assert: 404 Not Found                            │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Frontend Route Tests                                            │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testFrontendGroupsListRouteExists                    │      │
│  │ ├─ Request: GET /crud6/groups                        │      │
│  │ └─ Assert: Status is 200, 302, or 401               │      │
│  └──────────────────────────────────────────────────────┘      │
│                                                                  │
│  ┌──────────────────────────────────────────────────────┐      │
│  │ testFrontendSingleGroupRouteExists                   │      │
│  │ ├─ Create test group                                │      │
│  │ ├─ Request: GET /crud6/groups/{id}                  │      │
│  │ └─ Assert: Status is 200, 302, or 401               │      │
│  └──────────────────────────────────────────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
```

## Test Coverage Matrix

| Test Area            | API Endpoint              | Frontend Route        | Status Code | Screenshot |
|---------------------|---------------------------|-----------------------|-------------|------------|
| **Authentication**   |                          |                       |             |            |
| Guest user list     | GET /api/crud6/groups     | -                     | 401 ✅      | -          |
| Guest user single   | GET /api/crud6/groups/1   | -                     | 401 ✅      | -          |
| **Authorization**    |                          |                       |             |            |
| No permission list  | GET /api/crud6/groups     | -                     | 403 ✅      | -          |
| No permission single| GET /api/crud6/groups/1   | -                     | 403 ✅      | -          |
| **Data Retrieval**   |                          |                       |             |            |
| List all groups     | GET /api/crud6/groups     | -                     | 200 ✅      | -          |
| Get single group    | GET /api/crud6/groups/1   | -                     | 200 ✅      | -          |
| **Error Handling**   |                          |                       |             |            |
| Non-existent group  | GET /api/crud6/groups/999 | -                     | 404 ✅      | -          |
| **Frontend Routes**  |                          |                       |             |            |
| Groups list page    | -                         | /crud6/groups         | 200/302 ✅  | ✅         |
| Group detail page   | -                         | /crud6/groups/1       | 200/302 ✅  | ✅         |

## Screenshot Examples

### Groups List Page
![Groups List](./docs/images/screenshot_groups_list_example.png)
*Screenshot shows the groups list page with data table, search, filter, and action buttons*

### Group Detail Page
![Group Detail](./docs/images/screenshot_group_detail_example.png)
*Screenshot shows the group detail page with all fields and related data*

## How to Access Test Results

### 1. GitHub Actions Workflow Run
```
Repository → Actions → Integration Test with UserFrosting 6
```

### 2. View Test Logs
```
Workflow Run → Jobs → integration-test → Steps
```

### 3. Download Screenshots
```
Workflow Run → Scroll to bottom → Artifacts section → Download integration-test-screenshots.zip
```

### 4. Run Tests Locally
```bash
vendor/bin/phpunit app/tests/Controller/CRUD6GroupsIntegrationTest.php --testdox
```

## Test Execution Time

| Stage                    | Approximate Time |
|--------------------------|------------------|
| Environment setup        | 2-3 minutes      |
| Database migrations      | 30 seconds       |
| API tests                | 10 seconds       |
| Frontend tests           | 10 seconds       |
| Screenshot capture       | 20 seconds       |
| **Total**                | **~4 minutes**   |

## Success Criteria

✅ All API authentication tests return 401
✅ All API authorization tests return 403
✅ All API data tests return 200 with valid data
✅ All error handling tests return 404
✅ All frontend routes return 200 or 302
✅ Screenshots captured successfully
✅ No PHP errors in logs
✅ No JavaScript errors in console
