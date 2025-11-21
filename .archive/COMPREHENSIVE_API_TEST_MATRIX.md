# Comprehensive API Test Matrix for CRUD6

## Purpose
This document outlines ALL API paths in CRUD6 and ensures each is tested with both authenticated and unauthenticated users in multiple testing scenarios:
1. **PHPUnit Integration Tests** (automated)
2. **Manual API Testing** (curl/Postman)
3. **Browser Testing** (real frontend usage)

## API Endpoints to Test

Based on `app/src/Routes/CRUD6Routes.php`, the following endpoints need comprehensive testing:

### 1. Config Endpoint (Public - No Auth Required)
- **Path**: `GET /api/crud6/config`
- **Auth**: Not required
- **Tests Needed**:
  - ✅ Returns config without authentication
  - ✅ Returns expected config structure

### 2. Schema Endpoint
- **Path**: `GET /api/crud6/{model}/schema`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific read permission (e.g., `uri_users`)
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200 with schema

### 3. List Endpoint (Sprunje)
- **Path**: `GET /api/crud6/{model}`
- **Query Params**: size, page, sorts, filters, search
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific read permission (e.g., `uri_users`)
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200 with paginated data
  - [ ] Pagination works (page 0, page 1, etc.)
  - [ ] Sorting works
  - [ ] Filtering works
  - [ ] Search works

### 4. Create Endpoint
- **Path**: `POST /api/crud6/{model}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific create permission (e.g., `create_user`)
- **Tests Needed**:
  - ✅ Unauthenticated → 401
  - ✅ Authenticated but no permission → 403
  - ✅ Authenticated with permission → 200 with created record
  - ✅ Validation errors handled correctly
  - ✅ Duplicate records rejected
  - ✅ Default values applied
  - ✅ Relationship actions (on_create) executed

### 5. Read Single Record Endpoint
- **Path**: `GET /api/crud6/{model}/{id}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific read permission (e.g., `uri_users`)
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200 with record data
  - [ ] Non-existent ID → 404

### 6. Update Record Endpoint
- **Path**: `PUT /api/crud6/{model}/{id}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific update permission (e.g., `update_user_field`)
- **Tests Needed**:
  - ✅ Unauthenticated → 401
  - ✅ Authenticated but no permission → 403
  - ✅ Authenticated with permission → 200 with updated record
  - ✅ Partial updates work
  - ✅ Validation errors handled
  - ✅ Readonly fields rejected
  - ✅ Non-existent ID → 404

### 7. Update Single Field Endpoint
- **Path**: `PUT /api/crud6/{model}/{id}/{field}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific update permission (e.g., `update_user_field`)
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200 with updated field
  - [ ] Boolean toggle works (true → false, false → true)
  - [ ] Non-existent field → 500/400
  - [ ] Readonly field → 500/400
  - [ ] Non-existent ID → 404

### 8. Delete Endpoint
- **Path**: `DELETE /api/crud6/{model}/{id}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific delete permission (e.g., `delete_user`)
- **Tests Needed**:
  - ✅ Unauthenticated → 401
  - ✅ Authenticated but no permission → 403
  - ✅ Authenticated with permission → 200/204
  - ✅ Soft delete works (if applicable)
  - ✅ Non-existent ID → 404
  - ✅ Relationship actions (on_delete) executed

### 9. Custom Action Endpoint
- **Path**: `POST /api/crud6/{model}/{id}/a/{actionKey}`
- **Auth**: Required (AuthGuard)
- **Permission**: Action-specific permission (defined in schema)
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200/varies by action
  - [ ] Non-existent action → 404/400
  - [ ] Action logic executes correctly

### 10. Get Related Data Endpoint (Nested)
- **Path**: `GET /api/crud6/{model}/{id}/{relation}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific read permission
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200 with related data
  - [ ] Non-existent relation → 404/400
  - [ ] Pagination works for related data

### 11. Attach Relationship Endpoint
- **Path**: `POST /api/crud6/{model}/{id}/{relation}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific update/relationship permission
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200
  - [ ] Pivot data handled correctly
  - [ ] Invalid related IDs rejected

### 12. Detach Relationship Endpoint
- **Path**: `DELETE /api/crud6/{model}/{id}/{relation}`
- **Auth**: Required (AuthGuard)
- **Permission**: Model-specific update/relationship permission
- **Tests Needed**:
  - [ ] Unauthenticated → 401
  - [ ] Authenticated but no permission → 403
  - [ ] Authenticated with permission → 200
  - [ ] Relationship removed from database

## Testing Matrices by Model

### Users Model
All 12 endpoint types × 3 auth scenarios = 36 test cases

### Roles Model
All 12 endpoint types × 3 auth scenarios = 36 test cases

### Groups Model
All 12 endpoint types × 3 auth scenarios = 36 test cases

### Permissions Model
All 12 endpoint types × 3 auth scenarios = 36 test cases

### Activities Model
All 12 endpoint types × 3 auth scenarios = 36 test cases

## Test Implementation Status

### PHPUnit Tests (Automated)
- [x] CreateActionTest.php - Full coverage
- [x] DeleteActionTest.php - Full coverage
- [x] EditActionTest.php - Full coverage
- [x] SchemaActionTest.php - Full coverage
- [x] SprunjeActionTest.php - ✅ **UPDATED** with auth tests
- [ ] UpdateFieldActionTest.php - ⚠️ **NEEDS IMPLEMENTATION** (currently all skipped)
- [ ] RelationshipActionTest.php - ⚠️ **MISSING** (needs creation)
- [ ] CustomActionTest.php - ⚠️ **MISSING** (needs creation)
- [x] ConfigActionTest.php - Full coverage (no auth required)

### Integration Tests (Automated)
- [x] SchemaBasedApiTest.php - Comprehensive multi-model testing
- [x] NestedEndpointsTest.php - Tests GET /{id}/{relation}
- [x] RoleUsersRelationshipTest.php - Tests relationship endpoints
- [x] BooleanToggleSchemaTest.php - Schema validation
- [x] RedundantApiCallsTest.php - Performance testing

### Manual Testing Documentation
- [ ] **NEEDS CREATION**: curl command reference for all endpoints
- [ ] **NEEDS CREATION**: Postman collection
- [ ] **NEEDS CREATION**: Manual test checklist

## Summary of Gaps

### Critical Gaps (High Priority)
1. **UpdateFieldActionTest.php** - All tests skipped, need real implementation
2. **RelationshipAction** - No dedicated test file for attach/detach
3. **CustomActionController** - No dedicated test file for custom actions
4. **Manual testing documentation** - Need curl/Postman examples

### Medium Priority
1. **Read single record endpoint** - No dedicated test (covered in EditActionTest but not explicitly)
2. **Nested GET endpoints** - Partially covered but need comprehensive auth testing

### Already Covered Well
1. Create, Update, Delete - Full auth coverage ✅
2. Schema endpoint - Full auth coverage ✅
3. List endpoint - NOW has full auth coverage ✅
4. Config endpoint - Full coverage ✅

## Next Steps
1. ✅ Add auth tests to SprunjeActionTest.php
2. Implement UpdateFieldActionTest.php with real tests (remove skips)
3. Create RelationshipActionTest.php
4. Create CustomActionTest.php
5. Create manual testing documentation with curl examples
6. Create Postman collection for all endpoints
7. Ensure SchemaBasedApiTest.php covers ALL models (users, roles, groups, permissions, activities)
