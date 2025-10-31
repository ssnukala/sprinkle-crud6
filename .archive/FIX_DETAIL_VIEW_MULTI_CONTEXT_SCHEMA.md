# Fix: Detail View Not Showing with Multi-Context Schema Loading

## Issue Description

The detail view at `/crud6/users/1` was not displaying the detail table. Only the title and CRUD6.Edit/CRUD6.Delete buttons were visible. The issue was caused by the multi-context schema loading feature introduced in PR #141.

## Root Cause

When PageRow.vue loads schema with `context='detail,form'`, the backend SchemaService returns a multi-context response structure:

```javascript
{
  model: 'users',
  title: 'User Management',
  singular_title: 'User',
  primary_key: 'id',
  permissions: { ... },
  contexts: {
    detail: {
      fields: { ... },
      detail: {              // <-- Detail configuration nested here
        model: 'roles',
        foreign_key: 'user_id',
        list_fields: ['name', 'description']
      },
      detail_editable: { ... },
      render_mode: 'row'
    },
    form: {
      fields: { ... }
    }
  }
}
```

However, PageRow.vue was checking `schema?.detail` which returned `undefined` because the `detail` configuration was nested at `contexts.detail.detail` in multi-context responses.

## Solution

Added a `flattenedSchema` computed property in PageRow.vue that:

1. Detects multi-context schema responses (has `contexts` property)
2. Merges context-specific data to the root level
3. Maintains backward compatibility with single-context schemas

### Implementation

```typescript
// Flattened schema - handles multi-context responses
const flattenedSchema = computed(() => {
    if (!schema.value) return null
    
    // If schema has contexts property (multi-context response)
    if (schema.value.contexts) {
        // Start with base schema properties
        const flattened = {
            model: schema.value.model,
            title: schema.value.title,
            singular_title: schema.value.singular_title,
            description: schema.value.description,
            primary_key: schema.value.primary_key,
            permissions: schema.value.permissions,
        }
        
        // Merge 'detail' context data (includes detail config)
        if (schema.value.contexts.detail) {
            Object.assign(flattened, schema.value.contexts.detail)
        }
        
        // Merge 'form' context fields if needed
        if (isEditMode.value && schema.value.contexts.form) {
            if (!flattened.fields && schema.value.contexts.form.fields) {
                flattened.fields = schema.value.contexts.form.fields
            }
        }
        
        return flattened
    }
    
    // Single-context or full schema - use as-is
    return schema.value
})
```

## Files Modified

- `app/assets/views/PageRow.vue`
  - Added `flattenedSchema` computed property
  - Updated all references from `schema` to `flattenedSchema`
  - Ensures `schema.detail`, `schema.fields`, and other properties are accessible

## Schema Call Optimization

The fix maintains the optimization introduced in previous PRs:

**Before fix (3 schema calls per page load):**
1. `GET /api/crud6/users/schema` - PageRow loading full schema
2. `GET /api/crud6/users/schema?context=form` - Form component loading independently
3. `GET /api/crud6/users/schema?context=detail` - Details component loading independently

**After fix (1 schema call for parent model):**
1. `GET /api/crud6/users/schema?context=detail,form` - PageRow loading both contexts
2. Schema shared with Info, EditModal, and Form components via props
3. `GET /api/crud6/roles/schema` - Details component loading CHILD model schema (expected)

## Related Components

### Component Schema Flow

```
PageRow (loads schema with 'detail,form' context)
  ↓ (passes flattenedSchema via prop)
Info Component
  ↓ (passes finalSchema via prop)
EditModal
  ↓ (passes schema via prop)
Form Component (extracts form context if needed)
```

### Form Component Multi-Context Handling

The Form component already had logic to handle multi-context schemas:

```typescript
const schema = computed(() => {
    if (props.schema) {
        // Check if this is a multi-context schema response
        if (props.schema.contexts?.form) {
            return {
                ...props.schema,
                fields: props.schema.contexts.form.fields || props.schema.fields,
                ...props.schema.contexts.form
            }
        }
        return props.schema
    }
    return composableSchema.value
})
```

## Testing

To test the fix:

1. Navigate to `/crud6/users/1` (or any model with detail configuration)
2. Verify the detail table displays correctly
3. Check browser network tab - should see only 2 schema calls:
   - One for parent model with `context=detail,form`
   - One for child/detail model (if applicable)
4. Verify Edit button opens modal with correct form fields
5. Verify all data displays correctly in Info component

## Backward Compatibility

The fix maintains full backward compatibility:

- Single-context schemas (e.g., `context=detail`) work as before
- Full schemas (no context) work as before
- Multi-context schemas now properly flatten context data
- All child components respect schema props to avoid duplicate API calls

## Prevention

To prevent similar issues in the future:

1. Always use `flattenedSchema` in PageRow.vue when accessing schema properties
2. Ensure all child components accept and use schema props
3. Add logging to detect multi-context responses in development
4. Consider adding TypeScript types for multi-context schema structure
5. Document the multi-context schema structure in SchemaService

## Date

October 31, 2025

## Related Issues

- Issue: Detail view not showing detail table
- PR #141: Schema structure review (introduced multi-context support)
