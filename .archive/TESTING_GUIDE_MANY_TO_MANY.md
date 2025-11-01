# Testing Guide: Many-to-Many Relationships Fix

## Overview
This guide provides step-by-step instructions for testing the many-to-many relationship fix in a live UserFrosting 6 environment with the sprinkle-c6admin schema.

## Prerequisites

1. **UserFrosting 6 Application** with:
   - sprinkle-admin installed
   - sprinkle-c6admin installed
   - sprinkle-crud6 (this package) installed
   - Database with users, roles, permissions tables
   - Sample data in pivot tables (role_user, role_permission)

2. **Test User Setup**:
   - At least one user in the database (e.g., user ID 1)
   - User assigned to at least one role (entry in role_user table)
   - Role has at least one permission (entry in role_permission table)
   - User has at least one activity (entry in activities table)

## Test Scenarios

### Test 1: Activities (Baseline - Should Already Work)

**Endpoint**: `GET /api/crud6/users/1/activities`

**Expected Behavior**:
- Returns JSON with activities for user ID 1
- Activities have user_id = 1
- Uses direct foreign key relationship

**Sample Response**:
```json
{
  "rows": [
    {
      "id": 1,
      "user_id": 1,
      "occurred_at": "2024-01-01 12:00:00",
      "type": "sign_in",
      "description": "User signed in",
      "ip_address": "192.168.1.1"
    }
  ],
  "count": 1,
  "count_filtered": 1
}
```

**Verification**:
- [ ] Response status is 200
- [ ] Response contains activity records
- [ ] All activities belong to user_id = 1

---

### Test 2: Roles (Main Fix - Many-to-Many)

**Endpoint**: `GET /api/crud6/users/1/roles`

**Expected Behavior**:
- Returns JSON with roles for user ID 1
- Roles are fetched via JOIN through role_user pivot table
- Uses many-to-many relationship logic

**Sample Response**:
```json
{
  "rows": [
    {
      "id": 1,
      "name": "Administrator",
      "slug": "admin",
      "description": "Site administrator"
    }
  ],
  "count": 1,
  "count_filtered": 1
}
```

**Verification**:
- [ ] Response status is 200
- [ ] Response contains role records
- [ ] Roles match entries in role_user table for user_id = 1
- [ ] Check error logs for: "Using many-to-many relationship with pivot table"

**SQL to verify data exists**:
```sql
SELECT roles.* 
FROM roles 
JOIN role_user ON roles.id = role_user.role_id 
WHERE role_user.user_id = 1;
```

---

### Test 3: Permissions (Advanced - Nested Many-to-Many)

**Endpoint**: `GET /api/crud6/users/1/permissions`

**Expected Behavior**:
- Returns JSON with permissions for user ID 1
- Permissions fetched via nested JOINs through roles
- Uses DISTINCT to avoid duplicates

**Sample Response**:
```json
{
  "rows": [
    {
      "id": 1,
      "slug": "uri_users",
      "name": "View users",
      "description": "View user list and details"
    },
    {
      "id": 2,
      "slug": "create_user",
      "name": "Create user",
      "description": "Create new users"
    }
  ],
  "count": 2,
  "count_filtered": 2
}
```

**Verification**:
- [ ] Response status is 200
- [ ] Response contains permission records
- [ ] Permissions match those assigned to user's roles
- [ ] No duplicate permissions in results
- [ ] Check error logs for: "Using nested many-to-many for permissions through roles"

**SQL to verify data exists**:
```sql
SELECT DISTINCT permissions.* 
FROM permissions 
JOIN role_permission ON permissions.id = role_permission.permission_id 
JOIN role_user ON role_permission.role_id = role_user.role_id 
WHERE role_user.user_id = 1;
```

---

## Debugging

### Enable Debug Logging

Ensure debug logging is enabled in your UserFrosting configuration:

```php
// app/config/default.php
'debug' => [
    'queries' => true,
];

'logger' => [
    'channels' => [
        'default' => [
            'level' => 'debug',
        ],
    ],
];
```

### Check Logs

Look for these log messages in your debug logs:

**For Roles**:
```
CRUD6 [SprunjeAction] Using many-to-many relationship with pivot table
pivot_table: role_user
foreign_key: user_id
related_key: role_id
```

**For Permissions**:
```
CRUD6 [SprunjeAction] Using nested many-to-many for permissions through roles
```

### Common Issues

#### Issue: Empty Results for Roles
**Possible Causes**:
1. No entries in role_user table for the user
2. Relationship not defined in schema
3. Incorrect pivot table name

**Debug**:
```sql
-- Check if user has roles
SELECT * FROM role_user WHERE user_id = 1;

-- Check if schema has relationship
-- Look in users.json for relationships array
```

#### Issue: Empty Results for Permissions
**Possible Causes**:
1. User has no roles assigned
2. Roles have no permissions assigned
3. role_permission table is empty

**Debug**:
```sql
-- Check user's roles
SELECT * FROM role_user WHERE user_id = 1;

-- Check permissions for those roles
SELECT * FROM role_permission WHERE role_id IN (
    SELECT role_id FROM role_user WHERE user_id = 1
);
```

#### Issue: Duplicate Permissions
**Possible Causes**:
1. DISTINCT not working (shouldn't happen with current implementation)
2. Multiple roles with same permission

**Debug**:
- Check if DISTINCT is in the query (look at SQL logs)
- This is expected behavior - DISTINCT should handle it

---

## Performance Testing

### Large Dataset Tests

Test with users who have:
- Multiple roles (5-10 roles)
- Many permissions (50+ permissions)

**Verify**:
- [ ] Reasonable response time (< 2 seconds)
- [ ] No timeout errors
- [ ] Pagination works correctly
- [ ] Filtering works correctly

### Pagination Test

**Endpoint**: `GET /api/crud6/users/1/permissions?size=5&page=1`

**Expected**:
- Returns only 5 permissions
- Includes pagination metadata
- Can navigate to next page

---

## Integration Testing Checklist

- [ ] Test with real UserFrosting 6 application
- [ ] Test with sprinkle-c6admin schema
- [ ] Activities endpoint returns data
- [ ] Roles endpoint returns data (NEW)
- [ ] Permissions endpoint returns data (NEW)
- [ ] No duplicate records
- [ ] Pagination works
- [ ] Filtering works
- [ ] Sorting works
- [ ] Debug logs show correct query strategy
- [ ] Performance is acceptable
- [ ] No errors in error logs

---

## Success Criteria

✅ **All tests pass when**:
1. Activities detail shows user's activities
2. Roles detail shows user's assigned roles
3. Permissions detail shows all permissions from user's roles
4. No duplicate permissions appear
5. Pagination and filtering work correctly
6. Debug logs confirm correct relationship handling

---

## Rollback Plan

If issues occur:

1. **Immediate Fix**: Revert to previous version
   ```bash
   git revert <commit-hash>
   ```

2. **Temporary Workaround**: 
   - Remove roles and permissions from details array in schema
   - Keep only activities (which uses old logic)

3. **Report Issues**:
   - Include error logs
   - Include SQL queries from debug logs
   - Include sample data structure

---

## Contact

For issues or questions:
- Open issue on GitHub: https://github.com/ssnukala/sprinkle-crud6/issues
- Include test results and log output
- Specify UserFrosting version and database type
