# CRUD6 Field Types Reference

## Overview

CRUD6 supports a rich set of field types that automatically configure frontend input types, validation, and backend data handling. This document provides a complete reference for all supported field types.

## Supported Field Types

### Basic Types

#### string
General text input field.

**Backend:** Stored as string
**Frontend:** `<input type="text">`
**Validation:** Length constraints

```json
{
  "name": {
    "type": "string",
    "label": "Name",
    "validation": {
      "length": { "min": 1, "max": 100 }
    }
  }
}
```

#### integer
Whole numbers.

**Backend:** Cast to integer
**Frontend:** `<input type="number" step="1">`
**Validation:** Range constraints

```json
{
  "age": {
    "type": "integer",
    "validation": {
      "range": { "min": 0, "max": 150 }
    }
  }
}
```

#### float / decimal
Decimal numbers.

**Backend:** Cast to float
**Frontend:** `<input type="number" step="any">`
**Validation:** Range constraints

```json
{
  "price": {
    "type": "decimal",
    "validation": {
      "range": { "min": 0 }
    }
  }
}
```

#### boolean
True/false values.

**Backend:** Cast to boolean
**Frontend:** `<input type="checkbox">`
**Validation:** None (binary value)

```json
{
  "is_active": {
    "type": "boolean",
    "default": true
  }
}
```

### Text Types

#### text
Multi-line text area (default 6 rows).

**Backend:** Stored as string
**Frontend:** `<textarea rows="6">`
**Validation:** Length constraints

```json
{
  "description": {
    "type": "text",
    "validation": {
      "length": { "max": 1000 }
    }
  }
}
```

#### textarea-rX
Text area with custom row count.

**Backend:** Stored as string
**Frontend:** `<textarea rows="X">`

```json
{
  "notes": {
    "type": "textarea-r3",
    "label": "Notes"
  }
}
```

#### textarea-rXcY
Text area with custom rows and columns.

**Backend:** Stored as string
**Frontend:** `<textarea rows="X" cols="Y">`

```json
{
  "bio": {
    "type": "textarea-r5c80",
    "label": "Biography"
  }
}
```

### Date/Time Types

#### date
Date picker (no time).

**Backend:** Date value
**Frontend:** `<input type="date">`
**Validation:** Date format

```json
{
  "birth_date": {
    "type": "date",
    "label": "Date of Birth"
  }
}
```

#### datetime
Date and time picker.

**Backend:** DateTime value
**Frontend:** `<input type="datetime-local">`
**Validation:** DateTime format

```json
{
  "created_at": {
    "type": "datetime",
    "editable": false
  }
}
```

### Security Types

#### password
Password input with automatic bcrypt hashing.

**Backend:** Automatically hashed using UserFrosting's Hasher
**Frontend:** `<input type="password">`
**Validation:** Length constraints (min 8 recommended)
**Security:** Not listable, not viewable by default

```json
{
  "password": {
    "type": "password",
    "listable": false,
    "viewable": false,
    "validation": {
      "length": { "min": 8, "max": 255 }
    }
  }
}
```

**See:** [PASSWORD_FIELD_TYPE.md](PASSWORD_FIELD_TYPE.md) for detailed documentation.

### Contact/Communication Types

#### email
Email address with validation.

**Backend:** Stored as string
**Frontend:** `<input type="email">` (HTML5 validation)
**Validation:** Email format, uniqueness

```json
{
  "email": {
    "type": "email",
    "label": "Email Address",
    "validation": {
      "email": true,
      "unique": true,
      "length": { "max": 254 }
    }
  }
}
```

#### phone
Phone number with tel input.

**Backend:** Stored as string
**Frontend:** `<input type="tel">` with pattern attribute
**Validation:** Pattern matching (default: XXX-XXX-XXXX)

```json
{
  "phone": {
    "type": "phone",
    "label": "Phone Number",
    "placeholder": "XXX-XXX-XXXX",
    "validation": {
      "regex": {
        "pattern": "\\d{3}-\\d{3}-\\d{4}",
        "message": "Phone must be in format XXX-XXX-XXXX"
      }
    }
  }
}
```

#### url
Website URL with validation.

**Backend:** Stored as string
**Frontend:** `<input type="url">` (HTML5 validation)
**Validation:** URL format

```json
{
  "website": {
    "type": "url",
    "label": "Website",
    "placeholder": "https://example.com"
  }
}
```

### Geographic Types

#### address
Google Places autocomplete address field with automatic geocoding.

**Backend:** Stored as string (full formatted address)
**Frontend:** Google Places Autocomplete input
**Validation:** Text validation
**Special:** Auto-populates related address fields (addr_line1, addr_line2, city, state, zip, lat, lng)

```json
{
  "full_address": {
    "type": "address",
    "label": "Address",
    "placeholder": "Start typing to search...",
    "required": true,
    "address_fields": {
      "addr_line1": "address_line_1",
      "addr_line2": "address_line_2",
      "city": "city",
      "state": "state",
      "zip": "postal_code",
      "latitude": "lat",
      "longitude": "lng"
    }
  }
}
```

**See:** [GOOGLE_ADDRESS_FIELD.md](GOOGLE_ADDRESS_FIELD.md) for setup and configuration.

#### zip
US ZIP code (5 or 9 digit).

**Backend:** Stored as string
**Frontend:** `<input type="text">` with pattern attribute
**Validation:** Pattern matching (default: XXXXX or XXXXX-XXXX)

```json
{
  "zip": {
    "type": "zip",
    "label": "ZIP Code",
    "placeholder": "12345 or 12345-6789",
    "validation": {
      "regex": {
        "pattern": "\\d{5}(-\\d{4})?",
        "message": "ZIP must be 5 or 9 digits"
      }
    }
  }
}
```

