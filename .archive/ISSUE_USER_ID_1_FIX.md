# Integration Test Fix: User ID 1 Modification Issue

**Date:** 2025-11-22  
**Related Workflow:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19586785625

## Problem Statement

The integration test workflow was failing because it attempted to update and delete user ID 1, which is the logged-in admin user. This caused validation errors as UserFrosting prevents users from modifying or deleting themselves for security reasons.

## Symptoms

From the failed workflow run (19586785625):
- 15 API tests were failing with status 400 instead of expected 200
- Failed tests included:
  - `users_create` (400 instead of 200)
  - `users_update` (400 instead of 200)
  - `users_update_field` (400 instead of 200)
  - `users_custom_action` (400 instead of 200)
  - `users_relationship_attach` (400 instead of 200)
  - `users_relationship_detach` (400 instead of 200)
  - `users_delete` (400 instead of 200)
  - Several group/role/permission create/update/delete operations

## Root Cause

The test configuration in `.github/config/integration-test-paths.json` was using user ID 1 for all user operations. User ID 1 is assigned to the admin user created during test setup, and this is the same user that logs in to run the authenticated tests. UserFrosting's security model prevents users from modifying or deleting their own accounts.

## Solution

### 1. Create a Second Test User

Added a new step in `.github/workflows/integration-test.yml` to create a second test user:

```yaml
- name: Create test user for modification tests
  run: |
    cd userfrosting
    # Create a second user that can be safely modified/deleted in tests
    # This prevents issues with modifying the logged-in admin user (ID 1)
    php bakery create:user \
      --username=testuser \
      --password=TestPass123 \
      --email=testuser@example.com \
      --firstName=Test \
      --lastName=User
    echo "✅ Test user created (testuser) - will be used for modification/deletion tests"
```

This step creates:
- **User ID 1:** admin (used for authentication)
- **User ID 2:** testuser (used for modification/deletion operations)

### 2. Update Test Configuration

Modified `.github/config/integration-test-paths.json` to use user ID 2 for all modification operations:

#### Authenticated API Tests
- `users_update`: `/api/crud6/users/1` → `/api/crud6/users/2`
- `users_update_field`: `/api/crud6/users/1/flag_enabled` → `/api/crud6/users/2/flag_enabled`
- `users_custom_action`: `/api/crud6/users/1/a/reset_password` → `/api/crud6/users/2/a/reset_password`
- `users_relationship_attach`: `/api/crud6/users/1/roles` → `/api/crud6/users/2/roles`
- `users_relationship_detach`: `/api/crud6/users/1/roles` → `/api/crud6/users/2/roles`
- `users_delete`: `/api/crud6/users/1` → `/api/crud6/users/2`

#### Frontend Tests
- `users_detail`: `/crud6/users/1` → `/crud6/users/2`

#### Unauthenticated Tests
- `users_single`: `/api/crud6/users/1` → `/api/crud6/users/2`
- `users_update`: `/api/crud6/users/1` → `/api/crud6/users/2`
- `users_delete`: `/api/crud6/users/1` → `/api/crud6/users/2`
- Frontend `users_detail`: `/crud6/users/1` → `/crud6/users/2`

### 3. Documentation

Added explanatory notes to each modified endpoint in the configuration file to document the reason for using user ID 2.

## Testing Strategy

The fix maintains the following testing approach:

1. **Admin user (ID 1)** is used for:
   - Authentication (login)
   - Read operations (safe)
   - Testing access to lists and schemas

2. **Test user (ID 2)** is used for:
   - All modification operations (update, update_field, custom_action)
   - All relationship operations (attach, detach)
   - Delete operations
   - Detail page viewing

This separation ensures:
- Tests don't attempt to modify the logged-in user
- Tests can safely delete users without breaking authentication
- The test environment remains consistent across runs

## Expected Impact

With this fix:
- All 15 previously failing API tests should now pass
- The logged-in admin user is protected from modification/deletion
- Tests more accurately reflect real-world usage where users cannot modify themselves
- Integration test workflow should complete successfully

## Files Modified

1. `.github/workflows/integration-test.yml` - Added test user creation step
2. `.github/config/integration-test-paths.json` - Updated user IDs for modification operations

## Commit Hash

655e041 - Fix integration tests - use user ID 2 for modification operations

## Related Issues

- Original failing workflow: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19586785625
- Problem identified in issue comment: "i think this may be because we are updating user id 1 which is the login user"
