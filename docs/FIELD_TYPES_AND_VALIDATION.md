# Field Types and Validation - Recommendations

## Executive Summary

This document provides recommendations for enhancing CRUD6's field type system with:
1. **Built-in field type presets** with automatic validation and formatting
2. **Regex validation support** for custom patterns
3. **Schema builder tools** to simplify JSON creation
4. **UserFrosting 6 validation integration** for backend consistency

## 1. Recommended Field Type System

### 1.1 Standard Field Types with Built-in Validation

Define a set of standard field types that automatically apply sensible defaults:

```json
{
  "fields": {
    "email": {
      "type": "email",
      // 🎯 AUTO-APPLIED: validation.email = true, max length = 254, HTML5 email input
    },
    "phone": {
      "type": "phone",
      // 🎯 AUTO-APPLIED: pattern validation, HTML5 tel input, formatting hints
    },
    "zip": {
      "type": "zip",
      // 🎯 AUTO-APPLIED: pattern for US zip (5 or 9 digits), formatting
    },
    "url": {
      "type": "url",
      // 🎯 AUTO-APPLIED: URL validation, HTML5 url input
    },
    "username": {
      "type": "username",
      // 🎯 AUTO-APPLIED: alphanumeric + underscore, min 3, max 50
    },
    "slug": {
      "type": "slug",
      // 🎯 AUTO-APPLIED: lowercase, hyphens only, auto-generate from name
    }
  }
}
```

### 1.2 Enhanced Text Field Types

Support configuration options in type string:

```json
{
  "description": {
    "type": "textarea-r3c60",  // rows=3, cols=60
    "label": "Description"
  },
  "notes": {
    "type": "textarea-r5",  // rows=5, cols=default
    "label": "Notes"
  },
  "bio": {
    "type": "textarea",  // default rows and cols
    "label": "Biography"
  }
}
```

### 1.3 Specialized Field Types

```json
{
  "fields": {
    "credit_card": {
      "type": "creditcard",
      // 🎯 AUTO-APPLIED: Luhn validation, formatting with spaces
    },
    "ssn": {
      "type": "ssn",
      // 🎯 AUTO-APPLIED: XXX-XX-XXXX format, masking in display
    },
    "currency": {
      "type": "currency",
      "currency_code": "USD",
      // 🎯 AUTO-APPLIED: decimal(10,2), min 0, formatting with $
    },
    "percentage": {
      "type": "percentage",
      // 🎯 AUTO-APPLIED: decimal(5,2), min 0, max 100, % suffix
    }
  }
}
```

## 2. Regex Validation Support

### 2.1 Basic Regex Validation

Add regex support to validation rules:

```json
{
  "fields": {
    "product_code": {
      "type": "string",
      "label": "Product Code",
      "validation": {
        "required": true,
        "regex": {
          "pattern": "^[A-Z]{3}-\\d{4}$",
          "message": "Product code must be 3 letters, dash, 4 digits (e.g., ABC-1234)"
        }
      }
    },
    "phone": {
      "type": "phone",
      "validation": {
        "regex": {
          "pattern": "^\\d{3}-\\d{3}-\\d{4}$",
          "flags": "i",
          "message": "Phone must be in format XXX-XXX-XXXX"
        }
      }
    }
  }
}
```

### 2.2 Named Regex Patterns

Provide common patterns that users can reference:

```json
{
  "fields": {
    "zip": {
      "type": "string",
      "validation": {
        "regex": "us_zip_5"  // Predefined: ^\d{5}$
      }
    },
    "zip_plus4": {
      "type": "string",
      "validation": {
        "regex": "us_zip_9"  // Predefined: ^\d{5}(-\d{4})?$
      }
    },
    "phone": {
      "type": "string",
      "validation": {
        "regex": "us_phone"  // Predefined: ^\d{3}-\d{3}-\d{4}$
      }
    }
  }
}
```

