# CRUD6 Relationship Configuration Guide

## Overview

CRUD6 SprunjeAction is now **100% generic** and **JSON schema-driven**. There are NO hardcoded table names or special cases for users/roles/permissions. All relationships must be defined in your schema JSON files.

## Supported Relationship Types

### 1. Many-to-Many (`many_to_many`)

For two tables connected through a pivot table.

**Example: Products ↔ Categories**

```json
{
  "model": "products",
  "table": "products",
  "relationships": [
    {
      "name": "categories",
      "type": "many_to_many",
      "pivot_table": "product_category",
      "foreign_key": "product_id",
      "related_key": "category_id"
    }
  ]
}
```

**Example: Users ↔ Roles**

```json
{
  "model": "users",
  "table": "users",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_user",
      "foreign_key": "user_id",
      "related_key": "role_id"
    }
  ]
}
```

### 2. Belongs-to-Many-Through (`belongs_to_many_through`)

For nested many-to-many relationships through an intermediate table.

**Example: Products → Categories → Suppliers**

```json
{
  "model": "products",
  "table": "products",
  "relationships": [
    {
      "name": "suppliers",
      "type": "belongs_to_many_through",
      "through": "App\\Models\\Category",
      "first_pivot_table": "product_category",
      "first_foreign_key": "product_id",
      "first_related_key": "category_id",
      "second_pivot_table": "category_supplier",
      "second_foreign_key": "category_id",
      "second_related_key": "supplier_id"
    }
  ]
}
```

**Example: Users → Roles → Permissions**

```json
{
  "model": "users",
  "table": "users",
  "relationships": [
    {
      "name": "permissions",
      "type": "belongs_to_many_through",
      "through": "UserFrosting\\Sprinkle\\Account\\Database\\Models\\Role",
      "first_pivot_table": "role_user",
      "first_foreign_key": "user_id",
      "first_related_key": "role_id",
      "second_pivot_table": "permission_role",
      "second_foreign_key": "role_id",
      "second_related_key": "permission_id"
    }
  ]
}
```

### 3. One-to-Many (Default/Fallback)

Simple foreign key relationships. No configuration needed in `relationships` array - just define in `details`.

**Example: Orders → Order Items**

```json
{
  "model": "orders",
  "table": "orders",
  "details": [
    {
      "model": "order_items",
      "foreign_key": "order_id"
    }
  ]
}
```

## Configuration Requirements

### For `many_to_many`:
- ✅ `pivot_table`: The intermediate/pivot table name
- ✅ `foreign_key`: Column in pivot table referencing the parent model
- ✅ `related_key`: Column in pivot table referencing the related model

### For `belongs_to_many_through`:
- ✅ `through`: Fully qualified class name of the intermediate model
- ✅ `first_pivot_table`: First pivot table (parent → through)
- ✅ `first_foreign_key`: Column in first pivot for parent ID
- ✅ `first_related_key`: Column in first pivot for through model ID
- ✅ `second_pivot_table`: Second pivot table (through → related)
- ✅ `second_foreign_key`: Column in second pivot for through model ID
- ✅ `second_related_key`: Column in second pivot for related model ID

### For one-to-many (fallback):
- ✅ `foreign_key`: Column in related table referencing parent ID

## API Endpoints

All relationship queries use the same pattern:

```
GET /api/crud6/{model}/{id}/{relation}?size=10&page=0
```

Examples:
- `GET /api/crud6/users/1/roles` - Get roles for user 1
- `GET /api/crud6/users/1/permissions` - Get permissions for user 1
- `GET /api/crud6/products/5/categories` - Get categories for product 5
- `GET /api/crud6/products/5/suppliers` - Get suppliers for product 5 (through categories)
- `GET /api/crud6/orders/123/order_items` - Get items for order 123

## Debug Logging

All relationship queries now log:
- Relationship type detected
- Configuration used
- SQL queries generated
- Pivot tables and keys

Check your PHP error logs for detailed debug information with prefix `[CRUD6 SprunjeAction]`.

## Migration Notes

If you were relying on the old hardcoded "permissions" logic, you MUST now:

1. Add a `permissions` relationship to your `users.json` schema
2. Use type `belongs_to_many_through`
3. Specify BOTH pivot tables (`role_user` and `permission_role`)
4. Provide all required keys

The system will throw clear error messages if configuration is missing.
