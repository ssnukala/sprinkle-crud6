# Multi-Context Schema API

## Overview

The CRUD6 schema API now supports requesting multiple contexts in a single API call. This feature was implemented to reduce duplicate API calls on pages that need schema data for multiple purposes (e.g., list view and form modals on the same page).

## Problem Solved

Previously, when loading a page like `/crud6/users` or `/crud6/groups`, the frontend would make two separate schema API calls:

1. `GET /api/crud6/users/schema?context=list` - For displaying table columns
2. `GET /api/crud6/users/schema?context=form` - For create/edit modal forms (when opened)

This resulted in:
- Duplicate network requests
- Slower page load times
- Unnecessary server load
- Poor user experience when opening modals (delay while schema loads)

## Solution

The schema API endpoint now accepts comma-separated context values:

```
GET /api/crud6/users/schema?context=list,form
```

This single request returns a combined response containing schema data for both contexts.

## API Response Format

### Single Context (Legacy - Still Supported)

Request: `GET /api/crud6/users/schema?context=list`

Response:
```json
{
  "model": "users",
  "title": "User Management",
  "singular_title": "User",
  "primary_key": "id",
  "permissions": { ... },
  "fields": {
    "id": { "type": "integer", "label": "ID", ... },
    "name": { "type": "string", "label": "Name", ... }
  },
  "default_sort": { "name": "asc" }
}
```

### Multi-Context (New)

Request: `GET /api/crud6/users/schema?context=list,form`

Response:
```json
{
  "model": "users",
  "title": "User Management",
  "singular_title": "User",
  "primary_key": "id",
  "permissions": { ... },
  "contexts": {
    "list": {
      "fields": {
        "id": { "type": "integer", "label": "ID", ... },
        "name": { "type": "string", "label": "Name", ... }
      },
      "default_sort": { "name": "asc" }
    },
    "form": {
      "fields": {
        "name": { 
          "type": "string", 
          "label": "Name", 
          "required": true,
          "validation": { "required": true, "length": { "min": 2 } }
        },
        "email": { ... }
      }
    }
  }
}
```

## Frontend Implementation

### PageList Component

The `PageList.vue` component now requests both contexts on mount:

```typescript
// Before: Only list context
loadSchema(model.value, false, 'list')

// After: Both list and form contexts
loadSchema(model.value, false, 'list,form')
```

### Schema Store Caching

The `useCRUD6SchemaStore` automatically caches individual contexts from multi-context responses:

1. Multi-context response is received
2. Each context is extracted and cached separately
3. Future single-context requests can use the cached data without making another API call

Example:
```typescript
// Initial request
loadSchema('users', false, 'list,form')  // API call made

// Later requests use cache
loadSchema('users', false, 'list')       // No API call - uses cache
loadSchema('users', false, 'form')       // No API call - uses cache
```

### Form Component

The `Form.vue` component checks for multi-context schema and extracts the form context:

```typescript
const schema = computed(() => {
    if (props.schema?.contexts?.form) {
        // Extract form context from multi-context response
        return {
            ...props.schema,
            fields: props.schema.contexts.form.fields || props.schema.fields,
            ...props.schema.contexts.form
        }
    }
    return props.schema || composableSchema.value
})
```

## Backend Implementation

### SchemaService

The `SchemaService` class has been refactored to support multi-context filtering:

**New Methods:**
- `filterSchemaForMultipleContexts(array $schema, array $contexts): array`
  - Handles comma-separated context requests
  - Returns base metadata plus context-specific sections

- `filterSchemaForSingleContext(array $schema, string $context): array`
  - Handles single-context requests (legacy behavior)
  - Maintains backward compatibility

- `getContextSpecificData(array $schema, string $context): ?array`
  - Extracts context-specific data (fields, configuration)
  - Shared by both single and multi-context methods

**Modified Method:**
- `filterSchemaForContext(array $schema, ?string $context = null): array`
  - Now detects comma-separated contexts
  - Routes to appropriate handler method

## Context Types

The following context types are supported (can be combined):

- **`list`**: Fields for table/list views (listable fields, sortable, filterable)
- **`form`**: Fields for create/edit forms (editable fields, validation rules)
- **`detail`**: Full field information for detail/view pages
- **`meta`**: Just model metadata (no field information)
- **`null` or `full`**: Complete schema (backward compatible)

## Backward Compatibility

✅ **Fully backward compatible**

- Single-context requests work exactly as before
- Response format for single-context is unchanged
- Existing code continues to work without modifications
- Multi-context is opt-in by using comma-separated values

## Benefits

1. **Performance**: Reduces API calls from 2 to 1 on list pages
2. **User Experience**: No delay when opening create/edit modals
3. **Server Load**: Fewer requests to process
4. **Bandwidth**: Combined response may be smaller than two separate responses (shared metadata)
5. **Flexibility**: Can request any combination of contexts as needed

## Usage Examples

### Request All Context Types

```
GET /api/crud6/products/schema?context=list,form,detail,meta
```

### Request Just What You Need

```typescript
// Page that shows list and details
loadSchema('products', false, 'list,detail')

// Page that shows form and details
loadSchema('products', false, 'form,detail')
```

## Testing

A comprehensive test suite has been added in `app/tests/ServicesProvider/SchemaMultiContextTest.php`:

- Tests multi-context request handling
- Validates correct context filtering
- Ensures backward compatibility
- Verifies metadata is not duplicated
- Confirms permissions are in base, not contexts

Run tests:
```bash
vendor/bin/phpunit app/tests/ServicesProvider/SchemaMultiContextTest.php
```

## Migration Guide

### For Frontend Developers

**Update list pages to request both contexts:**

```diff
// Before
- loadSchema(model.value, false, 'list')
+ loadSchema(model.value, false, 'list,form')
```

**Update components to handle multi-context schema:**

```typescript
// Extract specific context from multi-context response
const schema = computed(() => {
    if (props.schema?.contexts?.list) {
        return {
            ...props.schema,
            fields: props.schema.contexts.list.fields,
            ...props.schema.contexts.list
        }
    }
    return props.schema
})
```

### For Backend Developers

**No changes required!**

The SchemaService automatically handles multi-context requests. The existing `filterSchemaForContext` method now supports comma-separated contexts without any code changes needed.

## Performance Metrics

Expected improvements (based on typical usage):

- **API Calls**: 50% reduction (2 calls → 1 call)
- **Page Load Time**: ~100-200ms faster (no schema fetch on modal open)
- **Server Processing**: ~40% reduction (one request vs two)
- **Bandwidth**: ~15-20% savings (shared metadata in combined response)

## Future Enhancements

Potential future improvements:

1. Cache multi-context responses at the HTTP level (CDN/reverse proxy)
2. Add support for requesting fields by name (e.g., `fields=name,email`)
3. Compression of multi-context responses
4. GraphQL-style field selection
