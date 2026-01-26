<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Database\Seeds;

use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\SchemaTestHelper;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Test for CRUD6 default seeds.
 * 
 * This test verifies that CRUD6 seeds can be run successfully after
 * Account sprinkle seeds have been run, without manual dependency calls.
 * 
 * Follows UserFrosting 6 integration test patterns.
 */
class DefaultSeedsTest extends CRUD6TestCase
{
    use RefreshDatabase;

    /**
     * Setup test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Refresh database for clean state
        $this->refreshDatabase();
        
        // Seed Account sprinkle data first (simulating what bakery seed does)
        // Using inherited seedDatabase() from WithDatabaseSeeds trait
        $this->seedDatabase();
    }

    /**
     * Test that CRUD6 DefaultRoles seed can run successfully.
     * 
     * This test verifies the crud6-admin role is created.
     */
    public function testDefaultRolesSeed(): void
    {
        // Run the CRUD6 roles seed
        $seed = new DefaultRoles();
        $seed->run();
        
        // Verify crud6-admin role was created
        $role = Role::where('slug', 'crud6-admin')->first();
        $this->assertNotNull($role);
        $this->assertEquals('CRUD6 Administrator', $role->name);
        $this->assertStringContainsString('CRUD6 administrators', $role->description);
    }

    /**
     * Test that CRUD6 DefaultPermissions seed can run successfully.
     * 
     * This test verifies that:
     * 1. CRUD6 permissions are created
     * 2. Permissions are synced with roles
     * 3. No manual dependency calls are made (roles must exist beforehand)
     */
    public function testDefaultPermissionsSeed(): void
    {
        // First run DefaultRoles since permissions depend on it
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        
        // Run the permissions seed
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
        
        // Verify all CRUD6 permissions were created
        $expectedPermissions = [
            'create_crud6',
            'delete_crud6',
            'update_crud6_field',
            'uri_crud6',
            'uri_crud6_list',
            'view_crud6_field',
        ];
        
        foreach ($expectedPermissions as $slug) {
            $permission = Permission::where('slug', $slug)->first();
            $this->assertNotNull($permission, "Permission {$slug} should exist");
            $this->assertEquals('always()', $permission->conditions);
        }
        
        // Verify permissions are synced with crud6-admin role
        // Permission count varies based on schema type:
        // 
        // Static schemas (default):
        // - 6 legacy CRUD6 permissions (crud6_*, delete_crud6_field, etc.)
        // - 20 schema-defined permissions from 5 example schemas (users, roles, groups, permissions, activities)
        //   Each schema generates ~4 permissions (read, create, update, delete)
        // = 26 total
        //
        // Auto-generated schemas (GENERATE_TEST_SCHEMAS=1):
        // - 6 legacy CRUD6 permissions
        // - Schema-defined permissions from all tables scanned (users, roles, groups, permissions, activities, etc.)
        // = 26+ total (may include additional tables or fields)
        $role = Role::where('slug', 'crud6-admin')->first();
        $this->assertNotNull($role);
        
        // Use SchemaTestHelper to assert correct count based on schema type
        SchemaTestHelper::assertPermissionCount(
            $this,
            $role->permissions,
            SchemaTestHelper::isUsingGeneratedSchemas()
                ? 'Auto-generated schemas: crud6-admin should have at least 26 permissions'
                : 'Static schemas: crud6-admin should have exactly 26 permissions (6 legacy + 20 from 5 schemas)'
        );
        
        // Verify site-admin role also has CRUD6 permissions (if it exists)
        $siteAdminRole = Role::where('slug', 'site-admin')->first();
        if ($siteAdminRole !== null) {
            $this->assertGreaterThanOrEqual(24, $siteAdminRole->permissions->count());
        }
    }

    /**
     * Test that seeds can be run in sequence without errors.
     * 
     * This simulates the bakery seed command running seeds in order.
     */
    public function testSeedSequence(): void
    {
        // Run seeds in order (simulating bakery seed command)
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
        
        // Verify both roles and permissions exist
        $role = Role::where('slug', 'crud6-admin')->first();
        $this->assertNotNull($role);
        
        $permission = Permission::where('slug', 'create_crud6')->first();
        $this->assertNotNull($permission);
        
        // Verify relationship
        $this->assertTrue($role->permissions->contains($permission));
    }

    /**
     * Test that seeds can be run multiple times (idempotency).
     * 
     * Seeds should not fail or create duplicates when run multiple times.
     */
    public function testSeedIdempotency(): void
    {
        // Run seeds first time
        $rolesSeed = new DefaultRoles();
        $rolesSeed->run();
        
        $permissionsSeed = new DefaultPermissions();
        $permissionsSeed->run();
        
        // Count records
        $initialRoleCount = Role::where('slug', 'crud6-admin')->count();
        $initialPermissionCount = Permission::where('slug', 'create_crud6')->count();
        
        // Run seeds again
        $rolesSeed->run();
        $permissionsSeed->run();
        
        // Verify no duplicates created
        $finalRoleCount = Role::where('slug', 'crud6-admin')->count();
        $finalPermissionCount = Permission::where('slug', 'create_crud6')->count();
        
        $this->assertEquals($initialRoleCount, $finalRoleCount);
        $this->assertEquals($initialPermissionCount, $finalPermissionCount);
    }

    // Note: seedDatabase() is now inherited from WithDatabaseSeeds trait
    // No need to redefine it here
}
