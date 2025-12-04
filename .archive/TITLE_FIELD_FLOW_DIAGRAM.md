# Title Field Flow Diagram

## Request Flow: How title_field Works

```
┌─────────────────────────────────────────────────────────────────────┐
│                         User Navigates to Record                     │
│                         /crud6/users/8                               │
└─────────────────────────────┬───────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     Frontend: PageRow.vue Loads                      │
│                                                                       │
│  1. Fetches schema from API: GET /api/crud6/users/schema?context=detail
│  2. Fetches record data: GET /api/crud6/users/8                     │
└─────────────────────────────┬───────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    Backend: SchemaService                            │
│                                                                       │
│  File: app/src/ServicesProvider/SchemaService.php                   │
│                                                                       │
│  1. Loads schema JSON: examples/schema/users01.json                 │
│  2. Filters for detail context                                       │
│  3. Includes title_field in response (line 1125-1127):              │
│                                                                       │
│     if (isset($schema['title_field'])) {                            │
│         $data['title_field'] = $schema['title_field'];              │
│     }                                                                 │
│                                                                       │
│  4. Returns filtered schema with title_field                        │
└─────────────────────────────┬───────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│               Frontend Receives Schema & Record                      │
│                                                                       │
│  Schema Response:                                                     │
│  {                                                                    │
│    "model": "users",                                                 │
│    "title_field": "user_name",  ← Configured field                  │
│    "fields": { ... }                                                 │
│  }                                                                    │
│                                                                       │
│  Record Data:                                                         │
│  {                                                                    │
│    "id": 8,                                                          │
│    "user_name": "john_doe",    ← Value to display                   │
│    "email": "john@example.com"                                       │
│  }                                                                    │
└─────────────────────────────┬───────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│              PageRow.vue Determines Display Name                     │
│                                                                       │
│  Code (lines 14-17):                                                 │
│                                                                       │
│  const titleField = flattenedSchema.value?.title_field || 'name'    │
│  const fieldOptions = [titleField, 'name', 'username',              │
│                        'user_name', 'title']                         │
│  const uniqueFields = [...new Set(fieldOptions)]                    │
│  let recordName = uniqueFields.map(field => fetchedRow[field])      │
│                               .find(val => val) || recordId.value    │
│                                                                       │
│  Process:                                                             │
│  1. titleField = "user_name" (from schema)                          │
│  2. fieldOptions = ["user_name", "name", "username", ...]           │
│  3. uniqueFields = ["user_name", "name", "username", ...]           │
│  4. Try fetchedRow["user_name"] → "john_doe" ✓                      │
│  5. recordName = "john_doe"                                          │
└─────────────────────────────┬───────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│              Breadcrumb Updated via setDetailBreadcrumbs()          │
│                                                                       │
│  Code:                                                                │
│  await setDetailBreadcrumbs(modelLabel.value, recordName, listPath) │
│                                                                       │
│  Breadcrumb Display:                                                 │
│                                                                       │
│  ┌──────┐   ┌───────┐   ┌──────────┐                               │
│  │ Home │ > │ Users │ > │ john_doe │                                │
│  └──────┘   └───────┘   └──────────┘                                │
│                                                                       │
│  Instead of:                                                          │
│  ┌──────┐   ┌───────┐   ┌───┐                                       │
│  │ Home │ > │ Users │ > │ 8 │                                        │
│  └──────┘   └───────┘   └───┘                                        │
└─────────────────────────────────────────────────────────────────────┘
```

## Fallback Mechanism

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Field Selection Priority                          │
└─────────────────────────────────────────────────────────────────────┘

Priority 1: schema['title_field']
    ↓ (if exists)
    ├─ Use value from fetchedRow[title_field]
    │  Example: title_field="user_name" → "john_doe" ✓
    │
    ↓ (if title_field empty or not in record)
    
