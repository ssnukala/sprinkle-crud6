# SmartLookup Nested Lookup Object Feature

## Overview

SmartLookup fields now support a cleaner, more intuitive nested `lookup` object structure instead of flat attributes with the `lookup_` prefix.

## New Structure (Recommended)

```json
{
    "customer_id": {
        "type": "smartlookup",
        "label": "Customer",
        "required": true,
        "lookup": {
            "model": "customers",
            "id": "id",
            "desc": "name"
        },
        "placeholder": "Search for a customer...",
        "description": "Type to search for customers by name"
    }
}
```

## Legacy Structure (Still Supported)

The old flat structure is still fully supported for backward compatibility:

```json
{
    "customer_id": {
        "type": "smartlookup",
        "label": "Customer",
        "required": true,
        "lookup_model": "customers",
        "lookup_id": "id",
        "lookup_desc": "name",
        "placeholder": "Search for a customer...",
        "description": "Type to search for customers by name"
    }
}
```

## Benefits of Nested Structure

1. **Cleaner and More Intuitive**: Groups related configuration together
2. **Consistent**: Matches the pattern of other nested objects like `validation`
3. **Easier to Extend**: Future features can be added within the `lookup` object
4. **Better IDE Support**: Autocomplete and validation work better with nested structures
5. **Shorter Attribute Names**: `model`, `id`, `desc` instead of `lookup_model`, `lookup_id`, `lookup_desc`

## Lookup Object Attributes

| Attribute | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `model` | string | Yes | - | The model name to look up (e.g., "customers", "products") |
| `id` | string | No | "id" | The field name to use as the ID/value |
| `desc` | string | No | "name" | The field name to display to users |

## Fallback Logic

The system supports multiple fallback layers for maximum compatibility:

**Priority Order** (first available wins):
1. Flat attributes: `lookup_model`, `lookup_id`, `lookup_desc`
2. Nested lookup object: `lookup.model`, `lookup.id`, `lookup.desc`
3. Shorthand attributes: `model`, `id`, `desc`

**Example**: All three of these are equivalent:

```json
// Option 1: New nested structure
{
    "type": "smartlookup",
    "lookup": {
        "model": "customers",
        "id": "id",
        "desc": "name"
    }
}

// Option 2: Legacy flat structure
{
    "type": "smartlookup",
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name"
}

// Option 3: Shorthand (also supported)
{
    "type": "smartlookup",
    "model": "customers",
    "id": "id",
    "desc": "name"
}
```

## Backend Processing

### SchemaService Normalization

The `SchemaService::normalizeLookupAttributes()` method automatically:

1. Detects smartlookup fields
2. Expands nested `lookup` object to flat attributes internally
3. Provides fallbacks to shorthand attributes
4. Ensures consistent internal representation

This means the rest of the codebase always works with the flat `lookup_model`, `lookup_id`, `lookup_desc` attributes, ensuring backward compatibility.

### How It Works

```php
// Input schema (nested)
"customer_id": {
    "type": "smartlookup",
    "lookup": {
        "model": "customers",
        "id": "id",
        "desc": "name"
    }
}

// After normalization (internal representation)
"customer_id": {
    "type": "smartlookup",
    "lookup": { /* preserved */ },
    "lookup_model": "customers",  // Added
    "lookup_id": "id",            // Added
    "lookup_desc": "name"         // Added
}
```

## Frontend Support

All frontend components support both structures with fallback logic:

### Components Updated

1. **useCRUD6FieldRenderer.ts**: Field renderer composable
2. **Form.vue**: CRUD6 form component
3. **PageRow.vue**: Row detail/edit page
4. **DetailGrid.vue**: Detail grid component  
5. **PageMasterDetail.vue**: Master-detail page

### Fallback Pattern

```typescript
// All components use this pattern
:model="field.lookup_model || field.lookup?.model || field.model"
:id-field="field.lookup_id || field.lookup?.id || field.id || 'id'"
:display-field="field.lookup_desc || field.lookup?.desc || field.desc || 'name'"
```

This ensures:
- Legacy flat attributes work
- New nested structure works
- Shorthand attributes work
- Sensible defaults when nothing is specified

## Migration Guide

### For New Schemas

Use the new nested structure:

```json
{
    "type": "smartlookup",
    "lookup": {
        "model": "model_name",
        "id": "id",
        "desc": "name"
    }
}
```

### For Existing Schemas

**No changes required!** Your existing schemas will continue to work exactly as before.

**Optional**: You can gradually migrate to the new structure by:

1. Copy your existing schema file
2. Replace flat attributes with nested `lookup` object:
   ```json
   // Before
   "lookup_model": "customers",
   "lookup_id": "id",
   "lookup_desc": "name"
   
   // After
   "lookup": {
       "model": "customers",
       "id": "id",
       "desc": "name"
   }
   ```
3. Remove the old flat attributes
4. Test to ensure everything works

## Examples

See the following example files:

- **New Structure**: `examples/schema/smartlookup-example.json`
- **Legacy Structure**: `examples/schema/smartlookup-legacy-example.json`

## Future Extensibility

The nested `lookup` object makes it easy to add future features:

```json
{
    "type": "smartlookup",
    "lookup": {
        "model": "customers",
        "id": "id",
        "desc": "name",
        // Future features:
        "search_fields": ["name", "email", "phone"],
        "filters": {
            "is_active": true,
            "deleted_at": null
        },
        "order_by": "name",
        "limit": 50
    }
}
```

## Testing

Both structures have been tested and validated:

1. ✅ Schema loading and normalization
2. ✅ Backend API responses
3. ✅ Frontend component rendering
4. ✅ Form submission and validation
5. ✅ Backward compatibility

## Related Documentation

- [SCHEMA_OPTIMIZATION_ANALYSIS.md](.archive/SCHEMA_OPTIMIZATION_ANALYSIS.md) - Comprehensive schema structure analysis
- [examples/README.md](../examples/README.md) - Example schemas and usage
- [docs/AutoLookup.md](../docs/AutoLookup.md) - AutoLookup component documentation

## Changelog

### Version 6.x (Current)
- Added support for nested `lookup` object
- Maintained full backward compatibility with flat attributes
- Updated all frontend components to support both structures
- Added normalization in SchemaService
- Created example schemas demonstrating both approaches
