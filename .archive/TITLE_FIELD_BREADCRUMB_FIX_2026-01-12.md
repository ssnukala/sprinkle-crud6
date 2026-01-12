# Title Field Breadcrumb Display Fix

**Date:** 2026-01-12
**Issue:** Title field not displaying in breadcrumbs and page headers
**PR Branch:** copilot/fix-breadcrumb-title-display

## Problem Statement

The `title_field` attribute from schema was no longer being used to display record titles in breadcrumbs and page headers. Instead of showing "User1 (1)", the system was showing just "1" or "User1" without the ID suffix.

**Expected Behavior:**
- Breadcrumb trail: `Dashboard > Users > User1 (1)`
- Page header h3 tag: `User1 (1)`

**Actual Behavior:**
- Breadcrumb trail: `Dashboard > Users > 1`
- Page header h3 tag: `User1` or `1`

## Root Cause Analysis

### Backend Investigation
✅ **EditAction.php** - Working correctly
- Line 221: Calls `calculateBreadcrumbName()` method
- Lines 403-441: Method implementation correctly:
  - Checks for `title_field` in schema (line 417)
  - Extracts value from that field (line 427)
  - Appends ID in parentheses: `"Title (ID)"` (line 428)
  - Falls back to just ID if title_field not set (line 436-440)
- Line 238: Returns breadcrumb in API response as `breadcrumb` field

**API Response Structure (Flat Format):**
```json
{
  "id": 1,
  "user_name": "User1",
  "first_name": "John",
  "last_name": "Doe",
  "email": "user1@example.com",
  "breadcrumb": "User1 (1)",
  "message": "Success",
  "model": "users",
  "modelDisplayName": "User"
}
```

### Frontend Issues

❌ **Info.vue Line 266** - NOT using pre-computed breadcrumb
```vue
<!-- BEFORE (Wrong) -->
<h3 class="uk-text-center uk-margin-remove">
    {{ finalSchema.title_field ? (crud6[finalSchema.title_field] || crud6.id) : crud6.id }}
</h3>
```
This was directly accessing `crud6[finalSchema.title_field]` which would show "User1" without the ID suffix.

⚠️ **PageRow.vue Line 241** - Only checking `_breadcrumb`
```typescript
// BEFORE (Incomplete)
let recordName = (fetchedRow as any)._breadcrumb
```
This was only checking for `_breadcrumb` field (nested response format) but not `breadcrumb` field (flat response format from EditAction).

### Response Format Confusion

The code had to handle two different response formats:

1. **Flat Format** (EditAction.php):
   - Fields at root level
   - Breadcrumb as `breadcrumb` field
   - Returned by useCRUD6Api.ts line 240

2. **Nested Format** (hypothetical other controllers):
   - Data wrapped in `data` property
   - Breadcrumb transformed to `_breadcrumb` by useCRUD6Api
   - Handled by useCRUD6Api.ts lines 221-231

PageRow.vue was only checking for the nested format (`_breadcrumb`) and missing the flat format (`breadcrumb`).

## Solution Implemented

### 1. Info.vue - Use Pre-computed Breadcrumb
**File:** `app/assets/components/CRUD6/Info.vue`
**Line:** 266

```vue
<!-- AFTER (Correct) -->
<!-- Title - use pre-computed breadcrumb from API (includes "Title (ID)" format) or fallback to ID -->
<h3 class="uk-text-center uk-margin-remove">
    {{ crud6.breadcrumb || crud6.id }}
</h3>
```

**Rationale:**
- Backend already computes the correct format "Title (ID)"
- No need to duplicate logic in frontend
- Cleaner, more maintainable code
- Single source of truth for breadcrumb calculation

### 2. PageRow.vue - Check Both Field Names
**File:** `app/assets/views/PageRow.vue`
**Line:** 241

```typescript
// AFTER (Correct)
// Use pre-computed breadcrumb from API response
// Check both 'breadcrumb' (flat response) and '_breadcrumb' (nested response)
let recordName = (fetchedRow as any).breadcrumb || (fetchedRow as any)._breadcrumb
```

**Rationale:**
- Handles both flat and nested response formats
- Provides resilience against API changes
- Maintains backward compatibility
- Ensures breadcrumb trail displays correctly

### Debug Logging Enhanced
Added additional debug logging to track both fields:

```typescript
debugLog('[PageRow.fetch] ===== BREADCRUMB RESOLUTION =====', {
    step1_fetchedRow_breadcrumb: (fetchedRow as any).breadcrumb ?? 'NULL',
    step1b_fetchedRow__breadcrumb: (fetchedRow as any)._breadcrumb ?? 'NULL',
    step2_recordBreadcrumb_value: recordBreadcrumb.value ?? 'NULL',
    step3_recordId: recordId.value,
})
```

## Data Flow

### Complete Flow from Backend to Display

1. **Schema Definition** (users.json):
   ```json
   {
     "title_field": "user_name",
     ...
   }
   ```

2. **Backend Calculation** (EditAction.php):
   ```php
   // Line 221: Calculate breadcrumb
   $breadcrumbName = $this->calculateBreadcrumbName($crudSchema, $recordData, $recordId);
   // Returns: "User1 (1)"
   
   // Line 238: Include in response
   'breadcrumb' => $breadcrumbName
   ```

