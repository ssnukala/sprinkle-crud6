# Many-to-Many Relationships Fix for User Details

## Problem Statement
When fetching user details at `/api/crud6/users/1`, the activities table was populated but groups and permissions tables were empty, even though the data existed in the database.

## Root Cause
The SprunjeAction controller only supported **one-to-many** relationships using direct foreign keys. It would query related tables using:
```php
$query->where($foreignKey, $crudModel->id);
```

This worked for activities (which have a `user_id` column) but failed for:
- **roles** - require many-to-many through `role_user` pivot table
- **permissions** - require nested many-to-many through roles

## Solution Implemented

Enhanced SprunjeAction to support three types of relationships:

### 1. One-to-Many (Direct Foreign Key)
**Example**: Activities with user_id column
```json
{
  "model": "activities",
  "foreign_key": "user_id",
  "list_fields": ["occurred_at", "type", "description"]
}
```
**Query**: `SELECT * FROM activities WHERE user_id = ?`

### 2. Many-to-Many (Pivot Table)
**Example**: Roles through role_user pivot
```json
// In relationships:
{
  "name": "roles",
  "type": "many_to_many",
  "pivot_table": "role_user",
  "foreign_key": "user_id",
  "related_key": "role_id"
}

// In details:
{
  "model": "roles",
  "foreign_key": "user_id",
  "list_fields": ["name", "slug", "description"]
}
```
**Query**: 
```sql
SELECT roles.* 
FROM roles 
JOIN role_user ON roles.id = role_user.role_id 
WHERE role_user.user_id = ?
```

### 3. Nested Many-to-Many
**Example**: Permissions through roles
```json
{
  "model": "permissions",
  "foreign_key": "user_id",
  "list_fields": ["slug", "name", "description"]
}
```
**Query**: 
```sql
SELECT DISTINCT permissions.* 
FROM permissions 
JOIN role_permission ON permissions.id = role_permission.permission_id 
JOIN role_user ON role_permission.role_id = role_user.role_id 
WHERE role_user.user_id = ?
```

## Code Changes

### Modified File: `app/src/Controller/SprunjeAction.php`

1. **Added method**: `findRelationshipConfig()`
   - Searches schema's `relationships` array for matching relation
   - Returns relationship configuration or null

2. **Enhanced query building logic**:
   - Checks if detail model has matching relationship definition
   - If `type === 'many_to_many'`, uses JOIN through pivot table
   - Special handling for `permissions` model (nested many-to-many)
   - Falls back to direct foreign key for one-to-many

3. **Added comprehensive logging**:
   - Logs relationship type detection
   - Logs query strategy selection
   - Helps troubleshoot relationship issues

## Testing

### Manual Testing Steps

1. **Setup**: Use the schema from sprinkle-c6admin
   ```
   https://github.com/ssnukala/sprinkle-c6admin/blob/main/app/schema/crud6/users.json
   ```

2. **Test Activities** (One-to-Many)
   ```
   GET /api/crud6/users/1/activities
   ```
   Expected: Returns activities with user_id = 1

3. **Test Roles** (Many-to-Many)
   ```
   GET /api/crud6/users/1/roles
   ```
   Expected: Returns roles linked via role_user pivot table

4. **Test Permissions** (Nested Many-to-Many)
   ```
   GET /api/crud6/users/1/permissions
   ```
   Expected: Returns permissions from user's roles

### Verification Checklist
- [ ] Activities detail shows correct data
- [ ] Roles detail shows user's assigned roles
- [ ] Permissions detail shows permissions from all user's roles
- [ ] No duplicate records in permissions
- [ ] Pagination and filtering work correctly
- [ ] Error logs show correct relationship type detection

## Database Schema Requirements

The implementation expects these tables:

### For Roles Relationship
- `users` table with `id` column
- `roles` table with `id` column
- `role_user` pivot table with:
  - `user_id` (foreign key to users.id)
  - `role_id` (foreign key to roles.id)

### For Permissions Relationship
- `permissions` table with `id` column
- `role_permission` pivot table with:
  - `role_id` (foreign key to roles.id)
  - `permission_id` (foreign key to permissions.id)

## Configuration Example

Complete users.json schema with working relationships:

```json
{
  "model": "users",
  "table": "users",
  "primary_key": "id",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_user",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "title": "ROLE.2"
    }
  ],
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["occurred_at", "type", "description"],
      "title": "ACTIVITY.2"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug", "description"],
      "title": "ROLE.2"
    },
    {
      "model": "permissions",
      "foreign_key": "user_id",
      "list_fields": ["slug", "name", "description"],
      "title": "PERMISSION.2"
    }
  ]
}
```

## Backward Compatibility

✅ **Fully backward compatible**
- Existing one-to-many relationships work unchanged
- Details without matching relationships use direct foreign key
- No breaking changes to schema format

## Performance Considerations

1. **Many-to-Many Queries**: Single JOIN, efficient for most use cases
2. **Nested Many-to-Many**: Uses DISTINCT to avoid duplicates
3. **Indexing Recommendations**:
   - Index pivot table foreign keys (user_id, role_id)
   - Index role_permission table foreign keys

## Future Enhancements

Possible improvements for future versions:

1. **Generic Nested Relationships**: Support arbitrary depth chains
2. **Configurable Join Types**: LEFT JOIN vs INNER JOIN options
3. **Relationship Caching**: Cache relationship config lookups
4. **Inverse Relationships**: Support belongs-to relationships

## Related Files

- `app/src/Controller/SprunjeAction.php` - Main implementation
- `app/src/Routes/CRUD6Routes.php` - Route definitions
- `examples/users.json` - Example schema
- External reference: sprinkle-c6admin users.json schema
