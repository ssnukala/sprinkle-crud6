# Duplicate Schema API Calls Fix

## Issue
When navigating to `/crud6/users/1`, three separate schema API calls were being made:
1. `/api/crud6/users/schema` (full context) - from PageDynamic
2. `/api/crud6/users/schema?context=form` - from useCRUD6Api
3. `/api/crud6/users/schema?context=detail%2Cform` - from PageRow

This resulted in unnecessary network requests and slower page loads.

## Root Cause Analysis

### Call #1: PageDynamic Loading Full Schema
**File**: `app/assets/views/PageDynamic.vue`
- PageDynamic was loading schema to determine `render_mode` from schema
- This was unnecessary because PageDynamic is just a routing wrapper
- The actual rendering components (PageRow/PageMasterDetail) need the schema, not the wrapper

### Call #2: useCRUD6Api Loading Form Schema
**File**: `app/assets/composables/useCRUD6Api.ts`
- Loaded schema immediately for validation rules
- This happened during PageRow's setup phase, before PageRow's watcher could fire

### Call #3: PageRow Loading Detail+Form Schema
**File**: `app/assets/views/PageRow.vue`
- PageRow loads schema with `'detail,form'` context for comprehensive data
- This is the correct call, but it happened AFTER the other two

### Timing Issue
The problem was execution order:
1. PageDynamic setup → loads schema (full)
2. PageRow setup → useCRUD6Api initialized → loads schema (form)
3. PageRow watcher fires → loads schema (detail,form)

Even though the store had caching, each used a different cache key:
- `users:full`
- `users:form`
- `users:detail,form`

These are all different cache entries, so all three API calls were made.

## Solution

### Fix #1: Remove Schema Loading from PageDynamic
**File**: `app/assets/views/PageDynamic.vue`

Removed all schema loading logic from PageDynamic since it's just a routing wrapper. The component now:
- Only checks query parameters to determine which view to render
- Delegates schema loading to child components (PageRow or PageMasterDetail)
- No longer shows a loading state or makes API calls

**Changes**:
- Removed `useCRUD6Schema` import and usage
- Removed schema loading watcher
- Removed loading/error states from template
- Simplified component to pure routing logic

### Fix #2: Smart Context Waiting in Store
**File**: `app/assets/stores/useCRUD6SchemaStore.ts`

Added `isRelatedContextLoading()` function that detects when a broader context is loading that includes the requested context.

**Logic**:
- When requesting `'form'` context, check if `'detail,form'` is already loading
- If broader context is loading, wait for it to complete instead of making a new API call
- After broader context completes, check cache (which will now have the requested context)

**How it works**:
1. PageRow calls `loadSchema('users', false, 'detail,form')`
   - Not in cache, not loading → makes API call
   - Sets loading state for `users:detail,form`

2. useCRUD6Api calls `loadSchema('users', false, 'form')`
   - Not in cache yet (call #1 hasn't completed)
   - Checks `isRelatedContextLoading('users', 'form')`
   - Finds that `users:detail,form` is loading (which includes 'form')
   - **Waits** for that request to complete
   - When complete, checks cache for `users:form` (now available from multi-context caching)
   - Returns cached schema - **NO API CALL**

3. Multi-context caching (existing feature in lines 161-171):
   - When `'detail,form'` response arrives, store caches it under multiple keys:
     - `users:detail,form` (original request)
     - `users:detail` (extracted context)
     - `users:form` (extracted context)
   - This ensures future single-context requests are cached

## Result
After these fixes, navigating to `/crud6/users/1` should make only **ONE** schema API call:
- `/api/crud6/users/schema?context=detail%2Cform` (from PageRow)

The other components will either:
- Not load schema at all (PageDynamic)
- Wait and use cached result (useCRUD6Api)

## Testing
To verify the fix:
1. Navigate to `/crud6/users/1`
2. Open browser console
3. Check network tab for schema API calls
4. Should see only ONE call to `/api/crud6/users/schema?context=detail%2Cform`
5. Verify useCRUD6Api logs show "Waiting for related context"
6. Verify PageDynamic logs don't show schema loading

## Benefits
1. **Reduced Network Traffic**: 3 API calls → 1 API call (67% reduction)
2. **Faster Page Loads**: No waiting for duplicate requests
3. **Better Resource Usage**: Less server load, less bandwidth
4. **Cleaner Architecture**: Clear separation of concerns (routing wrapper vs data components)
5. **Smarter Caching**: Context-aware waiting prevents race conditions

## Files Changed
1. `app/assets/views/PageDynamic.vue` - Removed schema loading
2. `app/assets/stores/useCRUD6SchemaStore.ts` - Added smart context waiting
3. `app/assets/composables/useCRUD6Api.ts` - Updated comments to reflect new behavior

## Backward Compatibility
- ✅ All existing functionality preserved
- ✅ Schema caching still works as before
- ✅ Multi-context requests still cache individual contexts
- ✅ Force reload still bypasses cache
- ✅ Component props and APIs unchanged
