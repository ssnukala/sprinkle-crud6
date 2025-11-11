# Field Types Utility

## Overview

The `fieldTypes.ts` utility provides a centralized, extensible system for handling CRUD6 field types. This utility can be easily extended to support custom field types without modifying component code.

## Location

`app/assets/utils/fieldTypes.ts`

## Core Functions

### parseTextareaConfig(type: string): TextareaConfig

Parses textarea type formats to extract row and column configuration.

**Supported Formats:**
- `"text"` or `"textarea"` → 6 rows, default cols
- `"textarea-r5"` → 5 rows, default cols
- `"textarea-r3c60"` → 3 rows, 60 cols
- `"text-r2c50"` → 2 rows, 50 cols

**Example:**
```typescript
import { parseTextareaConfig } from '@ssnukala/sprinkle-crud6/utils'

const config = parseTextareaConfig('textarea-r5c80')
// Returns: { rows: 5, cols: 80 }
```

### getInputType(fieldType: string): string

Maps CRUD6 field types to HTML5 input types.

**Example:**
```typescript
import { getInputType } from '@ssnukala/sprinkle-crud6/utils'

const inputType = getInputType('email')
// Returns: 'email'

const phoneType = getInputType('phone')
// Returns: 'tel'
```

### getInputPattern(fieldType: string, validation?: any): string | undefined

Returns appropriate regex pattern for field validation.

**Priority:**
1. Custom regex from validation.regex
2. Named pattern reference
3. Default pattern for field type

**Example:**
```typescript
import { getInputPattern } from '@ssnukala/sprinkle-crud6/utils'

// Using field type default
const pattern1 = getInputPattern('zip')
// Returns: '\\d{5}(-\\d{4})?'

// Using validation regex
const pattern2 = getInputPattern('custom', { 
    regex: { pattern: '^[A-Z]{3}$' } 
})
// Returns: '^[A-Z]{3}$'

// Using named pattern
const pattern3 = getInputPattern('zip', { 
    regex: 'us_zip_5' 
})
// Returns: '^\\d{5}$'
```

## Helper Functions

### isTextareaType(fieldType: string): boolean

Checks if field type is a textarea variant.

```typescript
isTextareaType('textarea-r5c60') // true
isTextareaType('text') // true
isTextareaType('string') // false
```

### isTextInputType(fieldType: string): boolean

Checks if field should use standard text input.

```typescript
isTextInputType('email') // true
isTextInputType('phone') // true
isTextInputType('integer') // false
```

### isNumericType(fieldType: string): boolean

Checks if field type is numeric.

```typescript
isNumericType('integer') // true
isNumericType('float') // true
isNumericType('string') // false
```

## Extensibility

### Registering Custom Field Types

Add custom field types with `registerFieldType()`:

```typescript
import { registerFieldType } from '@ssnukala/sprinkle-crud6/utils'

// Register a credit card field type
registerFieldType(
    'creditcard',           // Field type name
    'text',                 // HTML input type
    '\\d{4}-\\d{4}-\\d{4}-\\d{4}'  // Optional pattern
)
```

### Registering Named Patterns

Add custom named patterns with `registerNamedPattern()`:

```typescript
import { registerNamedPattern } from '@ssnukala/sprinkle-crud6/utils'

// Register a Canadian postal code pattern
registerNamedPattern(
    'ca_postal_code',
    '^[A-Z]\\d[A-Z]\\s?\\d[A-Z]\\d$'
)
```

## Built-in Mappings

### Field Type to HTML Input Type

| CRUD6 Type | HTML Type |
|------------|-----------|
| email | email |
| url | url |
| phone | tel |
| zip | text |
| password | password |
| date | date |
| datetime | datetime-local |
| number | number |
| integer | number |
| decimal | number |
| float | number |

### Default Patterns

| Field Type | Pattern | Description |
|------------|---------|-------------|
| zip | `\d{5}(-\d{4})?` | US ZIP (5 or 9 digits) |
| phone | `\d{3}-\d{3}-\d{4}` | US phone (XXX-XXX-XXXX) |

