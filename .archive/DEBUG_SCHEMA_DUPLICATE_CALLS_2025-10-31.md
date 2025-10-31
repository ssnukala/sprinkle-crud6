# Debug Guide: Duplicate Schema API Calls

**Date:** October 31, 2025  
**Issue:** Duplicate schema API calls when accessing `/crud6/users/1`  
**Goal:** Track down and eliminate duplicate calls to `/api/crud6/{model}/schema`

## Problem Description

When accessing a detail page like `/crud6/users/1`, there are multiple API calls being made:
1. `/api/crud6/users/schema` (full schema)
2. `/api/crud6/users/schema?context=form`
3. `/api/crud6/users/schema?context=detail`
4. Potentially a 4th call for "form" (unconfirmed)

This is a recurring issue that keeps getting reintroduced with code changes.

## Debug Logging Added

### Backend (PHP)

All backend debug logs use `error_log()` to write to the PHP error log and are prefixed with component names for easy filtering.

#### 1. SchemaService.php - Schema Loading

**Location:** `app/src/ServicesProvider/SchemaService.php`

**Logs Added:**
- `getSchema()` entry: Logs every schema load attempt with caller information
- Schema path attempts: Logs connection-based and default path lookups
- Schema load success: Logs when schema is successfully loaded
- `filterSchemaForContext()`: Logs context filtering requests

**Key Features:**
- Includes `getCallerInfo()` to track who called getSchema()
- Logs timestamp with microseconds for precise timing
- Logs model name, connection, and context parameters

**Example Output:**
```
[CRUD6 SchemaService] getSchema() called - model: users, connection: null, timestamp: 2025-10-31 15:30:45.123456, caller: CRUD6Injector::getInstance():109 <- CRUD6Injector::process():245
[CRUD6 SchemaService] Trying default path: schema://crud6/users.json
[CRUD6 SchemaService] Schema loaded successfully - model: users, table: users, field_count: 12
```

#### 2. CRUD6Injector.php - Middleware

**Location:** `app/src/Middlewares/CRUD6Injector.php`

**Logs Added:**
- `process()` entry: Logs when middleware starts processing
- Route parsing: Logs model name, connection, and ID
- `getInstance()` call: Logs before calling getInstance()
- **DUPLICATE WARNING**: Special warning when loading schema the second time in process()
- Controller completion: Logs response status

**Key Features:**
- Clearly marks the duplicate schema load with ⚠️ emoji
- Shows the flow: parse route → getInstance() → load schema (duplicate!)
- Includes error logging for failures

**Example Output:**
```
[CRUD6 CRUD6Injector] ===== MIDDLEWARE PROCESS START ===== URI: /api/crud6/users/1, method: GET, timestamp: 2025-10-31 15:30:45.100000
[CRUD6 CRUD6Injector] Route parsed - model: users, connection: null, id: 1
[CRUD6 CRUD6Injector] Calling getInstance() - model: users, id: 1
[CRUD6 CRUD6Injector] getInstance() called - model: users, connection: null, id: 1, timestamp: 2025-10-31 15:30:45.110000
[CRUD6 CRUD6Injector] Loading schema from SchemaService - model: users, connection: null
[CRUD6 CRUD6Injector] Schema loaded in getInstance() - model: users, table: users
[CRUD6 CRUD6Injector] Record loaded successfully - model: users, id: 1
[CRUD6 CRUD6Injector] ⚠️ DUPLICATE SCHEMA LOAD: Calling getSchema() AGAIN in process() - model: users, connection: null
[CRUD6 CRUD6Injector] ⚠️ DUPLICATE SCHEMA LOADED in process() - model: users, table: users
```

#### 3. ApiAction.php - Schema API Endpoint

**Location:** `app/src/Controller/ApiAction.php`

**Logs Added:**
- API request entry: Logs when schema API is called
- Context parameter: Logs which context is requested
- Schema filtering: Logs before and after filtering
- Response: Logs response size and context

