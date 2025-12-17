# CI Run #20293538413 - Detailed Error Classification

## Error Distribution by Test Suite

| Test Suite | Total | Passed | Failed | Errors | Warnings | Pass Rate |
|------------|-------|--------|--------|--------|----------|-----------|
| ApiActionModelValidation | 11 | 11 | 0 | 0 | 0 | 100% |
| BreadcrumbTrait | 10 | 10 | 0 | 0 | 0 | 100% |
| C R U D 6 Routes | 5 | 5 | 0 | 0 | 0 | 100% |
| C R U D 6 Schema | 16 | 16 | 0 | 0 | 0 | 100% |
| CRUD6Model | 22 | 22 | 0 | 0 | 0 | 100% |
| CRUD6SprunjeIntegration | 13 | 13 | 0 | 0 | 0 | 100% |
| CRUD6SprunjeSearch | 8 | 6 | 0 | 2 | 0 | 75% |
| CRUD6UsersIntegration | 14 | 2 | 12 | 0 | 0 | 14% |
| ConfigAction | 3 | 2 | 0 | 1 | 0 | 67% |
| CreateAction | 9 | 2 | 7 | 0 | 0 | 22% |
| CreateActionSignature | 4 | 4 | 0 | 0 | 0 | 100% |
| CustomAction | 6 | 2 | 2 | 0 | 2 | 33% |
| DatabaseConfiguration | 8 | 8 | 0 | 0 | 0 | 100% |
| DebugMode | 4 | 4 | 0 | 0 | 0 | 100% |
| DebugModeIntegration | 1 | 0 | 0 | 0 | 1 | 0% |
| DefaultSeeds | 4 | 3 | 1 | 0 | 0 | 75% |
| DeleteAction | 7 | 1 | 5 | 0 | 1 | 14% |
| EditAction | 13 | 1 | 12 | 0 | 0 | 8% |
| FrontendUserWorkflow | 10 | 0 | 10 | 0 | 0 | 0% |
| ListableFields | 5 | 3 | 2 | 0 | 0 | 60% |
| NestedEndpoints | 6 | 1 | 5 | 0 | 0 | 17% |
| RedundantApiCalls | 9 | 0 | 8 | 0 | 1 | 0% |
| RelationshipAction | 9 | 2 | 7 | 0 | 0 | 22% |
| RoleUsersRelationship | 3 | 0 | 3 | 0 | 0 | 0% |
| SchemaAction | 6 | 6 | 0 | 0 | 0 | 100% |
| SchemaBasedApi | 5 | 0 | 4 | 0 | 1 | 0% |
| Schema* (7 suites) | 55 | 55 | 0 | 0 | 0 | 100% |
| SprunjeAction | 9 | 2 | 4 | 0 | 3 | 22% |
| UpdateFieldAction | 7 | 1 | 5 | 0 | 1 | 14% |

---

## Error Classification by Type

### Type 1: Expected Status Code Mismatches (82 failures)

All failing with pattern: `Failed asserting that 500 is identical to [200|201|400|404]`

#### Breakdown by Expected Status
- **500 vs 200**: 65 failures
- **500 vs 201**: 3 failures
- **500 vs 400**: 5 failures
- **500 vs 404**: 9 failures

#### By Controller Action
| Action | Failures | Percentage |
|--------|----------|------------|
| Read/List | 28 | 34% |
| Update | 22 | 27% |
| Create | 12 | 15% |
| Delete | 10 | 12% |
| Relationship | 10 | 12% |

---

### Type 2: Expected Error Message Mismatches (15 failures)

Pattern: Expected "Access Denied" but got "We've sensed a great disturbance in the Force"

#### Test Methods
1. `CRUD6UsersIntegration::testSingleUserApiRequiresPermission`
2. `CRUD6UsersIntegration::testToggleFlagEnabledRequiresPermission`
3. `CustomAction::testCustomActionRequiresPermission`
4. `DeleteAction::testDeleteRequiresPermission`
5. `EditAction::testReadRequiresPermission`
6. `EditAction::testUpdateRequiresPermission`
7. `RelationshipAction::testAttachRelationshipRequiresPermission`
8. `RelationshipAction::testDetachRelationshipRequiresPermission`
9. `UpdateFieldAction::testUpdateFieldRequiresPermission`
10-15. Similar permission test failures

---

### Type 3: SQL/Database Errors (4 failures)

#### Error Pattern
```sql
SQLSTATE[HY000]: General error: 1 no such column: groups.
SQL: select count(*) as aggregate from "groups" where "groups"."" is null
```

#### Affected Tests
1. `CRUD6SprunjeSearchTest::testSearchWithNoSearchableFields`
2. `CRUD6SprunjeSearchTest::testSearchWithNoFilterableFields`
3. Two related test cases

#### Analysis
- Column name is empty string `""`
- Occurs when searchable/filterable field arrays are empty
- Query builder doesn't validate before adding WHERE clause

---

### Type 4: DI Container Errors (1 error)

```
DI\NotFoundException: No entry or class found for 'config'
```

**Test**: `ConfigAction::testConfigEndpointReturnsDebugModeWhenEnabled`  
**Location**: `app/tests/Controller/ConfigActionTest.php:50`

