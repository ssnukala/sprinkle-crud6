# Backend Error Analysis - Integration Test Run #19519222499

## Problem Statement
The integration test workflow run #19519222499 was incorrectly marking tests as PASSED even though error notifications were visible on the detail pages (screenshot_role_detail.png and screenshot_permission_detail.png).

## Errors Detected

### Error 1: Role Detail Page Relationship Query Failure
**Location:** `/crud6/roles/1` → API call to `/api/crud6/roles/1/{relationship}`

**Symptom:** 
- Red error notification box on top right corner
- Message: "We've sensed a great disturbance in the Force.: Oops, looks like server might have goofed. If you're an admin, please check the PHP or Userfrosting logs."

**Network Activity:**
- Total requests: 348
- CRUD6 API calls: 4
- Schema API calls: 1
- One of the CRUD6 calls failed (relationship endpoint)

### Error 2: Permission Detail Page Relationship Query Failure
**Location:** `/crud6/permissions/1` → API call to `/api/crud6/permissions/1/{relationship}`

**Symptom:**
- Same red error notification on top right corner
- Same error message as above

**Network Activity:**
- Total requests: 348
- CRUD6 API calls: 4
- Schema API calls: 1
- One of the CRUD6 calls failed (relationship endpoint)

## Root Cause Analysis

### Bug Location: `app/src/Controller/RelationshipAction.php`

#### Problem 1: Wrong Count Timing in getManyToManyRelationship()
```php
// Lines 334-344 (BEFORE FIX)
// Build the base query
$query = $this->db->table($relatedTable)
    ->join($pivotTable, "{$pivotTable}.{$relatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
    ->where("{$pivotTable}.{$foreignKey}", $crudModel->id);

// Apply search if provided
if ($search && !empty($listFields)) {
    $query->where(function($q) use ($search, $listFields, $relatedTable) {
        foreach ($listFields as $field) {
            $q->orWhere("{$relatedTable}.{$field}", 'LIKE', "%{$search}%");
        }
    });
}

// ❌ BUG: Get total count AFTER applying search filters
$total = $query->count();
```

**Issue:** `$total` is calculated after the search filter is applied, so it represents the *filtered* count, not the total unfiltered count.

#### Problem 2: Wrong Count Mapping in handleGetRelationship()
```php
// Lines 263-276 (BEFORE FIX)
$responseData = [
    'rows' => $result['rows'],
    'count' => $result['total'],           // Uses 'total' which is actually filtered count
    'count_filtered' => $result['count'],  // Uses 'count' which is count($rows) = page count
    // ...
];
```

**Issue:** The response sends:
- `count`: Should be total unfiltered count, but is actually filtered count
- `count_filtered`: Should be filtered count (before pagination), but is actually current page row count

#### UFSprunjeTable Expected Format
```javascript
{
    rows: [],              // Current page data
    count: 100,            // Total unfiltered count
    count_filtered: 25     // Count after filters but before pagination
}
```

#### What Was Actually Sent
```javascript
{
    rows: [],              // Current page data
    count: 25,             // Filtered count (WRONG - should be total)
    count_filtered: 10     // Page count (WRONG - should be filtered before pagination)
}
```

### Impact
When UFSprunjeTable receives incorrect counts, it:
1. Miscalculates pagination
2. Shows wrong total counts
3. May fail to render properly
4. Triggers UserFrosting error notification system
5. Displays error message to user

## The Fix

### Fix 1: Correct Count Calculation Timing
```php
// NEW CODE (AFTER FIX)
// Build the base query
$query = $this->db->table($relatedTable)
    ->join($pivotTable, "{$pivotTable}.{$relatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
    ->where("{$pivotTable}.{$foreignKey}", $crudModel->id);

// ✅ Get total count BEFORE applying search filters (unfiltered total)
$totalCount = $query->count();

// Apply search if provided
if ($search && !empty($listFields)) {
    $query->where(function($q) use ($search, $listFields, $relatedTable) {
        foreach ($listFields as $field) {
            $q->orWhere("{$relatedTable}.{$field}", 'LIKE', "%{$search}%");
        }
    });
}

// ✅ Get filtered count AFTER applying search but BEFORE pagination
$filteredCount = $query->count();
```

### Fix 2: Return Correct Count Values
```php
return [
    'rows' => $rows,
    'count' => count($rows),              // Current page row count
    'total' => $totalCount,                // ✅ Total unfiltered count
    'filtered' => $filteredCount,          // ✅ Total filtered count (before pagination)
    'total_pages' => (int) ceil($filteredCount / $perPage),  // Pages based on filtered count
];
```

### Fix 3: Update Response Mapping
```php
$responseData = [
    'rows' => $result['rows'],
    'count' => $result['total'],           // ✅ Total count without filters
    'count_filtered' => $result['filtered'],  // ✅ Count with current filters/search (before pagination)
    // ...
];
```

### Fix 4: Add Debug Logging
```php
$this->logger->debug("CRUD6 [RelationshipAction] Many-to-many counts", [
    'model' => $crudSchema['model'],
    'record_id' => $crudModel->id,
    'relationship' => $relatedModel,
    'total_count' => $totalCount,
    'filtered_count' => $filteredCount,
    'has_search' => !empty($search),
]);
```

## Test Improvements

### Added Error Notification Detection
Updated `take-screenshots-with-tracking.js` to:
1. Check for error notification elements (`.uf-alert-danger`, `.uk-alert-danger`)
2. Look for specific error text: "We've sensed a great disturbance in the Force"
3. Fail the test if error notifications are detected
4. Exit with error code to fail CI workflow

### Added PHP Error Log Capture
Created `capture-php-error-logs.sh` to:
1. Search for PHP error logs in standard locations
2. Display UserFrosting logs from `app/logs/` and `app/storage/logs/`
3. Show last 50 lines of each log file
4. Check system journal for PHP errors
5. Run with `if: always()` in workflow to capture logs even on failure

## Expected Results After Fix

1. ✅ Relationship queries return correct count values
2. ✅ UFSprunjeTable receives proper pagination data
3. ✅ Detail pages render without errors
4. ✅ No error notifications appear on role/permission detail pages
5. ✅ Tests fail if error notifications are detected
6. ✅ PHP error logs captured and displayed in workflow output
7. ✅ Debug logs show correct count calculations

## Files Modified

1. `app/src/Controller/RelationshipAction.php`
   - Fixed `getManyToManyRelationship()` 
   - Fixed `getBelongsToManyThroughRelationship()`
   - Updated `handleGetRelationship()` response mapping
   - Added debug logging

2. `.github/scripts/take-screenshots-with-tracking.js`
   - Added error notification detection
   - Added test failure on error detection
   - Return fail count for proper exit code

3. `.github/scripts/capture-php-error-logs.sh` (NEW)
   - Script to capture and display PHP error logs
   - Searches multiple log locations
   - Displays relevant log entries

4. `.github/workflows/integration-test.yml`
   - Added PHP error log capture step
   - Updated screenshot step description
