# useCRUD6Api Function Rename Summary

## Overview
This document describes the function name changes in `useCRUD6Api` composable to match the expectations of the `@ssnukala/theme-crud6` package.

## Changes Made

### Function Renames

| Old Name (v0.4.3 and earlier) | New Name (v0.4.4+) | Description |
|-------------------------------|-------------------|-------------|
| `fetchCRUD6()` | `fetchRow()` | Fetch a single CRUD6 record by ID |
| `fetchCRUD6Row()` | `fetchRows()` | Alias for fetchRow (maintained for compatibility) |
| `createCRUD6()` | `createRow()` | Create a new CRUD6 record |
| `updateCRUD6()` | `updateRow()` | Update an existing CRUD6 record |
| `deleteCRUD6()` | `deleteRow()` | Delete a CRUD6 record |

### Unchanged Exports
The following exports remain unchanged:
- `apiLoading` - Loading state
- `apiError` - Error state
- `formData` - Form data reactive reference
- `r$` - Regle validation state
- `resetForm()` - Reset form to default state
- `slugLocked` - Slug field lock state

## Migration Guide

### Before (v0.4.3 and earlier)
```typescript
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'

const {
    fetchCRUD6,
    fetchCRUD6Row,
    createCRUD6,
    updateCRUD6,
    deleteCRUD6,
    apiLoading,
    apiError,
    formData,
    resetForm
} = useCRUD6Api()

// Usage
await fetchCRUD6(id)
await createCRUD6(data)
await updateCRUD6(id, data)
await deleteCRUD6(id)
```

### After (v0.4.4+)
```typescript
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'

const {
    fetchRow,
    fetchRows,  // alias for fetchRow
    createRow,
    updateRow,
    deleteRow,
    apiLoading,
    apiError,
    formData,
    resetForm
} = useCRUD6Api()

// Usage
await fetchRow(id)
await createRow(data)
await updateRow(id, data)
await deleteRow(id)
```

## Compatibility with theme-crud6

The function names now match the expectations in:
- `@ssnukala/theme-crud6/src/views/CRUD6/PageRow.vue`
- `@ssnukala/theme-crud6/src/components/Pages/CRUD6/Base/Form.vue`
- `@ssnukala/theme-crud6/src/components/Pages/CRUD6/Base/DeleteModal.vue`

## Files Modified

1. **app/assets/composables/useCRUD6Api.ts**
   - Renamed internal functions
   - Updated JSDoc comments
   - Updated return statement exports

2. **docs/UFTable-Integration.md**
   - Updated code examples with new function names

3. **examples/UFTable-Usage-Guide.md**
   - Updated code examples with new function names

## API Endpoints (Unchanged)

The backend API endpoints remain the same:
- `GET /api/crud6/{model}/{id}` - Fetch single record
- `POST /api/crud6/{model}` - Create record
- `PUT /api/crud6/{model}/{id}` - Update record
- `DELETE /api/crud6/{model}/{id}` - Delete record

## Breaking Changes

⚠️ **BREAKING CHANGE**: Applications using `useCRUD6Api` must update their code to use the new function names.

### Quick Migration Checklist
- [ ] Replace `fetchCRUD6` with `fetchRow`
- [ ] Replace `fetchCRUD6Row` with `fetchRow` or `fetchRows`
- [ ] Replace `createCRUD6` with `createRow`
- [ ] Replace `updateCRUD6` with `updateRow`
- [ ] Replace `deleteCRUD6` with `deleteRow`

## Rationale

The function names were simplified to:
1. Remove redundant "CRUD6" prefix (already implied by the composable name)
2. Match naming conventions in theme-crud6
3. Improve developer experience with shorter, clearer names
4. Align with common CRUD operation naming patterns

## Testing

After updating, verify that:
1. Fetch operations work correctly
2. Create operations save new records
3. Update operations modify existing records
4. Delete operations remove records
5. Error handling continues to function properly

## Support

For questions or issues related to this change, please refer to:
- Repository: https://github.com/ssnukala/sprinkle-crud6
- Issue Tracker: https://github.com/userfrosting/UserFrosting/issues
