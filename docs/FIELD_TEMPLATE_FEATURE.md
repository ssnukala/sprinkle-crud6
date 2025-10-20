# Field Template Feature

## Overview

The `field_template` attribute provides powerful customization for how fields are displayed in list views. This feature allows you to create rich, consolidated column displays by combining multiple field values into a single rendered cell using custom HTML templates.

## Purpose

The field_template feature addresses the need for:
- **Consolidated Displays**: Combine multiple related fields into a single column
- **Rich Formatting**: Use HTML and CSS to create visually appealing field presentations
- **Flexible Layouts**: Design custom layouts for complex data structures
- **Improved UX**: Reduce horizontal scrolling by consolidating information

## Usage

### Basic Syntax

Add a `field_template` attribute to any field in your schema:

```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "<div>{{id}} - {{name}}</div>"
  }
}
```

### Template Placeholders

Templates use double curly braces `{{field_name}}` to reference field values:

- `{{id}}` - Replaced with the value of the `id` field
- `{{name}}` - Replaced with the value of the `name` field
- `{{any_field}}` - Access any field from the current row

All fields from the row data are available for use in the template, not just the field the template is defined on.

## Examples

### Example 1: Simple Badge Display

```json
{
  "status": {
    "type": "string",
    "label": "Status",
    "listable": true,
    "field_template": "<span class='uk-badge'>Status: {{status}}</span>"
  }
}
```

### Example 2: Multi-Field Consolidated Display

```json
{
  "description": {
    "type": "text",
    "label": "Product Details",
    "listable": true,
    "field_template": "<div class='uk-text-meta'><strong>ID:</strong> {{id}} | <strong>SKU:</strong> {{sku}}<br/><strong>Status:</strong> {{is_active}}<br/>{{description}}</div>"
  }
}
```

### Example 3: Card-Style Layout

```json
{
  "description": {
    "type": "text",
    "label": "Task Info",
    "listable": true,
    "field_template": "<div class='uk-card uk-card-small uk-card-default uk-card-body'><div class='uk-grid-small' uk-grid><div class='uk-width-auto'><span class='uk-badge uk-badge-{{status}}'>{{status}}</span></div><div class='uk-width-expand'><div class='uk-text-bold'>{{title}}</div><p class='uk-text-small'>{{description}}</p></div></div></div>"
  }
}
```

### Example 4: Full-Featured Template

See `examples/field-template-example.json` for a comprehensive example showing:
- Multiple field values in a single template
- UIkit grid system for layout
- Status badges with dynamic classes
- Date formatting
- Text styling and metadata display

## Best Practices

### 1. Use UIkit Classes

The CRUD6 sprinkle uses UIkit for styling. Leverage UIkit classes for consistent appearance:

```html
<!-- Cards -->
<div class='uk-card uk-card-small uk-card-default'>...</div>

<!-- Badges -->
<span class='uk-badge'>{{value}}</span>

<!-- Text Formatting -->
<div class='uk-text-meta'>...</div>
<div class='uk-text-bold'>...</div>
<div class='uk-text-small'>...</div>

<!-- Grid Layout -->
<div class='uk-grid-small' uk-grid>
  <div class='uk-width-auto'>...</div>
  <div class='uk-width-expand'>...</div>
</div>
```

### 2. Keep Templates Readable

For complex templates, consider formatting them across multiple lines in your schema JSON:

```json
{
  "field_template": "<div class='container'><span>{{field1}}</span><span>{{field2}}</span></div>"
}
```

### 3. Set listable to true

When using `field_template`, ensure the field has `"listable": true` so it appears in list views:

```json
{
  "description": {
    "type": "text",
    "label": "Details",
    "listable": true,
    "field_template": "..."
  }
}
```

### 4. Consider Performance

- Keep templates simple and lightweight
- Avoid deeply nested HTML structures
- Minimize the number of fields with templates in a single view

### 5. Handle Missing Values

Templates will replace missing or null values with empty strings. Design your templates to handle this gracefully:

```html
<!-- Good: Will show empty string if description is null -->
<div>{{description}}</div>

<!-- Better: Add context that makes sense even when empty -->
<div>Description: {{description}}</div>
```

## Technical Details

### Implementation

The field template rendering is handled in the Vue.js frontend:

1. **Schema Definition**: Templates are defined in JSON schema files
2. **Schema Loading**: SchemaService loads schemas with default values applied
3. **Frontend Rendering**: Vue components render templates using `v-html`
4. **Placeholder Replacement**: Simple regex replacement of `{{field_name}}` with row values

### Security Considerations

- **XSS Protection**: Since templates use `v-html`, ensure that:
  - Templates are defined by trusted administrators in schema files
  - User-supplied data in field values is automatically escaped by Vue
  - Templates themselves should not include user-supplied content directly

### Limitations

1. **No JavaScript Execution**: Templates are static HTML only
2. **No Vue Directives**: Cannot use `v-if`, `v-for`, etc. in templates
3. **Simple Replacement**: Only basic `{{field_name}}` placeholder substitution
4. **List View Only**: Currently only supported in list/table views (PageList.vue)

## Migration from Previous Versions

If you have existing schemas without field templates, no changes are required. The feature is:
- **Opt-in**: Only fields with `field_template` defined will use custom rendering
- **Backward Compatible**: Existing schemas continue to work as before
- **Progressive Enhancement**: Add templates only where needed

## Related Features

### Schema Defaults

Field templates work alongside the new schema defaults feature:
- `primary_key` defaults to `"id"`
- `timestamps` defaults to `true`
- `soft_delete` defaults to `false`

See the main README for more details on schema defaults.

## Examples in Repository

Check these files for working examples:

1. **examples/categories.json** - Simple multi-field display
2. **examples/products.json** - Card-style layout with badges
3. **examples/field-template-example.json** - Comprehensive task management example

## Future Enhancements

Potential future additions to this feature:
- Support for template functions (date formatting, number formatting)
- Conditional rendering based on field values
- Template library/snippets
- Support in detail/info views
- Template validation and preview tools
