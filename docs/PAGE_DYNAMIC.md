# PageDynamic Component - Dynamic View Rendering

## Overview

`PageDynamic` is a smart wrapper component that automatically chooses between `PageRow` and `PageMasterDetail` components based on URL query parameters or schema configuration.

## Query Parameter Control (Recommended)

The **easiest and most flexible** way to control the view is using the `?v` query parameter:

### Simple Usage

```
# Master-detail view
/crud6/orders/123?v=md

# Row view (simple view)
/crud6/orders/123?v=row
```

### Alternative Forms (also supported)

```
# Master-detail view (long form)
/crud6/orders/123?view=master-detail

# Row view (long form)
/crud6/orders/123?view=row
```

### Auto-detect (no query parameter)

```
# Use schema setting or default to row view
/crud6/orders/123
```

## Priority Order

The component determines which view to render using this **simplified priority order**:

1. **Query Parameter** (highest priority - overrides everything)
   - `?v=md` or `?view=master-detail` → PageMasterDetail
   - `?v=row` or `?view=row` or `?view=standard` → PageRow

2. **Schema `render_mode` setting** (used only if NO query parameter)
   - `"render_mode": "master-detail"` → PageMasterDetail
   - `"render_mode": "row"` → PageRow

3. **Default** (if no query parameter AND no schema setting)
   - **Always PageRow**

> **Important**: The query parameter ALWAYS takes precedence. If `?v` parameter doesn't exist, use the schema `render_mode`. If neither exists, always default to PageRow.

## Benefits of Query Parameter Approach

✅ **No schema changes needed** - Users can switch views without modifying JSON files

✅ **User choice** - Different users can view the same data differently

✅ **Easy testing** - Quickly test both views by changing URL

✅ **Bookmarkable** - Users can bookmark their preferred view

✅ **Flexible** - Can override schema settings when needed

## Use Cases

### Use Case 1: Quick View Toggle

User wants to see order details in master-detail mode:
```
/crud6/orders/123?v=md
```

User wants simple row view:
```
/crud6/orders/123?v=row
```

### Use Case 2: Different User Preferences

**Power users** can use master-detail view with inline editing:
```
<a href="/crud6/orders/{{ order.id }}?v=md">Edit Order (Master-Detail)</a>
```

**Basic users** can use simple row view:
```
<a href="/crud6/orders/{{ order.id }}">View Order</a>
```

### Use Case 3: Context-Dependent Views

**From order list** - Use master-detail for full editing:
```html
<button onclick="window.location='/crud6/orders/123?v=md'">
  Edit with Details
</button>
```

**From quick view** - Use simple row view:
```html
<button onclick="window.location='/crud6/orders/123'">
  Quick View
</button>
```

## Schema Configuration (Optional)

You can still set a default in the schema:

```json
{
  "model": "order",
  "render_mode": "master-detail",
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price"]
  }
}
```

**Note:** Query parameters will always override schema settings.

## Route Configuration

The CRUD6Routes already use PageDynamic:

```typescript
{
  path: ':id',
  name: 'crud6.view',
  component: () => import('../views/PageDynamic.vue')
}
```

## Examples

### Example 1: Order with Optional Master-Detail

**Schema** (no render_mode - let users choose via query param):
```json
{
  "model": "order",
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity"]
  }
}
```

**URLs**:
- `/crud6/order/123` → PageRow (default, no query param, no render_mode)
- `/crud6/order/123?v=md` → PageMasterDetail (user choice)
- `/crud6/order/123?v=row` → PageRow (explicit user choice)

### Example 2: Product with Schema Default

**Schema** (with render_mode to set a default):
```json
{
  "model": "product",
  "render_mode": "row"
}
```

**URLs**:
- `/crud6/product/456` → PageRow (from schema render_mode)
- `/crud6/product/456?v=md` → PageMasterDetail (override via query param)

### Example 3: Order with Master-Detail Default