**Key Features:**
- Shows which contexts are being requested from the frontend
- Helps identify if multiple API calls are being made
- Includes response payload size for performance analysis

**Example Output:**
```
[CRUD6 ApiAction] ===== SCHEMA API REQUEST ===== model: users, context: form, URI: /api/crud6/users/schema?context=form, timestamp: 2025-10-31 15:30:45.200000
[CRUD6 ApiAction] Filtering schema for context: form
[CRUD6 SchemaService] filterSchemaForContext() called - model: users, context: form, timestamp: 2025-10-31 15:30:45.201000
[CRUD6 ApiAction] Schema filtered - field_count: 8, has_contexts: no
[CRUD6 ApiAction] ===== SCHEMA API RESPONSE ===== model: users, context: form, response_size: 2456 bytes
```

### Frontend (JavaScript/TypeScript)

Frontend debug logs already exist in `useCRUD6SchemaStore.ts` and use `console.log()` with prefixes.

**Location:** `app/assets/stores/useCRUD6SchemaStore.ts`

**Existing Logs:**
- `loadSchema()` calls with cache check
- API request details
- Response handling
- Cache key management

**Example Output:**
```
[useCRUD6SchemaStore] loadSchema called {model: "users", force: false, context: "form", cacheKey: "users:form", hasCache: false}
[useCRUD6SchemaStore] Loading schema from API - cacheKey: users:form force: false context: form
[useCRUD6SchemaStore] Making API request {url: "/api/crud6/users/schema?context=form", method: "GET"}
[useCRUD6SchemaStore] Schema loaded successfully {model: "users", context: "form", fieldCount: 8}
```

## How to Use Debug Logs

### 1. Enable PHP Error Logging

Ensure PHP error logging is enabled in your development environment:

```php
// In your PHP configuration or .htaccess
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

Or check UserFrosting's debug configuration in `app/configuration/default.php`.

### 2. Monitor Logs in Real-Time

**Backend (PHP):**
```bash
# Tail the PHP error log
tail -f /path/to/error.log | grep "CRUD6"

# Or if using Docker/built-in server
php -S localhost:8080 2>&1 | grep "CRUD6"
```

**Frontend (Browser):**
- Open Browser DevTools (F12)
- Go to Console tab
- Filter by "CRUD6" or "useCRUD6SchemaStore"
- Also check Network tab for actual HTTP requests

### 3. Test the Issue

Navigate to a detail page that exhibits the problem:
```
http://localhost:8080/crud6/users/1
```

### 4. Analyze the Logs

Look for these patterns:

#### Pattern 1: Duplicate Schema Load in CRUD6Injector
```
[CRUD6 SchemaService] getSchema() called - model: users, connection: null
[CRUD6 CRUD6Injector] Schema loaded in getInstance()
[CRUD6 CRUD6Injector] ⚠️ DUPLICATE SCHEMA LOAD: Calling getSchema() AGAIN
[CRUD6 SchemaService] getSchema() called - model: users, connection: null  ← DUPLICATE!
```

**Root Cause:** CRUD6Injector loads schema twice - once in `getInstance()` and once in `process()`

**Fix:** Store schema from `getInstance()` and reuse it in `process()`

#### Pattern 2: Multiple API Calls from Frontend
```
[CRUD6 ApiAction] ===== SCHEMA API REQUEST ===== context: null/full
[CRUD6 ApiAction] ===== SCHEMA API REQUEST ===== context: form
[CRUD6 ApiAction] ===== SCHEMA API REQUEST ===== context: detail
```

**Root Cause:** Frontend components making separate API calls for different contexts

**Fix:** Use multi-context API call (e.g., `?context=form,detail`) or improve caching

#### Pattern 3: Cache Not Working
```
[useCRUD6SchemaStore] loadSchema called {hasCache: false}
[useCRUD6SchemaStore] Loading schema from API
[useCRUD6SchemaStore] loadSchema called {hasCache: false}  ← Should be true!
[useCRUD6SchemaStore] Loading schema from API
```

**Root Cause:** Cache key mismatch or cache not being set properly

**Fix:** Verify cache key generation and storage logic

## Expected Behavior

For a single page load of `/crud6/users/1`, you should see:

**Backend:**
1. One middleware process for the detail page route
2. One schema load for the model
3. Optional: One API request if frontend needs schema

**Frontend:**
1. Maximum one API call per unique model+context combination
2. Subsequent requests should use cache

**Total API Calls:** 
- If frontend has schema from backend: **0 API calls** (ideal)
- If frontend needs schema: **1 API call** (acceptable)
- **Never:** 2+ API calls for the same model+context

## Known Issues and Fixes

### Issue #1: CRUD6Injector Duplicate Schema Load

**Status:** IDENTIFIED - Logged in this PR

**Description:** CRUD6Injector calls `schemaService->getSchema()` twice:
- Line 109 (in `getInstance()`)
- Line 238 (in `process()`)

**Impact:** Every request loads schema from disk twice

**Proposed Fix:**
```php
// Store schema from getInstance() to reuse in process()
private ?array $currentSchema = null;

