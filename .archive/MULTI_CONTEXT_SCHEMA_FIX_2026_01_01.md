# Multi-Context Schema Response Fix

**Date**: 2026-01-01  
**Issue**: Frontend failing to load schemas with multiple contexts (e.g., `context=list,form`)  
**Error**: "Invalid schema response structure"

## Problem Analysis

### Backend Behavior (Correct)
When multiple contexts are requested via `?context=list,form`, the backend correctly returns:
```json
{
  "model": "users",
  "title": "Users",
  "actions": [...],
  "permissions": {...},
  "contexts": {
    "list": {
      "fields": { "user_name": {...}, "first_name": {...} },
      "actions": [...]
    },
    "form": {
      "fields": { "user_name": {...}, "password": {...} }
    }
  }
}
```

**Note**: No `fields` at root level - fields are inside each context object.

### Frontend Expectation
The CRUD6Schema interface and downstream validation logic expects:
```typescript
interface CRUD6Schema {
  model: string
  fields: Record<string, SchemaField>  // Required at root
  // ...
}
```

### Root Cause
Frontend validation in `useCRUD6SchemaStore.ts` was rejecting multi-context responses because:
1. Response has `contexts` object ✓
2. Response has NO `fields` at root ✓ (by design)
3. Validation expected `fields` to always be at root ✗

## Solution Implemented

### Approach
Reconstruct the multi-context response to have `fields` at root level for compatibility with existing validation logic.

### Implementation Details

**File**: `app/assets/stores/useCRUD6SchemaStore.ts`  
**Lines**: 279-338

#### Step 1: Detect Multi-Context Response
```typescript
if ('contexts' in response.data && 
    response.data.contexts && 
    typeof response.data.contexts === 'object' && 
    !Array.isArray(response.data.contexts) &&
    Object.keys(response.data.contexts).length > 0) {
```

#### Step 2: Extract Base Schema
```typescript
const baseSchema = { ...response.data }
delete baseSchema.contexts
// baseSchema = { model, title, actions, permissions, ... }
```

#### Step 3: Cache Individual Contexts
```typescript
for (const [ctxName, ctxData] of Object.entries(response.data.contexts)) {
  const ctxCacheKey = getCacheKey(model, ctxName)  // e.g., "users:list"
  const ctxSchema = { ...baseSchema, ...ctxData }
  schemas.value[ctxCacheKey] = ctxSchema
}
// Now "users:list" and "users:form" are cached separately
```

#### Step 4: Merge Fields from Requested Contexts
```typescript
const requestedContexts = context.split(',').map(c => c.trim())  // ['list', 'form']
let mergedFields = {}
let mergedContextData = {}

for (const ctxName of requestedContexts) {
  const ctxData = response.data.contexts[ctxName]
  mergedFields = { ...mergedFields, ...ctxData.fields }
  mergedContextData = { ...mergedContextData, ...ctxData }
}
```

#### Step 5: Reconstruct Schema with Fields at Root
```typescript
schemaData = {
  ...baseSchema,           // model, title, actions, permissions
  ...mergedContextData,    // context-specific properties
  fields: mergedFields     // merged fields from all contexts
}
// Result: { model, title, actions, permissions, fields: {...} }
```

#### Step 6: Cache Reconstructed Schema
```typescript
schemas.value[cacheKey] = schemaData  // Cache as "users:list,form"
```

## Benefits

1. **Backward Compatible**: All downstream code expecting `fields` at root continues to work
2. **Efficient Caching**: Individual contexts cached separately for future single-context requests
3. **No Backend Changes**: Backend API remains unchanged
4. **Clean Separation**: Frontend handles response structure transformation

## Testing

### Backend Test Coverage
**File**: `app/tests/Controller/SchemaActionTest.php`
- `testSchemaSingleContextReturnsFieldsAtRoot()` - Single context returns fields at root
- `testSchemaMultiContextReturnsContextsObject()` - Multi-context returns contexts object

### Manual Verification Steps
1. Request schema: `GET /api/crud6/users/schema?context=list,form`
2. Verify response has `contexts` object without root `fields`
3. Frontend should reconstruct with `fields` at root
4. Downstream components should work without errors

## Future Considerations

### Alternative Approaches Considered
1. **Change validation logic**: Would require changes throughout the codebase
2. **Make fields optional**: Would break TypeScript type safety
3. **Backend reconstruction**: Would duplicate logic and affect API consistency

### Recommendation
Current approach is optimal because:
- Minimal changes (single file)
- Preserves type safety
- Maintains API consistency
- Easy to maintain and test

## Related Files
- `app/assets/stores/useCRUD6SchemaStore.ts` - Main fix
- `app/assets/composables/useCRUD6Schema.ts` - Type definition
- `app/src/Controller/ApiAction.php` - Backend endpoint
- `app/src/ServicesProvider/SchemaFilter.php` - Schema filtering logic
- `app/tests/Controller/SchemaActionTest.php` - Test coverage
