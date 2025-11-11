# CRUD6 Field Renderer Composable

## Overview

The `useCRUD6FieldRenderer` composable provides a centralized, declarative way to render form fields based on their type configuration. Instead of having extensive conditional logic in components, you can use this composable to determine the appropriate renderer for any field.

## Benefits

✅ **Centralized Logic** - All field rendering decisions in one place
✅ **Declarative** - Get field configuration without manual conditionals  
✅ **Type-Safe** - Full TypeScript support with interfaces
✅ **Maintainable** - Easy to add new field types
✅ **Reusable** - Use in any component that needs field rendering
✅ **Testable** - Pure functions that are easy to test

## Installation

The composable is automatically exported from the CRUD6 package:

```typescript
import { useCRUD6FieldRenderer } from '@ssnukala/sprinkle-crud6/composables'
```

## Basic Usage

### Get Field Renderer Type

```typescript
import { getFieldRendererType } from '@ssnukala/sprinkle-crud6/composables'

const rendererType = getFieldRendererType('email')
// Returns: 'text-input'

const boolType = getFieldRendererType('boolean-yn')
// Returns: 'boolean-select'

const addressType = getFieldRendererType('address')
// Returns: 'address'
```

### Get Field Attributes

```typescript
import { getFieldAttributes } from '@ssnukala/sprinkle-crud6/composables'

const field = {
    type: 'email',
    label: 'Email Address',
    required: true,
    validation: {
        email: true
    }
}

const attrs = getFieldAttributes(field, 'email', 'user@example.com')
// Returns: {
//   id: 'email',
//   class: 'uk-input',
//   type: 'email',
//   'aria-label': 'Email Address',
//   'data-test': 'email',
//   required: true,
//   disabled: false,
//   placeholder: 'Email Address',
//   pattern: undefined
// }
```

### Get Complete Render Configuration

```typescript
import { getFieldRenderConfig } from '@ssnukala/sprinkle-crud6/composables'
import GoogleAddress from './GoogleAddress.vue'
import AutoLookup from './AutoLookup.vue'

const field = {
    type: 'address',
    label: 'Store Address',
    address_fields: {
        addr_line1: 'address_line_1',
        city: 'city',
        state: 'state',
        zip: 'postal_code'
    }
}

const config = getFieldRenderConfig(
    field,
    'full_address',
    '',
    { GoogleAddress, AutoLookup }
)

// Returns: {
//   rendererType: 'address',
//   element: 'input',
//   component: GoogleAddress,
//   attributes: {
//     'field-key': 'full_address',
//     placeholder: 'Store Address',
//     required: false,
//     disabled: false,
//     'address-fields': { ... }
//   }
// }
```

## Complete Example: Simplified Form Component

**Before (with extensive conditionals):**

```vue
<template>
  <div v-for="(field, fieldKey) in schema.fields" :key="fieldKey">
    <!-- Text input -->
    <input
      v-if="['string', 'email', 'url', 'phone', 'zip'].includes(field.type)"
      :id="fieldKey"
      class="uk-input"
      :type="getInputType(field.type)"
      :pattern="getInputPattern(field.type, field.validation)"
      v-model="formData[fieldKey]"
    />
    
    <!-- Number input -->
    <input
      v-else-if="['number', 'integer', 'decimal', 'float'].includes(field.type)"
      :id="fieldKey"
      class="uk-input"
      type="number"
      :step="field.type === 'integer' ? '1' : 'any'"
      v-model="formData[fieldKey]"
    />
    
    <!-- Password -->
    <input
      v-else-if="field.type === 'password'"
      :id="fieldKey"
      class="uk-input"
      type="password"
      v-model="formData[fieldKey]"
    />
    
    <!-- ... many more conditionals ... -->
  </div>
</template>
```

**After (using field renderer):**