3. **API Response**:
   ```json
   {
     "id": 1,
     "user_name": "User1",
     "breadcrumb": "User1 (1)"
   }
   ```

4. **Frontend Reception** (useCRUD6Api.ts):
   ```typescript
   // Line 240: Return flat response
   return response.data
   // Contains: { id: 1, user_name: "User1", breadcrumb: "User1 (1)" }
   ```

5. **PageRow Display** (PageRow.vue):
   ```typescript
   // Line 241: Extract breadcrumb
   let recordName = (fetchedRow as any).breadcrumb  // "User1 (1)"
   
   // Line 268: Set in breadcrumb trail
   await setDetailBreadcrumbs(modelTitle.value, recordName, listPath)
   ```

6. **Info Display** (Info.vue):
   ```vue
   <!-- Line 266: Display in h3 -->
   <h3>{{ crud6.breadcrumb }}</h3>
   <!-- Shows: "User1 (1)" -->
   ```

## Files Modified

1. **app/assets/components/CRUD6/Info.vue**
   - Line 266: Changed from `crud6[finalSchema.title_field]` to `crud6.breadcrumb`
   - Updated comment to explain pre-computed breadcrumb usage

2. **app/assets/views/PageRow.vue**
   - Line 241: Changed from `(fetchedRow as any)._breadcrumb` to check both fields
   - Updated comment to explain both response formats
   - Enhanced debug logging to show both field values

## Testing Checklist

- [ ] Navigate to `/crud6/users/1` - verify h3 shows "User1 (1)"
- [ ] Check breadcrumb trail shows "Dashboard > Users > User1 (1)"
- [ ] Navigate to `/crud6/products/5` - verify format "Product Name (5)"
- [ ] Test with model that has no title_field - should show just ID
- [ ] Test with model where title_field value is empty - should show just ID
- [ ] Verify page.title in browser tab shows correct format
- [ ] Check debug logs show correct breadcrumb values
- [ ] Test with different models (groups, roles, permissions)
- [ ] Verify breadcrumb updates when navigating between records
- [ ] Test edit mode - breadcrumb should remain consistent

## Verification Commands

```bash
# Syntax validation
php -l app/src/Controller/EditAction.php
php -l app/assets/components/CRUD6/Info.vue
php -l app/assets/views/PageRow.vue

# Check for title_field usage
grep -r "title_field" app/assets --include="*.vue" --include="*.ts"

# Check for breadcrumb handling
grep -r "breadcrumb" app/assets/views/PageRow.vue
grep -r "breadcrumb" app/assets/components/CRUD6/Info.vue
```

## Related Code Patterns

### calculateBreadcrumbName() Method
**Location:** `app/src/Controller/EditAction.php` lines 403-441

**Logic:**
```php
protected function calculateBreadcrumbName(array $crudSchema, array $recordData, $recordId): string
{
    $titleField = $crudSchema['title_field'] ?? null;
    
    // If title_field is defined and exists in record data, use it with ID suffix
    if ($titleField && isset($recordData[$titleField]) && !empty($recordData[$titleField])) {
        return $recordData[$titleField] . ' (' . $recordId . ')';
    }
    
    // Fall back to just the record ID
    return (string) $recordId;
}
```

**Examples:**
- `title_field: "user_name"`, `user_name: "Admin"`, `id: 1` → `"Admin (1)"`
- `title_field: "name"`, `name: "Product ABC"`, `id: 42` → `"Product ABC (42)"`
- `title_field: null`, `id: 5` → `"5"`
- `title_field: "name"`, `name: ""`, `id: 10` → `"10"`

## Benefits of This Fix

1. **Consistency**: Both breadcrumb trail and page header show the same format
2. **Single Source of Truth**: Backend calculates, frontend displays
3. **Maintainability**: Logic in one place, easier to update
4. **Schema-Driven**: Uses `title_field` from schema configuration
5. **Robust Fallback**: Handles missing or empty title fields gracefully
6. **Format Flexibility**: Supports both flat and nested API responses
7. **Better UX**: Users can identify records by meaningful names, not just IDs

## Edge Cases Handled

1. **No title_field in schema**: Falls back to ID
2. **Empty title_field value**: Falls back to ID
3. **Null title_field value**: Falls back to ID
4. **Nested API response**: Checks `_breadcrumb` field
5. **Flat API response**: Checks `breadcrumb` field
6. **No breadcrumb in response**: Multiple fallback layers (recordBreadcrumb.value, recordId)

## Future Considerations

1. **Translation Support**: The title field value could be translated if it's a translation key
2. **Custom Format**: Schema could specify format template like `"{title} - {id}"` or `"#{id} {title}"`
3. **Icon Support**: Could prepend an icon to the breadcrumb
4. **Tooltip**: Could show full record data on hover
5. **Link Behavior**: Make breadcrumb clickable to navigate
6. **Caching**: Cache breadcrumb values to reduce recalculation

## References

- UserFrosting 6 Documentation: https://learn.userfrosting.com/
- Schema-driven CRUD patterns
- Vue 3 Composition API patterns
- Breadcrumb composable: `app/assets/composables/useCRUD6Breadcrumbs.ts`
- API composable: `app/assets/composables/useCRUD6Api.ts`
