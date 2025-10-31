# Schema Structure Enhancement: Adding "viewable" Attribute

**Date**: October 31, 2024  
**Related to**: Schema Structure Analysis and Recommendation  
**Issue**: Distinguishing between fields visible in detail/view mode vs editable in forms

---

## Background

Following the schema structure analysis, a new requirement has been identified:

**Problem**: Currently, there's no clear distinction between:
1. Fields shown in **list views** (`listable`)
2. Fields shown in **detail/view pages** (currently all fields shown)
3. Fields that can be **edited** in forms (`editable`)

**Use Case Example** (from `sprinkle-c6admin/app/schema/crud6/users.json`):
- `password`, `created_at`, `updated_at` are marked `readonly: true`
- These fields should be **visible** in the detail/view page
- But they are **not editable** in forms
- Currently, `listable: false` hides them from list views, but they still show in detail views

---

## Proposed Enhancement

### Add "viewable" Attribute

Introduce a new field attribute `viewable` to control visibility in detail/view pages:

```json
{
  "fields": {
    "password": {
      "type": "string",
      "label": "Password",
      "listable": false,      // Don't show in list/table views
      "viewable": true,       // Show in detail/view page
      "editable": false,      // Can't be edited (or use readonly: true)
      "readonly": true
    },
    "created_at": {
      "type": "datetime",
      "label": "Created At",
      "listable": false,      // Don't show in list views
      "viewable": true,       // Show in detail/view page
      "editable": false,      // Not editable
      "readonly": true
    },
    "updated_at": {
      "type": "datetime",
      "label": "Updated At",
      "listable": false,      // Don't show in list views
      "viewable": true,       // Show in detail/view page
      "editable": false,      // Not editable
      "readonly": true
    },
    "user_name": {
      "type": "string",
      "label": "Username",
      "listable": true,       // Show in list views
      "viewable": true,       // Show in detail/view page
      "editable": true        // Can be edited
    },
    "internal_notes": {
      "type": "text",
      "label": "Internal Notes",
      "listable": false,      // Don't show in list views
      "viewable": false,      // Don't show in detail/view page
      "editable": true        // But can be edited in forms
    }
  }
}
```

---

## Attribute Clarification

### Current Attributes

| Attribute | Purpose | Default | Context |
|-----------|---------|---------|---------|
| `listable` | Show in list/table views | `false` (security) | List view |
| `editable` | Can be edited in forms | `true` (unless readonly) | Form context |
| `readonly` | Cannot be modified | `false` | Form/Edit |
| `sortable` | Can sort by this field | `false` | List view |
| `filterable` | Can filter by this field | `false` | List view |

### Proposed Addition

| Attribute | Purpose | Default | Context |
|-----------|---------|---------|---------|
| **`viewable`** | **Show in detail/view page** | **`true`** | **Detail/View page** |

---

## Use Cases Enabled

### 1. Readonly Fields Visible in Detail View
```json
"created_at": {
  "listable": false,   // Not in table
  "viewable": true,    // But show in detail page
  "editable": false,   // Can't edit
  "readonly": true
}
```

### 2. Editable But Not Displayed
```json
"internal_notes": {
  "listable": false,   // Not in table
  "viewable": false,   // Not in detail view
  "editable": true     // But can be edited via edit form
}
```

### 3. Sensitive Fields Hidden Everywhere
```json
"password_hash": {
  "listable": false,   // Not in table
  "viewable": false,   // Not in detail view
  "editable": false    // Not editable
}
```

### 4. Fully Visible Fields
```json
"user_name": {
  "listable": true,    // In table
  "viewable": true,    // In detail view
  "editable": true     // Can edit
}
```

---

## Implementation Impact

### 1. SchemaService Changes

Update `getContextSpecificData()` method for 'detail' context:

**Current** (shows all fields):
```php
case 'detail':
    // For detail/view pages: all fields with full display properties
    $data = ['fields' => []];
    
    foreach ($schema['fields'] as $fieldKey => $field) {
        $data['fields'][$fieldKey] = [
            'type' => $field['type'] ?? 'string',
            'label' => $field['label'] ?? $fieldKey,
            'readonly' => $field['readonly'] ?? false,
        ];
        // ... other properties
    }
    return $data;
```

