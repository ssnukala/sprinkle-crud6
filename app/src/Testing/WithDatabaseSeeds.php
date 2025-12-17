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
     * 
     * This ensures that groups, roles, and permissions exist before any tests run,
     * and that an admin user is available for authenticated tests.
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
     * The user creation will trigger UserCreatedEvent listeners that automatically
     * assign default groups and roles (via AssignDefaultGroups and AssignDefaultRoles).
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
        
        // Create admin user - this will trigger the UserCreatedEvent
        // which in turn triggers AssignDefaultGroups and AssignDefaultRoles listeners
        /** @var User */
        $admin = User::factory()->create([
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
}
