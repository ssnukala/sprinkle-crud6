# Testing Guide: Verify Duplicate Schema Call Fix

## Quick Test
1. Navigate to `/crud6/users/1` in browser
2. Open Developer Console (F12)
3. Open Network tab, filter by "schema"
4. Refresh the page
5. **Expected**: Only ONE network request to `/api/crud6/users/schema?context=detail%2Cform`
6. **Before Fix**: Would see THREE requests:
   - `/api/crud6/users/schema` (full context)
   - `/api/crud6/users/schema?context=form`
   - `/api/crud6/users/schema?context=detail%2Cform`

## Console Log Verification

Look for these log messages in the console to verify the fix is working:

### Expected Log Sequence:

```
[PageRow] Pre-loading schema before useCRUD6Api initialization - model: users

[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====
{model: 'users', context: 'detail,form', cacheKey: 'users:detail,form', ...}

[useCRUD6SchemaStore] 🌐 MAKING API CALL to load schema - cacheKey: users:detail,form

[useCRUD6Api] ===== LOAD SCHEMA FOR VALIDATION =====
{model: 'users', context: 'form', ...}

[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====
{model: 'users', context: 'form', cacheKey: 'users:form', ...}

[useCRUD6SchemaStore] ⏳ Related context loading (broader or equal):
{requested: 'form', loading: 'detail,form', loadingKey: 'users:detail,form', ...}

[useCRUD6SchemaStore] ⏳ Waiting for related context to finish loading - relatedKey: users:detail,form

[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
{url: '/api/crud6/users/schema?context=detail%2Cform', status: 200, ...}

[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully

[useCRUD6SchemaStore] ✅ Related context loaded, checking cache - cacheKey: users:form

[useCRUD6Api] ✅ Schema loaded for validation
{model: 'users', hasSchema: true, fieldCount: 13}
```

### Key Indicators of Success:

1. ✅ "Pre-loading schema before useCRUD6Api initialization" appears first
2. ✅ "Related context loading (broader or equal)" appears for 'form' request
3. ✅ "Waiting for related context to finish loading" appears
4. ✅ Only ONE "MAKING API CALL" message (for 'detail,form')
5. ✅ Only ONE "HTTP RESPONSE RECEIVED" message
6. ✅ "Related context loaded, checking cache" appears after response
7. ✅ useCRUD6Api gets schema from cache (no API call)

### What Should NOT Appear:

1. ❌ NO "[PageDynamic] Loading schema for model" message
2. ❌ NO second "MAKING API CALL" for 'form' context
3. ❌ NO second "HTTP RESPONSE RECEIVED"
4. ❌ NO three separate API calls in Network tab

## Network Tab Details

### Single Request Details:
- **URL**: `/api/crud6/users/schema?context=detail%2Cform`
- **Method**: GET
- **Status**: 200 OK
- **Response**: JSON with multi-context structure

### Response Structure:
```json
{
  "schema": {
    "model": "users",
    "title": "Users",
    "contexts": {
      "detail": { ... },
      "form": { ... }
    }
  }
}
```

## Functional Verification

After verifying the network reduction, ensure the page still works correctly:

1. ✅ Page loads without errors
2. ✅ User details display correctly
3. ✅ Edit button works
4. ✅ Form validation works (if editing)
5. ✅ All fields render properly
6. ✅ No JavaScript errors in console
7. ✅ Navigation between users works

## Performance Impact

### Before Fix:
- **API Calls**: 3
- **Data Transfer**: ~3x (same schema loaded 3 times)
- **Latency**: 3 round trips (if not cached by browser)

### After Fix:
- **API Calls**: 1
- **Data Transfer**: ~1x (schema loaded once)
- **Latency**: 1 round trip

### Expected Improvement:
- **67% reduction** in API calls
- **Faster page load** (no waiting for duplicate requests)
- **Reduced server load** (2 fewer requests per page view)

## Edge Cases to Test

### Test 1: Force Refresh
1. Navigate to `/crud6/users/1`
2. Hard refresh (Ctrl+Shift+R)
3. **Expected**: Still only ONE API call

### Test 2: Different Models
1. Navigate to `/crud6/products/1` (if available)
2. **Expected**: Only ONE API call for products schema

### Test 3: Navigation Between Records
1. Navigate to `/crud6/users/1`
2. Navigate to `/crud6/users/2`
3. **Expected**: No additional schema calls (schema is cached)

### Test 4: Query Parameter Views
1. Navigate to `/crud6/users/1?v=md`
2. **Expected**: Still only ONE API call
3. PageDynamic should render PageMasterDetail without loading schema

## Troubleshooting

### If you still see 3 API calls:
1. Clear browser cache and reload
2. Check git branch is correct: `copilot/fix-duplicate-schema-calls`
3. Verify all 3 files were modified (check git log)
4. Check for TypeScript compilation errors
5. Verify PageDynamic template doesn't have schema loading logic

### If you see 2 API calls:
- Check if PageRow pre-loading is working (look for "Pre-loading schema" log)
- Verify useCRUD6Api is initialized AFTER the pre-load call
- Check timing in console logs to see execution order

### If validation doesn't work:
- Check that 'form' context is being cached from 'detail,form' response
- Verify multi-context caching logic in store (lines 200-215)
- Check that useCRUD6Api receives schema from cache

## Success Criteria

✅ **PASS**: Only 1 API call to `/api/crud6/users/schema?context=detail%2Cform`
✅ **PASS**: Console shows "Related context loading" and "Waiting for related context"
✅ **PASS**: Page functionality works correctly (view, edit, validation)
✅ **PASS**: No errors in console
✅ **PASS**: Performance improved (67% fewer API calls)

❌ **FAIL**: 2 or more schema API calls
❌ **FAIL**: Errors in console
❌ **FAIL**: Page doesn't load or functionality broken
❌ **FAIL**: Validation doesn't work
