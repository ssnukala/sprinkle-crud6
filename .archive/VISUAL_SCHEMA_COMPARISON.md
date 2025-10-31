# Visual Schema Comparison: UF6 vs CRUD6

This document provides side-by-side visual comparisons to illustrate the key differences between UserFrosting 6's approach and CRUD6's approach.

## Use Case Comparison

### UserFrosting 6 sprinkle-admin

```
┌─────────────────────────────────────────────────┐
│  Hard-Coded Model Classes                       │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐│
│  │ User.php   │  │ Group.php  │  │ Role.php   ││
│  │ (Eloquent) │  │ (Eloquent) │  │ (Eloquent) ││
│  └────────────┘  └────────────┘  └────────────┘│
│        ↓                ↓                ↓      │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐│
│  │  Multiple  │  │  Multiple  │  │  Multiple  ││
│  │   Schema   │  │   Schema   │  │   Schema   ││
│  │   Files    │  │   Files    │  │   Files    ││
│  │            │  │            │  │            ││
│  │ create.yml │  │ create.yml │  │ create.yml ││
│  │  edit.yml  │  │  edit.yml  │  │  edit.yml  ││
│  │ field.yml  │  │ field.yml  │  │ field.yml  ││
│  └────────────┘  └────────────┘  └────────────┘│
│                                                 │
│  Purpose: Request Validation Only               │
│  Format: YAML                                   │
│  Content: Validators, Transformations           │
└─────────────────────────────────────────────────┘
```

### CRUD6

```
┌─────────────────────────────────────────────────┐
│  No Hard-Coded Models - Dynamic Generic Model   │
│  ┌─────────────────────────────────────────────┐│
│  │       CRUD6Model.php (Generic)              ││
│  │       Configured at Runtime from Schema     ││
│  └─────────────────────────────────────────────┘│
│                      ↑                          │
│                      │                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│  │  Single  │  │  Single  │  │  Single  │     │
│  │  Schema  │  │  Schema  │  │  Schema  │     │
│  │   File   │  │   File   │  │   File   │     │
│  │          │  │          │  │          │     │
│  │users.json│  │groups.   │  │products. │     │
│  │          │  │  json    │  │  json    │     │
│  └──────────┘  └──────────┘  └──────────┘     │
│                                                 │
│  Purpose: Complete Model Definition             │
│  Format: JSON                                   │
│  Content: Types, UI, Validation, Permissions,   │
│           Templates, Behavior                   │
└─────────────────────────────────────────────────┘
```

## Field Definition Comparison

### UserFrosting 6 (create.yaml)

```yaml
user_name:
  validators:                 ← Validation ONLY
    required:
      label: "&USERNAME"
      message: VALIDATE.REQUIRED
    length:
      label: "&USERNAME"
      min: 1
      max: 50
      message: VALIDATE.LENGTH_RANGE
  transformations:
  - trim

# No type information
# No UI hints (sortable, filterable, listable)
# No display metadata (label for UI)
# No field templates
# No permissions
```

**What's Missing:**
- ❌ Field type (string, integer, etc.)
- ❌ UI display properties (sortable, filterable, listable)
- ❌ Custom rendering (field_template)
- ❌ Model/database metadata

**Why It Works for UF6:**
- Model class `User.php` already defines types
- Hard-coded controllers know which fields to show/hide
- Validation is the only dynamic part

### CRUD6 (users.json)

```json
"user_name": {
  "type": "string",           ← Database type
  "label": "Username",        ← UI label
  "sortable": true,           ← Can sort by this field
  "filterable": true,         ← Can filter by this field
  "listable": true,           ← Show in list views
  "editable": true,           ← Can edit this field
  "field_template": "...",    ← Custom rendering (UNIQUE)
  "validation": {             ← Validation rules
    "required": true,
    "length": {
      "min": 3,
      "max": 50
    }
  }
}
```

**Why Everything is Needed:**
- ✅ No model class exists - schema defines type
- ✅ Generic controllers need UI hints
- ✅ Frontend components need display metadata
- ✅ Custom templates enable flexible rendering
- ✅ Single source of truth for everything

## Proposed Change Visualization

### Current Structure (Recommended)

```json
{
  "model": "products",
  "fields": {
    "name": {
      ┌─────────────────────────────────────────┐
      │ "type": "string",           // Database │
      │ "label": "Product Name",    // UI       │
      │ "sortable": true,           // Behavior │
      │ "filterable": true,         // Behavior │
      │ "listable": true,           // Behavior │
      │ "field_template": "...",    // Render   │
      │ "validation": {             // Rules    │
      │   "required": true          //          │
      │ }                                       │
      └─────────────────────────────────────────┘
      
      ✅ Everything in ONE place
      ✅ Self-documenting
      ✅ Easy to copy/paste
    },
    "price": {
      ┌─────────────────────────────────────────┐
      │ "type": "decimal",          // Database │
      │ "label": "Price",           // UI       │
      │ "sortable": true,           // Behavior │
      │ "filterable": true,         // Behavior │
      │ "filter_type": "between",   // Specific │
      │ "listable": true,           // Behavior │
      │ "validation": {             // Rules    │
      │   "min": 0                  //          │
      │ }                                       │
      └─────────────────────────────────────────┘
      
      ✅ Field-specific config natural
    }
  }
}
```

### Proposed Array Structure (Not Recommended)