```vue
<script setup lang="ts">
import { getFieldRenderConfig } from '@ssnukala/sprinkle-crud6/composables'
import GoogleAddress from './GoogleAddress.vue'
import AutoLookup from './AutoLookup.vue'

// Get render config for each field
const getConfig = (field: any, fieldKey: string) => {
  return getFieldRenderConfig(
    field,
    fieldKey,
    formData.value[fieldKey],
    { GoogleAddress, AutoLookup }
  )
}
</script>

<template>
  <div v-for="(field, fieldKey) in schema.fields" :key="fieldKey">
    <!-- Component-based rendering -->
    <component
      v-if="getConfig(field, fieldKey).component"
      :is="getConfig(field, fieldKey).component"
      v-bind="getConfig(field, fieldKey).attributes"
      v-model="formData[fieldKey]"
      @address-selected="handleAddressSelected"
    />
    
    <!-- Standard elements -->
    <component
      v-else
      :is="getConfig(field, fieldKey).element"
      v-bind="getConfig(field, fieldKey).attributes"
      v-model="formData[fieldKey]"
    >
      <!-- For select dropdowns -->
      <option
        v-for="opt in getConfig(field, fieldKey).options"
        :key="opt.value"
        :value="opt.value"
      >
        {{ opt.label }}
      </option>
    </component>
  </div>
</template>
```

## Renderer Types

The composable recognizes these renderer types:

| Renderer Type | Field Types | Element/Component |
|--------------|-------------|-------------------|
| `text-input` | string, email, url, phone, zip | `<input type="...">` |
| `number-input` | number, integer, decimal, float | `<input type="number">` |
| `password-input` | password | `<input type="password">` |
| `date-input` | date | `<input type="date">` |
| `datetime-input` | datetime | `<input type="datetime-local">` |
| `textarea` | text, textarea, textarea-rXcY | `<textarea>` |
| `boolean-select` | boolean-yn | `<select>` |
| `boolean-checkbox` | boolean, boolean-toggle | `<input type="checkbox">` |
| `address` | address | GoogleAddress component |
| `smartlookup` | smartlookup | AutoLookup component |

## API Reference

### Functions

#### `getFieldRendererType(fieldType: string): string`

Determines which renderer type to use for a field.

**Parameters:**
- `fieldType` - The field type from schema (e.g., 'email', 'address')

**Returns:** Renderer type string (e.g., 'text-input', 'address')

**Example:**
```typescript
getFieldRendererType('email')      // 'text-input'
getFieldRendererType('boolean-yn') // 'boolean-select'
getFieldRendererType('address')    // 'address'
```

#### `getFieldAttributes(field: FieldConfig, fieldKey: string, modelValue: any): Record<string, any>`

Gets HTML attributes for a field element.

**Parameters:**
- `field` - Field configuration object
- `fieldKey` - Field name/key
- `modelValue` - Current field value

**Returns:** Object with HTML attributes

**Example:**
```typescript
const attrs = getFieldAttributes(
  { type: 'email', label: 'Email', required: true },
  'user_email',
  'test@example.com'
)
// { id: 'user_email', class: 'uk-input', type: 'email', ... }
```

#### `getFieldRenderConfig(field: FieldConfig, fieldKey: string, modelValue: any, components?: object): FieldRenderConfig`

Gets complete render configuration including component, element, and attributes.

**Parameters:**
- `field` - Field configuration object
- `fieldKey` - Field name/key
- `modelValue` - Current field value
- `components` - Optional components map (GoogleAddress, AutoLookup)

**Returns:** FieldRenderConfig object

**Example:**
```typescript
const config = getFieldRenderConfig(
  { type: 'address', address_fields: {...} },
  'full_address',
  '',
  { GoogleAddress }
)
// {
//   rendererType: 'address',
//   element: 'input',
//   component: GoogleAddress,
//   attributes: {...}
// }
```

### Interfaces

#### `FieldConfig`

```typescript
interface FieldConfig {
    type: string
    label?: string
    placeholder?: string
    required?: boolean
    readonly?: boolean
    disabled?: boolean
    validation?: any
    rows?: number
    cols?: number
    address_fields?: any
    [key: string]: any
}
```

#### `FieldRenderConfig`

```typescript
interface FieldRenderConfig {
    rendererType: string
    element: string
    component?: Component
    attributes: Record<string, any>
    wrapInLabel?: boolean
    labelText?: string
    options?: Array<{ value: any; label: string }>
}
```

## Advanced Usage

