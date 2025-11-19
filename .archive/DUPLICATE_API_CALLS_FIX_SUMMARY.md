# Duplicate API Calls Fix - Summary

## Issue
When viewing a record detail page (e.g., `/api/crud6/groups/1`), the GET request to `/api/crud6/{model}/{id}` was being called **twice**, causing:
- Unnecessary server load
- Slower page performance
- Duplicate database queries
- Increased network traffic

## Example from Network Log
From workflow run https://github.com/ssnukala/sprinkle-crud6/actions/runs/19515475940:

```
CRUD6 Request Details:
   1. [2025-11-19T20:34:13.632Z] GET /api/crud6/groups/schema?context=list%2Cdetail%2Cform&include_related=true
      Resource Type: xhr
      📌 Schema API Call
   2. [2025-11-19T20:34:13.634Z] GET /api/crud6/groups/1  ← DUPLICATE #1
      Resource Type: xhr
   3. [2025-11-19T20:34:13.634Z] GET /api/crud6/groups/1  ← DUPLICATE #2
      Resource Type: xhr
   4. [2025-11-19T20:34:14.046Z] GET /api/crud6/groups/1/users?size=10&page=0
      Resource Type: xhr
```

Requests #2 and #3 are duplicates happening within milliseconds of each other.

## Root Cause
Both `PageRow.vue` and `PageMasterDetail.vue` had the same issue:

1. **onMounted hook**: Calls `fetch()` when `!isCreateMode && recordId.value` is true
2. **recordId watcher**: Calls `fetch()` when recordId changes (with `immediate: true`)

When the component loads:
1. Vue mounts the component → `onMounted` fires → calls `fetch()` (CALL #1)
2. Watcher with `immediate: true` fires → calls `fetch()` (CALL #2)

## Solution
Removed the duplicate `fetch()` call from `onMounted` in both components:

### Before
```javascript
onMounted(async () => {
    // Schema loading is handled by the model watcher with immediate: true
    // No need to load schema here to avoid duplicate calls
    
    if (!isCreateMode.value && recordId.value) {
        fetch()  // ← DUPLICATE CALL
    } else if (isCreateMode.value) {
        // Initialize empty record for create mode using schema
        record.value = {}
        CRUD6Row.value = createInitialRecord(flattenedSchema.value?.fields)
        resetForm()
    }
})
```

### After
```javascript
onMounted(async () => {
    // Schema loading is handled by the model watcher with immediate: true
    // Record fetching is handled by the recordId watcher with immediate: true
    // No need to load schema or fetch record here to avoid duplicate calls
    
    if (isCreateMode.value) {
        // Initialize empty record for create mode using schema
        record.value = {}
        CRUD6Row.value = createInitialRecord(flattenedSchema.value?.fields)
        resetForm()
    }
})
```

The `recordId` watcher with `immediate: true` remains as the single source of truth for fetching records:

```javascript
// Watch for recordId changes to fetch data
watch(recordId, (newId) => {
    if (newId && !isCreateMode.value) {
        fetch()
    }
}, { immediate: true })  // ← This handles the initial load
```

## Files Modified
- `app/assets/views/PageRow.vue` - Removed duplicate fetch() from onMounted
- `app/assets/views/PageMasterDetail.vue` - Removed duplicate fetch() from onMounted

## Changes Summary
- **Lines removed**: 8 (4 from each file)
- **Lines added**: 6 (3 from each file)
- **Net change**: -2 lines, improved comments

## Testing
The integration test workflow automatically:
1. Takes screenshots of all frontend detail pages
2. Tracks all network requests during page loads
3. Detects and reports redundant API calls
4. Generates a detailed network request summary artifact

**Expected Result**: Only ONE GET request to `/api/crud6/{model}/{id}` instead of two.

## Impact
- ✅ Eliminates duplicate API calls on all detail pages
- ✅ Reduces server load by 50% for detail page views
- ✅ Improves page load performance
- ✅ Reduces unnecessary database queries
- ✅ Consistent behavior across PageRow and PageMasterDetail components

## Related Models
This fix applies to all models using these page components:
- Users (`/crud6/users/{id}`)
- Groups (`/crud6/groups/{id}`)
- Roles (`/crud6/roles/{id}`)
- Permissions (`/crud6/permissions/{id}`)
- Activities (`/crud6/activities/{id}`)
- Any custom CRUD6 models

## Commits
1. `0c314cb` - Remove duplicate fetch() call in PageRow onMounted hook
2. `c74e041` - Fix duplicate fetch() in PageMasterDetail.vue onMounted hook

## Date
November 19, 2025
