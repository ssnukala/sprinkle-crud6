# Manual Testing Guide for Multi-Context Schema Fix

## Test Environment Requirements
- UserFrosting 6.0.4+ application
- CRUD6 sprinkle installed
- Browser with developer console access
- User with `uri_users` permission (or equivalent for the model being tested)

## Test Scenario 1: Multi-Context Schema Request

### Steps
1. Open browser developer console
2. Navigate to a CRUD6 page that requests multiple contexts (e.g., `/crud6/users`)
3. Check the Network tab for the schema API call
4. Verify the request URL contains multiple contexts: `/api/crud6/users/schema?context=list,form`
5. Check Console logs for debug messages

### Expected Results

#### Backend Response (Network Tab)
```json
{
  "message": "Retrieved Users schema successfully",
  "modelDisplayName": "Users",
  "model": "users",
  "title": "Users",
  "actions": [...],
  "permissions": {...},
  "contexts": {
    "list": {
      "fields": { "user_name": {...}, "first_name": {...}, ... }
    },
    "form": {
      "fields": { "user_name": {...}, "password": {...}, ... }
    }
  }
}
```

**Key Points**:
- ✅ Has `contexts` object at root
- ✅ NO `fields` at root level
- ✅ Each context has its own `fields`

#### Frontend Console Logs
Should show these debug messages in order:

1. **Schema Load Called**:
```
[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====
{
  model: 'users',
  context: 'list,form',
  cacheKey: 'users:list,form',
  ...
}
```

2. **API Request**:
```
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST
{
  url: '/api/crud6/users/schema?context=list%2Cform',
  method: 'GET',
  cacheKey: 'users:list,form',
  ...
}
```

3. **Response Received**:
```
[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
{
  url: '/api/crud6/users/schema?context=list%2Cform',
  status: 200,
  hasData: true,
  dataKeys: ['message', 'model', 'contexts', ...],
  ...
}
```

4. **Response Analysis**:
```
[useCRUD6SchemaStore] Analyzing response structure
{
  hasContexts: true,
  contextsIsObject: true,
  contextsIsArray: false,
  contextsKeys: ['list', 'form'],
  ...
}
```

5. **Multi-Context Detected** (KEY LOG):
```
[useCRUD6SchemaStore] ✅ Multi-context response detected
{
  model: 'users',
  contexts: ['list', 'form'],
  requestedContext: 'list,form'
}
```

6. **Individual Contexts Cached**:
```
[useCRUD6SchemaStore] ✅ Cached context separately
{
  context: 'list',
  cacheKey: 'users:list',
  fieldCount: 6
}

[useCRUD6SchemaStore] ✅ Cached context separately
{
  context: 'form',
  cacheKey: 'users:form',
  fieldCount: 10
}
```

7. **Schema Reconstructed** (KEY LOG):
```
[useCRUD6SchemaStore] ✅ Reconstructed schema with fields at root
{
  model: 'users',
  fieldCount: 10,  // Merged from list + form
  contexts: ['list', 'form']
}
```

8. **Final Cache**:
```
[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully
{
  model: 'users',
  context: 'list,form',
  cacheKey: 'users:list,form',
  schemaKeys: ['model', 'title', 'actions', 'permissions', 'fields', ...],
  fieldCount: 10,
  hasContexts: false  // NO contexts in reconstructed schema
}
```

9. **NO ERROR** should appear:
```
❌ [useCRUD6SchemaStore] ❌ Invalid schema response structure
❌ [useCRUD6SchemaStore] ❌ Schema load ERROR
```

### FAIL Indicators
If the fix is NOT working, you'll see:
```
❌ [useCRUD6SchemaStore] ❌ Invalid schema response structure
{
  dataKeys: [...],
  hasContexts: true,
  hasFields: false,  // <-- This causes the error
  ...
}

❌ [useCRUD6SchemaStore] ❌ Schema load ERROR
{
  errorType: 'Error',
  message: 'Invalid schema response',
  ...
}
```

## Test Scenario 2: Single-Context Schema Request (Regression Test)

### Steps
1. Navigate to a page that requests single context
2. Or manually call: `/api/crud6/users/schema?context=list`

### Expected Results

#### Backend Response
```json
{
  "model": "users",
  "title": "Users",
  "fields": { "user_name": {...}, ... },  // Fields at root
  "actions": [...]
}
```

**Key Points**:
- ✅ Has `fields` at root level
- ✅ NO `contexts` object

#### Frontend Behavior
Should use the third condition branch (`else if ('fields' in response.data)`):
```
[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)
{
  model: 'users',
  fieldCount: 6
}
```

## Test Scenario 3: Cache Reuse

### Steps
1. Request multi-context: `/api/crud6/users/schema?context=list,form`
2. Wait for response and caching
3. Navigate to a page that requests single context: `context=list`

### Expected Results
- First request makes API call and caches `users:list,form`, `users:list`, `users:form`
- Second request for `context=list` should use cached `users:list` (NO API call):
```
[useCRUD6SchemaStore] ✅ Using cached schema - cacheKey: users:list (NO API CALL)
```

## Test Scenario 4: Page Loads Successfully

### Steps
1. Navigate to `/crud6/users` (or equivalent CRUD6 list page)
2. Verify page loads without errors

### Expected Results
- ✅ No JavaScript errors in console
- ✅ User list displays correctly
- ✅ Actions buttons appear
- ✅ Table columns render properly
- ✅ Filters and search work

## Debugging Tips

### Enable Debug Logging
If debug logs are not showing:
1. Check if debug mode is enabled in application config
2. Or add this to browser console:
```javascript
localStorage.setItem('debug', 'crud6:*')
```

### Check Cache State
In browser console:
```javascript
// Get Pinia store
const stores = window.__PINIA__
// or
const schemaStore = useC

RUD6SchemaStore()
console.log('Cached schemas:', schemaStore.schemas)
```

### Clear Cache
If testing multiple times:
```javascript
const schemaStore = useCRUD6SchemaStore()
schemaStore.clearAllSchemas()
```

## Success Criteria
- ✅ Multi-context requests work without "Invalid schema response" error
- ✅ Single-context requests still work (no regression)
- ✅ Schemas are properly cached for reuse
- ✅ Pages load and display data correctly
- ✅ All console logs show expected flow

## Failure Indicators
- ❌ "Invalid schema response structure" error appears
- ❌ Page fails to load or shows error message
- ❌ Fields/columns missing from display
- ❌ Actions buttons don't appear
- ❌ Console shows schema load ERROR messages
