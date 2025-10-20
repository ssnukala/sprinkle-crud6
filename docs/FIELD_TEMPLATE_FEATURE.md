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

Add a `field_template` attribute to any field in your schema. You can use inline templates, external HTML files, or Vue component files:

**Inline Template:**
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

**External HTML Template File:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "product-card.html"
  }
}
```

**Vue Component Template File:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Info",
    "listable": true,
    "field_template": "ProductCard.vue"
  }
}
```

When using external files:
- **HTML templates**: Place in `app/assets/templates/crud6/` with `.html` or `.htm` extension
- **Vue components**: Place in `app/assets/templates/crud6/` with `.vue` extension
- Reference the filename (e.g., `"ProductCard.vue"`)
- Files are loaded at build time for optimal performance

### Vue Component Templates

Vue component templates provide the most powerful option, allowing you to use full Vue features:

**Benefits:**
- Full Vue 3 Composition API support
- Reactive data with `ref()` and `computed()`
- Vue directives (`v-if`, `v-for`, `v-show`, etc.)
- TypeScript support with type-safe props
- Component methods and event handling
- Better IDE support and syntax highlighting

**Creating a Vue Template Component:**

Create a file at `app/assets/templates/crud6/ProductCard.vue`:

```vue
<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  rowData: any  // Receives the complete row data
}

const props = defineProps<Props>()

// Use computed properties
const isActive = computed(() => props.rowData.is_active)
const statusClass = computed(() => 
  isActive.value ? 'uk-label-success' : 'uk-label-warning'
)
</script>

<template>
  <div class="uk-card uk-card-small uk-card-default uk-card-body">
    <div class="uk-grid-small" uk-grid>
      <div class="uk-width-auto">
        <span class="uk-badge">ID: {{ rowData.id }}</span>
      </div>
      <!-- Use Vue directives -->
      <div v-if="rowData.sku" class="uk-width-auto">
        <span class="uk-badge">SKU: {{ rowData.sku }}</span>
      </div>
      <div class="uk-width-auto">
        <span class="uk-label" :class="statusClass">
          <template v-if="isActive">Active</template>
          <template v-else>Inactive</template>
        </span>
      </div>
    </div>
    <p class="uk-text-small uk-margin-small-top">{{ rowData.description }}</p>
  </div>
</template>
```

**Key Points:**
- Vue components receive row data via the `rowData` prop
- Must define props interface to receive data
- All row fields are accessible via `props.rowData.fieldName`
- Can use all Vue features: computed properties, methods, directives, etc.
- TypeScript support for better type safety

### Template Placeholders (HTML Templates Only)

HTML templates (inline or `.html` files) use double curly braces `{{field_name}}` to reference field values:

- `{{id}}` - Replaced with the value of the `id` field
- `{{name}}` - Replaced with the value of the `name` field
- `{{any_field}}` - Access any field from the current row

All fields from the row data are available for use in the template, not just the field the template is defined on.

**Note:** Vue component templates (`.vue` files) access data through the `rowData` prop instead of placeholders.

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

### Example 4: Using External HTML Template Files

For complex HTML templates, it's often cleaner to use external template files:

**Schema Definition:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Details",
    "listable": true,
    "field_template": "product-card.html"
  }
}
```

**Template File** (`app/assets/templates/crud6/product-card.html`):
```html
<div class='uk-card uk-card-small uk-card-default uk-card-body'>
  <div class='uk-grid-small' uk-grid>
    <div class='uk-width-auto'>
      <span class='uk-badge'>ID: {{id}}</span>
    </div>
    <div class='uk-width-auto'>
      <span class='uk-badge'>SKU: {{sku}}</span>
    </div>
  </div>
  <p class='uk-text-small'>{{description}}</p>
</div>
```

See `examples/products-template-file.json` for a complete working example.

### Example 5: Using Vue Component Templates

For maximum flexibility and power, use Vue component templates:

**Schema Definition:**
```json
{
  "description": {
    "type": "text",
    "label": "Product Details",
    "listable": true,
    "field_template": "ProductCard.vue"
  }
}
```

**Vue Component** (`app/assets/templates/crud6/ProductCard.vue`):
```vue
<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  rowData: any
}

const props = defineProps<Props>()

const isActive = computed(() => props.rowData.is_active)
const statusClass = computed(() => 
  isActive.value ? 'uk-label-success' : 'uk-label-warning'
)
</script>

<template>
  <div class="uk-card uk-card-small uk-card-default uk-card-body">
    <div class="uk-grid-small" uk-grid>
      <div class="uk-width-auto">
        <span class="uk-badge">ID: {{ rowData.id }}</span>
      </div>
      <div v-if="rowData.sku" class="uk-width-auto">
        <span class="uk-badge">SKU: {{ rowData.sku }}</span>
      </div>
      <div class="uk-width-auto">
        <span class="uk-label" :class="statusClass">
          {{ isActive ? 'Active' : 'Inactive' }}
        </span>
      </div>
    </div>
    <p class="uk-text-small uk-margin-small-top">{{ rowData.description }}</p>
  </div>
