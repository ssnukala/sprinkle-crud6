# UserFrosting Account Migrations

This directory contains reference migrations from UserFrosting's sprinkle-account (v400) for local testing and reference.

## Migration Files

These migrations define the database schema for users, roles, permissions, and related tables:

1. **UsersTable.php** - Main users table
   - Primary user information (username, email, first_name, last_name, etc.)
   - Authentication flags (verified, enabled)
   - Timestamps and soft deletes

2. **GroupsTable.php** - User groups
   - Group management for user organization

3. **RolesTable.php** - User roles
   - Role definitions for authorization

4. **PermissionsTable.php** - Permissions
   - Fine-grained permission definitions

5. **RoleUsersTable.php** - Many-to-many: users ↔ roles
   - Pivot table linking users to roles
   - Composite primary key (user_id, role_id)

6. **PermissionRolesTable.php** - Many-to-many: roles ↔ permissions
   - Pivot table linking roles to permissions
   - Composite primary key (role_id, permission_id)

7. **ActivitiesTable.php** - User activity log
   - Tracks user actions for auditing

8. **PasswordResetsTable.php** - Password reset tokens
   - Temporary tokens for password reset flows

9. **PersistencesTable.php** - Remember me tokens
   - Persistent authentication tokens

10. **VerificationsTable.php** - Email verification tokens
    - Email verification workflow

## Source

These migrations are from UserFrosting sprinkle-account version 4.0.0 (v400):
- Repository: https://github.com/userfrosting/sprinkle-account/tree/6.0
- Path: `app/src/Database/Migrations/v400/`

## Relationship Structure

The migrations define the following relationship structure:

### Many-to-Many: Users ↔ Roles
```
users (id) ←→ role_users (user_id, role_id) ←→ roles (id)
```

### Many-to-Many-Through: Users → Roles → Permissions
```
users (id) ←→ role_users (user_id, role_id) ←→ roles (id) ←→ permission_roles (role_id, permission_id) ←→ permissions (id)
```

### One-to-Many: Users → Activities
```
users (id) ←─ activities (user_id)
```

## Usage

### As Database Reference

Use these migrations to understand the exact table structure when creating CRUD6 schemas:

1. **Table names**: Match exactly (e.g., `users`, `roles`, `role_users`)
2. **Column names**: Match field names in your schema (e.g., `user_name`, `first_name`)
3. **Pivot tables**: Use correct foreign key names (e.g., `user_id`, `role_id`)

### With CRUD6 Schemas

The `examples/schema/c6admin-*.json` files provide CRUD6 schema definitions that match these migrations:

- `c6admin-users.json` → UsersTable.php
- `c6admin-roles.json` → RolesTable.php
- `c6admin-permissions.json` → PermissionsTable.php
- `c6admin-groups.json` → GroupsTable.php
- `c6admin-activities.json` → ActivitiesTable.php

### Testing Relationships

These migrations are particularly useful for testing the CRUD6 relationship features:

1. **Many-to-many** (`many_to_many`):
   - Users to Roles via `role_users` pivot table
   - Schema: `examples/schema/c6admin-users.json` (roles relationship)

2. **Belongs-to-many-through** (`belongs_to_many_through`):
   - Users → Roles → Permissions
   - Goes through two pivot tables: `role_users` and `permission_roles`
   - Schema: `examples/schema/c6admin-users.json` (permissions relationship)

## Migration Details

### RoleUsersTable (Many-to-Many Pivot)
```php
Schema::create('role_users', function (Blueprint $table) {
    $table->integer('user_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->timestamps();
    $table->primary(['user_id', 'role_id']);  // Composite key
    $table->index('user_id');
    $table->index('role_id');
});
```

### PermissionRolesTable (Through Relationship Pivot)
```php
Schema::create('permission_roles', function (Blueprint $table) {
    $table->integer('permission_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->timestamps();
    $table->primary(['permission_id', 'role_id']);  // Composite key
    $table->index('permission_id');
    $table->index('role_id');
});
```

## Version Notes

These are the v400 migrations which represent the stable UserFrosting 4.0.0+ database schema. This structure is maintained in UserFrosting 6.0 for backward compatibility.
