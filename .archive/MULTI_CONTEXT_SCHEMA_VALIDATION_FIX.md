# Multi-Context Schema Validation Fix

**Date**: 2026-01-01  
**Issue**: Schema endpoint `/api/crud6/users/schema?context=list,form` was returning valid data but frontend validation was failing  
**Status**: ✅ Fixed

## Problem Description

The CRUD6 users page was not loading in the UserFrosting 6 environment with the following error in browser console:

```
[useCRUD6SchemaStore] ❌ Invalid schema response structure
[useCRUD6SchemaStore] ❌ Schema load ERROR
```

The API endpoint was successfully returning data (HTTP 200 OK), but the frontend validation logic was rejecting it as invalid.

## Root Cause Analysis

### API Response Structure (Working Correctly)

When requesting multiple contexts (e.g., `context=list,form`), the backend API correctly returns:

```json
{
  "message": "Retrieved Users schema successfully",
  "modelDisplayName": "Users",
  "breadcrumb": {...},
  "model": "users",
  "title": "Users",
  "primary_key": "id",
  "permissions": {...},
  "actions": [...],
  "contexts": {
    "list": {
      "fields": {...},
      "default_sort": {...},
      "actions": [...]
    },
    "form": {
      "fields": {...}
    }
  }
}
```

Note: No root-level `fields` property, only `contexts` with nested fields.

### Frontend Validation Logic (Incomplete)

The validation in `app/assets/stores/useCRUD6SchemaStore.ts` (lines 266-324) had three checks:

1. ✅ If `response.data.schema` exists → Handle nested schema
2. ✅ If `response.data.fields` exists → Handle direct schema (single context)
3. ❌ Otherwise → **Throw "Invalid schema response" error**

The validation was missing a check for `response.data.contexts` (multi-context case).

### Why It Failed

Multi-context responses have:
- ✅ `contexts` object with `list` and `form` properties
- ❌ NO root-level `fields` property
- ❌ NO nested `schema` property

So it fell through to the error case.

## Solution

### Code Changes

**File**: `app/assets/stores/useCRUD6SchemaStore.ts`

Added a third validation branch between the `fields` check and the error throw:

```typescript
} else if (response.data.contexts) {
    // Response has multi-context structure (e.g., context=list,form)
    schemaData = response.data as CRUD6Schema
    debugLog('[useCRUD6SchemaStore] ✅ Schema found in response.data (multi-context)', {
        model: schemaData.model,
        contexts: Object.keys(schemaData.contexts)
    })
    
    // Cache each context separately for future single-context requests
    const baseSchema = { ...schemaData }
    delete baseSchema.contexts
    
    for (const [ctxName, ctxData] of Object.entries(schemaData.contexts)) {
        const ctxCacheKey = getCacheKey(model, ctxName)
        const ctxSchema = { ...baseSchema, ...ctxData }
        schemas.value[ctxCacheKey] = ctxSchema as CRUD6Schema
        debugLog('[useCRUD6SchemaStore] ✅ Cached context separately', {
            context: ctxName,
            cacheKey: ctxCacheKey,
            fieldCount: ctxData.fields ? Object.keys(ctxData.fields).length : 0
        })
    }
} else {
    // Now throw error only if none of the valid cases match
    debugError('[useCRUD6SchemaStore] ❌ Invalid schema response structure', {
        dataKeys: Object.keys(response.data),
        data: response.data
    })
    throw new Error('Invalid schema response')
}
```

### Test Coverage

**File**: `app/tests/Controller/SchemaActionTest.php`

Added two new tests to ensure both response formats work correctly:

1. **`testSchemaSingleContextReturnsFieldsAtRoot()`**
   - Tests: `GET /api/crud6/users/schema?context=list`
   - Expects: `fields` at root level, NO `contexts` object
   - Validates single-context API responses

2. **`testSchemaMultiContextReturnsContextsObject()`**
   - Tests: `GET /api/crud6/users/schema?context=list,form`
   - Expects: `contexts` object with `list` and `form`, NO root `fields`
   - Validates multi-context API responses

## Validation Flow

After the fix, the validation now handles three valid response structures:

1. **Nested Schema**: `response.data.schema.fields` exists
   - Used when API wraps schema in a `schema` property
   - Example: `{ schema: { model: "users", fields: {...} } }`

2. **Direct Single Context**: `response.data.fields` exists
   - Used when requesting single context or no context
   - Example: `{ model: "users", fields: {...} }`

3. **Multi-Context** (NEW): `response.data.contexts` exists
   - Used when requesting multiple contexts (e.g., `context=list,form`)
   - Example: `{ model: "users", contexts: { list: {...}, form: {...} } }`
   - Each context is cached separately for future use

## Impact

### Before Fix
- ❌ Multi-context requests failed with "Invalid schema response"
- ❌ Users page did not load
- ❌ Any page using `context=list,form` or similar combinations failed

### After Fix
- ✅ Multi-context requests work correctly
- ✅ Users page loads successfully
- ✅ Proper caching of individual contexts for future single-context requests
- ✅ Maintains backward compatibility with single-context and nested schema responses

## Related Files

- **Frontend Validation**: `app/assets/stores/useCRUD6SchemaStore.ts`
- **Backend API**: `app/src/Controller/ApiAction.php` (unchanged, was working correctly)
- **Schema Filtering**: `app/src/ServicesProvider/SchemaFilter.php` (unchanged, was working correctly)
- **Tests**: `app/tests/Controller/SchemaActionTest.php`

## Notes

- This fix maintains full backward compatibility with existing single-context and nested schema response formats
- The caching strategy ensures that after a multi-context request, each individual context is also cached separately
- The fix follows UserFrosting 6 patterns for Pinia store management and schema handling
- No backend changes were required - the API was already returning correct data
