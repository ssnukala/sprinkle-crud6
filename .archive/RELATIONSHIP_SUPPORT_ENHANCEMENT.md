# Enhanced Relationship Support in CRUD6

## Overview

The CRUD6 SprunjeAction has been enhanced to support dynamic relationships for any model, not just hardcoded 'users' relationships. This enables full one-to-many relationship display across all CRUD6 models.

## Features

### Dynamic Relationship Queries

The `SprunjeAction` now automatically handles relationships defined in schema's `detail` configuration:

```json
{
  "model": "groups",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name"],
    "title": "GROUP.USERS"
  }
}
```

When accessing `/api/crud6/groups/5/users`:
1. Loads the `users` schema to get field configuration
2. Filters users by `group_id = 5`
3. Returns paginated results with sortable/filterable columns

### Schema-Driven Configuration

The relationship sprunje automatically reads configuration from the related model's schema:

- **Sortable fields**: Read from `fields[].sortable` in related schema
- **Filterable fields**: Read from `fields[].filterable` in related schema  
- **Listable fields**: Uses `detail.list_fields` if specified, otherwise reads from related schema

### Backwards Compatibility

- Special handling for `users` relationship maintains compatibility with UserSprunje
- All existing detail sections continue to work without changes
- Schema-based configuration is optional

## API Endpoints

### Relationship Data Endpoint

```
GET /api/crud6/{model}/{id}/{relation}
```

**Example:**
```
GET /api/crud6/groups/5/users
```

**Query Parameters:**
- All standard Sprunje parameters (size, page, sorts, filters, search)

**Response:**
Same format as standard Sprunje response with rows, count, pagination

## Schema Configuration

### Basic Relationship

```json
{
  "model": "categories",
  "detail": {
    "model": "products",
    "foreign_key": "category_id",
    "list_fields": ["name", "sku", "price", "description"],
    "title": "CATEGORY.PRODUCTS"
  }
}
```

### Multiple Fields

The `list_fields` array specifies which fields to display in the relationship table:

```json
"list_fields": ["id", "name", "status", "created_at"]
```

### Foreign Key

The `foreign_key` specifies which field in the related table links back to the parent:

```json
"foreign_key": "parent_id"  // products.parent_id references categories.id
```

## Frontend Usage

### Using Details Component

```vue
<UFCRUD6Details
    :recordId="group.id"
    parentModel="groups"
    :detailConfig="schema.detail"
/>
```

The Details component automatically:
1. Loads the related model's schema
2. Builds the data URL: `/api/crud6/groups/{id}/users`
3. Renders UFSprunjeTable with proper columns
4. Handles sorting, filtering, and pagination

### Manual API Call

```typescript
import axios from 'axios'

const response = await axios.get('/api/crud6/groups/5/users', {
  params: {
    size: 25,
    page: 1,
    sorts: { user_name: 'asc' },
    filters: { flag_enabled: 1 }
  }
})
```

## Implementation Details

### Helper Methods Added to SprunjeAction

```php
/**
 * Get sortable fields from a schema array
 */
protected function getSortableFieldsFromSchema(array $schema): array

/**
 * Get filterable fields from a schema array
 */
protected function getFilterableFieldsFromSchema(array $schema): array

/**
 * Get listable fields from a schema array
 */
protected function getListableFieldsFromSchema(array $schema): array
```

These methods parse the field configuration from any schema to determine:
- Which fields can be sorted
- Which fields can be filtered
- Which fields should be displayed

### Query Extension

The SprunjeAction extends the query builder to filter by the foreign key:

```php
$this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
    return $query->where($foreignKey, $crudModel->id);
});
```

This ensures only related records are returned.

## Examples

### Groups → Users Relationship

**Schema (groups.json):**
```json
{
  "model": "groups",
  "detail": {
    "model": "users",
    "foreign_key": "group_id",
    "list_fields": ["user_name", "email", "first_name", "last_name"],
    "title": "GROUP.USERS"
  }
}
```

**API Call:**
```
GET /api/crud6/groups/3/users?size=10&page=1&sorts[user_name]=asc
```

### Categories → Products Relationship

**Schema (categories.json):**
```json
{
  "model": "categories",
  "detail": {
    "model": "products",
    "foreign_key": "category_id",
    "list_fields": ["name", "sku", "price", "stock"],
    "title": "CATEGORY.PRODUCTS"
  }
}
```

**API Call:**
```
GET /api/crud6/categories/electronics/products?filters[stock]=>0
```

## Benefits

1. **Reusable**: Works with any model pair
2. **Schema-Driven**: No hardcoding required
3. **Flexible**: Supports custom field lists per relationship
4. **Consistent**: Uses same Sprunje pattern as main listings
5. **Compatible**: Maintains backwards compatibility with existing code

## Migration from Hardcoded Relationships

Old approach (hardcoded):
```php
if ($relation === 'users') {
    // Hardcoded UserSprunje logic
}
```

New approach (schema-driven):
```php
// Load related schema
$relatedSchema = $this->schemaService->getSchema($relation, $request);

// Setup sprunje dynamically
$this->sprunje->setupSprunje(
    $relatedModel->getTable(),
    $this->getSortableFieldsFromSchema($relatedSchema),
    $this->getFilterableFieldsFromSchema($relatedSchema),
    $detailConfig['list_fields'] ?? []
);
```

This makes relationship handling generic and extensible for any model combination.
