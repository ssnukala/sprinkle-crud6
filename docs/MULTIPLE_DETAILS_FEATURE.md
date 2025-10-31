# Multiple Detail Sections Feature

## Overview

The Multiple Detail Sections feature extends CRUD6's relationship display capabilities by allowing you to show multiple related data tables on a single detail/row page. This is particularly useful for models with multiple one-to-many relationships, such as a User with Activities, Roles, and Permissions.

## Features

- **Multiple Relationships**: Display multiple related tables on one page
- **Schema-Driven**: Configure all relationships in your JSON schema
- **Backward Compatible**: Existing `detail` (singular) configuration still works
- **Flexible Layout**: Each detail section renders independently
- **Permission-Aware**: Respects view permissions
- **Automatic Loading**: Related data loads automatically via API

## Configuration

### Single Detail Section (Legacy Format)

The existing `detail` property continues to work for backward compatibility:

```json
{
  "model": "groups",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "flag_enabled"],
    "title": "GROUP.USERS"
  }
}
```

### Multiple Detail Sections (New Format)

Use the `details` array to configure multiple relationships:

```json
{
  "model": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "USER.ROLES"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "USER.PERMISSIONS"
    }
  ]
}
```

## Detail Configuration Properties

Each detail section configuration supports these properties:

- **`model`** (required, string): Name of the related model
- **`foreign_key`** (required, string): Foreign key field in the related table
- **`list_fields`** (required, array): Array of field names to display in the table
- **`title`** (optional, string): Title/heading for the detail section (supports i18n keys)

## Complete Example

Here's a comprehensive example for a User model with multiple detail sections:

```json
{
  "model": "users",
  "title": "User Management",
  "singular_title": "User",
  "description": "Manage system users with enhanced features",
  "table": "users",
  "permissions": {
    "read": "uri_users",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  },
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "USER.ROLES"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "USER.PERMISSIONS"
    }
  ],
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "listable": true
    },
    "user_name": {
      "type": "string",
      "label": "Username",
      "listable": true
    },
    "email": {
      "type": "string",
      "label": "Email",
      "listable": true
    }
  }
}
```

## How It Works

### Backend API

The backend automatically handles relationship queries through the existing CRUD6 API endpoints:

```
GET /api/crud6/{parentModel}/{parentId}/{relatedModel}
```

For example:
```
GET /api/crud6/users/5/activities
GET /api/crud6/users/5/roles
GET /api/crud6/users/5/permissions
```

### Frontend Rendering

The `PageRow.vue` component:
1. Loads the schema with detail configurations
2. Converts single `detail` to array format for consistency
3. Renders each detail section using the `CRUD6Details` component
4. Each detail section operates independently

### Component Structure

```vue
<CRUD6Details 
  v-for="(detailConfig, index) in detailConfigs"
  :key="`detail-${index}-${detailConfig.model}`"
  :recordId="recordId" 
  :parentModel="model" 
  :detailConfig="detailConfig"
/>
```

## Backward Compatibility

The implementation is fully backward compatible:

1. **Single `detail` Object**: Automatically converted to single-item array
2. **No Breaking Changes**: Existing schemas continue to work without modification
3. **Graceful Degradation**: Pages without detail sections render normally

## Layout and Styling

- Detail sections stack vertically
- Each section is wrapped in a `UFCardBox` component
- Default UK width is `uk-width-2-3` (two-thirds of the container)
- Bottom margin applied between sections for spacing

## Field Display

Each detail section displays:
- Column headers based on field labels from the related model's schema
- Data formatted according to field types (boolean, date, datetime, etc.)
- Sortable columns (if configured in the related model's schema)
- Data tables with pagination and filtering

## Use Cases

### User Management

Display a user's:
- Recent activities/login history
- Assigned roles
- Permissions
- Group memberships

### Product Management

Display a product's:
- Price history
- Inventory transactions
- Reviews/ratings
- Related products

### Order Management

Display an order's:
- Line items
- Payment history
- Shipment tracking
- Customer communications

## TypeScript Interface

```typescript
export interface DetailConfig {
  model: string
  foreign_key: string
  list_fields: string[]
  title?: string
}

export interface CRUD6Schema {
  // ... other properties
  detail?: DetailConfig        // Single detail (legacy)
  details?: DetailConfig[]     // Multiple details (new)
}
```

## Migration Guide

### From Single to Multiple Details

**Before (single detail):**
```json
{
  "model": "users",
  "detail": {
    "model": "activities",
    "foreign_key": "user_id",
    "list_fields": ["type", "message", "created_at"],
    "title": "USER.ACTIVITIES"
  }
}
```

**After (multiple details):**
```json
{
  "model": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug"],
      "title": "USER.ROLES"
    }
  ]
}
```

## Best Practices

1. **Limit Detail Sections**: Don't overwhelm users - 3-4 sections maximum recommended
2. **Order by Importance**: Place most important relationships first
3. **Use Clear Titles**: Provide descriptive titles for each section
4. **Choose Relevant Fields**: Only display fields that make sense in list view
5. **Consider Performance**: Each detail section makes a separate API call
6. **Localize Titles**: Use i18n keys for multilingual support

## Performance Considerations

- Each detail section triggers a separate API call
- Detail sections load independently (non-blocking)
- Consider pagination for large datasets
- Use field selection to reduce data transfer

## Related Features

- **Custom Actions**: Add action buttons alongside detail sections
- **Detail Editable**: Edit detail records inline (master-detail feature)
- **Field Templates**: Customize how fields display in detail tables

## Example Files

See the following example schemas:
- `examples/users-extended.json` - User with multiple details and actions
- `examples/categories.json` - Category with single detail (products)
- `examples/orders.json` - Order with editable detail (order items)

## See Also

- [Custom Actions Feature](CUSTOM_ACTIONS_FEATURE.md) - Add custom action buttons
- [Detail Section Feature](DETAIL_SECTION_FEATURE.md) - Original single detail documentation
- [Master-Detail Feature](PAGE_MASTER_DETAIL.md) - Editable detail records
- [Schema API Quick Reference](SCHEMA_API_QUICK_REFERENCE.md) - Complete schema reference
