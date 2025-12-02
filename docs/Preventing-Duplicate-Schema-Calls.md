# Preventing Duplicate Schema API Calls

## Overview

The `useCRUD6Schema` composable has been enhanced to prevent duplicate API calls to `/api/crud6/{model}/schema` when the schema is already loaded.

## Problem

Previously, when components used `useCRUD6Schema` and called `loadSchema()` multiple times for the same model (e.g., in watchers or when re-mounting), it would make duplicate API calls even when the schema was already available.

## Solution

The composable now includes:

1. **Schema Caching**: Automatically caches the loaded schema and tracks the current model
2. **Smart Loading**: Skips API calls if schema is already loaded for the same model
3. **Direct Schema Setting**: Allows setting schema without making an API call
4. **Context-Aware Caching**: Caches schemas by model AND context (e.g., `users:list,detail,form`)
5. **Superset Caching**: When requesting a subset context, automatically uses cached superset if available (e.g., if `list,detail,form` is cached, requests for `list,form` will use the cached superset)

## Usage Patterns

### Pattern 1: Automatic Caching (Default Behavior)

The composable now automatically caches schemas and prevents duplicate calls:

```typescript
const { schema, loadSchema } = useCRUD6Schema()

// First call - makes API request
await loadSchema('users')

// Second call with same model - uses cached schema (no API call)
await loadSchema('users')

// Different model - makes new API request
await loadSchema('products')
```

### Pattern 2: Force Reload

If you need to force a reload of the schema:

```typescript
const { loadSchema } = useCRUD6Schema()

// Force reload even if schema is cached
await loadSchema('users', true)
```

### Pattern 3: Superset Context Caching

PageList and PageRow now use the same context (`list,detail,form`) to enable automatic cache sharing:

```typescript
// PageList.vue - loads schema with all contexts needed for navigation
await loadSchema('users', false, 'list,detail,form')

// PageRow.vue - reuses cached schema (no duplicate API call)
await loadSchema('users', false, 'list,detail,form', true) // includeRelated for detail sections

// The second call finds the cached 'users:list,detail,form' and uses it
// The 'includeRelated' parameter is only used if a new API call is needed
```

When user navigates from list page to detail page, the schema is already cached, eliminating duplicate API calls.

### Pattern 3: Direct Schema Setting

When you already have a schema (e.g., from parent component or initial page load):

```typescript
const { schema, setSchema } = useCRUD6Schema()

// Set schema directly without API call
setSchema(existingSchema, 'users')

// Now schema is available without making an API call
console.log(schema.value) // contains the schema
```

### Pattern 4: Parent-Child Schema Sharing

Parent component loads schema and passes it to children:

**Parent Component:**
```typescript
const { schema, loadSchema } = useCRUD6Schema()

// Load schema once in parent
await loadSchema('users')

// Pass schema to child via props
<ChildComponent :schema="schema" :model="'users'" />
```

**Child Component:**
```typescript
const props = defineProps<{
    schema: CRUD6Schema | null
    model: string
}>()

const { setSchema, schema: localSchema } = useCRUD6Schema()

onMounted(() => {
    if (props.schema) {
        // Use parent's schema - no API call
        setSchema(props.schema, props.model)
    }
})
```

## PageRow.vue Example

The PageRow.vue component in theme-crud6 can be optimized to prevent duplicate calls:

### Before (Multiple API Calls):
```typescript
const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
    await loadSchema(model.value) // API call 1
})

watch([model], async ([newModel]) => {
    await loadSchema(newModel) // API call 2 (duplicate!)
})
```

### After (Single API Call):
```typescript
const { schema, loadSchema } = useCRUD6Schema()

onMounted(async () => {
    await loadSchema(model.value) // API call 1
})

watch([model], async ([newModel]) => {
    await loadSchema(newModel) // Uses cache if same model, only calls API if different
})
```

## New Composable API

### Returns

- `schema`: Ref<CRUD6Schema | null> - The current schema
- `loading`: Ref<boolean> - Loading state
- `error`: Ref<ApiErrorResponse | null> - Error state
- `currentModel`: Ref<string | null> - Currently loaded model name
- `loadSchema(model: string, force?: boolean)`: Load schema (cached by default)
- `setSchema(schemaData: CRUD6Schema, model?: string)`: Set schema directly
- `sortableFields`: Computed<string[]> - List of sortable fields
- `filterableFields`: Computed<string[]> - List of filterable fields
- `filterableFields`: Computed<string[]> - List of searchable fields
- `tableColumns`: Computed<TableColumn[]> - Table column configuration
- `defaultSort`: Computed<Record<string, 'asc' | 'desc'>> - Default sort
- `hasPermission(action)`: Check user permission

### Methods

#### loadSchema(model: string, force?: boolean)

Loads schema for a model. Automatically caches and reuses schemas.

- `model`: The model name to load schema for
- `force`: Optional. Set to `true` to force reload even if cached

Returns: `Promise<CRUD6Schema | null>`

#### setSchema(schemaData: CRUD6Schema, model?: string)

Sets schema directly without making an API call.

- `schemaData`: The schema object to set
- `model`: Optional. The model name to associate with this schema

Returns: `void`

## Benefits

1. **Performance**: Eliminates unnecessary API calls
2. **Bandwidth**: Reduces network traffic
3. **Server Load**: Reduces server processing
4. **User Experience**: Faster page loads and navigation
5. **Flexibility**: Allows schema sharing between components

## Migration Guide

Existing code will continue to work without changes. The caching is automatic and transparent.

To optimize existing code:

1. Check if you're calling `loadSchema()` multiple times for the same model
2. Remove duplicate calls or use the `force` parameter only when needed
3. Consider using `setSchema()` when schema is already available from parent components