### Data Types

#### json
JSON data storage.

**Backend:** Encoded/decoded automatically
**Frontend:** Text input or custom JSON editor
**Validation:** Valid JSON

```json
{
  "metadata": {
    "type": "json",
    "label": "Metadata",
    "viewable": false
  }
}
```

## Field Type Matrix

| Type | Backend Type | HTML Input | Auto Validation | Hashing | Notes |
|------|-------------|------------|-----------------|---------|-------|
| `string` | string | text | length | - | General text |
| `integer` | int | number | range | - | Whole numbers |
| `float` | float | number | range | - | Decimals |
| `decimal` | float | number | range | - | Alias for float |
| `boolean` | bool | checkbox | - | - | True/false |
| `text` | string | textarea | length | - | Multi-line (6 rows) |
| `textarea-rX` | string | textarea | length | - | Custom rows |
| `textarea-rXcY` | string | textarea | length | - | Custom rows & cols |
| `date` | date | date | format | - | Date only |
| `datetime` | datetime | datetime-local | format | - | Date + time |
| `password` | string | password | length | ✅ bcrypt | Auto-hashed |
| `email` | string | email | email format | - | HTML5 validation |
| `phone` | string | tel | pattern | - | Formatted phone |
| `url` | string | url | URL format | - | HTML5 validation |
| `zip` | string | text | pattern | - | US ZIP codes |
| `json` | string | text | JSON | - | Auto encode/decode |

## Validation Support

### Built-in Validation Rules

All field types support these validation rules:

```json
{
  "validation": {
    "required": true,
    "unique": true,
    "length": {
      "min": 1,
      "max": 100
    },
    "range": {
      "min": 0,
      "max": 100
    },
    "email": true,
    "regex": {
      "pattern": "^[A-Z]{3}-\\d{4}$",
      "message": "Custom error message"
    }
  }
}
```

### Regex Validation

Custom regex patterns can be added to any field:

```json
{
  "product_code": {
    "type": "string",
    "validation": {
      "regex": {
        "pattern": "^[A-Z]{3}-\\d{4}$",
        "message": "Product code must be 3 letters, dash, 4 digits"
      }
    }
  }
}
```

**Shorthand (pattern only):**
```json
{
  "validation": {
    "regex": "^[A-Z]{3}-\\d{4}$"
  }
}
```

## Field Configuration Properties

All fields support these configuration properties:

```json
{
  "field_name": {
    "type": "string",              // Field type (required)
    "label": "Field Label",        // Human-readable label
    "placeholder": "Enter value",  // Placeholder text
    "default": "default_value",    // Default value
    "required": true,              // Required for creation
    "listable": true,              // Show in list views
    "viewable": true,              // Show in detail views
    "editable": true,              // Allow editing
    "sortable": true,              // Enable sorting
    "filterable": true,            // Include in search/filter
    "readonly": false,             // DEPRECATED: use editable: false
    "auto_increment": false,       // Auto-increment (integers only)
    "validation": {}               // Validation rules
  }
}
```

## Best Practices

### Security
1. **Always use `password` type** for password fields (auto-hashing)
2. **Set `listable: false`** for sensitive fields (passwords, SSN, etc.)
3. **Set `viewable: false`** for fields that shouldn't appear in detail views
4. **Use `unique: true`** for email, username fields

### Validation
1. **Set minimum lengths** for text fields to prevent empty strings
2. **Use `email` type** for email fields (HTML5 + backend validation)
3. **Add regex patterns** for formatted fields (phone, zip, codes)
4. **Set reasonable max lengths** to prevent database overflow

### User Experience
1. **Use appropriate placeholders** to show expected format
2. **Provide clear validation messages** in regex rules
3. **Use `phone` type** instead of string for phone numbers
4. **Use `textarea-rX`** for multi-line inputs with known size

### Performance
1. **Mark large text fields** as `listable: false` to improve table performance
2. **Use `filterable: true` selectively** to keep search queries fast
3. **Set `sortable: false`** on text/JSON fields that don't need sorting

## Examples

### Complete Contact Form

```json
{
  "model": "contacts",
  "fields": {
    "first_name": {
      "type": "string",
      "label": "First Name",
      "required": true,
      "listable": true,
      "validation": { "length": { "min": 1, "max": 50 } }
    },
    "email": {
      "type": "email",
      "label": "Email",
      "required": true,
      "listable": true,
      "validation": { "email": true, "unique": true }
    },
    "phone": {
      "type": "phone",
      "label": "Phone",
      "placeholder": "XXX-XXX-XXXX",
      "validation": {
        "regex": {
          "pattern": "\\d{3}-\\d{3}-\\d{4}",
          "message": "Format: XXX-XXX-XXXX"
        }
      }
    },
    "zip": {
      "type": "zip",
      "label": "ZIP Code",
      "placeholder": "12345",
      "validation": {
        "regex": "\\d{5}(-\\d{4})?"
      }
    },
    "notes": {
      "type": "textarea-r3c60",
      "label": "Notes",
      "listable": false
    }
  }
}
```

## See Also

- [PASSWORD_FIELD_TYPE.md](PASSWORD_FIELD_TYPE.md) - Password field documentation
- [FIELD_TYPES_AND_VALIDATION.md](FIELD_TYPES_AND_VALIDATION.md) - Detailed recommendations
- [SCHEMA_API_QUICK_REFERENCE.md](SCHEMA_API_QUICK_REFERENCE.md) - Schema API reference
