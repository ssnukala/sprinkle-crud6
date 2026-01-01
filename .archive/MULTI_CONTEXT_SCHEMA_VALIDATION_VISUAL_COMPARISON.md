# Visual Comparison: Schema Validation Logic

## Response Structure When Requesting `context=list,form`

```json
{
  "message": "Retrieved Users schema successfully",
  "modelDisplayName": "Users",
  "breadcrumb": { "modelTitle": "Users", "singularTitle": "User" },
  "model": "users",
  "title": "Users",
  "singular_title": "User",
  "primary_key": "id",
  "title_field": "user_name",
  "description": "A listing of the users...",
  "permissions": { "read": "uri_users", "create": "create_user", ... },
  "actions": [ {...}, {...}, ... ],
  "contexts": {                    ← KEY PROPERTY: Not at root, but nested!
    "list": {
      "fields": {
        "user_name": { "type": "string", "label": "Username", ... },
        "first_name": { "type": "string", "label": "First Name", ... },
        ...
      },
      "default_sort": { "user_name": "asc" },
      "actions": [ {...}, {...} ]
    },
    "form": {
      "fields": {
        "user_name": { "type": "string", "label": "Username", "required": true, ... },
        "first_name": { "type": "string", "label": "First Name", "required": true, ... },
        ...
      }
    }
  }
}
```

**Key Observation**: 
- ❌ No `response.data.schema` property
- ❌ No `response.data.fields` property
- ✅ Has `response.data.contexts` property

---

## Validation Logic Flow Diagram

### BEFORE FIX (Failed)

```
┌─────────────────────────────────────────┐
│  HTTP 200 OK Response Received          │
│  URL: /api/crud6/users/schema?context=  │
│       list,form                          │
└───────────────┬─────────────────────────┘
                │
                ▼
        ┌───────────────────┐
        │ Check 1:          │
        │ response.data     │
        │   .schema exists? │
        └────────┬──────────┘
                 │ NO
                 ▼
        ┌───────────────────┐
        │ Check 2:          │
        │ response.data     │
        │   .fields exists? │
        └────────┬──────────┘
                 │ NO
                 ▼
        ┌───────────────────┐
        │ ❌ THROW ERROR    │
        │ "Invalid schema   │
        │  response"        │
        └───────────────────┘
```

**Result**: Users page fails to load!

---

### AFTER FIX (Success)

```
┌─────────────────────────────────────────┐
│  HTTP 200 OK Response Received          │
│  URL: /api/crud6/users/schema?context=  │
│       list,form                          │
└───────────────┬─────────────────────────┘
                │
                ▼
        ┌───────────────────┐
        │ Check 1:          │
        │ response.data     │
        │   .schema exists? │
        └────────┬──────────┘
                 │ NO
                 ▼
        ┌───────────────────┐
        │ Check 2:          │
        │ response.data     │
        │   .fields exists? │
        └────────┬──────────┘
                 │ NO
                 ▼
        ┌───────────────────┐
        │ Check 3: (NEW!)   │
        │ response.data     │
        │   .contexts?      │
        └────────┬──────────┘
                 │ YES! ✅
                 ▼
        ┌───────────────────────────────┐
        │ ✅ SUCCESS                    │
        │ - Set schemaData = response.data │
        │ - Cache contexts separately:  │
        │   • users:list                │
        │   • users:form                │
        └───────────────────────────────┘
```

**Result**: Users page loads successfully!

---

## Code Comparison

### BEFORE (Lines 312-325)

```typescript
} else if (response.data.fields) {
    // Response is the schema itself
    schemaData = response.data as CRUD6Schema
    debugLog('[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)', {
        model: schemaData.model,
        fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
    })
} else {
    debugError('[useCRUD6SchemaStore] ❌ Invalid schema response structure', {
        dataKeys: Object.keys(response.data),
        data: response.data
    })
    throw new Error('Invalid schema response')  // ← FAILS HERE for multi-context!
}
```

### AFTER (Lines 312-347)

```typescript
} else if (response.data.fields) {
    // Response is the schema itself (single context or full)
    schemaData = response.data as CRUD6Schema
    debugLog('[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)', {
        model: schemaData.model,
        fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
    })
} else if (response.data.contexts) {                          // ← NEW CHECK ADDED!
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
    debugError('[useCRUD6SchemaStore] ❌ Invalid schema response structure', {
        dataKeys: Object.keys(response.data),
        data: response.data
    })
    throw new Error('Invalid schema response')
}
```

---

## Three Valid Response Formats

### Format 1: Nested Schema
```json
{
  "schema": {
    "model": "users",
    "fields": { ... }
  }
}
```
✅ Validated by: `if (response.data.schema)`

---

### Format 2: Direct Single Context
```json
{
  "model": "users",
  "fields": { ... }
}
```
✅ Validated by: `else if (response.data.fields)`

---

### Format 3: Multi-Context (NEW!)
```json
{
  "model": "users",
  "contexts": {
    "list": { "fields": { ... } },
    "form": { "fields": { ... } }
  }
}
```
✅ Validated by: `else if (response.data.contexts)`  ← **This was missing!**

---

## Caching Strategy After Fix

When multi-context response is received for `context=list,form`:

```
┌──────────────────────────────────────┐
│ Multi-context response received      │
│ contexts: { list: {...}, form: {...} │
└──────────────┬───────────────────────┘
               │
               ▼
    ┌──────────────────────┐
    │ Cache the full       │
    │ multi-context schema │
    │ Key: "users:list,form"│
    └──────────┬───────────┘
               │
               ▼
    ┌──────────────────────────────┐
    │ Extract and cache contexts   │
    │ separately for future use:   │
    │                              │
    │ 1. users:list                │
    │    (from contexts.list)      │
    │                              │
    │ 2. users:form                │
    │    (from contexts.form)      │
    └──────────────────────────────┘
```

**Benefit**: Next time `context=list` or `context=form` is requested alone, 
it can be served from cache without a new API call!

---

## Browser Console Log Comparison

### BEFORE (Error)
```
[CRUD6 Axios] ===== RESPONSE RECEIVED =====
  status: 200
  dataKeys: ['message', 'modelDisplayName', 'breadcrumb', 'model', 
             'title', 'singular_title', 'primary_key', 'title_field', 
             'description', 'permissions', 'actions', 'contexts']

[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
  status: 200
  hasData: true

[useCRUD6SchemaStore] ❌ Invalid schema response structure  ← ERROR!
  dataKeys: (12) ['message', 'modelDisplayName', ...]

[useCRUD6SchemaStore] ❌ Schema load ERROR
  errorType: 'Error'
  message: 'Invalid schema response'
```

### AFTER (Success)
```
[CRUD6 Axios] ===== RESPONSE RECEIVED =====
  status: 200
  dataKeys: ['message', 'modelDisplayName', 'breadcrumb', 'model', 
             'title', 'singular_title', 'primary_key', 'title_field', 
             'description', 'permissions', 'actions', 'contexts']

[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED
  status: 200
  hasData: true

[useCRUD6SchemaStore] ✅ Schema found in response.data (multi-context)  ← SUCCESS!
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
  model: 'users'
  context: 'list,form'
  cacheKey: 'users:list,form'
  fieldCount: 0  ← No root fields (fields are in contexts)
  hasContexts: true
```
