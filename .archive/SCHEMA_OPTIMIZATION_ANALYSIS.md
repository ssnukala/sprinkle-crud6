# CRUD6 JSON Schema Structure Analysis & Optimization Recommendations

**Date**: 2025-11-12  
**Purpose**: Comprehensive review of CRUD6 JSON schema structure for optimization and improved usability

## Executive Summary

After reviewing the existing JSON schema structure and all implemented features, here are the key findings and recommendations for making CRUD6 more intuitive and easier to use.

## Current Schema Structure Analysis

### Top-Level Schema Attributes

**Core Metadata** (Required):
- `model` - Model identifier
- `table` - Database table name
- `fields` - Field definitions (the heart of the schema)

**Optional Metadata**:
- `title` - Display title
- `singular_title` - Singular form title
- `description` - Model description
- `primary_key` - Primary key field (default: "id")
- `timestamps` - Enable timestamps (default: true)
- `soft_delete` - Enable soft delete (default: false)
- `connection` - Database connection name

**Permissions**:
```json
"permissions": {
    "read": "uri_permission",
    "create": "create_permission",
    "update": "update_permission",
    "delete": "delete_permission"
}
```

**Sorting**:
```json
"default_sort": {
    "field_name": "asc|desc"
}
```

**Relationships**:
```json
"relationships": [
    {
        "name": "related_model",
        "type": "many_to_many",
        "pivot_table": "pivot_table",
        "foreign_key": "local_id",
        "related_key": "foreign_id",
        "title": "Display Title",
        "actions": { /* on_create, on_update, on_delete */ }
    }
]
```

**Details/Related Data**:
```json
"details": [
    {
        "model": "related_model",
        "list_fields": ["field1", "field2"],
        "title": "Display Title"
    }
]
```

**Detail Editable** (Master-Detail):
```json
"detail_editable": {
    "model": "detail_model",
    "foreign_key": "master_id",
    "fields": ["field1", "field2"],
    "title": "Detail Title",
    "allow_add": true,
    "allow_edit": true,
    "allow_delete": true
}
```

**Render Mode**:
- `render_mode`: "master-detail" | "standard"

### Field-Level Attributes

**Core Field Attributes**:
- `type` - Field type (string, integer, boolean, date, datetime, text, json, decimal, password, smartlookup, etc.)
- `label` - Display label
- `required` - Is field required (default: false)
- `default` - Default value

**Visibility & Editability**:
- `readonly` - Field is read-only (default: false)
- `editable` - Field can be edited (default: true)
- `viewable` - Field is viewable in detail view (default: true)
- `listable` - Field appears in list view (default: true)
- `sortable` - Field can be sorted (default: false)
- `filterable` - Field can be filtered (default: false)

**Validation**:
```json
"validation": {
    "required": true,
    "unique": true,
    "email": true,
    "numeric": true,
    "min": 0,
    "max": 100,
    "length": {
        "min": 1,
        "max": 255
    }
}
```

**Display & Formatting**:
- `placeholder` - Placeholder text
- `description` - Help text
- `icon` - Icon identifier
- `rows` - For textarea fields
- `width` - Column width in list view
- `field_template` - Custom template for rendering
- `filter_type` - Filter type for filterable fields
- `date_format` - Date/datetime format

**SmartLookup Fields** (CURRENT - FLAT):
```json
"customer_id": {
    "type": "smartlookup",
    "lookup_model": "customers",
    "lookup_id": "id",
    "lookup_desc": "name"
}
```

**SmartLookup Fields** (PROPOSED - NESTED):
```json
"customer_id": {
    "type": "smartlookup",
    "lookup": {
        "model": "customers",
        "id": "id",
        "desc": "name"
    }
}
```

## Issues & Pain Points Identified

### 1. **Inconsistent Attribute Patterns**
- Some features use flat attributes (`lookup_model`, `lookup_id`, `lookup_desc`)
- Some use nested objects (`validation: {...}`, `permissions: {...}`)
- **Recommendation**: Standardize on nested objects for related configurations

### 2. **Redundant Flags**
Multiple boolean flags for visibility/editability can be confusing:
- `readonly` vs `editable` (opposite meanings)
- `viewable`, `listable`, `editable` (all serve similar visibility purposes)

**Current**:
```json
"field": {
    "readonly": false,
    "editable": true,
    "viewable": true,
    "listable": true
}
```

