# Multi-Context Schema Transformation Visual Guide

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ Step 1: Frontend Request                                        │
├─────────────────────────────────────────────────────────────────┤
│ GET /api/crud6/users/schema?context=list,form                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 2: Backend Response (Multi-Context Structure)              │
├─────────────────────────────────────────────────────────────────┤
│ {                                                               │
│   "model": "users",                                             │
│   "title": "Users",                                             │
│   "actions": [...],                                             │
│   "permissions": {...},                                         │
│   "contexts": {                    ◄─── NO 'fields' at root    │
│     "list": {                                                   │
│       "fields": {                  ◄─── Fields inside context  │
│         "user_name": {...},                                     │
│         "first_name": {...},                                    │
│         "email": {...}                                          │
│       },                                                        │
│       "actions": [...],                                         │
│       "default_sort": {...}                                     │
│     },                                                          │
│     "form": {                                                   │
│       "fields": {                  ◄─── Fields inside context  │
│         "user_name": {...},                                     │
│         "first_name": {...},                                    │
│         "email": {...},                                         │
│         "password": {...},                                      │
│         "role_ids": {...}                                       │
│       }                                                         │
│     }                                                           │
│   }                                                             │
│ }                                                               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 3: Frontend Processing (useCRUD6SchemaStore.ts)            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ A. Detect Multi-Context                                         │
│    ✓ Has 'contexts' object                                      │
│    ✓ No 'fields' at root                                        │
│                                                                 │
│ B. Extract Base Schema                                          │
│    baseSchema = {                                               │
│      model: "users",                                            │
│      title: "Users",                                            │
│      actions: [...],                                            │
│      permissions: {...}                                         │
│    }                                                            │
│                                                                 │
│ C. Cache Individual Contexts                                    │
│    users:list = baseSchema + contexts.list                      │
│    users:form = baseSchema + contexts.form                      │
│                                                                 │
│ D. Merge Requested Context Fields                              │
│    requestedContexts = ['list', 'form']                         │
│    mergedFields = {                                             │
│      ...contexts.list.fields,  ◄─── user_name, first_name, ... │
│      ...contexts.form.fields   ◄─── + password, role_ids       │
│    }                                                            │
│                                                                 │
│ E. Reconstruct Schema                                           │
│    schemaData = {                                               │
│      ...baseSchema,            ◄─── model, title, actions, ... │
│      fields: mergedFields      ◄─── ALL fields at root         │
│    }                                                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 4: Final Cached Schema (users:list,form)                   │
├─────────────────────────────────────────────────────────────────┤
│ {                                                               │
│   "model": "users",                                             │
│   "title": "Users",                                             │
│   "actions": [...],                                             │
│   "permissions": {...},                                         │
│   "fields": {                      ◄─── NOW at root level ✓    │
│     "user_name": {...},            ◄─── From list context      │
│     "first_name": {...},           ◄─── From list context      │
│     "email": {...},                ◄─── From both contexts     │
│     "password": {...},             ◄─── From form context      │
│     "role_ids": {...}              ◄─── From form context      │
│   }                                                             │
│ }                                                               │
│                                                                 │
│ ✅ Validation PASSES: has required 'fields' at root            │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ Step 5: Downstream Components Use Schema                        │
├─────────────────────────────────────────────────────────────────┤
│ ✓ PageList.vue reads schema.fields                             │
│ ✓ Table columns render from fields                             │
│ ✓ Form components get field definitions                        │
│ ✓ Validation rules apply from field.validation                 │
│ ✓ Actions display based on schema.actions                      │
└─────────────────────────────────────────────────────────────────┘
```

## Cache Structure After Processing

```
┌────────────────────────────────────────────────────────────────┐
│ Schema Store Cache                                              │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Key: "users:list,form"                                         │
│ ├─ model: "users"                                              │
│ ├─ fields: { merged list + form fields }   ◄─── 10 fields    │
│ ├─ actions: [...]                                              │
│ └─ permissions: {...}                                          │
│                                                                │
│ Key: "users:list"                                              │
│ ├─ model: "users"                                              │
│ ├─ fields: { list-specific fields }        ◄─── 6 fields     │
│ ├─ actions: [...]                                              │
│ ├─ default_sort: {...}                                         │
│ └─ permissions: {...}                                          │
│                                                                │
│ Key: "users:form"                                              │
│ ├─ model: "users"                                              │
│ ├─ fields: { form-specific fields }        ◄─── 10 fields    │
│ ├─ actions: [...]                                              │
│ └─ permissions: {...}                                          │
│                                                                │
└────────────────────────────────────────────────────────────────┘

Benefits:
- Future requests for "users:list" use cached data (no API call)
- Future requests for "users:form" use cached data (no API call)
- Original multi-context response also cached for reference
```

## Before vs After Comparison

### BEFORE (Broken) ❌
```javascript
// Response structure
response.data = {
  model: "users",
  contexts: {
    list: { fields: {...} },
    form: { fields: {...} }
  }
  // NO 'fields' at root
}

// Validation check
if ('fields' in response.data && response.data.fields) {
  // ❌ FAILS: No 'fields' at root
}

// Error thrown
throw new Error('Invalid schema response')
```

### AFTER (Fixed) ✅
```javascript
// Response structure (same from backend)
response.data = {
  model: "users",
  contexts: {
    list: { fields: {...} },
    form: { fields: {...} }
  }
}

// Multi-context detection
if ('contexts' in response.data && 
    response.data.contexts && 
    typeof response.data.contexts === 'object' &&
    !Array.isArray(response.data.contexts) &&
    Object.keys(response.data.contexts).length > 0) {
    
    // ✅ RECONSTRUCTION
    schemaData = {
      ...baseSchema,
      fields: mergedFields  // NOW at root
    }
}

// Validation passes
schemaData.fields  // ✅ EXISTS at root
```

## Key Insight

The fix transforms the multi-context structure **ON THE FRONTEND** to match the expected single-context structure:

**Multi-Context Structure** (from backend):
```
contexts: {
  list: { fields: {...} },
  form: { fields: {...} }
}
```

↓ **TRANSFORMS TO** ↓

**Single-Context Structure** (for frontend):
```
fields: { merged fields }
```

This allows:
1. ✅ Backend API stays unchanged
2. ✅ Frontend validation stays unchanged
3. ✅ Downstream components stay unchanged
4. ✅ Type safety maintained
5. ✅ Individual contexts cached for efficiency

## Edge Cases Handled

1. **Empty contexts**: `Object.keys(response.data.contexts).length > 0` ✓
2. **Array instead of object**: `!Array.isArray(response.data.contexts)` ✓
3. **Null/undefined contexts**: `response.data.contexts &&` ✓
4. **Missing requested context**: `if (response.data.contexts[ctxName])` ✓
5. **No fields in context**: `if (ctxData.fields)` ✓
6. **Single context request**: Falls through to direct fields check ✓
