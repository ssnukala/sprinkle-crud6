# Frontend Debug Logging Guide

**Date:** October 31, 2025  
**Commit:** ea469dd  
**Purpose:** Track schema API calls in frontend layer

## Overview

Comprehensive console.log debug statements have been added to the frontend layer to track all schema-related API calls and cache operations. This helps identify duplicate schema loads that may be happening on the frontend.

## Enhanced Components

### 1. useCRUD6SchemaStore.ts - Global Schema Store

**Location:** `app/assets/stores/useCRUD6SchemaStore.ts`

**Enhanced Methods:**
- `loadSchema()` - Tracks API calls vs cache hits
- `setSchema()` - Logs direct schema setting (no API)

**Log Examples:**

**Cache Hit (No API Call):**
```javascript
[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====
{
  model: "users",
  force: false,
  context: "form",
  cacheKey: "users:form",
  hasCache: true,
  timestamp: "2025-10-31T16:30:00.000Z",
  caller: "at PageRow.vue:123:45"
}
[useCRUD6SchemaStore] ✅ Using CACHED schema - cacheKey: users:form (NO API CALL)
```

**API Call:**
```javascript
[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====
[useCRUD6SchemaStore] 🌐 MAKING API CALL to load schema - cacheKey: users:form
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST
{
  url: "/api/crud6/users/schema?context=form",
  method: "GET",
  cacheKey: "users:form",
  requestNumber: 1,  // ← Counter to identify concurrent calls
  timestamp: "2025-10-31T16:30:00.100Z"
}
[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
{
  url: "/api/crud6/users/schema?context=form",
  status: 200,
  statusText: "OK",
  hasData: true,
  dataKeys: ["message", "model", "modelDisplayName", "schema"]
}
[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully
{
  model: "users",
  context: "form",
  cacheKey: "users:form",
  fieldCount: 8,
  hasContexts: false
}
```

**Multi-Context Response:**
```javascript
[useCRUD6SchemaStore] 📦 Multi-context schema detected, caching contexts separately
{
  contexts: ["list", "form"]
}
[useCRUD6SchemaStore] ✅ Cached context separately
{
  context: "list",
  cacheKey: "users:list",
  fieldCount: 12
}
[useCRUD6SchemaStore] ✅ Cached context separately
{
  context: "form",
  cacheKey: "users:form",
  fieldCount: 8
}
```

### 2. useCRUD6Schema.ts - Composable

**Location:** `app/assets/composables/useCRUD6Schema.ts`

**Enhanced Methods:**
- `loadSchema()` - Logs with caller stack trace
- `setSchema()` - Logs direct schema setting

**Log Examples:**

**Local Cache Hit:**
```javascript
[useCRUD6Schema] ===== LOAD SCHEMA CALLED =====
{
  model: "users",
  force: false,
  context: "form",
  hasLocalCache: true,
  currentModel: "users",
  timestamp: "2025-10-31T16:30:00.000Z",
  caller: "at PageRow.vue:150:20"  // ← Shows WHERE schema load was called
}
[useCRUD6Schema] ✅ Using LOCAL cached schema - model: users context: form
```

**Delegating to Store:**
```javascript
[useCRUD6Schema] ===== LOAD SCHEMA CALLED =====
[useCRUD6Schema] Delegating to STORE - model: users force: false context: form
[useCRUD6Schema] ✅ Schema loaded and set - model: users context: form fieldCount: 8
```

**Error:**
```javascript
[useCRUD6Schema] ❌ Schema load failed - model: users context: form
error: {title: "Schema Load Error", description: "Failed to load schema"}
```

### 3. useCRUD6Api.ts - API Composable

**Location:** `app/assets/composables/useCRUD6Api.ts`

**Enhanced Methods:**
- `loadSchema()` - Logs validation schema loads

**Log Examples:**

```javascript
[useCRUD6Api] ===== LOAD SCHEMA FOR VALIDATION =====
{
  model: "users",
  context: "form",
  purpose: "validation rules",
  timestamp: "2025-10-31T16:30:00.000Z"
}
[useCRUD6Api] ✅ Schema loaded for validation
{
  model: "users",
  hasSchema: true,
  fieldCount: 8
}
```

