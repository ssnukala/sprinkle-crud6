# Schema API Context Filtering - Quick Reference

## For Frontend Developers

### When to Use Each Context

```typescript
// ✅ List/Table Views (PageList)
await loadSchema(model, false, 'list')
// Returns: Only listable fields, sortable/filterable flags

// ✅ Detail/View Pages (PageRow, PageMasterDetail)  
await loadSchema(model, false, 'detail')
// Returns: All fields + relationships + detail config

// ✅ Create/Edit Forms
await loadSchema(model, false, 'form')
// Returns: Editable fields + validation rules

// ✅ Navigation/Permissions
await loadSchema(model, false, 'meta')
// Returns: Model metadata only (no fields)

// ⚠️ Backward Compatible (not recommended)
await loadSchema(model)
// Returns: Complete schema (security risk)
```

### Context Comparison

| What You Need | Context | What You Get |
|--------------|---------|--------------|
| Show in table | `list` | Listable fields, display props |
| Create/edit form | `form` | Editable fields, validation |
| Detail page | `detail` | All fields, relationships |
| Check permissions | `meta` | Just permissions, no fields |

## For Schema Authors

### Field Visibility Control

```json
{
  "fields": {
    "id": {
      "listable": true,   // ✅ Show in lists
      "editable": false   // ❌ Hide from forms
    },
    "name": {
      "listable": true,   // ✅ Show in lists
      "editable": true    // ✅ Show in forms
    },
    "password": {
      "listable": false,  // ❌ Hide from lists
      "editable": true    // ✅ Show in forms
    },
    "internal_notes": {
      "listable": false,  // ❌ Hide from lists
      "editable": true    // ✅ Show in forms (admin only)
    }
  }
}
```

### Security Best Practices

```json
{
  "fields": {
    // ✅ GOOD: Public field
    "product_name": {
      "listable": true,
      "editable": true
    },
    
    // ✅ GOOD: Hidden sensitive field
    "internal_cost": {
      "listable": false,
      "editable": true
    },
    
    // ⚠️ CAUTION: Validation visible in forms
    "password": {
      "listable": false,
      "editable": true,
      "validation": {
        "min_length": 8  // Only in 'form' context
      }
    }
  }
}
```

## Caching Behavior

### How It Works

```
Request                           Cache Key          Cached?
-------------------------------- ----------------- ---------
GET /schema?context=list          products:list     NO → API
GET /schema?context=list          products:list     YES ✓
GET /schema?context=detail        products:detail   NO → API  
GET /schema?context=detail        products:detail   YES ✓
GET /schema?context=list          products:list     YES ✓
```

### Cache Keys

```typescript
// Different contexts = different cache entries
'products:list'    // List view schema
'products:form'    // Form view schema
'products:detail'  // Detail view schema
'products:meta'    // Metadata only
'products:full'    // Complete schema (default)
```

### No Duplicate Requests ✓

Same model + same context = uses cache (no API call)  
Same model + different context = new API call (different data needed)

## API Examples

### List Context

**Request:**
```
GET /api/crud6/products/schema?context=list
```

**Response:**
```json
{
  "model": "products",
  "title": "Products",
  "fields": {
    "id": {"type": "integer", "sortable": true},
    "name": {"type": "string", "sortable": true, "filterable": true},
    "price": {"type": "decimal", "sortable": true}
  }
}
```
✅ 60% smaller than full schema  
✅ No validation rules  
✅ No sensitive fields

### Form Context

**Request:**
```
GET /api/crud6/products/schema?context=form
```

**Response:**
```json
{
  "model": "products",
  "fields": {
    "name": {
      "type": "string",
      "required": true,
      "validation": {"min": 2, "max": 255}
    },
    "price": {
      "type": "decimal",
      "required": true,
      "validation": {"min": 0}
    }
  }
}
```
✅ 45% smaller than full schema  
✅ Includes validation rules  
✅ Only editable fields

### Detail Context

**Request:**
```
GET /api/crud6/orders/schema?context=detail
```

**Response:**
```json
{
  "model": "orders",
  "fields": {...},
  "detail": {
    "model": "order_items",
    "foreign_key": "order_id"
  }
}
```
✅ All fields included  
✅ Relationships included  
✅ Most comprehensive

### Meta Context

**Request:**
```
GET /api/crud6/products/schema?context=meta
```

**Response:**
```json
{
  "model": "products",
  "title": "Products",
  "singular_title": "Product",
  "permissions": {
    "read": "view_products",
    "create": "create_product"
  }
}
```
✅ 90% smaller than full schema  
✅ No field details  
✅ Perfect for navigation

## Common Mistakes

### ❌ DON'T

```typescript
// ❌ Using full schema in list view
await loadSchema(model)  // Sends unnecessary data

// ❌ Not specifying context
const schema = await loadSchema(model)  // Default = full

// ❌ Manually filtering fields
const listFields = Object.keys(schema.fields)
  .filter(key => schema.fields[key].listable)  // Backend should do this
```

### ✅ DO

```typescript
// ✅ Request appropriate context
await loadSchema(model, false, 'list')  // Only what you need

// ✅ Let backend filter
const schema = await loadSchema(model, false, 'list')
// schema.fields already filtered

// ✅ Cache is automatic
await loadSchema(model, false, 'list')  // API call
await loadSchema(model, false, 'list')  // Cached ✓
```

## Debug Checklist

### Verify No Duplicate Requests

1. Open DevTools → Network tab
2. Filter by `schema`
3. Navigate through app
4. Count requests to `/api/crud6/{model}/schema`

**Expected:**
- ✓ First list view: 1 request with `?context=list`
- ✓ First detail view: 1 request with `?context=detail`
- ✓ Return to list: 0 requests (cached)
- ✓ Different model: New requests (different cache key)

### Check Cache State

```javascript
// In browser console
import { useCRUD6SchemaStore } from '@ssnukala/sprinkle-crud6/stores'

const store = useCRUD6SchemaStore()
console.log('Cached schemas:', Object.keys(store.schemas))
// Expected: ['products:list', 'products:detail', 'orders:list']
```

### Verify Context Filtering

```bash
# Check if context is being used
curl http://localhost/api/crud6/products/schema?context=list | jq '.schema.fields | length'
# Should return fewer fields than full schema
```

## Performance Metrics

| Context | Typical Size | Reduction | Use When |
|---------|-------------|-----------|----------|
| Full | 15 KB | 0% | Never (backward compat only) |
| List | 6 KB | 60% ↓ | Showing table of records |
| Form | 8 KB | 45% ↓ | Creating/editing record |
| Detail | 11 KB | 25% ↓ | Viewing single record |
| Meta | 1.5 KB | 90% ↓ | Navigation/permissions |

## Related Documentation

📖 [Complete API Guide](../docs/SCHEMA_API_FILTERING.md)  
📖 [Caching Visual Guide](../docs/SCHEMA_CACHING_WITH_CONTEXTS.md)  
📖 [Implementation Summary](../.archive/SCHEMA_FILTERING_COMPLETE_SUMMARY.md)

## Support

**Questions?** Check the documentation above  
**Issues?** Verify cache behavior first  
**Bug?** Include cache key and context in report