**Proposed Simplified**:
```json
"field": {
    "show": {
        "list": true,
        "form": true,
        "detail": true
    },
    "readonly": false
}
```

Or even simpler with defaults:
```json
"field": {
    "contexts": ["list", "form", "detail"],  // default: all
    "readonly": false
}
```

### 3. **Validation Structure Could Be Cleaner**

**Current**:
```json
"validation": {
    "required": true,
    "unique": true,
    "email": true,
    "length": {
        "min": 1,
        "max": 255
    }
}
```

**Alternative** (Laravel-style rules):
```json
"rules": "required|unique|email|min:1|max:255"
```

But the object approach is actually better for JSON. **Keep as-is**.

### 4. **Field Types Could Be More Intuitive**

**Current**:
- `boolean-tgl` (toggle)
- `boolean-chk` (checkbox)
- `boolean-sel` (select)

**Proposed** (more intuitive):
```json
"flag_enabled": {
    "type": "boolean",
    "ui": "toggle"  // or "checkbox", "select"
}
```

### 5. **SmartLookup Optimization** (Current Focus)

**Current Issues**:
- Flat attributes with `lookup_` prefix are verbose
- Inconsistent with other nested configurations
- Harder to extend (e.g., adding search filters, custom queries)

**Proposed Solution**:
```json
"customer_id": {
    "type": "smartlookup",
    "lookup": {
        "model": "customers",
        "id": "id",
        "desc": "name",
        // Future extensibility:
        "search_fields": ["name", "email"],
        "filters": { "is_active": true },
        "order_by": "name"
    }
}
```

### 6. **Relationship Actions Are Complex**

**Current**:
```json
"relationships": [
    {
        "name": "roles",
        "actions": {
            "on_create": {
                "attach": [
                    {
                        "related_id": 2,
                        "pivot_data": { "created_at": "now" }
                    }
                ]
            },
            "on_update": {
                "sync": "role_ids"
            }
        }
    }
]
```

This is powerful but complex. **Keep as-is** for power users, but provide simpler shortcuts:

**Proposed Shorthand**:
```json
"relationships": [
    {
        "name": "roles",
        "sync_field": "role_ids",  // Simple auto-sync
        "default_attach": [2]       // Simple default assignment
    }
]
```

## Recommended Optimizations

### Priority 1: SmartLookup Nested Structure ✅ (In Progress)

**Benefits**:
- More consistent with other features
- Easier to extend
- Cleaner, more intuitive
- Better IDE autocomplete support

**Implementation**: Already in progress in this PR.

### Priority 2: Simplify Visibility Flags

**Current Problem**: Too many overlapping flags
```json
"editable": false,
"viewable": true,
"listable": true,
"readonly": true
```

**Option A - Contexts Array** (Recommended):
```json
"show_in": ["list", "detail"],  // Not in "form" = not editable
"readonly": true                 // Read-only in detail view
```

**Option B - Nested Show Object**:
```json
"show": {
    "list": true,
    "form": false,
    "detail": true
},
"readonly": true
```

**Backward Compatibility**: Keep current flags but normalize internally to new structure.

### Priority 3: Boolean Field UI Types

**Current**:
```json
"type": "boolean-tgl"
```

**Proposed**:
```json
"type": "boolean",
"ui": "toggle"
```

**Default UI Options**:
- `checkbox` (default)
- `toggle`
- `select` (Yes/No dropdown)
- `radio` (Yes/No radio buttons)

### Priority 4: Consolidate Related Attributes Into Objects

**Fields**: Already good with nested `validation`, but could improve:
- `lookup` for smartlookup ✅ (in progress)
- `date` for date-related options
- `number` for numeric options

**Example - Date Fields**:
```json
"launch_date": {
    "type": "date",
    "date": {
        "format": "Y-m-d",
        "min": "2020-01-01",
        "max": "2030-12-31",
        "picker": "calendar"
    }
}
```

**Example - Number Fields**:
```json
"price": {
    "type": "decimal",
    "number": {
        "min": 0,
        "max": 999999.99,
        "precision": 2,
        "step": 0.01,
        "prefix": "$"
    }
}
```

### Priority 5: Smart Defaults & Convention Over Configuration

**Current**: Many explicit settings needed
**Proposed**: Intelligent defaults based on field type and name

**Examples**:
- Field named `*_id`: Auto-detect as foreign key, suggest smartlookup
- Field named `is_*` or `flag_*`: Auto-detect as boolean
- Field named `*_at`: Auto-detect as datetime with readonly
- Field named `email`: Auto-add email validation
- Field named `password`: Auto-detect as password type with hidden visibility

