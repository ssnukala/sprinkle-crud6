# Context-Specific Field Visibility

## Overview

CRUD6 now supports granular control over field visibility across different contexts: **create**, **edit**, **list**, and **detail** views. This addresses common requirements like showing password fields only in create/edit forms but never in list or detail views.

## Supported Contexts

| Context | Description | Where It Appears | Editable? |
|---------|-------------|------------------|-----------|
| `list` | Table/list view | Main table/grid showing all records | No - display only |
| `create` | Create form | Form for adding new records | Yes - input fields |
| `edit` | Edit form | Form for modifying existing records | Yes - input fields |
| `detail` | Detail/view page | Single record view page (read-only) | No - display only |

### Understanding `detail` Context

The **`detail`** context refers to the **read-only view page** that displays a single record's information. This is distinct from the edit form:

**Example User Journey**:
1. User clicks on a row in the **list** table → navigates to **detail** page
2. On the **detail** page, user sees all field values (read-only display)
3. User clicks "Edit" button → navigates to **edit** form
4. On the **edit** form, user can modify field values

**Visual Example**:

```
┌─────────────────────────────────────┐
│ Users List (list context)          │
├─────────────────────────────────────┤
│ Username  | Email       | Status   │
│ john      | john@ex.com | Active   │  ← Click to view details
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ User Details (detail context)      │  ← Read-only display
├─────────────────────────────────────┤
│ Username:    john                   │
│ Email:       john@example.com       │
│ First Name:  John                   │
│ Last Name:   Doe                    │
│ Status:      Active                 │
│ Created:     2024-01-15             │
│                                     │
│ [Edit] [Delete]                     │
└─────────────────────────────────────┘
                ↓ Click Edit
┌─────────────────────────────────────┐
│ Edit User (edit context)           │  ← Editable form
├─────────────────────────────────────┤
│ Username:    [john          ]       │
│ Email:       [john@example.com]     │
│ First Name:  [John          ]       │
│ Last Name:   [Doe           ]       │
│ Status:      [✓] Active             │
│                                     │
│ [Save] [Cancel]                     │
└─────────────────────────────────────┘
```

## Usage

### Basic Example

```json
{
  "password": {
    "type": "password",
    "label": "Password",
    "show_in": ["create", "edit"]
  }
}
```

This password field will:
- ✅ Appear in create forms (input field)
- ✅ Appear in edit forms (input field)
- ❌ Never appear in list views (security)
- ❌ Never appear in detail views (security - passwords should never be displayed)

### Full Visibility Example

```json
{
  "name": {
    "type": "string",
    "label": "Full Name",
    "show_in": ["list", "create", "edit", "detail"]
  }
}
```

This name field will:
- ✅ Appear in list table as a column
- ✅ Appear in create form as an input field
- ✅ Appear in edit form as an input field
- ✅ Appear in detail page as a read-only display

### Shorthand: `form` Context

For convenience, `"form"` is a shorthand that expands to both `"create"` and `"edit"`:

```json
{
  "name": {
    "type": "string",
    "show_in": ["list", "form", "detail"]
  }
}
```

Equivalent to:
```json
{
  "name": {
    "type": "string",
    "show_in": ["list", "create", "edit", "detail"]
  }
}
```

## Context Comparison Table

Here's how different `show_in` combinations affect field visibility:

| `show_in` Value | List Table | Create Form | Edit Form | Detail Page |
|----------------|------------|-------------|-----------|-------------|
| `["list"]` | ✅ Show | ❌ Hide | ❌ Hide | ❌ Hide |
| `["create"]` | ❌ Hide | ✅ Show | ❌ Hide | ❌ Hide |
| `["edit"]` | ❌ Hide | ❌ Hide | ✅ Show | ❌ Hide |
| `["detail"]` | ❌ Hide | ❌ Hide | ❌ Hide | ✅ Show |
| `["form"]` | ❌ Hide | ✅ Show | ✅ Show | ❌ Hide |
| `["list", "detail"]` | ✅ Show | ❌ Hide | ❌ Hide | ✅ Show |
| `["create", "edit"]` | ❌ Hide | ✅ Show | ✅ Show | ❌ Hide |
| `["list", "form", "detail"]` | ✅ Show | ✅ Show | ✅ Show | ✅ Show |
| `["list", "create", "detail"]` | ✅ Show | ✅ Show | ❌ Hide | ✅ Show |
| `["list", "edit", "detail"]` | ✅ Show | ❌ Hide | ✅ Show | ✅ Show |

