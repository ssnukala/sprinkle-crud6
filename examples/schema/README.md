# CRUD6 Schema Examples

This directory contains example JSON schema files for the CRUD6 sprinkle.

> **Note on Comments in Examples**: Some example schemas include a `"comment"` field for documentation purposes. This is not part of the official schema specification and should be removed for production use. Standard JSON does not support comments, so these are included only to explain example features inline.

## Schema Files

### Local Examples
These are example schemas demonstrating CRUD6 features:

- `products.json` - Product catalog with various field types and features
- `products-1column.json` - Product form with 1-column layout
- `products-2column.json` - Product form with 2-column layout (default)
- `products-3column.json` - Product form with 3-column layout
- `products-optimized.json` - Optimized product schema with smart defaults
- `products-template-file.json` - Product schema using external template file
- `products-vue-template.json` - Product schema with inline Vue template
- `products-unified-modal.json` - Product schema with unified modal system
- `categories.json` - Product categories
- `product_categories.json` - Many-to-many relationship between products and categories
- `orders.json` - Order management
- `order_details.json` - Order line items
- `activities.json` - Activity tracking
- `users.json` - User management with roles, permissions, and activities relationships
- `groups.json` - User groups
- `roles.json` - User roles with permissions relationship
- `permissions.json` - Permission management
- `contacts.json` - Contact management with boolean-yn field
- `field-template-example.json` - Field template demonstration
- `smartlookup-example.json` - Smart lookup field example
- `smartlookup-legacy-example.json` - Legacy smart lookup pattern for reference

### Relationship Examples
The following schemas demonstrate working relationships that match UserFrosting 6 database structure:

- `users.json` - Demonstrates:
  - Many-to-many with roles
  - Belongs-to-many-through with permissions (via roles)
  - One-to-many with activities
- `roles.json` - Many-to-many with permissions
- `groups.json` - Example group relationships

## Modal Button Configurations

CRUD6 supports schema-driven modal button combinations. You can configure buttons using presets or custom configurations.

### Smart Defaults

Button combinations are automatically determined based on modal type:

| Modal Type | Default Buttons | Use Case |
|------------|-----------------|----------|
| `confirm` | Yes / No | Confirmation dialogs |
| `input` | Cancel / Save | Field input forms |
| `form` | Cancel / Save | Full CRUD forms |
| `message` | Cancel / OK | Information display |

This means you often don't need to specify buttons at all:

```json
{
  "actions": [
    {
      "key": "reset_password",
      "type": "api_call",
      "confirm": "Send password reset email?",
      "modal_config": {
        "type": "confirm"
      }
    },
    {
      "key": "change_password",
      "type": "field_update",
      "field": "password",
      "modal_config": {
        "type": "input",
        "fields": ["password"]
      }
    }
  ]
}
```

### Button Presets

If you need to override the defaults, use these preset strings in `modal_config.buttons`:

| Preset | Buttons | Use Case |
|--------|---------|----------|
| `yes_no` | No / Yes | Simple confirmations |
| `save_cancel` | Cancel / Save | Form submissions |
| `ok_cancel` | Cancel / OK | Acknowledgments |
| `confirm_cancel` | Cancel / [Action Label] | Uses action's label |

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
        "buttons": "ok_cancel"
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

For password changes, the modal automatically shows a confirmation field when the field has `validation.match: true`:

```json
{
  "actions": [
    {
      "key": "change_password",
      "type": "field_update",
      "label": "Change Password",
      "icon": "key",
      "field": "password",
      "style": "warning",
      "confirm": "Enter a new password for <strong>{{user_name}}</strong>.",
      "modal_config": {
        "type": "input",
        "fields": ["password"]
      }
    }
  ]
}
```

## Conditional Action Visibility

Actions can be conditionally shown or hidden based on the current record's field values using the `visible_when` property.

### Basic Usage

The `visible_when` property is an object where keys are field names and values are the expected values. The action is only visible when ALL conditions are met.