**Recommended Predefined Patterns:**
- `us_zip_5`: `^\d{5}$`
- `us_zip_9`: `^\d{5}(-\d{4})?$`
- `us_phone`: `^\d{3}-\d{3}-\d{4}$`
- `us_phone_flexible`: `^\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}$`
- `alphanumeric`: `^[a-zA-Z0-9]+$`
- `alphanumeric_dash`: `^[a-zA-Z0-9-_]+$`
- `slug`: `^[a-z0-9-]+$`
- `hex_color`: `^#[0-9A-Fa-f]{6}$`
- `ipv4`: `^(?:\d{1,3}\.){3}\d{1,3}$`
- `url_safe`: `^[a-zA-Z0-9._~:/?#\[\]@!$&'()*+,;=-]+$`

## 3. UserFrosting 6 Validation Integration

### 3.1 Validation Rule Mapping

Map CRUD6 schema validation to UserFrosting's Fortress validation:

**Current CRUD6 Schema:**
```json
{
  "validation": {
    "required": true,
    "email": true,
    "unique": true,
    "length": {
      "min": 5,
      "max": 254
    }
  }
}
```

**Maps to Fortress Rules:**
```php
[
    'required',
    'email',
    'unique' => [
        'table' => 'users',
        'field' => 'email'
    ],
    'length' => [
        'min' => 5,
        'max' => 254
    ]
]
```

### 3.2 Additional Fortress Validators to Support

Expand validation options to match Fortress capabilities:

```json
{
  "fields": {
    "username": {
      "type": "string",
      "validation": {
        "required": true,
        "length": { "min": 3, "max": 50 },
        "regex": "^[a-zA-Z0-9_]+$",
        "unique": true,
        "no_leading_whitespace": true,
        "no_trailing_whitespace": true
      }
    },
    "age": {
      "type": "integer",
      "validation": {
        "integer": true,
        "range": { "min": 18, "max": 120 }
      }
    },
    "password": {
      "type": "password",
      "validation": {
        "required": true,
        "length": { "min": 8 },
        "matches": "password_confirm"  // Must match another field
      }
    },
    "terms": {
      "type": "boolean",
      "validation": {
        "equals": true  // Must be checked
      }
    }
  }
}
```

## 4. Schema Builder Tool Recommendations

### 4.1 Interactive Schema Builder (Web UI)

Create a visual schema builder tool:

**Features:**
- Drag-and-drop field addition
- Field type selector with preview
- Automatic validation rule suggestions
- Live JSON preview
- Schema validation before save
- Import/export schemas

**Example UI Flow:**
```
1. Select Model Name: "products"
2. Add Fields:
   - Click "Add Field" → Choose type "email"
   - Auto-fills: label, validation rules, input type
   - User customizes as needed
3. Preview JSON
4. Export/Save
```

### 4.2 CLI Schema Generator

```bash
# Create new schema
php bakery crud6:make-schema products

# Interactive prompts:
? Model name: products
? Add field (type 'done' to finish): name
? Field type: string
? Required? yes
? Max length: 100
? Unique? no
? Add field: sku
? Field type: string
? Required? yes
? Pattern: [A-Z]{3}-\d{4}
? Add field: price
? Field type: currency
? Min value: 0
? Add field: done

✓ Schema created: app/schema/crud6/products.json
```

### 4.3 Schema Templates

Provide pre-built templates for common use cases:

```bash
php bakery crud6:make-schema users --template=user-account
php bakery crud6:make-schema products --template=e-commerce
php bakery crud6:make-schema posts --template=blog
php bakery crud6:make-schema contacts --template=crm
```

**Template Examples:**

**User Account Template:**
```json
{
  "model": "users",
  "fields": {
    "username": { "type": "username" },
    "email": { "type": "email" },
    "password": { "type": "password" },
    "first_name": { "type": "string" },
    "last_name": { "type": "string" },
    "phone": { "type": "phone" }
  }
}
```

**E-Commerce Product Template:**
```json
{
  "model": "products",
  "fields": {
    "sku": { "type": "string", "validation": { "regex": "^[A-Z0-9-]+$" } },
    "name": { "type": "string" },
    "description": { "type": "textarea-r5" },
    "price": { "type": "currency" },
    "quantity": { "type": "integer" }
  }
}
```

