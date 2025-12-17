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
     * This method ensures migrations are run before seeds, following UserFrosting 6 patterns.
     * 
     * Order of operations:
     * 1. Migrations are run (via RefreshDatabase trait)
     * 2. Account sprinkle base data is seeded
     * 3. CRUD6 sprinkle seeds are run (DefaultRoles, DefaultPermissions)
     */
    protected function seedDatabase(): void
    {
        try {
            // Log seeding start
            fwrite(STDERR, "\n[SEEDING] Starting database seed...\n");
            
            // Seed Account sprinkle base data first
            fwrite(STDERR, "[SEEDING] Step 1: Seeding Account data...\n");
            $this->seedAccountData();
            
            // Run CRUD6 seeds (which depend on Account data)
            fwrite(STDERR, "[SEEDING] Step 2: Seeding CRUD6 data...\n");
            $this->seedCRUD6Data();
            
            fwrite(STDERR, "[SEEDING] Database seed complete.\n\n");
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
        fwrite(STDERR, "[SEEDING] - Creating default group (terran)...\n");
        Group::create([
            'slug' => 'terran',
            'name' => 'Terran',
            'description' => 'The terrans are the default user group.',
            'icon' => 'fa fa-user',
        ]);
        
        // Create site-admin role (simulating DefaultRoles seed)
        fwrite(STDERR, "[SEEDING] - Creating site-admin role...\n");
        $siteAdminRole = Role::create([
            'slug' => 'site-admin',
            'name' => 'Site Administrator',
            'description' => 'This role is meant for "site administrators".',
        ]);
        fwrite(STDERR, "[SEEDING] - Created site-admin role (ID: {$siteAdminRole->id})\n");
        
        // Create base permissions for all models used in tests
        // These match the permissions defined in example schemas
        fwrite(STDERR, "[SEEDING] - Creating base Account permissions...\n");
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
        $permissionIds = collect($permissions)->pluck('id')->toArray();
        $siteAdminRole->permissions()->sync($permissionIds);
        fwrite(STDERR, "[SEEDING] - Created " . count($permissions) . " Account permissions\n");
        fwrite(STDERR, "[SEEDING] - Synced " . count($permissionIds) . " permissions to site-admin role\n");
    }

    /**
     * Seed CRUD6 sprinkle data.
     * 
     * Runs:
     * - DefaultRoles seed (creates crud6-admin role)
     * - DefaultPermissions seed (creates CRUD6 permissions and syncs with roles)
     * 
     * This ensures CRUD6-specific roles and permissions are available for tests.
     * This method is called AFTER seedAccountData() to ensure Account data exists.
     */
    protected function seedCRUD6Data(): void
    {
        // Run DefaultRoles seed to create crud6-admin role
        fwrite(STDERR, "[SEEDING] - Running DefaultRoles seed...\n");
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        
        // Verify crud6-admin role was created
        $crud6Role = Role::where('slug', 'crud6-admin')->first();
        if ($crud6Role) {
            fwrite(STDERR, "[SEEDING] - Created crud6-admin role (ID: {$crud6Role->id})\n");
        }
        
        // Run DefaultPermissions seed to create CRUD6 permissions
        fwrite(STDERR, "[SEEDING] - Running DefaultPermissions seed...\n");
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
        
        // Verify CRUD6 permissions were created
        $crud6Permissions = Permission::whereIn('slug', [
            'create_crud6', 'delete_crud6', 'update_crud6_field',
            'uri_crud6', 'uri_crud6_list', 'view_crud6_field'
        ])->count();
        fwrite(STDERR, "[SEEDING] - Created {$crud6Permissions} CRUD6 permissions\n");
        
        // Verify site-admin role has CRUD6 permissions
        $siteAdmin = Role::where('slug', 'site-admin')->first();
        if ($siteAdmin) {
            $permCount = $siteAdmin->permissions()->count();
            fwrite(STDERR, "[SEEDING] - site-admin role has {$permCount} total permissions\n");
        }
    }
}