```json
{
  "actions": [
    {
      "key": "disable_user",
      "label": "Disable User",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "style": "danger",
      "confirm": "Are you sure you want to disable this user?",
      "visible_when": {
        "flag_enabled": true
      }
    },
    {
      "key": "enable_user",
      "label": "Enable User",
      "type": "field_update",
      "field": "flag_enabled",
      "value": true,
      "style": "primary",
      "confirm": "Are you sure you want to enable this user?",
      "visible_when": {
        "flag_enabled": false
      }
    }
  ]
}
```

### Multiple Conditions

You can specify multiple conditions. All must be true for the action to be visible:

```json
{
  "visible_when": {
    "status": "pending",
    "is_verified": true
  }
}
```

### Supported Comparisons

| Field Type | Comparison | Example |
|------------|------------|---------|
| Boolean | Exact match | `"flag_enabled": true` |
| String | Exact match | `"status": "active"` |
| Number | Exact match | `"priority": 1` |
| Null | Null check | `"deleted_at": null` |

**Note:** Boolean comparisons handle truthy/falsy values (0, 1, "0", "1", etc.) correctly.

### Use Cases

| Pattern | Use Case |
|---------|----------|
| Show only when enabled | `"visible_when": { "flag_enabled": true }` |
| Show only when disabled | `"visible_when": { "flag_enabled": false }` |
| Show only for pending items | `"visible_when": { "status": "pending" }` |
| Show only for verified users | `"visible_when": { "flag_verified": true }` |

This feature helps reduce UI clutter by showing only relevant actions for the current record state.

## Boolean Field Types

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

The schemas in this directory demonstrate working relationships that match UserFrosting 6 database structure:

- `users.json` shows how to configure:
  - Many-to-many with roles (via `role_users` pivot table)
  - Belongs-to-many-through with permissions (via roles → `permission_roles`)
  - One-to-many with activities (user has many activities)
- `roles.json` shows:
  - Many-to-many with permissions (via `permission_roles` pivot table)
- `groups.json` shows group relationships

See the corresponding migration files in `examples/Migrations/` for the database schema these JSON schemas reference.

## Breadcrumb and Page Title Configuration

### `title_field` Attribute

The `title_field` attribute controls which field is displayed in breadcrumbs and page titles when viewing individual records. This is essential for displaying meaningful identifiers instead of database IDs.

#### Default Behavior

Without `title_field`, the system will display the record's ID (primary key).

#### Example Usage

For a users model accessed via `/crud6/users/8`, instead of showing "8" in the breadcrumb, configure it to show the username:

```json
{
  "model": "users",
  "title_field": "user_name",
  "fields": {
    "id": { "type": "integer", "auto_increment": true },
    "user_name": { "type": "string", "label": "Username" }
  }
}
```

Breadcrumb will show: **Home** > **Users** > **john_doe** (instead of "8")

#### Schema Examples with `title_field`

The example schemas in this directory demonstrate different use cases:

- **users.json**: `"title_field": "user_name"` - Shows username in breadcrumb
- **products.json**: `"title_field": "name"` - Shows product name
- **orders.json**: `"title_field": "order_number"` - Shows order number (more meaningful than ID)
- **contacts.json**: `"title_field": "last_name"` - Shows last name
- **groups.json**: `"title_field": "name"` - Shows group name
- **categories.json**: `"title_field": "name"` - Shows category name
- **roles.json**: `"title_field": "name"` - Shows role name

#### Common Patterns

| Model Type | Recommended `title_field` | Why |
|------------|--------------------------|-----|
| Users | `user_name` or `email` | Human-readable identifier |
| Products | `name` or `sku` | Product identifier |
| Orders | `order_number` | Business identifier |
| Categories | `name` | Descriptive name |
| Invoices | `invoice_number` | Business identifier |
| Contacts | `last_name` or `email` | Person identifier |

#### When to Use

Configure `title_field` when:
- Your model uses numeric IDs but has a human-readable identifier field
- You want breadcrumbs to be more descriptive
- You're using business identifiers (order numbers, SKUs, etc.)

#### When to Skip

You can omit `title_field` when:
- Displaying the record ID is acceptable for your use case