**Common Use Cases**:

```json
// Standard field: everywhere
"name": {"show_in": ["list", "form", "detail"]}

// Password: forms only, never display
"password": {"show_in": ["create", "edit"]}

// System field: view only
"created_at": {"show_in": ["detail"]}

// Create-only: set once, view later
"api_token": {"show_in": ["create", "detail"]}

// Edit-only: not needed on create
"is_active": {"show_in": ["list", "edit", "detail"]}

// Summary field: list and view only
"record_count": {"show_in": ["list", "detail"]}
```

## Common Patterns

### 1. Password Fields

**Requirement**: Capture password on create/edit, never display

```json
{
  "password": {
    "type": "password",
    "label": "Password",
    "show_in": ["create", "edit"],
    "validation": {
      "length": {"min": 8}
    }
  }
}
```

**Auto-Handling**: Password fields automatically default to `["create", "edit"]` even without explicit `show_in`.

### 2. Read-Only System Fields

**Requirement**: Display in detail view only

```json
{
  "created_at": {
    "type": "datetime",
    "label": "Created At",
    "show_in": ["detail"]
  },
  "last_login_at": {
    "type": "datetime",
    "label": "Last Login",
    "show_in": ["detail"]
  }
}
```

### 3. Create-Only Fields

**Requirement**: Set during creation, cannot be modified later

```json
{
  "api_token": {
    "type": "string",
    "label": "API Token",
    "show_in": ["create", "detail"],
    "description": "Generated on creation, cannot be edited"
  }
}
```

### 4. Edit-Only Fields

**Requirement**: Not shown on creation (uses default), editable later

```json
{
  "is_active": {
    "type": "boolean",
    "label": "Active",
    "default": true,
    "show_in": ["list", "edit", "detail"],
    "description": "Uses default value on creation"
  }
}
```

### 5. List Summary Fields

**Requirement**: Show in list for quick overview, but not in forms

```json
{
  "full_name": {
    "type": "string",
    "label": "Full Name",
    "show_in": ["list"],
    "description": "Computed field - list display only"
  }
}
```

## Complete Example

See `examples/schema/users-context-visibility.json` for a full demonstration:

```json
{
  "model": "users",
  "fields": {
    "id": {
      "type": "integer",
      "show_in": ["detail"]
    },
    "user_name": {
      "type": "string",
      "show_in": ["list", "create", "edit", "detail"]
    },
    "password": {
      "type": "password",
      "show_in": ["create", "edit"]
    },
    "api_token": {
      "type": "string",
      "show_in": ["create", "detail"]
    },
    "is_active": {
      "type": "boolean",
      "show_in": ["list", "edit", "detail"]
    },
    "created_at": {
      "type": "datetime",
      "show_in": ["detail"]
    }
  }
}
```

## Backward Compatibility

### Legacy Flags Still Work

Old schemas using `editable`, `viewable`, `listable` continue to work:

```json
{
  "name": {
    "type": "string",
    "editable": true,
    "viewable": true,
    "listable": true
  }
}
```

This is automatically normalized to:
```json
{
  "name": {
    "type": "string",
    "show_in": ["list", "create", "edit", "detail"]
  }
}
```

### Conversion Rules

| Legacy Flags | Normalized `show_in` |
|--------------|----------------------|
| `listable: true` | `["list"]` |
| `editable: true, readonly: false` | `["create", "edit"]` |
| `viewable: true` | `["detail"]` |

