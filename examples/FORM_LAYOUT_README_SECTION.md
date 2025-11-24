# Form Layout Configuration

## Overview

CRUD6 supports configurable form layouts for create and edit modals. You can choose between 1-column, 2-column, or 3-column layouts to optimize space utilization and improve user experience.

**Default:** 2-column layout (provides best balance of space and readability)

## Configuration

Add the `form_layout` property to your schema JSON file:

```json
{
  "model": "products",
  "title": "Product Management",
  "form_layout": "2-column",
  "fields": {
    "name": { "type": "string", "required": true },
    "sku": { "type": "string", "required": true },
    "price": { "type": "decimal", "required": true },
    "description": { "type": "text" }
  }
}
```

## Supported Layouts

### 1-Column Layout
```json
"form_layout": "1-column"
```
- **Best for:** Forms with few fields (1-5 fields), complex field types, or fields requiring maximum width
- **Display:** Single column on all devices

### 2-Column Layout (Default)
```json
"form_layout": "2-column"
```
- **Best for:** Most forms with 4-12 fields, mix of field types
- **Display:** 1 column on mobile, 2 columns on tablet and desktop
- **Recommended:** This is the default and works well for most use cases

### 3-Column Layout
```json
"form_layout": "3-column"
```
- **Best for:** Data-heavy forms with many simple fields (10+ fields)
- **Display:** 1 column on mobile, 2 columns on tablet, 3 columns on desktop

## Responsive Behavior

All layouts are fully responsive:

| Screen Size | 1-Column | 2-Column | 3-Column |
|------------|----------|----------|----------|
| Mobile (< 640px) | 1 col | 1 col | 1 col |
| Tablet (≥ 640px) | 1 col | 2 cols | 2 cols |
| Desktop (≥ 960px) | 1 col | 2 cols | 3 cols |

## Examples

See the `examples/schema/` directory for complete examples:
- `products-1column.json` - Single column example
- `products-2column.json` - Two column example (default)
- `products-3column.json` - Three column example

## Documentation

For detailed information, see:
- [Form Layout Guide](examples/FORM_LAYOUT_GUIDE.md) - Complete usage guide
- [Visual Comparison](examples/FORM_LAYOUT_VISUAL_COMPARISON.md) - Visual diagrams and recommendations

## Backward Compatibility

Existing schemas without `form_layout` will automatically use the **2-column layout** as the default. To keep the old single-column behavior, explicitly set:

```json
"form_layout": "1-column"
```