### Named Patterns

| Pattern Name | Regex | Description |
|--------------|-------|-------------|
| us_zip_5 | `^\d{5}$` | US ZIP (5 digits only) |
| us_zip_9 | `^\d{5}(-\d{4})?$` | US ZIP (5 or 9 digits) |
| us_phone | `^\d{3}-\d{3}-\d{4}$` | US phone (strict format) |
| us_phone_flexible | `^\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$` | US phone (flexible) |
| alphanumeric | `^[a-zA-Z0-9]+$` | Letters and numbers only |
| alphanumeric_dash | `^[a-zA-Z0-9-_]+$` | Letters, numbers, dash, underscore |
| slug | `^[a-z0-9-]+$` | Lowercase slug format |
| hex_color | `^#[0-9A-Fa-f]{6}$` | Hex color code |
| ipv4 | `^(?:\d{1,3}\.){3}\d{1,3}$` | IPv4 address |
| url_safe | `^[a-zA-Z0-9._~:/?#\[\]@!$&'()*+,;=-]+$` | URL-safe characters |

## Usage in Components

### Basic Usage

```vue
<script setup lang="ts">
import { getInputType, getInputPattern, parseTextareaConfig } from '@ssnukala/sprinkle-crud6/utils'

// Map field type to HTML input type
const inputType = getInputType(field.type)

// Get validation pattern
const pattern = getInputPattern(field.type, field.validation)

// Parse textarea config
const textareaConfig = parseTextareaConfig(field.type)
</script>

<template>
    <input
        :type="inputType"
        :pattern="pattern"
    />
    
    <textarea
        :rows="textareaConfig.rows"
        :cols="textareaConfig.cols"
    />
</template>
```

### Advanced Usage with Custom Types

```typescript
// In your app initialization or plugin
import { registerFieldType, registerNamedPattern } from '@ssnukala/sprinkle-crud6/utils'

// Add custom field types for your application
registerFieldType('ssn', 'text', '\\d{3}-\\d{2}-\\d{4}')
registerFieldType('currency', 'number')

// Add custom named patterns
registerNamedPattern('product_code', '^[A-Z]{3}-\\d{4}$')
registerNamedPattern('uk_postcode', '^[A-Z]{1,2}\\d{1,2}[A-Z]?\\s?\\d[A-Z]{2}$')

// Now these can be used in schemas:
// {
//   "ssn": { "type": "ssn" },
//   "price": { "type": "currency" },
//   "code": { 
//     "type": "string",
//     "validation": { "regex": "product_code" }
//   }
// }
```

## Benefits

### Centralized Configuration
- All field type mappings in one place
- Easy to maintain and update
- Consistent behavior across the application

### Extensibility
- Add custom field types without modifying component code
- Register named patterns for reuse across schemas
- Support organization-specific validation patterns

### Type Safety
- TypeScript types for all functions
- Clear return types and interfaces
- IDE autocomplete support

### Reusability
- Use in any component that needs field type handling
- Import only what you need
- Tree-shakeable exports

## Future Enhancements

Planned additions to the utility:

1. **Field Formatters** - Format values for display
   ```typescript
   formatPhoneNumber(value: string): string
   formatCurrency(value: number, locale: string): string
   ```

2. **Field Validators** - Additional validation functions
   ```typescript
   validateEmail(value: string): boolean
   validateCreditCard(value: string): boolean
   ```

3. **Field Masks** - Input masking configurations
   ```typescript
   getFieldMask(fieldType: string): MaskConfig
   ```

4. **Internationalization** - Locale-specific patterns
   ```typescript
   registerLocalePatterns(locale: string, patterns: Record<string, string>)
   ```

## See Also

- [FIELD_TYPES_REFERENCE.md](../docs/FIELD_TYPES_REFERENCE.md) - Complete field types reference
- [FIELD_TYPES_AND_VALIDATION.md](../docs/FIELD_TYPES_AND_VALIDATION.md) - Validation recommendations
- [Form.vue](../components/CRUD6/Form.vue) - Usage example in Form component