Priority 2: fetchedRow['name']
    ↓ (if exists and not empty)
    ├─ Use value from fetchedRow['name']
    │  Example: name="John Doe" ✓
    │
    ↓ (if empty or doesn't exist)
    
Priority 3: fetchedRow['username']
    ↓ (if exists and not empty)
    ├─ Use value from fetchedRow['username']
    │  Example: username="jdoe" ✓
    │
    ↓ (if empty or doesn't exist)
    
Priority 4: fetchedRow['user_name']
    ↓ (if exists and not empty)
    ├─ Use value from fetchedRow['user_name']
    │  Example: user_name="jdoe123" ✓
    │
    ↓ (if empty or doesn't exist)
    
Priority 5: fetchedRow['title']
    ↓ (if exists and not empty)
    ├─ Use value from fetchedRow['title']
    │  Example: title="Administrator" ✓
    │
    ↓ (if empty or doesn't exist)
    
Priority 6: recordId
    └─ Use the record ID as last resort
       Example: "8"
```

## Schema Configuration Examples

### Example 1: Users Model

```json
{
  "model": "users",
  "title_field": "user_name",
  "fields": {
    "id": { "type": "integer" },
    "user_name": { "type": "string", "label": "Username" },
    "email": { "type": "string", "label": "Email" }
  }
}
```

**Result**: `/crud6/users/8` → **Home > Users > john_doe**

### Example 2: Orders Model

```json
{
  "model": "orders",
  "title_field": "order_number",
  "fields": {
    "id": { "type": "integer" },
    "order_number": { "type": "string", "label": "Order Number" },
    "customer_name": { "type": "string", "label": "Customer" }
  }
}
```

**Result**: `/crud6/orders/42` → **Home > Orders > ORD-2024-001**

### Example 3: Products Model

```json
{
  "model": "products",
  "title_field": "name",
  "fields": {
    "id": { "type": "integer" },
    "name": { "type": "string", "label": "Product Name" },
    "sku": { "type": "string", "label": "SKU" }
  }
}
```

**Result**: `/crud6/products/15` → **Home > Products > Premium Widget**

### Example 4: Without title_field (Uses Fallback)

```json
{
  "model": "categories",
  "fields": {
    "id": { "type": "integer" },
    "name": { "type": "string", "label": "Category Name" }
  }
}
```

**Result**: `/crud6/categories/5` → **Home > Categories > Electronics**
(Uses `name` field from fallback mechanism)

## Component Interaction

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Component Stack                               │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│         PageRow.vue (Main Component)        │
│  - Fetches schema & record                  │
│  - Determines recordName using title_field  │
│  - Updates breadcrumbs                       │
└────────────────┬────────────────────────────┘
                 │ uses
                 ▼
┌─────────────────────────────────────────────┐
│   useCRUD6Breadcrumbs.ts (Composable)      │
│  - setDetailBreadcrumbs(modelTitle,         │
│                         recordName,          │
│                         listPath)            │
│  - Updates page.breadcrumbs array           │
└────────────────┬────────────────────────────┘
                 │ uses
                 ▼
┌─────────────────────────────────────────────┐
│      usePageMeta.ts (Core Composable)       │
│  - Manages page.breadcrumbs                 │
│  - Renders breadcrumb trail in UI           │
└─────────────────────────────────────────────┘

Also used by:
├─ PageMasterDetail.vue - For master-detail views
└─ CRUD6/Info.vue - For info display components
```

## Key Files Modified

| File | Change | Purpose |
|------|--------|---------|
| `examples/schema/users01.json` | Added `"title_field": "user_name"` | Example for users |
| `examples/schema/products.json` | Added `"title_field": "name"` | Example for products |
| `examples/schema/orders.json` | Added `"title_field": "order_number"` | Example for orders |
| `examples/schema/contacts.json` | Added `"title_field": "last_name"` | Example for contacts |
| `examples/schema/groups.json` | Added `"title_field": "name"` | Example for groups |
| `examples/schema/categories.json` | Added `"title_field": "name"` | Example for categories |
| `README.md` | Documentation added | Main documentation |
| `examples/schema/README.md` | Comprehensive guide | Detailed usage guide |
| `app/tests/ServicesProvider/SchemaFilteringTest.php` | 2 new tests | Test coverage |

## Testing Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      Test: Detail Context                            │
└─────────────────────────────────────────────────────────────────────┘

Test Schema:
{
  "model": "users",
  "title_field": "user_name",
  "fields": { ... }
}
    ↓
SchemaService.getContextSpecificData('detail')
    ↓
Returns:
{
  "model": "users",
  "title_field": "user_name",  ← Assert this exists
  "fields": { ... }
}
    ↓
Test Assertions:
✓ assertArrayHasKey('title_field', $detailData)
✓ assertEquals('user_name', $detailData['title_field'])
```

## Summary

The `title_field` feature provides:

1. **Configuration**: Simple schema attribute to specify display field
2. **Backend Support**: SchemaService includes it in detail context
3. **Frontend Usage**: PageRow.vue uses it with smart fallbacks
4. **Flexibility**: Works with any field in the model
5. **Reliability**: Multiple fallbacks ensure breadcrumbs always show something
6. **Backward Compatible**: Works without configuration for common cases
7. **Well Tested**: Comprehensive unit tests verify functionality
8. **Documented**: Multiple documentation sources with examples