### Custom Field Type Rendering

You can extend the renderer with custom field types:

```typescript
import { registerFieldType } from '@ssnukala/sprinkle-crud6/utils'
import { getFieldRenderConfig } from '@ssnukala/sprinkle-crud6/composables'

// Register custom field type
registerFieldType('ssn', 'text', '\\d{3}-\\d{2}-\\d{4}')

// It will automatically use text-input renderer with pattern
const config = getFieldRenderConfig({ type: 'ssn' }, 'ssn_field', '')
// config.rendererType === 'text-input'
// config.attributes.pattern === '\\d{3}-\\d{2}-\\d{4}'
```

### Dynamic Component Resolution

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import { getFieldRenderConfig } from '@ssnukala/sprinkle-crud6/composables'
import GoogleAddress from './GoogleAddress.vue'

const components = { GoogleAddress }

const renderField = (field: any, fieldKey: string) => {
  const config = getFieldRenderConfig(
    field,
    fieldKey,
    formData.value[fieldKey],
    components
  )
  
  return {
    is: config.component || config.element,
    attrs: config.attributes,
    hasOptions: !!config.options,
    options: config.options || []
  }
}
</script>

<template>
  <component
    v-for="(field, key) in fields"
    :key="key"
    :is="renderField(field, key).is"
    v-bind="renderField(field, key).attrs"
    v-model="formData[key]"
  >
    <option
      v-if="renderField(field, key).hasOptions"
      v-for="opt in renderField(field, key).options"
      :key="opt.value"
      :value="opt.value"
    >
      {{ opt.label }}
    </option>
  </component>
</template>
```

### Conditional Rendering Based on Type

```typescript
import { getFieldRendererType } from '@ssnukala/sprinkle-crud6/composables'

const needsSpecialWrapper = (fieldType: string): boolean => {
  const rendererType = getFieldRendererType(fieldType)
  return ['address', 'smartlookup'].includes(rendererType)
}

const isSimpleInput = (fieldType: string): boolean => {
  const rendererType = getFieldRendererType(fieldType)
  return ['text-input', 'number-input', 'password-input'].includes(rendererType)
}
```

## Testing

The composable functions are pure and easy to test:

```typescript
import { describe, it, expect } from 'vitest'
import { getFieldRendererType, getFieldAttributes } from '@ssnukala/sprinkle-crud6/composables'

describe('useCRUD6FieldRenderer', () => {
  it('should determine text-input for email type', () => {
    expect(getFieldRendererType('email')).toBe('text-input')
  })
  
  it('should get correct attributes for email field', () => {
    const field = { type: 'email', label: 'Email', required: true }
    const attrs = getFieldAttributes(field, 'email', '')
    
    expect(attrs.type).toBe('email')
    expect(attrs.required).toBe(true)
    expect(attrs.class).toBe('uk-input')
  })
  
  it('should handle boolean-yn as select', () => {
    expect(getFieldRendererType('boolean-yn')).toBe('boolean-select')
  })
  
  it('should handle address type', () => {
    expect(getFieldRendererType('address')).toBe('address')
  })
})
```

## Migration Guide

### From Direct Conditionals

**Before:**
```vue
<input
  v-if="field.type === 'email'"
  type="email"
  :id="fieldKey"
  class="uk-input"
  v-model="formData[fieldKey]"
/>
```

**After:**
```vue
<component
  :is="getConfig(field, fieldKey).element"
  v-bind="getConfig(field, fieldKey).attributes"
  v-model="formData[fieldKey]"
/>
```

### From Helper Functions

**Before:**
```vue
<input
  :type="getInputType(field.type)"
  :pattern="getInputPattern(field.type, field.validation)"
  class="uk-input"
/>
```

**After:**
```vue
<component
  :is="config.element"
  v-bind="config.attributes"
/>
```

## See Also

- [FIELD_TYPES_UTILITY.md](FIELD_TYPES_UTILITY.md) - Field type utilities
- [FIELD_TYPES_REFERENCE.md](FIELD_TYPES_REFERENCE.md) - All supported field types
- [Form.vue](../components/CRUD6/Form.vue) - Example usage in Form component
