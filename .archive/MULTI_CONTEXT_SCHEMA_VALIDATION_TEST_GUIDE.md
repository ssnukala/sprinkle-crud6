# Quick Test Guide: Multi-Context Schema Validation Fix

## Prerequisites
- UserFrosting 6 application running locally
- CRUD6 sprinkle installed and configured
- User with `uri_crud6` permission

## Test Steps

### 1. Test Single-Context Request (Control Test)

**URL**: `http://localhost:8600/api/crud6/users/schema?context=list`

**Expected Response Structure**:
```json
{
  "message": "Retrieved Users schema successfully",
  "model": "users",
  "fields": {                    ← Fields at ROOT level
    "user_name": {...},
    "first_name": {...},
    ...
  }
}
```

**Browser Console Expected**:
```
[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)
  model: 'users'
  fieldCount: 6
```

---

### 2. Test Multi-Context Request (Fix Verification)

**URL**: `http://localhost:8600/api/crud6/users/schema?context=list,form`

**Expected Response Structure**:
```json
{
  "message": "Retrieved Users schema successfully",
  "model": "users",
  "contexts": {                  ← Contexts object (NOT fields at root)
    "list": {
      "fields": {...}
    },
    "form": {
      "fields": {...}
    }
  }
}
```

**Browser Console Expected**:
```
[useCRUD6SchemaStore] ✅ Schema found in response.data (multi-context)
  model: 'users'
  contexts: ['list', 'form']

[useCRUD6SchemaStore] ✅ Cached context separately
  context: 'list'
  cacheKey: 'users:list'
  fieldCount: 6

[useCRUD6SchemaStore] ✅ Cached context separately
  context: 'form'
  cacheKey: 'users:form'
  fieldCount: 10

[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully
```

**BEFORE FIX (Error)**:
```
[useCRUD6SchemaStore] ❌ Invalid schema response structure
[useCRUD6SchemaStore] ❌ Schema load ERROR
```

---

### 3. Test Users Page Load

**URL**: `http://localhost:8600/crud6/users` (or your configured route)

**Expected**:
- ✅ Page loads successfully
- ✅ Users table displays
- ✅ No console errors
- ✅ Schema loads correctly

**BEFORE FIX**:
- ❌ Page fails to load
- ❌ "Invalid schema response" error in console
- ❌ Users table does not display

---

### 4. Test Schema Caching

1. Navigate to users page
2. Check browser console for schema load messages
3. Refresh the page
4. **Expected**: Schema should be served from cache (no new API call)

**Browser Console on Second Load**:
```
[useCRUD6SchemaStore] Schema already cached, using cached version
  cacheKey: 'users:list,form'
```

---

## Verification Checklist

- [ ] Single-context request returns `fields` at root level
- [ ] Multi-context request returns `contexts` object
- [ ] No "Invalid schema response" errors in console
- [ ] Users page loads successfully
- [ ] Schema caching works (check network tab - no duplicate calls)
- [ ] Both list and form contexts are cached separately

---

## Automated Tests

If composer dependencies are installed, run:

```bash
# Test all schema endpoint tests
vendor/bin/phpunit app/tests/Controller/SchemaActionTest.php

# Test specific multi-context test
vendor/bin/phpunit app/tests/Controller/SchemaActionTest.php --filter testSchemaMultiContextReturnsContextsObject

# Test specific single-context test
vendor/bin/phpunit app/tests/Controller/SchemaActionTest.php --filter testSchemaSingleContextReturnsFieldsAtRoot
```

**Expected**: All tests pass ✅

---

## Troubleshooting

### Still seeing "Invalid schema response" error?

1. **Clear browser cache**: Hard refresh (Ctrl+Shift+R / Cmd+Shift+R)
2. **Check version**: Ensure you're on the branch with the fix
3. **Verify file changes**: Check that `app/assets/stores/useCRUD6SchemaStore.ts` has the new `else if (response.data.contexts)` block
4. **Check build**: If using compiled assets, rebuild with `npm run build`

### Page still not loading?

1. **Check permissions**: User must have `uri_crud6` permission
2. **Check schema file**: Ensure `app/schema/crud6/users.json` exists
3. **Check backend logs**: Look for PHP errors in UserFrosting logs
4. **Check network tab**: Verify API returns HTTP 200 (not 403, 404, or 500)

### Context caching not working?

1. **Check localStorage**: Pinia store may persist to localStorage
2. **Clear localStorage**: `localStorage.clear()` in browser console
3. **Check store configuration**: Verify Pinia persistence is enabled

---

## Success Criteria

✅ **Fix is working correctly if**:
1. Multi-context API calls return HTTP 200 with `contexts` object
2. Frontend validation accepts the response without errors
3. Users page loads and displays data
4. Both contexts are cached separately for future use
5. No "Invalid schema response" errors in console

❌ **Fix is NOT working if**:
1. "Invalid schema response" error appears
2. Users page fails to load
3. Console shows schema load errors
4. Contexts are not cached separately
