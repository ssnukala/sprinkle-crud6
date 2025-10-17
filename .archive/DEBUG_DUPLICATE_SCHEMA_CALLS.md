# Debug Console Logs for Duplicate Schema Calls

**Date:** October 16, 2025  
**Commit:** 1131ae5  
**Issue:** PageRow making 2 schema calls for `crud6/groups/1`

## Problem

The PageRow component was making duplicate API calls to load the schema when viewing a single record (e.g., `crud6/groups/1`). This needs to be debugged to identify where the second call is coming from and why the caching mechanism isn't preventing it.

## Debug Strategy

Strategic console.log statements have been added at key points in the schema loading flow to trace:
1. When and where schema loading is initiated
2. Whether cache is being used or bypassed
3. Which component/composable is making the calls
4. The model name and force parameters

## Debug Logs Added

### 1. PageRow.vue - Schema Loading Watcher

**Location:** Line 236-260 (model watcher with `immediate: true`)

**Logs Added:**
```javascript
console.log('[PageRow] Schema loading triggered - model:', newModel, 'currentModel:', currentModel)
// ... after loadSchema completes ...
console.log('[PageRow] Schema loaded successfully for model:', newModel)
```

**Purpose:** Track when PageRow initiates schema loading through its model watcher.

### 2. useCRUD6Schema.ts - Composable

**Location 1:** Line 72-106 (loadSchema function)

**Logs Added:**
```javascript
// Cache hit
console.log('[useCRUD6Schema] Using cached schema - model:', model)

// API call
console.log('[useCRUD6Schema] Loading schema from API - model:', model, 'force:', force)

// Success
console.log('[useCRUD6Schema] Schema loaded successfully - model:', model)
```

**Purpose:** Track every schema load attempt, showing whether cache is used or API call is made.

**Location 2:** Line 173-176 (auto-load on init)

**Log Added:**
```javascript
console.log('[useCRUD6Schema] Auto-loading schema on init - modelName:', modelName)
```

**Purpose:** Identify if the composable is auto-loading schema when initialized with a model name.

### 3. Info.vue - Component

**Location 1:** Line 17 (component initialization)

**Log Added:**
```javascript
console.log('[Info] Component initialized - hasProvidedSchema:', !!providedSchema, 'crud6.id:', crud6?.id)
```

**Purpose:** Show when Info component mounts and whether it has a schema prop.

**Location 2:** Line 38 (before creating schemaComposable)

**Log Added:**
```javascript
console.log('[Info] Creating schemaComposable - providedSchema exists:', !!providedSchema, 'model:', model.value)
```

**Purpose:** Show the decision point - will a composable be created or not?

**Location 3:** Line 47-56 (finalSchema computed)

**Logs Added:**
```javascript
// Using provided schema (optimal)
console.log('[Info] Using PROVIDED schema from parent')

// Using composable schema (potential duplicate)
console.log('[Info] Using COMPOSABLE schema (fallback - this may indicate duplicate load)')

// No schema
console.log('[Info] NO schema available')
```

**Purpose:** Show which schema source is being used and flag potential duplicates.

## Expected Console Output

### Normal Flow (No Duplicates)

When visiting `/crud6/groups/1`, the expected console output should be:

```
[PageRow] Schema loading triggered - model: groups, currentModel: 
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[useCRUD6Schema] Schema loaded successfully - model: groups
[PageRow] Schema loaded successfully for model: groups
[Info] Component initialized - hasProvidedSchema: true, crud6.id: 1
[Info] Creating schemaComposable - providedSchema exists: true, model: groups
[Info] Using PROVIDED schema from parent
```

**Analysis:**
1. PageRow loads schema (1 API call)
2. Schema passed to Info via prop
3. Info uses provided schema
4. **Total API calls: 1** ✅

### Duplicate Call Scenario

If a duplicate call occurs, the logs might show:

**Scenario A - Info auto-loads despite having prop:**
```
[PageRow] Schema loading triggered - model: groups, currentModel: 
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[useCRUD6Schema] Schema loaded successfully - model: groups
[PageRow] Schema loaded successfully for model: groups
[Info] Component initialized - hasProvidedSchema: true, crud6.id: 1
[Info] Creating schemaComposable - providedSchema exists: true, model: groups
[useCRUD6Schema] Auto-loading schema on init - modelName: groups  ← PROBLEM!
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[Info] Using PROVIDED schema from parent
```

**Analysis:** Info composable shouldn't be created when providedSchema exists.

