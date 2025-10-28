# AutoLookup Component Visual Reference

## Component Appearance

### State 1: Empty Input
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  Search for a product...                                 │
└─────────────────────────────────────────────────────────────┘
```
- Shows search icon on the left
- Placeholder text guides the user
- Clean, minimal appearance

### State 2: User Typing (Loading)
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  wid                                              ⏳     │
└─────────────────────────────────────────────────────────────┘
```
- User has typed "wid"
- Loading spinner appears on the right
- Search is debounced (waits 300ms after last keystroke)

### State 3: Search Results Dropdown
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  wid                                              ⏳     │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│ ▶ WID-001 - Widget Pro                                     │
│   WID-002 - Widget Mini                                     │
│   WID-003 - Widget Max                                      │
│   WID-BLUE - Blue Widget                                    │
└─────────────────────────────────────────────────────────────┘
```
- Dropdown appears below input
- Results show configured display fields (SKU + Name)
- First item can be highlighted (▶)
- Max 20 results shown
- Scrollable if more results

### State 4: Selected Item
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  WID-001 - Widget Pro                              ✖    │
└─────────────────────────────────────────────────────────────┘
```
- Selected item's display text shown
- Clear button (✖) appears on the right
- Input has success styling (green border)
- v-model contains the selected ID: 1

### State 5: No Results
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  zzz                                                      │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                     No results found                         │
└─────────────────────────────────────────────────────────────┘
```
- Friendly message when search returns no results
- User can continue typing to refine search

## Keyboard Navigation

### Arrow Keys
```
┌─────────────────────────────────────────────────────────────┐
│ 🔍  wid                                                      │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│   WID-001 - Widget Pro                                       │  ← ArrowUp
│ ▶ WID-002 - Widget Mini                                      │  ← Highlighted
│   WID-003 - Widget Max                                       │  ← ArrowDown
└─────────────────────────────────────────────────────────────┘
```
- **Arrow Down**: Move to next item
- **Arrow Up**: Move to previous item
- **Enter**: Select highlighted item
- **Escape**: Close dropdown

## Different Display Configurations

### Single Field (name only)
```
┌─────────────────────────────────────────────────────────────┐
│   Widget Pro                                                 │
│   Widget Mini                                                │
│   Widget Max                                                 │
└─────────────────────────────────────────────────────────────┘
```

### Multiple Fields (sku + name)
```
┌─────────────────────────────────────────────────────────────┐
│   WID-001 - Widget Pro                                       │
│   WID-002 - Widget Mini                                      │
│   WID-003 - Widget Max                                       │
└─────────────────────────────────────────────────────────────┘
```

### Custom Format (sku + name + price)
```
┌─────────────────────────────────────────────────────────────┐
│   WID-001 - Widget Pro ($29.99)                              │
│   WID-002 - Widget Mini ($19.99)                             │
│   WID-003 - Widget Max ($39.99)                              │
└─────────────────────────────────────────────────────────────┘
```

## Integration in Forms

### Product Category Assignment Form
```
┌─────────────────────────────────────────────────────────────┐
│  Manage Product Categories                                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Search for a Product *                                      │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ 🔍  Type to search products by SKU or name...        │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌─ Product Information ─────────────────────────────────┐  │
│  │ Product Name: Widget Pro                             │  │
│  │ SKU: WID-001                                          │  │
│  │ Price: $29.99                                         │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌─ Assign Categories ───────────────────────────────────┐  │
│  │ ☑ Electronics    ☐ Clothing      ☐ Books            │  │
│  │ ☐ Home & Garden  ☑ Sports                            │  │
│  │                                                        │  │
│  │ [Cancel] [Save Categories]                            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Order Entry Form
```
┌─────────────────────────────────────────────────────────────┐
│  Add Order Line Item                                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Product *                                                   │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ 🔍  WID-001 - Widget Pro                          ✖  │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐        │
│  │ Quantity             │  │ Unit Price           │        │
│  │ 10                   │  │ 29.99               │        │
│  └──────────────────────┘  └──────────────────────┘        │
│                                                              │
│  [Add to Order]                                             │
└─────────────────────────────────────────────────────────────┘
```

## Color Coding (UIkit Styling)

- **Normal state**: Gray border (#e5e5e5)
- **Focus state**: Blue border (#1e87f0)
- **Success state** (selected): Green border
- **Error state** (required, empty): Red border
- **Hover**: Light gray background (#f8f8f8)
- **Active item**: Blue background (#1e87f0), white text

## Responsive Behavior

### Desktop
- Full-width input with inline search icon and clear button
- Dropdown appears below input
- 20 results visible with scroll

### Mobile/Tablet
- Same functionality, adjusts to container width
- Touch-friendly tap targets
- Virtual keyboard doesn't obstruct dropdown

## Accessibility

- Screen reader friendly with ARIA labels
- Keyboard navigable
- Focus indicators visible
- Contrast ratios meet WCAG standards
- Labels associated with inputs

## Usage Examples in Different Contexts

### Context 1: Simple Lookup
```vue
<UFCRUD6AutoLookup
  model="products"
  v-model="productId"
/>
```

### Context 2: Product Category (Original Use Case)
```vue
<UFCRUD6AutoLookup
  model="products"
  :display-fields="['sku', 'name']"
  v-model="selectedProductId"
  @select="loadProductCategories"
/>
```

### Context 3: Order Entry with Auto-fill
```vue
<UFCRUD6AutoLookup
  model="products"
  :display-format="item => `${item.sku} - ${item.name} ($${item.price})`"
  v-model="lineItem.product_id"
  @select="product => lineItem.unit_price = product.price"
/>
```

## Performance Characteristics

- **Search Delay**: 300ms debounce (configurable)
- **Results Limit**: 20 items per search
- **API Call**: 1 per search (after debounce)
- **Render Time**: <100ms for 20 items
- **Network**: ~1-2KB per search request
- **Memory**: Minimal (results cleared on close)

## Browser Support

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- IE11: ❌ Not supported (Vue 3 requirement)
- Mobile browsers: ✅ Full support

## Notes

This is a visual reference based on the component implementation. The actual appearance will match the UserFrosting 6 UIkit theme and can be customized through UIkit classes and custom CSS.
