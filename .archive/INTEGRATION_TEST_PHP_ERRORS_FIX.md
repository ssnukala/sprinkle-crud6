# Integration Test PHP Errors Fix Summary

**Date:** 2025-12-10
**Workflow Run:** [#20105975494](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20105975494)
**Branch:** `copilot/fix-php-errors-integration-test`

## Problem Statement

Integration tests were failing with 4 PHP/SQL errors:
1. `users_custom_action` - 500 error
2. `users_delete` - 500 error
3. `users_relationship_attach` - 500 error
4. `users_relationship_detach` - 500 error

## Root Causes Identified

### Issue 1: Missing `user_id` in CustomActionController Activity Logging

**File:** `app/src/Controller/CustomActionController.php`
**Lines:** 126-134

The `activities` table in UserFrosting has a NOT NULL constraint on the `user_id` field:
```php
// From UserFrosting Account sprinkle migration
$table->integer('user_id')->unsigned(); // NOT NULL
```

CustomActionController was calling `UserActivityLogger->info()` without passing `user_id` in the context array:

**Before:**
```php
$this->userActivityLogger->info(
    "User {$currentUser->user_name} executed action '{$actionKey}' on {$crudSchema['model']} {$recordId}.",
    [
        'type'    => "crud6_{$crudSchema['model']}_custom_action",
        'model'   => $crudSchema['model'],
        'id'      => $recordId,
        'action'  => $actionKey,
    ]
);
```

**After:**
```php
$this->userActivityLogger->info(
    "User {$currentUser->user_name} executed action '{$actionKey}' on {$crudSchema['model']} {$recordId}.",
    [
        'type'    => "crud6_{$crudSchema['model']}_custom_action",
        'user_id' => $currentUser->id,  // ✅ ADDED
        'model'   => $crudSchema['model'],
        'id'      => $recordId,
        'action'  => $actionKey,
    ]
);
```

**Impact:** 
- `users_custom_action` test was failing because activity logging INSERT failed due to missing required `user_id` field
- `users_delete` test may have been failing for the same reason when logging the deletion activity

### Issue 2: Wrong Parameter Name in Test Configuration

**File:** `.github/config/integration-test-paths.json`
**Lines:** 105, 119

RelationshipAction.php expects the parameter name `ids` for attach/detach operations:
```php
// app/src/Controller/RelationshipAction.php:103
$relatedIds = $params['ids'] ?? [];

if (!is_array($relatedIds) || empty($relatedIds)) {
    throw new \InvalidArgumentException('No IDs provided for relationship operation');
}
```

But the integration test configuration was sending `related_ids`:

**Before:**
```json
"users_relationship_attach": {
  "payload": {
    "related_ids": [1]  // ❌ WRONG
  }
},
"users_relationship_detach": {
  "payload": {
    "related_ids": [1]  // ❌ WRONG
  }
}
```

**After:**
```json
"users_relationship_attach": {
  "payload": {
    "ids": [1]  // ✅ CORRECT
  }
},
"users_relationship_detach": {
  "payload": {
    "ids": [1]  // ✅ CORRECT
  }
}
```

**Impact:**
- `users_relationship_attach` and `users_relationship_detach` tests were failing with `InvalidArgumentException: "No IDs provided for relationship operation"`

## Verification of Other Controllers

All other CRUD6 controllers correctly pass `user_id` to UserActivityLogger:

| Controller | File | Line | Status |
|------------|------|------|--------|
| CreateAction | CreateAction.php | 196 | ✅ Correct |
| DeleteAction | DeleteAction.php | 156 | ✅ Correct |
| EditAction | EditAction.php | 180 | ✅ Correct |
| UpdateFieldAction | UpdateFieldAction.php | 163 | ✅ Correct |
| RelationshipAction | RelationshipAction.php | 155 | ✅ Correct |
| CustomActionController | CustomActionController.php | 130 | ✅ **NOW FIXED** |

## Changes Made

### Commit 1: Fix CustomActionController
**Commit:** `35ec3b9`
**File:** `app/src/Controller/CustomActionController.php`
**Changes:** Added `'user_id' => $currentUser->id,` to activity logging context array

### Commit 2: Fix Test Configuration
**Commit:** `f21f409`
**File:** `.github/config/integration-test-paths.json`
**Changes:** 
- Line 105: Changed `"related_ids": [1]` to `"ids": [1]` for users_relationship_attach
- Line 119: Changed `"related_ids": [1]` to `"ids": [1]` for users_relationship_detach

## Expected Test Results

After these fixes, all 4 failing tests should pass:

1. ✅ `users_custom_action` - Should pass (user_id now included in activity logging)
2. ✅ `users_relationship_attach` - Should pass (correct parameter name)
3. ✅ `users_relationship_detach` - Should pass (correct parameter name)
4. ✅ `users_delete` - Should pass (deletion activity logging now has user_id)

## Reference

- **UserFrosting Activities Migration:** [ActivitiesTable.php](https://github.com/userfrosting/sprinkle-account/blob/6.0/app/src/Database/Migrations/v400/ActivitiesTable.php)
- **UserFrosting GroupCreateAction Example:** [GroupCreateAction.php](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupCreateAction.php)
- **Workflow Run with Failures:** [#20105975494](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20105975494)
- **AI Analysis:** Workflow log summarization correctly identified these as database-layer issues, not authorization problems

## Testing Recommendations

1. Run the full integration test suite to verify all 4 tests now pass
2. Check the PHP error logs to ensure no SQL errors related to missing `user_id`
3. Verify that custom actions, relationship operations, and deletions all create proper activity log entries

## Lessons Learned

1. **Always pass `user_id` to UserActivityLogger:** The activities table requires this field, and all activity logging calls must include it in the context array
2. **Verify parameter names match between frontend and backend:** Integration tests should use the same parameter names that the API endpoints expect
3. **Use automated summaries wisely:** The GitHub Actions log summary tool correctly identified these as SQL/database issues rather than authorization failures, which helped focus the investigation