**Scenario B - Cache not working:**
```
[PageRow] Schema loading triggered - model: groups, currentModel: 
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[useCRUD6Schema] Schema loaded successfully - model: groups
[PageRow] Schema loaded successfully for model: groups
[Info] Component initialized - hasProvidedSchema: false, crud6.id: 1  ← PROBLEM!
[Info] Creating schemaComposable - providedSchema exists: false, model: groups
[useCRUD6Schema] Auto-loading schema on init - modelName: groups
[useCRUD6Schema] Loading schema from API - model: groups, force: false  ← DUPLICATE!
[Info] Using COMPOSABLE schema (fallback - this may indicate duplicate load)
```

**Analysis:** Schema prop not being passed correctly from PageRow to Info.

**Scenario C - Multiple composable instances:**
```
[PageRow] Schema loading triggered - model: groups, currentModel: 
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[useCRUD6Schema] Schema loaded successfully - model: groups
[PageRow] Schema loaded successfully for model: groups
[useCRUD6Schema] Auto-loading schema on init - modelName: groups  ← PROBLEM!
[useCRUD6Schema] Loading schema from API - model: groups, force: false
[Info] Component initialized - hasProvidedSchema: true, crud6.id: 1
[Info] Creating schemaComposable - providedSchema exists: true, model: groups
[Info] Using PROVIDED schema from parent
```

**Analysis:** Another component or composable instance is being created somewhere else.

## Debugging Steps

1. **Open Browser DevTools Console**
2. **Navigate to** `/crud6/groups/1`
3. **Observe Console Logs** - Look for the log pattern
4. **Identify the Issue:**
   - Count `[useCRUD6Schema] Loading schema from API` logs (should be 1)
   - Check if `Auto-loading schema on init` appears when it shouldn't
   - Verify `providedSchema exists: true` in Info component
   - Confirm `Using PROVIDED schema from parent` is logged

5. **Root Cause Analysis:**
   - If duplicate is from Info: Check why `providedSchema` is null/undefined
   - If duplicate is from auto-load: Check why composable is created with model name
   - If duplicate is timing issue: Check component lifecycle and prop reactivity

## Potential Root Causes

### 1. Prop Passing Issue
**Symptom:** `hasProvidedSchema: false` in Info logs

**Check:**
```vue
<!-- PageRow.vue line 428 -->
<CRUD6Info :crud6="CRUD6Row" :schema="schema" @crud6Updated="fetch()" />
```

Ensure `schema` reactive value exists when Info component mounts.

### 2. Composable Instance Sharing
**Symptom:** Multiple `useCRUD6Schema` instances created

**Check:** Each call to `useCRUD6Schema()` creates a new instance. Ensure:
- PageRow creates one instance (line 25-31)
- Info only creates if no prop (line 38-39)
- No other components/composables auto-instantiate

### 3. Reactivity Timing
**Symptom:** Schema prop updates after Info mounts

**Check:** If `schema.value` is null initially but updates later, Info won't see the prop.

**Solution:** Ensure schema is loaded before rendering Info:
```vue
<CRUD6Info v-if="schema" :crud6="CRUD6Row" :schema="schema" ... />
```

### 4. Route Change Side Effects
**Symptom:** Schema loads twice on route change

**Check:** Watchers with `immediate: true` can trigger on every route change.

## Resolution Steps

Once the duplicate call is identified through logs:

1. **Fix the root cause** based on the scenario identified
2. **Test the fix** - Verify only 1 API call appears
3. **Remove or comment out debug logs** - Keep only essential error logs
4. **Document the fix** - Update this file with the solution

## Success Criteria

After fixing:
- ✅ Only 1 API call to `/api/crud6/{model}/schema` per page load
- ✅ Schema cached and reused when navigating within same model
- ✅ Console shows `[Info] Using PROVIDED schema from parent`
- ✅ No `[useCRUD6Schema] Loading schema from API` duplicates

## Notes

- Debug logs use prefixes `[PageRow]`, `[useCRUD6Schema]`, `[Info]` for easy filtering
- All logs include relevant context (model name, cache status, prop status)
- Logs are production-safe (only console.log, no errors thrown)
- Can be removed once issue is fixed and verified

## Related Files

- `app/assets/views/PageRow.vue` - Main page component
- `app/assets/composables/useCRUD6Schema.ts` - Schema loading composable
- `app/assets/components/CRUD6/Info.vue` - Info display component
