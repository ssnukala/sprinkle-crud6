# CRUD6 Schema Examples

This directory contains example JSON schema files for the CRUD6 sprinkle.

## Schema Files

### Local Examples
These are example schemas demonstrating CRUD6 features:

- `products.json` - Product catalog with various field types
- `categories.json` - Product categories
- `product_categories.json` - Many-to-many relationship between products and categories
- `orders.json` - Order management
- `order_details.json` - Order line items
- `analytics.json` - Analytics tracking
- `users.json` - Basic user schema
- `users-extended.json` - Extended user schema with details and actions
- `users-boolean-test.json` - **NEW** Demonstrates all three boolean field types
- `contacts.json` - Contact management with boolean-yn field
- `field-template-example.json` - Field template demonstration
- `smartlookup-example.json` - Smart lookup field example
- `products-template-file.json` - Product schema with template file
- `products-vue-template.json` - Product schema with Vue template

### C6Admin Schemas (from sprinkle-c6admin)
These schemas are from the sprinkle-c6admin project and demonstrate integration with UserFrosting 6 account tables:

- `c6admin-users.json` - User management with roles and permissions relationships
- `c6admin-groups.json` - User groups
- `c6admin-roles.json` - User roles with permissions relationship
- `c6admin-permissions.json` - Permission management
- `c6admin-activities.json` - User activity log

## Modal Button Configurations

CRUD6 supports schema-driven modal button combinations. You can configure buttons using presets or custom configurations.

### Button Presets

Use these preset strings in `modal_config.buttons`:

| Preset | Buttons | Use Case |
|--------|---------|----------|
| `yes_no` | No / Yes | Simple confirmations |
| `save_cancel` | Cancel / Save | Form submissions |
| `ok_cancel` | Cancel / OK | Acknowledgments |
| `confirm_cancel` | Cancel / [Action Label] | Default, uses action's label |

### Preset Example

```json
{
  "actions": [
    {
      "key": "archive_record",
      "type": "api_call",
      "label": "Archive",
      "icon": "box-archive",
      "style": "warning",
      "confirm": "Are you sure you want to archive {{name}}?",
      "modal_config": {
        "type": "confirm",
        "buttons": "yes_no"
      }
    }
  ]
}
```

### Custom Buttons

For full control, provide an array of `ModalButtonConfig` objects:

```json
{
  "actions": [
    {
      "key": "submit_for_review",
      "type": "api_call",
      "label": "Submit for Review",
      "confirm": "This will send {{name}} for approval. Continue?",
      "modal_config": {
        "type": "confirm",
        "buttons": [
          {
            "label": "Not Now",
            "icon": "clock",
            "style": "default",
            "action": "cancel",
            "closeModal": true
          },
          {
            "label": "Submit",
            "icon": "paper-plane",
            "style": "primary",
            "action": "confirm",
            "closeModal": true
          }
        ]
      }
    }
  ]
}
```

### Button Properties

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | Button text (translation key or plain text) |
| `icon` | string | FontAwesome icon name |
| `style` | string | `primary`, `secondary`, `danger`, `warning`, `default` |
| `action` | string | `confirm`, `cancel`, `submit`, `close` |
| `closeModal` | boolean | Close modal after action |

### Modal Types

The `modal_config.type` determines what content is rendered:

| Type | Description |
|------|-------------|
| `confirm` | Message only with confirmation buttons |
| `input` | Single or multiple input fields |
| `form` | Full CRUD6 form (planned) |
| `message` | Information display only |

### Input Modal Example

```json
{
  "actions": [
    {
      "key": "update_password",
      "type": "field_update",
      "label": "Change Password",
      "field": "password",
      "confirm": "Enter a new password for {{user_name}}",
      "modal_config": {
        "type": "input",
        "fields": ["password"],
        "buttons": "save_cancel"
      }
    }
  ]
}
```

## Boolean Field Types

CRUD6 supports three different boolean field rendering options:

CRUD6 supports three different boolean field rendering options:

### 1. Standard Checkbox - `type: "boolean"`

Traditional checkbox with label next to it.

```json
{
  "is_admin": {
    "type": "boolean",
    "label": "Is Admin",
    "default": false,
    "editable": true
  }
}
```

**Use when:** Standard yes/no fields, space is limited, multiple boolean options in a row

### 2. Toggle Switch - `type: "boolean-tgl"` or `type: "boolean-toggle"`

Modern toggle switch with "Enabled/Disabled" label.

```json
{
  "flag_verified": {
    "type": "boolean-tgl",
    "label": "Verified",
    "description": "Toggle switch for email verification status",
    "default": true,
    "editable": true
  }
}
```

**Use when:** Binary state toggle (on/off, enabled/disabled), status flags, modern UI appearance desired

**Examples:** `flag_enabled`, `flag_verified`, `is_published`, `is_active`

### 3. Yes/No Dropdown - `type: "boolean-yn"`

Dropdown select with "Yes" and "No" options.

```json
{
  "accepts_marketing": {
    "type": "boolean-yn",
    "label": "Accepts Marketing",
    "description": "Yes/No dropdown for marketing consent",
    "default": false,
    "editable": true
  }
}
```

**Use when:** Explicit confirmation required, legal/compliance fields (GDPR consent), accessibility is critical

**Examples:** `accepts_marketing`, `terms_accepted`, `gdpr_consent`, `newsletter_opt_in`

See `users-boolean-test.json` for a complete example demonstrating all three types.

## Schema Structure

Each schema file defines:

- **model**: The model name (used in API routes)
- **table**: The database table name
- **fields**: Field definitions with types, validation, and UI properties
- **relationships**: Many-to-many and belongs-to-many-through relationships
- **details**: Related models to display in detail views
- **actions**: Custom actions available for the model
- **permissions**: Read/create/update/delete permissions

## Relationship Types

CRUD6 supports:

1. **Many-to-many** (`many_to_many`): Direct relationship via pivot table
   - Example: users ↔ roles via `role_users`

2. **Belongs-to-many-through** (`belongs_to_many_through`): Nested many-to-many
   - Example: users → roles → permissions
   - Goes through two pivot tables: `role_users` and `permission_roles`

For automatic pivot table management (like assigning default roles on user creation), see:
- `.archive/RELATIONSHIP_PIVOT_ACTIONS_PROPOSAL.md` - Future schema-based approach
- `.archive/MANUAL_PIVOT_MANAGEMENT_GUIDE.md` - Current manual implementation

## Testing Relationships

The c6admin schemas demonstrate working relationships that match UserFrosting 6 database structure:

- `c6admin-users.json` shows how to configure:
  - Many-to-many with roles
  - Belongs-to-many-through with permissions (via roles)
  - One-to-many with activities

See the corresponding migration files in `examples/Migrations/` for the database schema these JSON schemas reference.