**Proposed** (filter by viewable):
```php
case 'detail':
    // For detail/view pages: only viewable fields
    $data = ['fields' => []];
    
    foreach ($schema['fields'] as $fieldKey => $field) {
        // Include field if explicitly viewable or viewable is not set (default true)
        if (($field['viewable'] ?? true) === true) {
            $data['fields'][$fieldKey] = [
                'type' => $field['type'] ?? 'string',
                'label' => $field['label'] ?? $fieldKey,
                'readonly' => $field['readonly'] ?? false,
            ];
            
            // Include editable flag for detail pages that allow inline editing
            if (isset($field['editable'])) {
                $data['fields'][$fieldKey]['editable'] = $field['editable'];
            }
            
            // ... other properties
        }
    }
    return $data;
```

### 2. Schema-Level Defaults Support

With the optional defaults enhancement, `viewable` can also have a default:

```json
{
  "defaults": {
    "sortable": true,
    "filterable": false,
    "listable": false,    // Opt-in for security
    "viewable": true,     // Most fields viewable in detail
    "editable": true      // Most fields editable unless readonly
  },
  "fields": {
    "password": {
      "type": "string",
      "readonly": true,
      "editable": false,  // Override default
      "listable": false   // Override default
      // viewable: true (inherited - show in detail view)
    },
    "password_hash": {
      "type": "string",
      "viewable": false,  // Override - never show
      "editable": false,
      "listable": false
    }
  }
}
```

---

## Migration Strategy

### Backward Compatibility

**Default behavior**: `viewable: true` (if not specified)

This ensures existing schemas continue to work:
- Fields without `viewable` attribute will default to `true`
- Detail/view pages will show fields as before
- No breaking changes

### Migration Steps

1. **Phase 1**: Add `viewable` support to SchemaService
   - Update `getContextSpecificData()` for 'detail' context
   - Default to `true` if not specified
   - Test with existing schemas

2. **Phase 2**: Update example schemas
   - Add `viewable: false` to sensitive fields
   - Add `viewable: true` to readonly fields that should display
   - Document best practices

3. **Phase 3**: Update documentation
   - Add `viewable` to field attributes list in README
   - Provide examples and use cases
   - Update schema templates

---

## Recommended Schema Pattern

### Complete Field Visibility Matrix

```json
{
  "fields": {
    // Public, fully visible field
    "user_name": {
      "type": "string",
      "listable": true,
      "viewable": true,
      "editable": true
    },
    
    // Readonly field visible in detail view
    "created_at": {
      "type": "datetime",
      "readonly": true,
      "listable": false,
      "viewable": true,
      "editable": false
    },
    
    // Sensitive field - hidden everywhere
    "password_hash": {
      "type": "string",
      "listable": false,
      "viewable": false,
      "editable": false
    },
    
    // Internal field - editable but not displayed
    "internal_flags": {
      "type": "json",
      "listable": false,
      "viewable": false,
      "editable": true
    },
    
    // Display-only field (computed, readonly)
    "full_name": {
      "type": "string",
      "computed": true,
      "readonly": true,
      "listable": true,
      "viewable": true,
      "editable": false
    }
  }
}
```

---

## Benefits

### 1. **Fine-Grained Control**
- Separate concerns: list display, detail display, editability
- Explicit control over what users see where

### 2. **Security**
- Can hide sensitive fields from detail views
- Prevent accidental exposure of internal data
- Clear visibility rules

### 3. **Flexibility**
- Show readonly fields in detail view (timestamps, computed fields)
- Hide fields that are editable but shouldn't be displayed
- Support different view modes

### 4. **Consistency**
- Aligns with existing `listable`, `editable`, `sortable`, `filterable` pattern
- Self-documenting schemas
- Easy to understand field visibility

---

## Implementation Checklist

- [ ] Update `SchemaService::getContextSpecificData()` for 'detail' context
- [ ] Add `viewable` to field defaults in `applyDefaults()` (if implementing defaults)
- [ ] Update schema validation to recognize `viewable` attribute
- [ ] Update example schemas (users.json, products.json)
- [ ] Add tests for `viewable` filtering
- [ ] Update README documentation
- [ ] Update schema templates
- [ ] Add migration guide for existing schemas

---

## Estimated Effort

- **Schema Service Update**: 2-3 hours
- **Testing**: 2-3 hours
- **Documentation**: 2-3 hours
- **Example Schema Updates**: 1 hour

**Total**: 7-10 hours

---

## Conclusion

Adding the `viewable` attribute provides the missing piece in CRUD6's field visibility control:

- ✅ **listable** - Controls list/table view visibility
- ✅ **viewable** - Controls detail/view page visibility (NEW)
- ✅ **editable** - Controls form editability

This enhancement maintains the per-field attribute structure (recommended approach) while providing the granular control needed for real-world applications.

**Recommendation**: Implement `viewable` attribute with default value of `true` for backward compatibility.
