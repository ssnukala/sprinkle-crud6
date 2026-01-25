<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

use Exception;
use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Core\Seeder\SeedRepositoryInterface;

/**
 * WithDatabaseSeeds Trait
 * 
 * Provides database seeding functionality for tests that use RefreshDatabase.
 * This trait runs all registered seeds from UserFrosting sprinkles and creates
 * an admin user following the same pattern as the CI workflow.
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
 *     
 *     public function testUserCrud(): void
 *     {
 *         // Get permissions from schema file
 *         $permissions = $this->getSchemaPermissions('users');
 *         
 *         $user = User::factory()->create();
 *         $this->actAsUser($user, permissions: $permissions);
 *         
 *         // Now user has all permissions defined in users schema
 *         // (read, create, update, delete)
 *     }
 * }
 * ```
 * 
 * This follows the CI workflow pattern:
 * 1. Run migrations (via RefreshDatabase)
 * 2. Run all registered seeds (groups, roles, permissions)
 * 3. Create admin user (triggers default group/role assignment)
 */
trait WithDatabaseSeeds
{
    /**
     * Seed database using UserFrosting's registered seeds and create admin user.
     * 
     * Call this after refreshDatabase() to ensure tests have necessary data.
     * This method follows the CI workflow pattern:
     * 1. Runs all seeds registered in sprinkles via SeedRepositoryInterface
     * 2. Creates an admin user (similar to `php bakery create:admin-user`)
     * 3. Generates CRUD6 schemas from database tables using crud6:generate bakery command
     * 
     * This ensures that groups, roles, and permissions exist before any tests run,
     * and that an admin user is available for authenticated tests.
     * 
     * After seeding, the following will be available:
     * - Groups: hippo, dove, dragon (from Account's DefaultGroups)
     * - Roles: site-admin (Account), crud6-admin (CRUD6)
     * - Permissions: All Account and CRUD6 permissions including:
     *   - uri_crud6, uri_crud6_list, create_crud6, delete_crud6, update_crud6_field, view_crud6_field
     *   - crud6.users.read, crud6.users.create, crud6.users.edit, crud6.users.delete
     *   - crud6.groups.read, crud6.groups.create, crud6.groups.edit, crud6.groups.delete
     *   - crud6.roles.read, crud6.roles.create, crud6.roles.edit, crud6.roles.delete
     *   - crud6.permissions.read, crud6.permissions.create, crud6.permissions.edit, crud6.permissions.delete
     * - Admin user: 'admin' with site-admin role (has all permissions)
     * - CRUD6 Schemas: Generated from database tables (users, groups, roles, permissions, activities)
     */
    protected function seedDatabase(): void
    {
        // @phpstan-ignore-next-line Allow for extra protection in case Trait is misused.
        if (!isset($this->ci) || !$this->ci instanceof ContainerInterface) {
            throw new Exception('CI/Container not available. Make sure you extend the correct TestCase');
        }

        try {
            // Step 1: Run all registered seeds from all sprinkles
            // This includes Account's DefaultGroups, DefaultRoles, DefaultPermissions
            // and CRUD6's DefaultRoles, DefaultPermissions
            /** @var SeedRepositoryInterface */
            $seedRepository = $this->ci->get(SeedRepositoryInterface::class);
            
            $seeds = $seedRepository->all();
            
            foreach ($seeds as $seed) {
                $seed->run();
            }
            
            // Step 2: Create admin user (similar to CI workflow's create:admin-user)
            // This will trigger UserCreatedEvent listeners that assign default groups/roles
            $this->createAdminUser();
            
            // Step 3: Generate CRUD6 schemas from database using bakery command
            // This ensures integration tests use real generated schemas, not hardcoded ones
            \UserFrosting\Sprinkle\CRUD6\Tests\Testing\GenerateSchemas::generateFromDatabase($this->ci);
            
        } catch (\Exception $e) {
            // Log errors to help debug seeding failures
            fwrite(STDERR, "\n[SEEDING ERROR] " . $e->getMessage() . "\n");
            fwrite(STDERR, "[SEEDING ERROR] Stack trace: " . $e->getTraceAsString() . "\n\n");
            throw $e;
        }
    }
    
    /**
     * Create admin user following the same pattern as the CI workflow.
     * 
     * Creates a user with admin credentials that can be used in tests.
     * Uses createQuietly() to avoid triggering UserCreatedEvent listeners
     * that cause SQL errors when default group configuration is missing.
     * 
     * @return User The created admin user
     */
    protected function createAdminUser(): User
    {
        // Check if admin user already exists
        $existingAdmin = User::where('user_name', 'admin')->first();
        if ($existingAdmin) {
            return $existingAdmin;
        }
        
        // Create admin user WITHOUT triggering events
        // This prevents AssignDefaultGroups listener from causing SQL errors
        // when querying groups with empty column name
        /** @var User */
        $admin = User::factory()->createQuietly([
            'user_name' => 'admin',
            'email' => 'admin@example.com',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'password' => 'admin123',
            'flag_verified' => true,
            'flag_enabled' => true,
        ]);
        
        return $admin;
    }
    
    /**
     * Get permissions from a schema file for use with actAsUser().
     * 
     * Extracts all permission values from a schema's permissions object and returns
     * them as an array that can be passed to actAsUser().
     * 
     * Usage:
     * ```php
     * $permissions = $this->getSchemaPermissions('users');
     * // Returns: ['uri_crud6', 'create_user', 'update_user_field', 'delete_user']
     * 
     * $user = User::factory()->create();
     * $this->actAsUser($user, permissions: $permissions);
     * ```
     * 
     * @param string $modelName The model name (e.g., 'users', 'groups', 'roles')
     * @param string[] $operations Optional array of operations to include (e.g., ['read', 'create']).
     *                            If empty, all permissions from schema are returned.
     * 
     * @return string[] Array of permission slugs from the schema
     * 
     * @throws Exception If schema service is not available or schema not found
     */
    protected function getSchemaPermissions(string $modelName, array $operations = []): array
    {
        // @phpstan-ignore-next-line Allow for extra protection in case Trait is misused.
        if (!isset($this->ci) || !$this->ci instanceof ContainerInterface) {
            throw new Exception('CI/Container not available. Make sure you extend the correct TestCase');
        }
        
        // Get SchemaService from container
        $schemaService = $this->ci->get(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService::class);
        
        // Load the schema for the model
        $schema = $schemaService->getSchema($modelName);
        
        if (!isset($schema['permissions']) || !is_array($schema['permissions'])) {
            return [];
        }
        
        $permissions = [];
        
        // If specific operations requested, filter to those
        if (!empty($operations)) {
            foreach ($operations as $operation) {
                if (isset($schema['permissions'][$operation])) {
                    $permissions[] = $schema['permissions'][$operation];
                }
            }
        } else {
            // Return all permissions from schema
            $permissions = array_values($schema['permissions']);
        }
        
        // Remove duplicates and return
        return array_unique($permissions);
    }
}