## 5. Implementation Recommendations

### 5.1 Phase 1: Core Field Types (Immediate)

1. **Add new field types to Base controller:**
   - `phone`, `email`, `url`, `zip`
   - Parse `textarea-rXcY` format
   
2. **Update Form.vue:**
   - Map field types to HTML5 input types
   - Add pattern attributes for validation
   - Support rows/cols for textarea

3. **Documentation:**
   - Update README with all supported types
   - Create FIELD_TYPES reference guide

### 5.2 Phase 2: Validation Enhancement (Short-term)

1. **Add regex validation support:**
   - Extend validation rules in Base controller
   - Add predefined pattern library
   - Implement both frontend and backend validation

2. **Fortress integration:**
   - Map all CRUD6 validation to Fortress
   - Support advanced Fortress validators
   - Ensure validation consistency

### 5.3 Phase 3: Schema Builder Tools (Medium-term)

1. **CLI tool:**
   - Interactive schema generator
   - Schema templates
   - Validation and testing

2. **Web UI builder (optional):**
   - Visual schema designer
   - Live preview
   - Import/export

### 5.4 Phase 4: Advanced Features (Long-term)

1. **Smart defaults:**
   - Auto-infer field types from names
   - Suggest validation rules
   - Field relationship detection

2. **Schema validation:**
   - JSON schema validator
   - Compatibility checker
   - Migration tools

## 6. Backward Compatibility

All enhancements must maintain backward compatibility:

✅ **Compatible:**
```json
// Old schemas still work
{ "type": "string" }

// New features are opt-in
{ "type": "email" }  // Enhanced
{ "type": "textarea-r5c60" }  // New format
```

❌ **Avoid Breaking Changes:**
- Don't change existing type behavior
- Don't require new mandatory fields
- Don't remove existing validation options

## 7. Example: Complete Enhanced Schema

```json
{
  "model": "users",
  "title": "User Management",
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true,
      "listable": true
    },
    "username": {
      "type": "username",
      "label": "Username",
      "required": true,
      "listable": true,
      "validation": {
        "required": true,
        "unique": true,
        "length": { "min": 3, "max": 50 }
        // Auto-applied: alphanumeric + underscore pattern
      }
    },
    "email": {
      "type": "email",
      "label": "Email Address",
      "required": true,
      "listable": true,
      "validation": {
        "required": true,
        "unique": true
        // Auto-applied: email validation, max 254
      }
    },
    "phone": {
      "type": "phone",
      "label": "Phone Number",
      "listable": true,
      "validation": {
        "regex": "us_phone"
        // Auto-applied: tel input type, formatting
      }
    },
    "zip": {
      "type": "zip",
      "label": "ZIP Code",
      "validation": {
        "regex": "us_zip_9"  // Allow 5 or 9 digit ZIP
      }
    },
    "bio": {
      "type": "textarea-r5c80",
      "label": "Biography",
      "listable": false
    },
    "employee_id": {
      "type": "string",
      "label": "Employee ID",
      "validation": {
        "required": true,
        "regex": {
          "pattern": "^EMP-\\d{6}$",
          "message": "Must be format EMP-######"
        }
      }
    },
    "salary": {
      "type": "currency",
      "label": "Annual Salary",
      "currency_code": "USD",
      "listable": false,
      "validation": {
        "range": { "min": 0, "max": 1000000 }
      }
    },
    "password": {
      "type": "password",
      "label": "Password",
      "listable": false,
      "viewable": false,
      "validation": {
        "required": true,
        "length": { "min": 8 }
      }
    }
  }
}
```

## 8. Next Steps

### Immediate Actions:
1. ✅ Implement basic field types (email, phone, zip, url)
2. ✅ Add textarea row/column parsing
3. ✅ Update frontend Form.vue with HTML5 input types
4. ✅ Document new field types

### Follow-up Work:
1. 📋 Implement regex validation (backend + frontend)
2. 📋 Create predefined pattern library
3. 📋 Build CLI schema generator
4. 📋 Expand validation rule mapping
5. 📋 Create schema templates

Would you like me to proceed with implementing these recommendations?
