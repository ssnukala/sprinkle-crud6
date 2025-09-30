# Optimizing PageRow.vue for theme-crud6

This guide shows how to optimize the PageRow.vue component in the theme-crud6 repository to eliminate duplicate schema API calls.

## Current Implementation (Before Fix)

The current PageRow.vue in theme-crud6 makes duplicate API calls:

```typescript
// In PageRow.vue (theme-crud6)
const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
    if (model.value) {
        await loadSchema(model.value)  // API call 1
        
        if (!isCreateMode.value && recordId.value) {
            fetch()
        }
    }
})

watch([model, recordId], async ([newModel, newId]) => {
    if (newModel) {
        await loadSchema(newModel)  // API call 2 (duplicate if same model!)
        
        if (newId && !isCreateMode.value) {
            fetch()
        }
    }
})
```

**Problem**: When the component mounts and then the watcher fires immediately, `loadSchema` is called twice for the same model.

## Optimized Implementation (After Fix)

With the enhanced `useCRUD6Schema` composable, the duplicate calls are automatically prevented:

```typescript
// In PageRow.vue (theme-crud6) - No changes needed!
const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
    if (model.value) {
        await loadSchema(model.value)  // API call - loads schema
        
        if (!isCreateMode.value && recordId.value) {
            fetch()
        }
    }
})

watch([model, recordId], async ([newModel, newId]) => {
    if (newModel) {
        await loadSchema(newModel)  // Uses cached schema - NO API call!
        
        if (newId && !isCreateMode.value) {
            fetch()
        }
    }
})
```

**Solution**: The composable automatically caches the schema. The second `loadSchema` call returns the cached schema without making an API call.

## Even Better: Conditional Loading

For even more efficiency, you can check if the model actually changed:

```typescript
// In PageRow.vue (theme-crud6) - Optimized version
const { schema, loadSchema, currentModel } = useCRUD6Schema()

onMounted(async () => {
    if (model.value) {
        await loadSchema(model.value)
        
        if (!isCreateMode.value && recordId.value) {
            fetch()
        }
    }
})

watch([model, recordId], async ([newModel, newId], [oldModel]) => {
    if (newModel && newModel !== oldModel) {
        // Only load schema if model actually changed
        await loadSchema(newModel)
    }
    
    if (newId && !isCreateMode.value) {
        fetch()
    }
})
```

## Best Practice: Parent-Child Schema Sharing

If you have a parent component that already loaded the schema, you can share it:

### Parent Component (e.g., PageList.vue)
```typescript
const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
    await loadSchema(model.value)
})

// Pass schema to child
<PageRow :schema="schema" :model="model" />
```

### Child Component (PageRow.vue)
```typescript
const props = defineProps<{
    schema?: CRUD6Schema | null
    model: string
}>()

const { 
    schema: localSchema, 
    setSchema, 
    loadSchema 
} = useCRUD6Schema()

onMounted(async () => {
    if (props.schema) {
        // Use parent's schema - NO API call!
        setSchema(props.schema, props.model)
    } else {
        // Load schema if not provided
        await loadSchema(props.model)
    }
    
    if (!isCreateMode.value && recordId.value) {
        fetch()
    }
})
```

## API Call Comparison

### Before (Old Implementation)
1. Component mounts → API call to `/api/crud6/{model}/schema`
2. Watcher fires → Another API call to `/api/crud6/{model}/schema`
3. User navigates to same model → Yet another API call

**Total: 3+ API calls for the same schema**

### After (With Caching)
1. Component mounts → API call to `/api/crud6/{model}/schema` (cached)
2. Watcher fires → Uses cached schema (no API call)
3. User navigates to same model → Uses cached schema (no API call)

**Total: 1 API call, subsequent accesses use cache**

### With Parent-Child Sharing
1. Parent loads → API call to `/api/crud6/{model}/schema`
2. Child receives schema via props → `setSchema()` (no API call)
3. Watcher fires → Uses cached schema (no API call)

**Total: 1 API call shared across components**

## Testing the Optimization

To verify the optimization is working:

1. Open browser DevTools → Network tab
2. Navigate to a CRUD6 page (e.g., `/crud6/users/123`)
3. Filter for requests to `/api/crud6/*/schema`
4. You should see only ONE request per unique model

### Before Optimization
```
GET /api/crud6/users/schema  ← onMounted
GET /api/crud6/users/schema  ← watcher
```

### After Optimization
```
GET /api/crud6/users/schema  ← onMounted (watcher uses cache)
```

## Force Reload (When Needed)

In rare cases where you need to force reload the schema (e.g., after schema update):

```typescript
// Force reload schema, bypass cache
await loadSchema(model.value, true)
```

## Summary

1. **No changes required**: The caching is automatic and transparent
2. **Performance boost**: Eliminates unnecessary API calls
3. **Flexibility**: Can force reload or share schemas between components
4. **Backward compatible**: Existing code continues to work

The enhanced `useCRUD6Schema` composable makes your application faster and more efficient without requiring any code changes!
