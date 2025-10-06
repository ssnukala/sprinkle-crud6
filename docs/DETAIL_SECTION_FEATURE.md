# Dynamic Detail Section Feature

## Overview

The dynamic detail section feature allows schemas to declaratively define related data that should be displayed on a record's detail page. This provides a flexible way to show one-to-many relationships without hardcoding component logic.

## Use Cases

1. **Groups and Users**: Display all users belonging to a specific group
2. **Categories and Products**: Show all products in a category
3. **Orders and Items**: List all items in an order
4. **Projects and Tasks**: Display all tasks associated with a project
5. **Any One-to-Many Relationship**: Generic support for any related model

## Configuration

### Schema Structure

Add a `detail` section to your schema JSON file:

```json
{
  "model": "groups",
  "title": "Group Management",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name", "last_name", "flag_enabled"],
    "title": "GROUP.USERS"
  },
  "fields": {
    // ... field definitions
  }
}
```

### Detail Configuration Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `model` | string | Yes | The name of the related model to display |
| `foreign_key` | string | Yes | The foreign key field in the related table that references this model |
| `list_fields` | array | Yes | Array of field names to display in the detail list |
| `title` | string | No | Title for the detail section (supports i18n keys). Defaults to capitalized model name |

## Example Schemas

### Groups with Users

```json
{
  "model": "groups",
  "title": "Group Management",
  "singular_title": "Group",
  "table": "groups",
  "primary_key": "id",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name", "last_name", "flag_enabled"],
    "title": "GROUP.USERS"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID" },
    "name": { "type": "string", "label": "Group Name" }
  }
}
```

### Categories with Products

```json
{
  "model": "categories",
  "title": "Category Management",
  "singular_title": "Category",
  "table": "categories",
  "primary_key": "id",
  "detail": {
    "model": "products",
    "foreign_key": "category_id",
    "list_fields": ["name", "sku", "price", "is_active"],
    "title": "Products in Category"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID" },
    "name": { "type": "string", "label": "Category Name" }
  }
}
```

## Technical Implementation

### Frontend Components

#### Details.vue Component

The generic `Details.vue` component handles all detail sections dynamically:

- **Schema Loading**: Loads the related model's schema to get field information
- **Dynamic Rendering**: Renders fields based on their type (boolean, date, datetime, string, etc.)
- **Data Fetching**: Uses the Sprunje API to fetch related records
- **Field Formatting**: Automatically formats fields based on their type

Props:
- `recordId`: The ID of the parent record
- `parentModel`: The parent model name
- `detailConfig`: The detail configuration from the schema

#### PageRow.vue Integration

The `PageRow.vue` component conditionally renders the detail section:

```vue
<div class="uk-width-2-3" v-if="schema?.detail && $checkAccess('view_crud6_field')">
    <CRUD6Details 
        :recordId="recordId" 
        :parentModel="model" 
        :detailConfig="schema.detail" 
    />
</div>
```

### Backend API

#### SprunjeAction.php

The `SprunjeAction` controller handles detail data requests:

1. Checks for a `relation` parameter in the route
2. Validates that the relation matches the schema's detail configuration
3. Applies the foreign key constraint from the detail config
4. Returns filtered data for the related model

Route: `GET /api/crud6/{model}/{id}/{relation}`

Example: `GET /api/crud6/groups/1/users`

### TypeScript Interfaces

```typescript
export interface DetailConfig {
    model: string
    foreign_key: string
    list_fields: string[]
    title?: string
}

export interface CRUD6Schema {
    model: string
    title: string
    table: string
    primary_key: string
    fields: Record<string, SchemaField>
    detail?: DetailConfig  // Optional detail configuration
}
```

## Field Type Support

The detail component automatically handles various field types:

| Field Type | Display Format |
|------------|----------------|
| `boolean` | Badge with ENABLED/DISABLED label |
| `date` | Formatted date (locale-specific) |
| `datetime` | Formatted date and time (locale-specific) |
| `string` | Plain text |
| `integer` | Plain text |
| `decimal` | Plain text |
| `text` | Plain text |

## Backward Compatibility

This feature is fully backward compatible:

- **No Detail Section**: Models without a `detail` configuration work as before
- **Replaced Component**: The old hardcoded `Users.vue` component has been replaced by the generic `Details.vue` component
- **Optional Feature**: Detail section only renders when configured in the schema
- **No Breaking Changes**: Existing functionality is preserved

## Permissions

Detail sections respect UserFrosting's permission system:

- Detail section only shows when user has `view_crud6_field` permission
- Related data respects the permissions of the related model
- Access control follows UserFrosting 6 authorization patterns

## Benefits

1. **Declarative Configuration**: Define relationships in schema, not code
2. **Reusable Component**: Single component handles all detail sections
3. **Type-Safe**: TypeScript interfaces ensure proper configuration
4. **Maintainable**: Changes to relationships only require schema updates
5. **Consistent UX**: All detail sections follow the same pattern
6. **Flexible**: Supports any one-to-many relationship

## Limitations

1. **One-to-Many Only**: Currently supports only one-to-many relationships
2. **Single Detail Section**: Each model can have only one detail section
3. **Users Model Special Case**: The UserSprunje is used for users relations; other models use the generic CRUD6Sprunje

## Future Enhancements

Potential improvements for future versions:

1. **Multiple Detail Sections**: Support for multiple related models
2. **Many-to-Many Support**: Handle many-to-many relationships
3. **Custom Sprunje**: Allow specifying custom Sprunje classes for relations
4. **Field Customization**: Support custom field rendering and formatting
5. **Action Buttons**: Add create/edit/delete actions for related records
6. **Inline Editing**: Enable editing related records without navigation

## Example Use Case: Groups and Users

When viewing a group detail page:

1. User navigates to `/groups/1` (group with ID 1)
2. `PageRow.vue` loads the group schema
3. Schema contains detail configuration for users
4. `Details.vue` component renders with users list
5. Frontend calls `/api/crud6/groups/1/users`
6. Backend filters users where `group_id = 1`
7. Users table displays with configured fields
8. Users can sort, search, and paginate the list

## Testing

To test the detail section feature:

1. Create or update a schema with a `detail` configuration
2. Ensure the related model has a schema defined
3. Verify the foreign key exists in the database
4. Navigate to a record's detail page
5. Confirm the detail section displays with related records
6. Test sorting, searching, and pagination
7. Verify field types render correctly

## Migration Guide

To add detail sections to existing schemas:

1. Identify one-to-many relationships in your data model
2. Add the `detail` section to the parent model's schema
3. Specify the related model, foreign key, and fields to display
4. Test the detail section on the record detail page
5. Update translations for custom titles if needed

No code changes are required for basic detail sections!