## Emoji Legend

Visual indicators for quick log scanning:

- ✅ **Cache Hit** - Schema retrieved from cache (NO API call made)
- 🌐 **API Call** - Making HTTP request to load schema
- 📤 **HTTP Request** - Outgoing HTTP GET request
- 📥 **HTTP Response** - Incoming HTTP response
- 📦 **Multi-Context** - Schema contains multiple contexts
- ❌ **Error** - Schema load failed
- ⏳ **Waiting** - Waiting for concurrent load to complete

## How to Use

### 1. Open Browser DevTools

Press **F12** or right-click → Inspect

### 2. Navigate to Console Tab

### 3. Filter Logs

Type in filter box:
- `useCRUD6` - See all CRUD6 frontend logs
- `SchemaStore` - See only store logs
- `Schema` - See all schema-related logs

### 4. Navigate to Test Page

Go to a CRUD6 detail page, e.g.:
```
http://localhost:8080/crud6/users/1
```

### 5. Analyze Console Output

Look for patterns:

**Good (No Duplicates):**
```
[useCRUD6SchemaStore] 🌐 MAKING API CALL - cacheKey: users:full
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST {requestNumber: 1}
[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
[useCRUD6SchemaStore] ✅ Schema loaded and CACHED

// Later in same page:
[useCRUD6SchemaStore] ✅ Using CACHED schema (NO API CALL)
```

**Bad (Duplicates):**
```
[useCRUD6SchemaStore] 🌐 MAKING API CALL - cacheKey: users:form
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST {requestNumber: 1}

// DUPLICATE - same page, different component?
[useCRUD6SchemaStore] 🌐 MAKING API CALL - cacheKey: users:form
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST {requestNumber: 2}  // ⚠️ Duplicate!

// DUPLICATE - different context
[useCRUD6SchemaStore] 🌐 MAKING API CALL - cacheKey: users:detail
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST {requestNumber: 3}  // ⚠️ Another call!
```

### 6. Check Network Tab

Also verify in Network tab:
1. Filter by: `schema`
2. Count requests to `/api/crud6/*/schema`
3. Check query parameters for context

**Should see:** 0-1 request per unique model+context
**Should NOT see:** 3+ requests for same model

## Debugging Duplicate Calls

### If You See Duplicate API Calls

**Step 1: Check `requestNumber`**
- Sequential numbers (1, 2, 3) = multiple concurrent calls
- Same number = impossible (good for verification)

**Step 2: Check `caller` Stack Trace**
- Shows which component/file triggered the load
- Compare callers to find which components are duplicating

**Step 3: Check `cacheKey`**
- Same cache key = true duplicate
- Different context = may be intentional (list + form)

**Step 4: Check Timestamp**
- Close timestamps = concurrent calls
- Distant timestamps = separate page loads

### Common Issues

**Issue 1: Component Mounting Race Condition**
```
PageRow mounts → loads schema (users:full)
Form mounts   → loads schema (users:form)  // Different context, OK
Info mounts   → loads schema (users:full)  // Same context, DUPLICATE!
```

**Solution:** Pass schema via props from parent to children

**Issue 2: Missing Force Check**
```
loadSchema('users', true, 'form')  // force=true bypasses cache
```

**Solution:** Use `force=false` unless explicitly refreshing

**Issue 3: Different Context Requests**
```
loadSchema('users', false, 'form')
loadSchema('users', false, 'detail')
loadSchema('users', false, null)  // full
```

**Solution:** Use multi-context API: `loadSchema('users', false, 'form,detail')`

## Integration with Backend Logs

### Correlate Frontend + Backend

**Frontend console:**
```
[useCRUD6SchemaStore] 📤 HTTP GET REQUEST
{url: "/api/crud6/users/schema?context=form", timestamp: "16:30:00.123"}
```

**Backend error log:**
```
[CRUD6 ApiAction] ===== SCHEMA API REQUEST ===== 
model: users, context: form, timestamp: 2025-10-31 16:30:00.123456
```

**Match timestamps** to trace frontend request → backend processing

### Full Request Flow