---

### Type 5: Field Validation Errors (2 failures)

#### Test 1: Password Field Listability
```
Password field should not be listable by default
Failed asserting that an array does not contain 'password'.
```

#### Test 2: Readonly Field Filtering
```
Failed asserting that actual size 3 matches expected size 1.
```

---

### Type 6: Data Assertion Errors (3 failures)

#### Test 1: Seed Count Mismatch
```
Failed asserting that actual size 22 matches expected size 6.
```
**Test**: `DefaultSeeds::testDefaultPermissionsSeed`

#### Test 2: Error Message Content
```
Error should mention the field name
Failed asserting that 'Oops, looks like our server might have goofed...' contains "nonexistent_field"
```

#### Test 3: Readonly Field Error
```
Error should mention field is readonly or not editable
Failed asserting that false is true.
```

---

### Type 7: Type Errors (3 warnings)

```
TypeError: array_column(): Argument #1 ($array) must be of type array, null given
```

**Affected Tests**:
1. `SprunjeAction::testListUsersSortingWorks` (line 161)
2. `SprunjeAction::testListUsersSearchWorks` (line 230)
3. One additional occurrence

---

### Type 8: Route Validation Errors (2 failures)

```
Frontend route should exist and return 200, 302, or 401, got 404
Failed asserting that false is true.
```

**Tests**:
1. `CRUD6UsersIntegration::testFrontendUsersListRouteExists`
2. `CRUD6UsersIntegration::testFrontendSingleUserRouteExists`

---

## Error Frequency by HTTP Status Code

| Status Code | Frequency | Percentage | Issue Type |
|-------------|-----------|------------|------------|
| 500 | 100+ | 89% | Server errors (main issue) |
| 403 | 0 | 0% | Expected but not returned |
| 404 | 2 | 2% | Route missing errors |
| Other | 10 | 9% | SQL, DI, Type errors |

---

## Critical Files Requiring Investigation

### Controllers (All returning 500)
1. `app/src/Controller/ApiAction.php`
2. `app/src/Controller/CreateAction.php`
3. `app/src/Controller/EditAction.php`
4. `app/src/Controller/DeleteAction.php`
5. `app/src/Controller/SprunjeAction.php`
6. `app/src/Controller/RelationshipAction.php`
7. `app/src/Controller/UpdateFieldAction.php`
8. `app/src/Controller/CustomActionController.php`

### Sprunje (SQL Errors)
1. `app/src/Sprunje/CRUD6Sprunje.php`

### Middleware (Auth Errors)
1. `app/src/Middlewares/CRUD6Injector.php` (if exists)
2. Auth middleware configuration

### Services (DI Errors)
1. `app/src/ServicesProvider/SchemaServiceProvider.php`
2. DI configuration files

### Base Classes (Field Filtering)
1. `app/src/Controller/Base.php`
2. `app/src/ServicesProvider/SchemaService.php`

---

## Timeline of Test Execution

- **Duration**: ~47 seconds
- **Start**: 2025-12-17T06:14:35Z
- **End**: 2025-12-17T06:15:22Z
- **Environment**: PHP 8.4, MySQL 8.0, Ubuntu Latest

---

## Test Coverage Analysis

### Well-Tested Areas (100% Pass Rate)
- ✅ Schema validation and loading
- ✅ Model configuration
- ✅ Route definitions
- ✅ Database configuration
- ✅ Schema caching and filtering
- ✅ Debug mode functionality

### Problematic Areas (<50% Pass Rate)
- ❌ User CRUD operations (14%)
- ❌ Edit operations (8%)
- ❌ Delete operations (14%)
- ❌ Frontend workflows (0%)
- ❌ API redundancy checks (0%)
- ❌ Nested endpoints (17%)

---

## Recommended Investigation Order

1. **Enable Debug Mode** - See actual exceptions
2. **Check Latest Commits** - Identify what changed
3. **Review Exception Handler** - Find where 500s originate
4. **Test Single Controller** - Isolate the problem
5. **Fix Root Cause** - Apply solution
6. **Re-run Tests** - Verify fix
7. **Address Secondary Issues** - SQL, auth, filtering

---

## Expected vs Actual Behavior Summary

| Expected Behavior | Actual Behavior | Count |
|-------------------|-----------------|-------|
| HTTP 200 OK | HTTP 500 Error | 65 |
| "Access Denied" | "Force" error | 15 |
| HTTP 404 Not Found | HTTP 500 Error | 9 |
| Valid SQL query | Empty column name | 4 |
| HTTP 400 Bad Request | HTTP 500 Error | 5 |
| Array data | Null value | 3 |
| Config service | DI not found | 1 |
| Password hidden | Password visible | 1 |
| Route exists | Route missing | 2 |

---

## Conclusion

The primary issue is a **systemic 500 error** affecting all CRUD operations. This suggests a fundamental problem with exception handling or middleware configuration that was likely introduced in a recent commit. Once this root cause is fixed, approximately **89% of the failures** should be resolved automatically.

The remaining issues (SQL validation, field filtering, DI configuration) are independent problems that can be addressed separately.