**Schema** (render_mode set to master-detail):
```json
{
  "model": "order",
  "render_mode": "master-detail",
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity"]
  }
}
```

**URLs**:
- `/crud6/order/789` → PageMasterDetail (from schema render_mode)
- `/crud6/order/789?v=row` → PageRow (override via query param)

## UI/UX Patterns

### Toggle Button in UI

Add a button to switch between views:

```vue
<template>
  <div class="view-toggle">
    <button 
      @click="toggleView"
      class="uk-button uk-button-default uk-button-small">
      <font-awesome-icon :icon="currentView === 'md' ? 'th' : 'th-list'" />
      {{ currentView === 'md' ? 'Simple View' : 'Detail View' }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const currentView = computed(() => {
  return route.query.v || route.query.view || 'row'
})

function toggleView() {
  const newView = currentView.value === 'md' ? 'row' : 'md'
  router.push({ 
    path: route.path, 
    query: { ...route.query, v: newView }
  })
}
</script>
```

### Link with Default View

```html
<!-- Default view -->
<a href="/crud6/orders/123">View Order</a>

<!-- Master-detail view -->
<a href="/crud6/orders/123?v=md">Edit Order Details</a>
```

### Button Group

```html
<div class="uk-button-group">
  <a href="/crud6/orders/123" class="uk-button uk-button-default">
    Simple View
  </a>
  <a href="/crud6/orders/123?v=md" class="uk-button uk-button-primary">
    Master-Detail View
  </a>
</div>
```

## Query Parameter Reference

| Parameter | Values | Description |
|-----------|--------|-------------|
| `v` | `md`, `row` | **Recommended**: Short form view selector |
| `view` | `master-detail`, `row`, `standard` | Alternative: Long form view selector |

### Master-Detail View

**Recommended**:
- `?v=md`

**Also accepted**:
- `?view=master-detail`

### Row View (Simple View)

**Recommended**:
- `?v=row`

**Also accepted**:
- `?view=row`
- `?view=standard`

> **Best Practice**: Use the short form (`?v=md` or `?v=row`) for cleaner URLs and easier typing.

## Implementation Details

### Component Logic

```typescript
// Priority 1: Query parameter (overrides everything)
const viewParam = route.query.v || route.query.view
if (viewParam) {
  if (viewParam === 'md' || viewParam === 'master-detail') {
    return 'master-detail'
  }
  if (viewParam === 'row' || viewParam === 'standard') {
    return 'row'
  }
}

// Priority 2: Schema render_mode (only if NO query parameter)
if (!viewParam && schema.value.render_mode) {
  if (schema.value.render_mode === 'master-detail') {
    return 'master-detail'
  }
  if (schema.value.render_mode === 'row') {
    return 'row'
  }
}

// Priority 3: Default (always PageRow)
return 'row'
```

### Dynamic Component Loading

```vue
<PageMasterDetail v-if="componentToRender === 'master-detail'" />
<PageRow v-else-if="componentToRender === 'row'" />
```

## Best Practices

1. **Use query parameters for flexibility** - Let users choose their preferred view
2. **Set schema defaults for common cases** - Use `render_mode` for models that should default to a specific view
3. **Provide UI toggles** - Add buttons to switch between views easily
4. **Keep URLs simple** - Use short form (`?v=md`) for cleaner URLs
5. **Document your conventions** - Tell users which query parameters are available

## Migration from Static Routes

If you were using static routes to PageRow or PageMasterDetail:

**Before:**
```typescript
component: () => import('../views/PageRow.vue')
```

**After:**
```typescript
component: () => import('../views/PageDynamic.vue')
```

No other changes needed! PageDynamic will automatically route to the correct component.

## See Also

- [PageRow Component](../app/assets/views/PageRow.vue)
- [PageMasterDetail Component](./PAGE_MASTER_DETAIL.md)
- [SmartLookup Field Type](./SMARTLOOKUP_FIELD_TYPE.md)