1. **Frontend Component** calls `loadSchema()`
   ```
   [useCRUD6Schema] ===== LOAD SCHEMA CALLED ===== caller: PageRow.vue:123
   ```

2. **Frontend Composable** delegates to store
   ```
   [useCRUD6Schema] Delegating to STORE
   ```

3. **Frontend Store** checks cache
   ```
   [useCRUD6SchemaStore] hasCache: false
   [useCRUD6SchemaStore] 🌐 MAKING API CALL
   ```

4. **Frontend Store** makes HTTP request
   ```
   [useCRUD6SchemaStore] 📤 HTTP GET REQUEST {requestNumber: 1}
   ```

5. **Backend Middleware** receives request
   ```
   [CRUD6 CRUD6Injector] ===== MIDDLEWARE PROCESS START =====
   ```

6. **Backend Service** loads schema (from cache or disk)
   ```
   [CRUD6 SchemaService] ✅ Using CACHED schema (in-memory)
   ```

7. **Backend Controller** filters and responds
   ```
   [CRUD6 ApiAction] ===== SCHEMA API RESPONSE =====
   ```

8. **Frontend Store** receives response
   ```
   [useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED {status: 200}
   ```

9. **Frontend Store** caches result
   ```
   [useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully
   ```

10. **Next request** uses cache
    ```
    [useCRUD6SchemaStore] ✅ Using CACHED schema (NO API CALL)
    ```

## Performance Monitoring

### Count API Calls

Use browser console:
```javascript
// Count all schema API calls
console.log(
  performance.getEntriesByType('resource')
    .filter(r => r.name.includes('/schema'))
    .length
)

// Get all schema API URLs
performance.getEntriesByType('resource')
  .filter(r => r.name.includes('/schema'))
  .forEach(r => console.log(r.name, r.duration + 'ms'))
```

### Expected Metrics

**Good Performance:**
- 0-1 API call per unique model+context
- Cache hit rate: 80%+
- Most logs show ✅ (cache) not 🌐 (API)

**Poor Performance:**
- 3+ API calls for same model
- Cache hit rate: <50%
- Many 🌐 symbols in logs

## Troubleshooting

### No Logs Appearing

**Check:**
1. Console filter not too restrictive
2. Browser DevTools open before navigation
3. Console not cleared automatically

### Too Many Logs

**Filter by:**
- `[useCRUD6SchemaStore]` - Just store logs
- `🌐` - Just API calls
- `❌` - Just errors

### Can't Find Caller

**Stack trace shows:**
```
caller: "at loadSchema (useCRUD6Schema.ts:150:20)"
```

Look for the component that called `loadSchema()`, not the composable itself. Check the full stack trace in the console object.

## Maintenance

### When to Remove Debug Logs

**Keep:**
- Cache hit/miss indicators (✅/🌐)
- Error logs (❌)
- API request logs (📤📥)

**Can Remove After Testing:**
- Detailed timestamp objects
- Caller stack traces
- Field count details
- requestNumber counters

### When to Add More Logs

If duplicates are still hard to track:
1. Add component name to logs
2. Add props/state to context
3. Add lifecycle hook indicators (mounted, updated)

## Related Documentation

- `.archive/DEBUG_SCHEMA_DUPLICATE_CALLS_2025-10-31.md` - Backend debug guide
- `.archive/SCHEMA_OPTIMIZATION_SUMMARY_2025-10-31.md` - Performance summary
- `app/assets/stores/useCRUD6SchemaStore.ts` - Store implementation
- `app/assets/composables/useCRUD6Schema.ts` - Composable implementation

## Summary

The enhanced frontend debug logging provides complete visibility into schema loading patterns, making it easy to identify duplicate API calls and cache issues. The emoji-based visual indicators allow for quick scanning, while detailed log objects provide all necessary debugging information.

Use these logs to:
1. ✅ Verify cache is working correctly
2. 🌐 Count actual API calls
3. 📤📥 Track HTTP request/response flow
4. ❌ Identify and diagnose errors
5. 🔍 Find components making duplicate calls

Combined with backend logging, this provides end-to-end visibility into the entire schema loading pipeline.
