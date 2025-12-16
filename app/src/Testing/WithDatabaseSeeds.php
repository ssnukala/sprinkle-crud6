<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;

/**
 * WithDatabaseSeeds Trait
 * 
 * Provides database seeding functionality for tests that use RefreshDatabase.
 * This trait ensures tests have the necessary base data (groups, roles, permissions)
 * that the application expects.
 * 
 * Usage:
 * ```php
 * class MyTest extends CRUD6TestCase
 * {
 *     use RefreshDatabase;
 *     use WithDatabaseSeeds;
 *     
 *     public function setUp(): void
 *     {
 *         parent::setUp();
 *         $this->refreshDatabase();
 *         $this->seedDatabase();
 *     }
 * }
 * ```
 * 
 * This follows the pattern from DefaultSeedsTest and integration tests.
 */
trait WithDatabaseSeeds
{
    /**
     * Seed database with Account and CRUD6 data.
     * 
     * Call this after refreshDatabase() to ensure tests have necessary data.
     */
    protected function seedDatabase(): void
    {
        try {
            $this->seedAccountData();
            $this->seedCRUD6Data();
        } catch (\Exception $e) {
            // Log errors to help debug seeding failures
            fwrite(STDERR, "\n[SEEDING ERROR] " . $e->getMessage() . "\n");
            fwrite(STDERR, "[SEEDING ERROR] Stack trace: " . $e->getTraceAsString() . "\n\n");
            throw $e;
        }
    }

    /**
     * Seed Account sprinkle base data.
     * 
     * Creates:
     * - Default group (terran)
     * - Site admin role
     * - Base permissions for users, roles, groups, and permissions models
     * 
     * This simulates running Account sprinkle seeds before CRUD6 seeds.
     * Includes all permissions needed by CRUD6 integration tests.
     */
    protected function seedAccountData(): void
    {
        // Create a default group (simulating DefaultGroups seed)
        Group::create([
            'slug' => 'terran',
            'name' => 'Terran',
            'description' => 'The terrans are the default user group.',
            'icon' => 'fa fa-user',
        ]);
        
        // Create site-admin role (simulating DefaultRoles seed)
        $siteAdminRole = Role::create([
            'slug' => 'site-admin',
            'name' => 'Site Administrator',
            'description' => 'This role is meant for "site administrators".',
        ]);
        
        // Create base permissions for all models used in tests
        // These match the permissions defined in example schemas
        $permissions = [];
        
        // Users model permissions
        $permissions[] = Permission::create([
            'slug' => 'uri_users',
            'name' => 'View users',
            'conditions' => 'always()',
            'description' => 'View the user listing page.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'create_user',
            'name' => 'Create user',
            'conditions' => 'always()',
            'description' => 'Create a new user.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'update_user_field',
            'name' => 'Update user field',
            'conditions' => 'always()',
            'description' => 'Update a user field.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'delete_user',
            'name' => 'Delete user',
            'conditions' => 'always()',
            'description' => 'Delete a user.',
        ]);
        
        // Roles model permissions
        $permissions[] = Permission::create([
            'slug' => 'uri_roles',
            'name' => 'View roles',
            'conditions' => 'always()',
            'description' => 'View the role listing page.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'create_role',
            'name' => 'Create role',
            'conditions' => 'always()',
            'description' => 'Create a new role.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'update_role_field',
            'name' => 'Update role field',
            'conditions' => 'always()',
            'description' => 'Update a role field.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'delete_role',
            'name' => 'Delete role',
            'conditions' => 'always()',
            'description' => 'Delete a role.',
        ]);
        
        // Groups model permissions
        $permissions[] = Permission::create([
            'slug' => 'uri_groups',
            'name' => 'View groups',
            'conditions' => 'always()',
            'description' => 'View the group listing page.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'create_group',
            'name' => 'Create group',
            'conditions' => 'always()',
            'description' => 'Create a new group.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'update_group_field',
            'name' => 'Update group field',
            'conditions' => 'always()',
            'description' => 'Update a group field.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'delete_group',
            'name' => 'Delete group',
            'conditions' => 'always()',
            'description' => 'Delete a group.',
        ]);
        
        // Permissions model permissions
        $permissions[] = Permission::create([
            'slug' => 'uri_permissions',
            'name' => 'View permissions',
            'conditions' => 'always()',
            'description' => 'View the permission listing page.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'create_permission',
            'name' => 'Create permission',
            'conditions' => 'always()',
            'description' => 'Create a new permission.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'update_permission',
            'name' => 'Update permission',
            'conditions' => 'always()',
            'description' => 'Update a permission.',
        ]);
        
        $permissions[] = Permission::create([
            'slug' => 'delete_permission',
            'name' => 'Delete permission',
            'conditions' => 'always()',
            'description' => 'Delete a permission.',
        ]);
        
        // Attach all permissions to site-admin role
        $siteAdminRole->permissions()->sync(collect($permissions)->pluck('id')->toArray());
    }

    /**
     * Seed CRUD6 sprinkle data.
     * 
     * Runs:
     * - DefaultRoles seed (creates crud6-admin role)
     * - DefaultPermissions seed (creates CRUD6 permissions and syncs with roles)
     * 
     * This ensures CRUD6-specific roles and permissions are available for tests.
     */
    protected function seedCRUD6Data(): void
    {
        // Run DefaultRoles seed to create crud6-admin role
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        
        // Run DefaultPermissions seed to create CRUD6 permissions
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
    }
}