## API Context Parameters

When requesting schemas via API, specify context(s):

```
GET /api/crud6/users/schema?context=create
GET /api/crud6/users/schema?context=edit
GET /api/crud6/users/schema?context=create,edit
GET /api/crud6/users/schema?context=list,detail
```

**Multi-context requests** return a combined schema with separate sections:

```json
{
  "model": "users",
  "contexts": {
    "create": {
      "fields": {
        "password": {...}
      }
    },
    "edit": {
      "fields": {
        "is_active": {...}
      }
    }
  }
}
```

## Implementation Details

### Backend Processing

**SchemaService.php** `normalizeVisibilityFlags()`:

1. Detects `show_in` array
2. Expands `"form"` → `["create", "edit"]`
3. Special handling for password fields (auto-hide from detail/list)
4. Maintains legacy flag compatibility

**SchemaService.php** `getContextSpecificData()`:

- Supports: `list`, `create`, `edit`, `detail`, `form` (backward compatible)
- Returns only fields visible in the requested context
- Handles field-specific attributes (validation, placeholders, etc.)

### Security Considerations

**Password Fields**:
- Automatically excluded from `detail` and `list` contexts
- Never returned in API responses for these contexts
- Only appear in create/edit forms

**Sensitive Data**:
- Use `show_in: ["detail"]` for admin-only fields
- Combine with permissions for additional security

## Migration Guide

### Updating Existing Schemas

**Before** (old approach):
```json
{
  "password": {
    "type": "password",
    "editable": true,
    "viewable": false,
    "listable": false
  }
}
```

**After** (new approach):
```json
{
  "password": {
    "type": "password",
    "show_in": ["create", "edit"]
  }
}
```

**Note**: Both work! The new approach is clearer and more concise.

### Best Practices

1. **Be Explicit**: Always specify `show_in` for sensitive fields
2. **Use Shortcuts**: `"form"` is convenient for most editable fields
3. **Security First**: Never include passwords in `list` or `detail`
4. **Test Contexts**: Verify each context renders correctly
5. **Document Intent**: Add `description` to explain context choices

## Additional Optimizations

Based on the user's question about "what other optimizations can we make?", here are additional recommendations:

### 1. Conditional Visibility

Future enhancement: Support conditional visibility based on other field values

```json
{
  "billing_address": {
    "type": "text",
    "show_in": ["create", "edit"],
    "show_when": {
      "field": "needs_billing",
      "equals": true
    }
  }
}
```

### 2. Role-Based Visibility

Future enhancement: Show fields based on user permissions

```json
{
  "internal_notes": {
    "type": "text",
    "show_in": ["detail", "edit"],
    "show_for_roles": ["admin", "manager"]
  }
}
```

### 3. Computed Fields

Future enhancement: Mark fields as computed/virtual

```json
{
  "full_name": {
    "type": "string",
    "computed": true,
    "formula": "concat(first_name, ' ', last_name)",
    "show_in": ["list", "detail"]
  }
}
```

### 4. Field Groups/Tabs

Future enhancement: Organize fields into groups for better UX

```json
{
  "fields": {
    "user_name": {"group": "basic"},
    "password": {"group": "security"},
    "api_token": {"group": "security"}
  },
  "field_groups": [
    {
      "id": "basic",
      "label": "Basic Information",
      "order": 1
    },
    {
      "id": "security",
      "label": "Security Settings",
      "order": 2
    }
  ]
}
```

## See Also

- [SMARTLOOKUP_NESTED_LOOKUP.md](SMARTLOOKUP_NESTED_LOOKUP.md) - Nested lookup objects
- [ORM_ALIGNMENT_ANALYSIS.md](../.archive/ORM_ALIGNMENT_ANALYSIS.md) - ORM patterns
- [SCHEMA_OPTIMIZATION_ANALYSIS.md](../.archive/SCHEMA_OPTIMIZATION_ANALYSIS.md) - Full analysis
- `examples/schema/users-context-visibility.json` - Complete example