</template>
```

See `examples/products-vue-template.json` for a complete working example.

### Example 6: Full-Featured Inline Template

See `examples/field-template-example.json` for a comprehensive example showing:
- Multiple field values in a single template
- UIkit grid system for layout
- Status badges with dynamic classes
- Date formatting
- Text styling and metadata display

## Template Type Comparison

Choose the right template type for your needs:

| Feature | Inline HTML | External HTML File | Vue Component |
|---------|-------------|-------------------|---------------|
| **Simple syntax** | ✅ Best | ✅ Good | ⚠️ More complex |
| **Vue directives** | ❌ No | ❌ No | ✅ Yes (`v-if`, `v-for`, etc.) |
| **Computed properties** | ❌ No | ❌ No | ✅ Yes |
| **TypeScript support** | ❌ No | ❌ No | ✅ Yes |
| **Reactivity** | ❌ No | ❌ No | ✅ Yes |
| **Code reusability** | ⚠️ Limited | ✅ Good | ✅ Excellent |
| **Syntax highlighting** | ⚠️ Limited | ✅ Good | ✅ Excellent |
| **Maintainability** | ⚠️ Low | ✅ Good | ✅ Best |
| **Performance** | ✅ Fast | ✅ Fast | ✅ Fast (compiled) |
| **Best for** | Simple displays | Medium complexity | Complex logic |

**Recommendations:**
- **Use Inline HTML** for very simple field formatting (e.g., adding badges, basic styling)
- **Use External HTML Files** for medium-complexity templates that need better organization
- **Use Vue Components** for complex templates requiring conditional logic, computed values, or reactive features

## Best Practices

### 1. Choose the Right Template Type

Match template type to complexity:
- Simple badge or label → Inline HTML
- Multi-field card layout → External HTML file  
- Conditional rendering or computed values → Vue component

### 2. Use UIkit Classes

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

For complex templates, consider using external template files for better maintainability:

**Instead of inline:**
```json
{
  "field_template": "<div class='container'><span>{{field1}}</span><span>{{field2}}</span></div>"
}
```

**Use external file:**
```json
{
  "field_template": "my-template.html"
}
```

Then create `app/assets/templates/crud6/my-template.html` with your template content.

### 3. Template File Organization

When using external template files:
- Place all template files in `app/assets/templates/crud6/`
- Use descriptive names (e.g., `product-card.html`, `user-info.html`)
- Group related templates with prefixes (e.g., `product-card.html`, `product-list.html`)
- Templates are imported at build time, so changes require a rebuild

### 4. Set listable to true

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

### 5. Consider Performance

- Keep templates simple and lightweight
- Avoid deeply nested HTML structures
- Minimize the number of fields with templates in a single view
- External template files are loaded at build time, not runtime, for optimal performance

### 6. Handle Missing Values

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

1. **Schema Definition**: Templates are defined in JSON schema files (inline or as file references)
2. **Template Loading**: External template files are imported at build time using Vite's glob import
3. **Schema Loading**: SchemaService loads schemas with default values applied
4. **Frontend Rendering**: Vue components render templates using `v-html`
5. **Placeholder Replacement**: Simple regex replacement of `{{field_name}}` with row values

#### Template File Loading

When a `field_template` value ends with `.html` or `.htm`:
- The system treats it as a file reference
- Files are loaded from `app/assets/templates/crud6/` directory
- Vite's `import.meta.glob()` loads all templates at build time
- Templates are cached in memory for fast access
- No runtime HTTP requests are made

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
5. **Build-Time Loading**: External template files require a rebuild when modified

## Migration from Previous Versions

If you have existing schemas without field templates, no changes are required. The feature is:
- **Opt-in**: Only fields with `field_template` defined will use custom rendering
- **Backward Compatible**: Existing schemas continue to work as before
- **Progressive Enhancement**: Add templates only where needed

To migrate inline templates to external files:
1. Create a new `.html` file in `app/assets/templates/crud6/`
2. Copy your inline template HTML to the file
3. Update your schema to reference the filename
4. Rebuild your application

## Related Features

### Schema Defaults

Field templates work alongside the new schema defaults feature:
- `primary_key` defaults to `"id"`
- `timestamps` defaults to `true`
- `soft_delete` defaults to `false`

See the main README for more details on schema defaults.

## Examples in Repository

Check these files for working examples:

1. **examples/categories.json** - Simple multi-field display (inline template)
2. **examples/products.json** - Card-style layout with badges (inline template)
3. **examples/products-template-file.json** - Using external template file
4. **examples/field-template-example.json** - Comprehensive task management example (inline template)
5. **app/assets/templates/crud6/product-card.html** - Example external template file
6. **app/assets/templates/crud6/category-info.html** - Example external template file

## Future Enhancements

Potential future additions to this feature:
- Support for template functions (date formatting, number formatting)
- Conditional rendering based on field values
- Template library/snippets
- Support in detail/info views
- Template validation and preview tools