**Implementation**:
```json
"customer_id": {
    "type": "smartlookup"
    // Auto-infer: lookup.model = "customers", lookup.id = "id", lookup.desc = "name"
}

"email": {
    "type": "string"
    // Auto-infer: validation includes "email": true
}

"created_at": {
    "type": "datetime"
    // Auto-infer: readonly = true, show_in = ["detail"]
}
```

## Proposed Optimal Schema Structure

### Minimal Example (Smart Defaults):
```json
{
    "model": "products",
    "table": "products",
    "fields": {
        "id": {
            "type": "integer",
            "auto_increment": true
        },
        "name": {
            "type": "string",
            "required": true,
            "show_in": ["list", "form", "detail"]
        },
        "category_id": {
            "type": "smartlookup",
            "lookup": {
                "model": "categories"
            }
        },
        "price": {
            "type": "decimal",
            "required": true
        },
        "is_active": {
            "type": "boolean",
            "ui": "toggle"
        }
    }
}
```

### Full-Featured Example (All Options):
```json
{
    "model": "orders",
    "table": "orders",
    "title": "Orders",
    "permissions": {
        "read": "view_orders",
        "create": "create_order",
        "update": "edit_order",
        "delete": "delete_order"
    },
    "relationships": [
        {
            "name": "order_items",
            "type": "one_to_many",
            "foreign_key": "order_id"
        }
    ],
    "fields": {
        "id": {
            "type": "integer",
            "auto_increment": true,
            "show_in": ["detail"]
        },
        "customer_id": {
            "type": "smartlookup",
            "label": "Customer",
            "required": true,
            "lookup": {
                "model": "customers",
                "id": "id",
                "desc": "name",
                "search_fields": ["name", "email"],
                "filters": { "is_active": true }
            },
            "show_in": ["list", "form", "detail"]
        },
        "order_date": {
            "type": "date",
            "required": true,
            "date": {
                "format": "Y-m-d",
                "picker": "calendar"
            }
        },
        "status": {
            "type": "string",
            "default": "pending",
            "ui": "select",
            "options": [
                {"value": "pending", "label": "Pending"},
                {"value": "completed", "label": "Completed"}
            ]
        },
        "total": {
            "type": "decimal",
            "number": {
                "precision": 2,
                "prefix": "$"
            },
            "readonly": true
        }
    }
}
```

## Migration Strategy

### Phase 1: Add Nested Lookup Support ✅ (Current)
- Support both `lookup_*` flat and `lookup: {}` nested
- Normalize internally to flat for backward compatibility
- Update documentation and examples

### Phase 2: Add Visibility Contexts (Future)
- Support both old flags and new `show_in` array
- Normalize internally
- Update documentation

### Phase 3: Add UI Type for Booleans (Future)
- Support both `boolean-tgl` and `boolean` + `ui: toggle`
- Normalize internally

### Phase 4: Add Smart Defaults (Future)
- Implement convention-based auto-detection
- Make optional with config flag
- Add schema validation warnings

### Phase 5: Add Nested Options for Types (Future)
- `date: {}`, `number: {}`, etc.
- Keep backward compatibility

## Recommendations Summary

**Immediate (This PR)**:
1. ✅ Implement nested `lookup` object for smartlookup fields
2. ✅ Maintain backward compatibility with flat structure
3. ✅ Update documentation and examples

**Short-Term (Next 1-2 Releases)**:
1. Add `show_in` contexts array for field visibility
2. Add `ui` attribute for boolean field types
3. Update documentation with new patterns

**Medium-Term (Future Releases)**:
1. Add nested configuration objects (`date: {}`, `number: {}`)
2. Implement smart defaults based on field names
3. Add schema validation and warnings
4. Create schema generator/wizard tool

**Long-Term (Vision)**:
1. Visual schema editor
2. AI-powered schema suggestions
3. Database schema introspection
4. Migration generator from schema changes

## Conclusion

The current schema structure is already quite good and feature-rich. The main optimization opportunities are:

1. **Consistency**: Standardize on nested objects for related configurations
2. **Simplification**: Reduce overlapping/redundant flags
3. **Intuitiveness**: Use conventions and smart defaults
4. **Extensibility**: Structure for future enhancements

The nested `lookup` object is an excellent first step toward these goals and aligns with making CRUD6 more intuitive and easier to use.
