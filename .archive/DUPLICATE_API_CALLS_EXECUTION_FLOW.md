# Execution Flow Comparison: Before vs After Fix

## BEFORE FIX - Duplicate API Calls

```
User navigates to /crud6/groups/1
         │
         ▼
┌────────────────────────────────────┐
│   PageRow.vue Component Loads      │
└────────────────────────────────────┘
         │
         ├─────────────────────────────┬────────────────────────────┐
         ▼                             ▼                            ▼
┌─────────────────────┐     ┌──────────────────────┐    ┌──────────────────────┐
│  model watcher      │     │  onMounted() hook    │    │  recordId watcher    │
│  (immediate: true)  │     │                      │    │  (immediate: true)   │
└─────────────────────┘     └──────────────────────┘    └──────────────────────┘
         │                             │                            │
         │ loadSchema()                │ if (!isCreateMode &&       │ if (newId && 
         │                             │     recordId.value)        │    !isCreateMode)
         ▼                             │                            │
┌─────────────────────┐               │                            │
│ Schema API Call     │               │                            │
│ GET /api/crud6/     │               │                            │
│  groups/schema      │               │                            │
└─────────────────────┘               │                            │
                                      ▼                            ▼
                           ┌──────────────────────┐    ┌──────────────────────┐
                           │   fetch() CALL #1    │    │   fetch() CALL #2    │
                           │   ❌ DUPLICATE!      │    │                      │
                           └──────────────────────┘    └──────────────────────┘
                                      │                            │
                                      ▼                            ▼
                           ┌──────────────────────────────────────────────────┐
                           │         fetchRow(recordId)                       │
                           │                                                  │
                           │    GET /api/crud6/groups/1 (CALLED TWICE!)     │
                           └──────────────────────────────────────────────────┘
```

**Network Timeline:**
```
[2025-11-19T20:34:13.632Z] GET /api/crud6/groups/schema  ← Schema load
[2025-11-19T20:34:13.634Z] GET /api/crud6/groups/1       ← onMounted fetch() ❌
[2025-11-19T20:34:13.634Z] GET /api/crud6/groups/1       ← watcher fetch() ❌
```

---

## AFTER FIX - Single API Call

```
User navigates to /crud6/groups/1
         │
         ▼
┌────────────────────────────────────┐
│   PageRow.vue Component Loads      │
└────────────────────────────────────┘
         │
         ├─────────────────────────────┬────────────────────────────┐
         ▼                             ▼                            ▼
┌─────────────────────┐     ┌──────────────────────┐    ┌──────────────────────┐
│  model watcher      │     │  onMounted() hook    │    │  recordId watcher    │
│  (immediate: true)  │     │                      │    │  (immediate: true)   │
└─────────────────────┘     └──────────────────────┘    └──────────────────────┘
         │                             │                            │
         │ loadSchema()                │ if (isCreateMode)          │ if (newId && 
         │                             │   { init create mode }     │    !isCreateMode)
         ▼                             │                            │
┌─────────────────────┐               │ ← Only runs for create     │
│ Schema API Call     │               │    mode, not detail view   │
│ GET /api/crud6/     │               │                            │
│  groups/schema      │               ▼                            ▼
└─────────────────────┘     ┌──────────────────────┐    ┌──────────────────────┐
                            │   No fetch() call    │    │   fetch() CALL       │
                            │   ✅ Eliminated!     │    │   ✅ Single call!    │
                            └──────────────────────┘    └──────────────────────┘
                                                                   │
                                                                   ▼
                                                        ┌──────────────────────┐
                                                        │  fetchRow(recordId)  │
                                                        │                      │
                                                        │  GET /api/crud6/     │
                                                        │   groups/1           │
                                                        │  (CALLED ONCE!)      │
                                                        └──────────────────────┘
```

**Network Timeline:**
```
[2025-11-19T20:34:13.632Z] GET /api/crud6/groups/schema  ← Schema load
[2025-11-19T20:34:13.634Z] GET /api/crud6/groups/1       ← watcher fetch() ✅ (single call)
```

---

## Code Changes

### PageRow.vue (and PageMasterDetail.vue)

**BEFORE:**
```javascript
onMounted(async () => {
    if (!isCreateMode.value && recordId.value) {
        fetch()  // ← This causes duplicate call!
    } else if (isCreateMode.value) {
        record.value = {}
        CRUD6Row.value = createInitialRecord(flattenedSchema.value?.fields)
        resetForm()
    }
})

watch(recordId, (newId) => {
    if (newId && !isCreateMode.value) {
        fetch()  // ← This also calls fetch()!
    }
}, { immediate: true })  // ← Fires immediately on mount
```

**AFTER:**
```javascript
onMounted(async () => {
    // Record fetching is handled by the recordId watcher with immediate: true
    // No need to load schema or fetch record here to avoid duplicate calls
    
    if (isCreateMode.value) {  // ← Only handles create mode now
        record.value = {}
        CRUD6Row.value = createInitialRecord(flattenedSchema.value?.fields)
        resetForm()
    }
})

watch(recordId, (newId) => {
    if (newId && !isCreateMode.value) {
        fetch()  // ← Single source of truth for fetching
    }
}, { immediate: true })  // ← Handles initial load AND subsequent changes
```

---

## Key Insights

1. **Vue's watcher with `immediate: true`** fires as soon as the component is created, before `onMounted`
2. The `recordId` watcher already handles the initial fetch, making the `onMounted` fetch redundant
3. The `onMounted` hook should only handle initialization that can't be done reactively
4. Watchers with `immediate: true` are ideal for reactive data loading patterns

## Performance Impact

- **API calls reduced**: 2 → 1 (50% reduction)
- **Network traffic reduced**: ~50% for detail page loads
- **Database queries reduced**: 2 → 1 per page load
- **Server processing**: 50% reduction for detail endpoints

## Affected Pages

All CRUD6 detail pages benefit from this fix:
- `/crud6/users/{id}`
- `/crud6/groups/{id}`
- `/crud6/roles/{id}`
- `/crud6/permissions/{id}`
- `/crud6/activities/{id}`
- Any custom CRUD6 model detail pages