```json
{
  "model": "products",
  
  ┌─────────────────────────────────────────────┐
  │ "sortable": [                               │
  │   "name", "price", "category", "created_at" │
  │ ],                                          │
  │ "filterable": [                             │
  │   "name", "price", "category"               │
  │ ],                                          │
  │ "listable": [                               │
  │   "name", "price", "description"            │
  │ ],                                          │
  │ "filter_types": {                           │
  │   "price": "between",                       │
  │   "category": "equals"                      │
  │ }                                           │
  └─────────────────────────────────────────────┘
  ↑
  Must look here for behavior
  
  "fields": {
    "name": {
      ┌───────────────────────────────┐
      │ "type": "string",             │
      │ "label": "Product Name",      │
      │ "field_template": "...",      │
      │ "validation": {               │
      │   "required": true            │
      │ }                             │
      └───────────────────────────────┘
      
      ❌ Must check BOTH places
      ❌ Split definitions
    },
    "price": {
      ┌───────────────────────────────┐
      │ "type": "decimal",            │
      │ "label": "Price",             │
      │ "validation": {               │
      │   "min": 0                    │
      │ }                             │
      └───────────────────────────────┘
      
      ❌ Where is filter_type?
         Look in filter_types object above!
      ❌ Is it sortable?
         Check sortable array above!
    }
  }
}
```

## Code Complexity Comparison

### Getting Sortable Fields

#### Current (Simple)
```php
foreach ($schema['fields'] as $name => $field) {
    if ($field['sortable'] ?? false) {
        $sortable[] = $name;
    }
}
```
✅ **One location**  
✅ **Simple logic**  
✅ **No lookups**

#### With Arrays (Complex)
```php
// From schema-level array
$sortable = $schema['sortable'] ?? [];

// But wait, what if field overrides?
foreach ($schema['fields'] as $name => $field) {
    // Field-level override?
    if (isset($field['sortable'])) {
        if ($field['sortable']) {
            $sortable[] = $name;
        } else {
            // Remove from array if was there
            $sortable = array_diff($sortable, [$name]);
        }
    }
}
```
❌ **Two locations**  
❌ **Override logic needed**  
❌ **More complex**

## Field-Specific Configuration Examples

### Filter Types

#### Current
```json
"price": {
  "filterable": true,
  "filter_type": "between"
}
```
✅ Natural association

#### With Arrays
```json
"filterable": ["price"],
"filter_types": {
  "price": "between"
}
```
❌ Separate mapping needed

### Field Templates (UNIQUE to CRUD6)

#### Current
```json
"description": {
  "listable": true,
  "field_template": "<div class='card'>{{description}}</div>"
}
```
✅ Template with field definition

#### With Arrays
```json
"listable": ["description"],
"fields": {
  "description": {
    "field_template": "<div class='card'>{{description}}</div>"
  }
}
```
❌ Why is it listable? Check array above!

## Optional Enhancement: Schema Defaults

### Middle Ground Approach

```json
{
  "model": "products",
  
  ┌────────────────────────────┐
  │ "defaults": {              │  ← Common defaults
  │   "sortable": true,        │
  │   "filterable": false,     │
  │   "listable": false        │
  │ },                         │
  └────────────────────────────┘
  
  "fields": {
    "name": {
      "type": "string",
      "filterable": true,      ← Override default
      "listable": true         ← Override default
      // sortable: true INHERITED
    },
    "id": {
      "type": "integer",
      // All defaults inherited:
      // sortable: true
      // filterable: false
      // listable: false
    },
    "password": {
      "type": "string",
      "sortable": false,       ← Override default
      // filterable: false INHERITED
      // listable: false INHERITED
    }
  }
}
```

**Benefits:**
✅ Reduces repetition for common patterns  
✅ Overrides still visible at field level  
✅ Self-documenting (see what's different)  
✅ Backward compatible  
✅ Still one place to look per field  

**This is OPTIONAL** - current structure works great as-is!

## Line Count Savings Analysis

### Example: products.json

**Current: 139 lines**
```
{
  "model": "products",           // 1 line
  "title": "...",                // 1 line
  "table": "products",           // 1 line
  "permissions": { ... },        // 5 lines
  "fields": {                    // 1 line
    "id": {                      // 11 lines
      "type": "integer",
      "label": "ID",
      "sortable": true,
      "filterable": false,
      "listable": true,
      ...
    },
    ... 10 more fields           // 120 lines
  }
}
```

**With Arrays: ~125 lines (estimate)**
```
{
  "model": "products",           // 1 line
  "title": "...",                // 1 line
  "table": "products",           // 1 line
  "permissions": { ... },        // 5 lines
  "sortable": [ ... ],           // 3 lines (multi-line array)
  "filterable": [ ... ],         // 2 lines
  "listable": [ ... ],           // 2 lines
  "filter_types": { ... },       // 4 lines
  "fields": {                    // 1 line
    "id": {                      // 7 lines (4 fewer)
      "type": "integer",
      "label": "ID",
      ...
    },
    ... 10 more fields           // ~98 lines
  }
}
```

**Savings: 14 lines (10%)**

**Cost:**
- Split definitions across file
- Field-specific configs need mappings
- Harder to understand
- More complex parsing code
- Cognitive overhead

**Verdict: Not worth it!**

## Summary

| Aspect | Current | Arrays | Winner |
|--------|---------|--------|--------|
| **Lines saved** | 0 | 14 (~10%) | Arrays |
| **Readability** | High | Low | Current |
| **Maintainability** | Easy | Hard | Current |
| **Cognitive load** | Low | High | Current |
| **Field-specific config** | Natural | Requires mapping | Current |
| **Unique features** | Supported | Awkward | Current |
| **Code complexity** | Simple | Complex | Current |

**Recommendation: Keep current structure** ✅
