# Multi-Column Form Layout Configuration

This document explains how to configure the form layout for create and edit modals in CRUD6.

## Overview

CRUD6 now supports configurable multi-column layouts for create and edit forms. This allows you to optimize space utilization and improve user experience by displaying form fields in 1, 2, or 3 columns.

## Default Behavior

**The default layout is 2-column**, which provides a good balance between space utilization and readability. If you don't specify a `form_layout` in your schema, the forms will automatically use a 2-column layout.

## Configuration

Add the `form_layout` property to your schema JSON file at the root level:

```json
{
  "model": "products",
  "title": "Product Management",
  "form_layout": "2-column",
  "fields": {
    ...
  }
}
```

### Supported Values

- **`1-column`**: Single column layout (traditional stacked form)
  - Best for: Forms with few fields, complex field types, or when maximum field width is needed
  - Mobile: 1 column
  - Tablet: 1 column
  - Desktop: 1 column

- **`2-column`** (Default): Two column layout
  - Best for: Most forms, provides good balance of space and readability
  - Mobile: 1 column
  - Tablet: 2 columns
  - Desktop: 2 columns

- **`3-column`**: Three column layout
  - Best for: Forms with many simple fields (text inputs, numbers, dates)
  - Mobile: 1 column
  - Tablet: 2 columns
  - Desktop: 3 columns

## Responsive Behavior

All layouts are fully responsive and adjust automatically based on screen size:

- **Mobile devices**: Always display as 1 column for optimal mobile experience
- **Tablets** (sm breakpoint): Display according to configured layout (1-2 columns)
- **Desktop** (m breakpoint and above): Display full configured layout (1-3 columns)

## Examples

### Example 1: Single Column Layout

```json
{
  "model": "articles",
  "title": "Article Management",
  "form_layout": "1-column",
  "fields": {
    "title": { "type": "string", "required": true },
    "content": { "type": "text", "required": true },
    "author": { "type": "string", "required": true }
  }
}
```

### Example 2: Two Column Layout (Default)

```json
{
  "model": "products",
  "title": "Product Management",
  "form_layout": "2-column",
  "fields": {
    "name": { "type": "string", "required": true },
    "sku": { "type": "string", "required": true },
    "price": { "type": "decimal", "required": true },
    "category_id": { "type": "integer", "required": true },
    "description": { "type": "text" },
    "is_active": { "type": "boolean" }
  }
}
```

### Example 3: Three Column Layout

```json
{
  "model": "contacts",
  "title": "Contact Management",
  "form_layout": "3-column",
  "fields": {
    "first_name": { "type": "string", "required": true },
    "last_name": { "type": "string", "required": true },
    "email": { "type": "email", "required": true },
    "phone": { "type": "phone" },
    "company": { "type": "string" },
    "position": { "type": "string" },
    "city": { "type": "string" },
    "state": { "type": "string" },
    "country": { "type": "string" }
  }
}
```

## Best Practices

1. **Use 1-column layout when:**
   - Your form has complex field types (rich text editors, file uploads)
   - You have very few fields (1-3 fields)
   - Fields require maximum width (long text inputs)

2. **Use 2-column layout when:**
   - Your form has a moderate number of fields (4-10 fields)
   - Mix of short and long fields
   - This is the recommended default for most use cases

3. **Use 3-column layout when:**
   - Your form has many simple fields (10+ fields)
   - Most fields are short (names, numbers, dates, dropdowns)
   - You want to minimize scrolling on desktop screens

4. **Field ordering considerations:**
   - Fields flow left-to-right, then top-to-bottom
   - Group related fields together
   - Consider placing wide fields (like descriptions) at the end so they span properly

## UIKit Grid Classes

The implementation uses UIKit's responsive grid classes:

- `1-column`: `uk-child-width-1-1`
- `2-column`: `uk-child-width-1-1 uk-child-width-1-2@s`
- `3-column`: `uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@m`

## Backward Compatibility

Schemas without the `form_layout` property will automatically use the **2-column layout** as the default. This provides better space utilization while maintaining readability.

If you prefer the old single-column behavior, explicitly set `"form_layout": "1-column"` in your schema.

## Testing

Example schema files demonstrating each layout are available in:
- `examples/schema/products-1column.json` - Single column example
- `examples/schema/products-2column.json` - Two column example (default)
- `examples/schema/products-3column.json` - Three column example

## Technical Details

The form layout is controlled by the `formLayoutClass` computed property in the `Form.vue` component, which reads the `form_layout` value from the schema and applies the appropriate UIKit grid classes.
