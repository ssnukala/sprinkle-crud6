# Screenshot Error Fix Summary

**Issue:** Integration test run #19518884996 showed errors in `screenshot_permission_detail.png` and `screenshot_role_detail.png`

**Date:** 2025-11-19

**GitHub Actions Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19518884996

## Problem Analysis

### Symptoms
- Screenshots of permission detail and role detail pages showed errors
- Both pages use the Details.vue component to display related records (users, roles, permissions)
- Integration tests passed but frontend rendering failed

### Root Cause

The `RelationshipAction::handleGetRelationship()` controller method was returning API responses in an incorrect format that did not match what the `UFSprunjeTable` component expects.

**Incorrect Response Format (OLD):**
```json
{
  "relationship": "users",
  "title": "Users",
  "type": "many_to_many",
  "rows": [...],
  "count": 5,
  "total": 10,
  "page": 1,
  "per_page": 10,
  "total_pages": 1
}
```

**Expected Sprunje Format (REQUIRED):**
```json
{
  "rows": [...],
  "count": 10,          // Total count without filters
  "count_filtered": 5   // Count with current filters applied
}
```

### Why This Matters

1. **Details.vue Component** (line 83-115) uses `<UFSprunjeTable>` to display relationship data
2. **UFSprunjeTable** is a UserFrosting component that expects data in the standard Sprunje format
3. When the format doesn't match, the table component cannot properly:
   - Display the data rows
   - Show pagination information
   - Handle sorting and filtering

### Affected Schemas

Both schemas have `"details"` arrays that use the RelationshipAction endpoint:

**permissions.json:**
```json
"details": [
  {
    "model": "users",
    "list_fields": ["user_name", "first_name", "last_name", "email"],
    "title": "PERMISSION.USERS"
  },
  {
    "model": "roles",
    "list_fields": ["name", "slug", "description"],
    "title": "ROLE.2"
  }
]
```

**roles.json:**
```json
"details": [
  {
    "model": "users",
    "list_fields": ["user_name", "first_name", "last_name", "email", "flag_enabled"],
    "title": "ROLE.USERS"
  },
  {
    "model": "permissions",
    "list_fields": ["slug", "name", "description"],
    "title": "ROLE.PERMISSIONS"
  }
]
```

## Solution

### Code Changes

**File:** `app/src/Controller/RelationshipAction.php`

**Location:** Lines 263-276 in `handleGetRelationship()` method

**Change Summary:**
- Reordered response fields to put `rows`, `count`, and `count_filtered` first
- Set `count` to the total count (unfiltered)
- Set `count_filtered` to the current page count (with filters applied)
- Kept additional metadata fields for debugging but moved them after required fields

**Updated Response Format (NEW):**
```json
{
  "rows": [...],
  "count": 10,              // Total count - used for "X of Y" display
  "count_filtered": 5,      // Filtered count - used for pagination
  "page": 1,                // Additional metadata (optional)
  "per_page": 10,
  "total_pages": 1,
  "relationship": "users",
  "title": "Users",
  "type": "many_to_many"
}
```

### Why This Fix Works

1. **Sprunje Compatibility:** The response now matches the expected Sprunje format
2. **Proper Pagination:** `count` and `count_filtered` provide the data needed for pagination
3. **Backward Compatible:** Additional metadata fields are still included for debugging
4. **Framework Standard:** Follows UserFrosting 6 patterns used by other Sprunje endpoints

## Testing Verification

### Manual Testing Steps
1. Navigate to `/crud6/permissions/1` (permission detail page)
2. Verify the "Users" and "Roles" tables display without errors
3. Navigate to `/crud6/roles/1` (role detail page)
4. Verify the "Users" and "Permissions" tables display without errors
5. Check browser console for any JavaScript errors
6. Verify pagination works if there are multiple records

### Integration Test
Re-run the integration test workflow to verify:
- Screenshots no longer show errors
- Network requests return proper Sprunje format
- All detail tables render correctly

### Expected Results
- ✅ No console errors
- ✅ Tables display relationship data correctly
- ✅ Pagination works
- ✅ Screenshots show proper table rendering

## Impact

### Pages Fixed
- Permission detail pages (`/crud6/permissions/{id}`)
- Role detail pages (`/crud6/roles/{id}`)
- Any other CRUD6 model with `"details"` configuration using relationship endpoints

### Components Fixed
- Details.vue - Now receives properly formatted data from API
- UFSprunjeTable - Can now properly parse and display relationship data

## Related Files

### Frontend
- `app/assets/components/CRUD6/Details.vue` - Consumes the API response
- `app/assets/views/PageMasterDetail.vue` - Uses Details component

### Backend
- `app/src/Controller/RelationshipAction.php` - Returns API response (FIXED)
- `app/src/Routes/CRUD6Routes.php` - Defines the relationship endpoints

### Schemas
- `examples/schema/c6admin-permissions.json` - Defines permission relationships
- `examples/schema/c6admin-roles.json` - Defines role relationships

## Lessons Learned

1. **Follow Framework Standards:** Always use the expected response format for framework components
2. **Check UserFrosting Docs:** Sprunje format is documented in UserFrosting 6
3. **Integration Tests:** Screenshots are valuable for catching UI rendering issues
4. **API Contract:** Frontend and backend must agree on data format

## References

- **GitHub Issue:** N/A (found via integration test screenshot review)
- **PR:** (will be linked when merged)
- **Integration Test Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/19518884996
- **UserFrosting Sprunje Docs:** https://learn.userfrosting.com/database/sprunje
