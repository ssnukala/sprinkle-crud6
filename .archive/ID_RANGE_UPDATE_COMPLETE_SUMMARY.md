# Complete ID Range Update - All Changes Summary

**Date:** 2025-12-12  
**Purpose:** Update all test data to use IDs 100+ to avoid conflicts with PHP seed data

## Changes Overview

This update ensures complete separation between system data (PHP seeds) and test data (SQL seeds) by using different ID ranges:

- **IDs 1-99:** Reserved for PHP seed data (roles, permissions, groups, etc.)
- **IDs 100+:** Test data for integration tests

## Files Modified (6 total)

### 1. Integration Test Path Configuration
**File:** `.github/config/integration-test-paths.json`

**Changes:**
- Updated all API endpoint paths to use IDs 100+
- Updated all frontend paths to use IDs 100+
- Added note about ID range in file header

**Examples:**
```json
// Before
"path": "/api/crud6/users/2"
"path": "/crud6/users/2"

// After  
"path": "/api/crud6/users/100"
"path": "/crud6/users/100"
```

**Models Updated:**
- users (100, 101, 102)
- groups (100, 101, 102)
- roles (100, 101, 102)
- permissions (100, 101, 102)
- products (100, 101, 102)
- categories (100, 101, 102)
- orders (100, 101, 102)
- activities (100, 101, 102)
- contacts (100, 101, 102)

### 2. Integration Test Workflow
**File:** `.github/workflows/integration-test.yml`

**Changes:**
- Updated echo message: `"IDs start from 100"` (was: `"IDs start from 2"`)

### 3. Scripts Documentation
**File:** `.github/scripts/README.md`

**Changes:**
- Updated all references from "ID 2" to "ID 100"
- Updated "starts from 2" to "starts from 100"
- Updated "ID >= 2" to "ID >= 100"

### 4. Configuration Documentation
**File:** `.github/testing-framework/docs/CONFIGURATION.md`

**Changes:**
- Updated consistent ID guidelines: "Use ID 100+ for tests (IDs 1-99 reserved for PHP seeds)"

### 5. Relationship Actions Documentation
**File:** `docs/RELATIONSHIP_ACTIONS.md`

**Changes:**
- Updated example: "role ID 100" (was: "role ID 2")
- Updated test verification to check for role ID 100

### 6. Integration Testing Quick Start
**File:** `INTEGRATION_TESTING_QUICK_START.md`

**Changes:**
- Updated: "starting from ID 100" (was: "starting from ID 2")
- Updated delete operations:
  - "Delete users with ID >= 100"
  - "Delete groups with ID >= 100"
  - "Delete roles with ID >= 100"

## Search and Replace Patterns Used

```bash
# API and frontend paths
s|/users/2|/users/100|g
s|/users/3|/users/101|g
s|/users/4|/users/102|g
s|/groups/2|/groups/100|g
s|/roles/2|/roles/100|g
s|/permissions/2|/permissions/100|g
s|/products/2|/products/100|g
s|/categories/2|/categories/100|g
s|/orders/2|/orders/100|g
s|/activities/2|/activities/100|g
s|/contacts/2|/contacts/100|g

# Documentation
s/ID 2/ID 100/g
s/user ID 2/user ID 100/g
s/role ID 2/role ID 100/g
s/starts from 2/starts from 100/g
s/starting from ID 2/starting from ID 100/g
s/from ID 2/from ID 100/g
s/ID >= 2/ID >= 100/g
```

## Impact on Tests

### API Routes
All authenticated API tests now use IDs 100+:
- GET `/api/crud6/users/100` - Get user
- PUT `/api/crud6/users/100` - Update user
- PUT `/api/crud6/users/100/user_name` - Update field
- POST `/api/crud6/users/100/a/toggle_enabled` - Custom action
- POST `/api/crud6/users/100/roles` - Attach relationship
- DELETE `/api/crud6/users/100` - Delete user

### Frontend Routes
All frontend navigation uses IDs 100+:
- `/crud6/users/100` - User detail page
- `/crud6/groups/100` - Group detail page
- `/crud6/roles/100` - Role detail page
- `/crud6/permissions/100` - Permission detail page
- `/crud6/products/100` - Product detail page

### Screenshot Tests
All screenshot tests now capture pages with IDs 100+:
- `user_detail.png` - Shows user ID 100
- `group_detail.png` - Shows group ID 100
- `role_detail.png` - Shows role ID 100

## Validation

### No Conflicts
- PHP seeds create system data with IDs 1-99
- SQL seeds create test data with IDs 100+
- No overlap, no conflicts

### Predictable IDs
- Test data always starts from 100
- Relationships reference known IDs (100, 101, 102)
- Easy to debug and maintain

### Safe Delete Operations
- DELETE tests can safely use IDs >= 100
- System data (IDs 1-99) is never touched
- No risk of deleting crud6-admin role or permissions

## Files NOT Modified (Archived Documentation)

The following files in `.archive/` were NOT updated as they are historical:
- `INTEGRATION_TEST_CSRF_FIX_COMPLETE_SUMMARY.md`
- `INTEGRATION_TEST_EXECUTION_ORDER.md`
- `SCHEMA_DRIVEN_TESTING_IMPLEMENTATION.md`
- `pre-framework-migration/scripts-backup/README.md`

These files document older approaches and are kept for reference only.

## Verification Checklist

- [x] All API paths updated to IDs 100+
- [x] All frontend paths updated to IDs 100+
- [x] Workflow messages updated
- [x] Documentation updated
- [x] Configuration files updated
- [x] Examples updated
- [x] No hardcoded IDs 2, 3, 4 remain in active code
- [x] ID range clearly documented (1-99 vs 100+)

## Testing Instructions

When the integration test runs:

1. **Check diagnostic output** shows:
   - PHP seeds create crud6-admin role (ID in range 1-99)
   - SQL seeds create test users starting from ID 100
   - No ID conflicts

2. **Verify API routes** work:
   - GET /api/crud6/users/100 returns user data
   - PUT /api/crud6/users/100 updates user
   - DELETE /api/crud6/users/100 removes test user (safe)

3. **Verify frontend routes** work:
   - /crud6/users/100 displays user detail page
   - Screenshots capture correct data

4. **Validate seed integrity**:
   - crud6-admin role exists after all seeds
   - All 6 CRUD6 permissions exist
   - Role-permission assignments correct

## Rollback Instructions

If needed, to rollback to old ID range (2+):

```bash
# Reverse the changes
sed -i 's|/users/100|/users/2|g' .github/config/integration-test-paths.json
sed -i 's|/users/101|/users/3|g' .github/config/integration-test-paths.json
# ... etc for all models

sed -i 's/ID 100/ID 2/g' docs/*.md
sed -i 's/starts from 100/starts from 2/g' docs/*.md
# ... etc for all docs
```

However, this would reintroduce the original bug where SQL seeds overwrite PHP seed data.

## References

- **Main Fix Summary:** `.archive/SEED_VALIDATION_FIX_SUMMARY.md`
- **Integration Test Paths:** `.github/config/integration-test-paths.json`
- **Workflow:** `.github/workflows/integration-test.yml`
- **Quick Start Guide:** `INTEGRATION_TESTING_QUICK_START.md`
