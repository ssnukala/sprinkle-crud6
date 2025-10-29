# CRUD6 Seed Testing Documentation

This document describes the comprehensive seed testing added to the CRUD6 sprinkle integration tests.

## Overview

The CRUD6 sprinkle provides two database seeders:
- **DefaultRoles**: Creates the `crud6-admin` role
- **DefaultPermissions**: Creates 6 CRUD6 permissions and assigns them to roles

All seeders follow the UserFrosting 6 pattern from [sprinkle-account](https://github.com/userfrosting/sprinkle-account/tree/6.0/).

## Seed Implementation

### DefaultRoles
**File**: `app/src/Database/Seeds/DefaultRoles.php`

**Purpose**: Creates the `crud6-admin` role for managing CRUD6 resources.

**Implementation**:
- Implements `UserFrosting\Sprinkle\Core\Seeder\SeedInterface`
- Creates role with slug `crud6-admin`
- Idempotent: Checks if role exists before creating
- Follows exact pattern from Account sprinkle's DefaultRoles

### DefaultPermissions  
**File**: `app/src/Database/Seeds/DefaultPermissions.php`

**Purpose**: Creates 6 CRUD6 permissions and assigns them to relevant roles.

**Permissions Created**:
1. `create_crud6` - Create a new crud6 resource
2. `delete_crud6` - Delete a crud6 resource
3. `update_crud6_field` - Edit properties of any crud6 resource
4. `uri_crud6` - View the crud6 page of any crud6 resource
5. `uri_crud6_list` - View the crud6 management page
6. `view_crud6_field` - View certain properties of any crud6 resource

**Implementation**:
- Implements `UserFrosting\Sprinkle\Core\Seeder\SeedInterface`
- Manually calls `(new DefaultRoles())->run()` to ensure roles exist
- Creates all permissions with `always()` conditions
- Assigns permissions to `crud6-admin` role
- Assigns permissions to `site-admin` role (if exists)
- Idempotent: Checks if permissions exist before creating
- Follows exact pattern from Account sprinkle's DefaultPermissions

## Integration Test Coverage

The integration test workflow (`.github/workflows/integration-test.yml`) includes comprehensive seed testing:

### 1. Seed Execution
```bash
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force
```

### 2. Seed Data Validation
After seeding, the workflow validates:

#### Role Creation
- ✅ `crud6-admin` role exists
- ✅ Role has correct name: "CRUD6 Administrator"
- ✅ Role has correct description

#### Permission Creation  
- ✅ All 6 CRUD6 permissions exist:
  - create_crud6
  - delete_crud6
  - update_crud6_field
  - uri_crud6
  - uri_crud6_list
  - view_crud6_field

#### Permission Assignments
- ✅ `crud6-admin` role has all 6 permissions assigned
- ✅ `site-admin` role has all 6 CRUD6 permissions assigned

### 3. Seed Idempotency Test
Tests that seeds can be run multiple times without creating duplicates:

1. Count records before re-seeding
2. Re-run both seed classes
3. Count records after re-seeding
4. Verify counts are identical (no duplicates created)

This ensures seeds are safe to run during:
- Initial installation
- Updates/upgrades
- Manual re-seeding

## Validation Queries

The integration tests use inline PHP scripts to query the database:

```php
// Check role exists
$role = Role::where('slug', 'crud6-admin')->first();

// Check permission exists
$perm = Permission::where('slug', 'create_crud6')->first();

// Check permission assignments
$permCount = $role->permissions()->count();
$crud6Perms = $role->permissions()->whereIn('slug', [...list...])->count();
```

## Testing Locally

To test seeds locally in a UserFrosting 6 application:

```bash
# Seed Account sprinkle first (required dependencies)
php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups --force
php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultPermissions --force
php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultRoles --force

# Seed CRUD6 sprinkle
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force

# Verify data
php artisan tinker
>>> Role::where('slug', 'crud6-admin')->first()
>>> Permission::where('slug', 'create_crud6')->first()
```

## Unit Tests

Unit tests for seeds are located in:
- `app/tests/Database/Seeds/DefaultSeedsTest.php`

These tests verify:
- Role creation
- Permission creation
- Permission-role relationships
- Seed sequence (roles before permissions)
- Idempotency

## Reference

All seed implementations follow the official UserFrosting 6 patterns:
- **SeedInterface**: `UserFrosting\Sprinkle\Core\Seeder\SeedInterface`
- **Reference Implementation**: https://github.com/userfrosting/sprinkle-account/tree/6.0/
- **Pattern Source**: Account sprinkle's DefaultRoles and DefaultPermissions

## Troubleshooting

### Error: "Class is not a valid seed"
This error occurs when:
1. Class doesn't implement `SeedInterface`
2. Autoloading isn't configured correctly
3. Namespace doesn't match directory structure

**Solution**: Verify:
- Class implements `UserFrosting\Sprinkle\Core\Seeder\SeedInterface`
- Class has public `run(): void` method
- Namespace is `UserFrosting\Sprinkle\CRUD6\Database\Seeds`
- File is in `app/src/Database/Seeds/`
- Composer autoload is configured: `"UserFrosting\\Sprinkle\\CRUD6\\": "app/src/"`

### Seeds create duplicates
Seeds should be idempotent. Each seed checks if data exists before creating:

```php
if (Role::where('slug', $role->slug)->first() == null) {
    $role->save();
}
```

### Permissions not assigned to roles
DefaultPermissions manually runs DefaultRoles first:

```php
public function run(): void
{
    // We require the default roles seed
    (new DefaultRoles())->run();
    
    // ... then create and assign permissions
}
```

This ensures roles exist before trying to assign permissions.