protected function getInstance(?string $id): CRUD6ModelInterface
{
    // ... existing code ...
    $schema = $this->schemaService->getSchema($modelName, $this->currentConnectionName);
    $this->currentSchema = $schema;  // Store for reuse
    // ... rest of code ...
}

public function process(...): ResponseInterface
{
    // ... existing code ...
    $instance = $this->getInstance($id);
    
    // Reuse stored schema instead of loading again
    $schema = $this->currentSchema;
    // ... rest of code ...
}
```

### Issue #2: Frontend Multi-Context Requests

**Status:** TO BE INVESTIGATED

**Description:** Frontend may be making multiple API calls with different contexts

**Check:**
- Look for `schema?context=form` and `schema?context=detail` in Network tab
- Verify frontend components use cache correctly

**Proposed Fix:**
- Use multi-context API: `schema?context=form,detail` (already supported!)
- Ensure cache keys include context parameter
- Pass schema via props when possible

## Success Criteria

After implementing fixes:

✅ **Backend:**
- Schema loaded from disk only once per request
- No ⚠️ DUPLICATE warnings in logs
- CRUD6Injector reuses schema instead of reloading

✅ **Frontend:**
- Only one API call per unique model+context combination
- Cache hits show `Using cached schema` logs
- Network tab shows minimal schema API requests

✅ **Performance:**
- Page load time improved
- Reduced disk I/O
- Lower API call count

## Next Steps

1. **Immediate:** Run the application and collect debug logs
2. **Analyze:** Identify which pattern(s) match the observed behavior
3. **Fix:** Implement the appropriate fix based on root cause
4. **Verify:** Confirm fixes eliminate duplicate calls
5. **Cleanup:** Remove or reduce debug logging (keep critical logs)
6. **Document:** Update this file with findings and solution

## Related Files

**Backend:**
- `app/src/Middlewares/CRUD6Injector.php` - Middleware that loads schema
- `app/src/ServicesProvider/SchemaService.php` - Schema loading service
- `app/src/Controller/ApiAction.php` - Schema API endpoint
- `app/src/Routes/CRUD6Routes.php` - Route definitions

**Frontend:**
- `app/assets/stores/useCRUD6SchemaStore.ts` - Schema cache store
- `app/assets/composables/useCRUD6Schema.ts` - Schema composable

**Documentation:**
- `.archive/DEBUG_DUPLICATE_SCHEMA_CALLS.md` - Previous debug effort
- `.archive/CHANGELOG_SCHEMA_CACHING.md` - Frontend caching implementation
- `.archive/SCHEMA_CACHING_SUMMARY.md` - Cache architecture

## Maintenance

**Important:** When making changes to schema loading or caching:
1. Review this debug guide first
2. Keep debug logs in place (at least as comments)
3. Test with debug logs enabled
4. Verify no duplicate calls appear
5. Update this document if patterns change
