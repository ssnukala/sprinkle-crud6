# UserFrosting Account Migrations

This directory is intended to contain reference migrations from UserFrosting's sprinkle-account for local testing.

## Migration Files Needed

The following migrations from sprinkle-account define the database schema for users, roles, permissions, and related tables:

1. `CreateUsersTable.php` - Main users table
2. `CreateGroupsTable.php` - User groups
3. `CreateRolesTable.php` - Roles
4. `CreatePermissionsTable.php` - Permissions
5. `CreateRoleUsersTable.php` - Many-to-many: users ↔ roles
6. `CreatePermissionRolesTable.php` - Many-to-many: roles ↔ permissions
7. `CreateActivitiesTable.php` - User activity log
8. `CreatePasswordResetsTable.php` - Password reset tokens
9. `CreatePersistencesTable.php` - Remember me tokens
10. `CreateVerificationsTable.php` - Email verification tokens

## Source Repository

These migrations are from UserFrosting sprinkle-account (6.0 branch):
https://github.com/userfrosting/sprinkle-account/tree/6.0

## Usage

To use these migrations for local testing:

1. Copy the migration files from the sprinkle-account repository to this directory
2. The migrations define the exact database schema that CRUD6 schemas should match
3. Use these as a reference when creating CRUD6 schemas for UserFrosting tables

## Related Schema Files

The `examples/schema/c6admin-*.json` files provide CRUD6 schema definitions that match these migrations.
