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
        fwrite(STDERR, "\n[SEEDING DATABASE] Starting database seed process...\n");
        
        try {
            $this->seedAccountData();
            $this->seedCRUD6Data();
            
            fwrite(STDERR, "[SEEDING DATABASE] ✅ Database seeding completed successfully\n\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "[SEEDING DATABASE] ❌ Error during seeding: " . $e->getMessage() . "\n");
            fwrite(STDERR, "[SEEDING DATABASE] Stack trace: " . $e->getTraceAsString() . "\n\n");
            throw $e;
        }
    }

    /**
     * Seed Account sprinkle base data.
     * 
     * Creates:
     * - Default group (terran)
     * - Site admin role
     * - Base permissions
     * 
     * This simulates running Account sprinkle seeds before CRUD6 seeds.
     */
    protected function seedAccountData(): void
    {
        fwrite(STDERR, "  [SEED] Creating Account sprinkle base data...\n");
        
        // Create a default group (simulating DefaultGroups seed)
        $group = Group::create([
            'slug' => 'terran',
            'name' => 'Terran',
            'description' => 'The terrans are the default user group.',
            'icon' => 'fa fa-user',
        ]);
        fwrite(STDERR, "  [SEED] ✓ Created default group: {$group->slug}\n");
        
        // Create site-admin role (simulating DefaultRoles seed)
        $role = Role::create([
            'slug' => 'site-admin',
            'name' => 'Site Administrator',
            'description' => 'This role is meant for "site administrators".',
        ]);
        fwrite(STDERR, "  [SEED] ✓ Created site-admin role: {$role->slug}\n");
        
        // Create some base permissions (simulating DefaultPermissions seed)
        $permission = Permission::create([
            'slug' => 'uri_users',
            'name' => 'View users',
            'conditions' => 'always()',
            'description' => 'View the user listing page.',
        ]);
        fwrite(STDERR, "  [SEED] ✓ Created base permission: {$permission->slug}\n");
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
        fwrite(STDERR, "  [SEED] Running CRUD6 seeders...\n");
        
        // Run DefaultRoles seed to create crud6-admin role
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        fwrite(STDERR, "  [SEED] ✓ DefaultRoles seed completed\n");
        
        // Run DefaultPermissions seed to create CRUD6 permissions
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
        fwrite(STDERR, "  [SEED] ✓ DefaultPermissions seed completed\n");
    }
}
