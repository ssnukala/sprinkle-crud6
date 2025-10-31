# Schema Structure Recommendation - Executive Summary

**Date**: October 31, 2024  
**Issue**: Should CRUD6 switch from per-field attributes to schema-level arrays?

---

## 🎯 TL;DR Recommendation

**Keep the current per-field structure.** It's better for CRUD6's use case.

---

## 📊 Quick Comparison

### UserFrosting 6 Approach (sprinkle-admin)
```yaml
# app/schema/requests/user/create.yaml
user_name:
  validators:
    required: true
    length:
      min: 1
      max: 50
```

- **Purpose**: Request validation only
- **Format**: YAML, multiple files per model (create.yaml, edit-field.yaml, edit-info.yaml)
- **Models**: Hard-coded classes exist (User, Group, Role)
- **Scope**: Validation rules and transformations

### CRUD6 Current Approach
```json
{
  "model": "users",
  "fields": {
    "user_name": {
      "type": "string",
      "label": "Username",
      "sortable": true,
      "filterable": true,
      "listable": true,
      "editable": true,
      "validation": {
        "required": true,
        "length": { "min": 3, "max": 50 }
      }
    }
  }
}
```

- **Purpose**: Complete model definition (database + UI + validation + permissions)
- **Format**: JSON, single file per model
- **Models**: No hard-coded classes - schema is source of truth
- **Scope**: Types, labels, UI behavior, validation, display templates, permissions

---

## 🤔 The Question

Should we move to schema-level arrays to reduce repetition?

```json
{
  "sortable": ["id", "user_name", "email", "created_at"],
  "filterable": ["user_name", "email"],
  "listable": ["id", "user_name", "email"],
  "editable": ["user_name", "email", "password"],
  "fields": {
    "user_name": {
      "type": "string",
      "label": "Username",
      "validation": { ... }
    }
  }
}
```

---

## ✅ Why Keep Current Structure

### 1. Readability - Everything in One Place
**Current:**
```json
"price": {
  "type": "decimal",
  "label": "Price",
  "sortable": true,
  "filterable": true,
  "filter_type": "between",
  "listable": true,
  "field_template": "${price}",
  "validation": { "min": 0 }
}
```
✅ **See everything about "price" at a glance**

**Proposed:**
```json
// At top: "sortable": ["price", ...],
// At top: "filterable": ["price", ...],
// At top: "listable": ["price", ...],
// At top: "filter_types": { "price": "between" },
// In fields:
"price": {
  "type": "decimal",
  "label": "Price",
  "field_template": "${price}",
  "validation": { "min": 0 }
}
```
❌ **Must look in 5+ places to understand "price"**

### 2. Unique Features Work Naturally

CRUD6 has **field_template** - custom Vue/HTML rendering:

```json
"description": {
  "type": "text",
  "listable": true,
  "field_template": "<div class='uk-card uk-card-body'>{{description}}</div>"
}
```

Some fields are listable WITH templates, some WITHOUT. Per-field attributes make this natural.

### 3. Field-Specific Configuration

Many attributes have field-specific nuances:

```json
"price": {
  "filterable": true,
  "filter_type": "between"  // ← Specific to this field's filtering
}
```

With schema-level arrays:
```json
"filterable": ["price"],
"filter_types": { "price": "between" }  // ← Additional mapping
```
**More complex, not less!**

### 4. Different Use Cases

| Aspect | UF6 sprinkle-admin | CRUD6 |
|--------|-------------------|-------|
| Model classes | Hard-coded (User.php, Group.php) | Dynamic (no classes) |
| Schema purpose | Validation only | Complete definition |
| Operations | Multiple files (create/edit/delete) | Single file |
| Format | YAML (validation-optimized) | JSON (API-ready) |
| UI metadata | Not needed | Critical |

### 5. Minimal Savings

- Current: ~139 lines (products.json)
- With arrays: ~125 lines
- **Saved: 10% (14 lines)**
- **Cost: Split definitions, more cognitive load**

Not worth it!

---

## 🔧 Optional Enhancement: Schema-Level Defaults

If you want to reduce repetition WITHOUT losing readability:

```json
{
  "model": "products",
  "defaults": {
    "sortable": true,     // Most fields are sortable
    "filterable": false,  // Most fields not filterable
    "listable": false     // Opt-in for security
  },
  "fields": {
    "name": {
      "type": "string",
      "label": "Product Name",
      "filterable": true,   // Override default
      "listable": true      // Override default
      // sortable: true (inherited from defaults)
    },
    "password": {
      "type": "string",
      "label": "Password",
      "sortable": false,    // Override default
      // filterable: false (inherited)
      // listable: false (inherited)
    }
  }
}
```

**Benefits:**
✅ Reduces repetition for common patterns  
✅ Per-field overrides still visible  
✅ Self-documenting  
✅ Backward compatible  

**This is optional** - current structure works fine!

---

## 📈 Comparison Table

| Criteria | Per-Field (Current) | Schema Arrays | Winner |
|----------|---------------------|---------------|--------|
| **Readability** | All info in one place | Split across sections | ✅ Current |
| **Maintainability** | Change one location | Change multiple locations | ✅ Current |
| **Unique features** | Natural support | Awkward mapping | ✅ Current |
| **Line count** | 139 lines | ~125 lines | Arrays (minor) |
| **Cognitive load** | Low | High | ✅ Current |
| **UF6 alignment** | Different | Similar | Arrays (irrelevant) |
| **Generic CRUD fit** | Perfect | Suboptimal | ✅ Current |

---

## 🎬 Decision

**Keep current per-field structure.**

Reasons:
1. ✅ More readable and maintainable
2. ✅ Better fit for CRUD6's generic model approach
3. ✅ Supports unique features (field_template)
4. ✅ Self-documenting
5. ✅ Single source of truth
6. ✅ Simpler code

UserFrosting 6's approach works for **their** use case, but CRUD6 has **different requirements**.

---

## 📝 Full Analysis

See detailed analysis with code examples, implementation plan, and migration guide:  
**`.archive/SCHEMA_STRUCTURE_ANALYSIS_AND_RECOMMENDATION.md`**

---

## 💭 Your Feedback

Please review and let me know:
1. Do you agree with keeping the current structure?
2. Would you like the optional "schema-level defaults" enhancement implemented?
3. Any other considerations I missed?

**No changes have been made to code** - this is analysis only.
