# Frontend Usage Examples

## CRUD6 Frontend Vue Components

The CRUD6 sprinkle provides generic Vue.js components for managing any model defined in JSON schema.

### Routes

The frontend routes follow this pattern:

- `/crud6/{model}` - List all records for the model
- `/crud6/{model}/{id}` - View/edit a specific record

### Examples

#### Viewing Groups
- List all groups: `/crud6/groups`
- View specific group: `/crud6/groups/1` or `/crud6/groups/admin-slug`

#### Viewing Products  
- List all products: `/crud6/products`
- View specific product: `/crud6/products/123`

#### Viewing Users
- List all users: `/crud6/users`
- View specific user: `/crud6/users/456`

### Components

#### PageCRUD6s.vue
Generic list component that:
- Displays data in a responsive table
- Shows loading states
- Handles errors gracefully
- Provides navigation to detail view
- Supports any model type via route parameters

#### PageCRUD6.vue  
Generic detail/edit component that:
- Displays record details in read-only mode
- Switches to edit mode with form fields
- Handles field types dynamically
- Provides save/cancel functionality
- Works with any model schema

### API Integration

The components automatically use the correct API endpoints:
- `GET /api/crud6/{model}` - List records
- `GET /api/crud6/{model}/{id}` - Get single record
- `PUT /api/crud6/{model}/{id}` - Update record
- `DELETE /api/crud6/{model}/{id}` - Delete record

### Customization

The components are designed to work with any model but can be extended:

1. **Column Configuration**: The list component uses default columns but can be enhanced to load schema-specific columns
2. **Field Types**: The detail component infers field types but can be enhanced with schema-based validation
3. **Permissions**: Both components respect the `uri_crud6` permission but can be made model-specific
4. **Styling**: Components use Bootstrap classes but can be customized

### Schema Integration

For full functionality, ensure your model schemas are defined in:
- `app/schema/crud6/{model}.json`

The frontend will automatically adapt to the schema structure for optimal display and editing.