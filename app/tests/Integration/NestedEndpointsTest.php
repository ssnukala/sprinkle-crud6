<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Nested Endpoints Integration Test
 *
 * Tests the CRUD6 API endpoints for nested relationships:
 * - GET /api/crud6/roles/{id}/permissions (list permissions for a role)
 * - GET /api/crud6/permissions/{id}/roles (list roles for a permission)
 * - GET /api/crud6/permissions/{id} (get single permission)
 * - GET /api/crud6/roles/{id} (get single role)
 * 
 * This test was created to debug 500 errors reported in:
 * https://github.com/ssnukala/sprinkle-c6admin
 * 
 * The test includes comprehensive debug logging to help identify issues.
 */
class NestedEndpointsTest extends CRUD6TestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    /**
     * Setup test database for controller tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->seedDatabase();
        $this->startApiTracking();
    }

    /**
     * Cleanup after each test and output API call tracking summary
     */
    public function tearDown(): void
    {
        // Output API call tracking summary if any calls were made
        if ($this->getApiCallTracker() !== null) {
            $summary = $this->getApiCallSummary();
            
            if ($summary['total'] > 0) {
                echo "\n";
                echo "═══════════════════════════════════════════════════════════════\n";
                echo "API Call Tracking Summary for " . $this->name() . "\n";
                echo "═══════════════════════════════════════════════════════════════\n";
                echo sprintf("  Total API Calls:        %d\n", $summary['total']);
                echo sprintf("  Unique Calls:           %d\n", $summary['unique']);
                echo sprintf("  Redundant Call Groups:  %d\n", $summary['redundant']);
                echo sprintf("  Schema API Calls:       %d\n", $summary['schema_calls']);
                echo sprintf("  CRUD6 API Calls:        %d\n", $summary['crud6_calls']);
                
                // Show redundant calls if any
                if ($summary['redundant'] > 0) {
                    echo "\n⚠️  WARNING: Redundant API calls detected!\n";
                    $redundantCalls = $this->getRedundantApiCalls();
                    foreach ($redundantCalls as $key => $data) {
                        $firstCall = $data['calls'][0];
                        echo sprintf("  - %s %s (called %dx)\n", 
                            $firstCall['method'], 
                            $firstCall['uri'], 
                            $data['count']
                        );
                    }
                } else {
                    echo "\n✅ No redundant calls detected\n";
                }
                echo "═══════════════════════════════════════════════════════════════\n";
            }
        }
        
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    /**
     * Test nested endpoints require authentication
     */
    public function testNestedEndpointRequiresAuthentication(): void
    {
        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/permissions");
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(401, $response,
            'Nested endpoint should require authentication');
    }

    /**
     * Test nested endpoints require permission
     */
    public function testNestedEndpointRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/permissions");
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(403, $response,
            'Nested endpoint should require permission');
    }

    /**
     * Test GET /api/crud6/permissions/1 returns a single permission
     * 
     * This is the basic detail endpoint that was reported as failing.
     */
    public function testPermissionDetailEndpoint(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/permissions/{id}\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_permissions']);

        // Create a permission
        /** @var Permission */
        $permission = Permission::factory()->create([
            'slug' => 'test_permission',
            'name' => 'Test Permission',
            'description' => 'A test permission',
        ]);

        echo sprintf("[TEST] Created permission: id=%d, slug=%s\n", $permission->id, $permission->slug);

        // Make request to get single permission
        $request = $this->createJsonRequest('GET', "/api/crud6/permissions/{$permission->id}");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/permissions/%d\n", $permission->id);
        
        $response = $this->handleRequestWithTracking($request);

        // Debug output
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        echo sprintf("[TEST] Response body length: %d bytes\n", strlen($body));
        
        if ($statusCode !== 200) {
            echo "[TEST] ❌ ERROR Response body:\n";
            echo $body . "\n";
        } else {
            echo "[TEST] ✅ SUCCESS Response body:\n";
            echo $body . "\n";
        }

        // Assert response status & body
        $this->assertResponseStatus(200, $response, 'Permission detail endpoint should return 200');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($permission->id, $data['id']);
        $this->assertEquals('test_permission', $data['slug']);
    }

    /**
     * Test GET /api/crud6/roles/1/permissions returns permissions for a role
     * 
     * This is the nested endpoint that was reported as failing.
     */
    public function testRolePermissionsNestedEndpoint(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/roles/{id}/permissions\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_roles']);

        // Create a role
        /** @var Role */
        $role = Role::factory()->create([
            'slug' => 'test_role',
            'name' => 'Test Role',
            'description' => 'A test role',
        ]);

        echo sprintf("[TEST] Created role: id=%d, slug=%s\n", $role->id, $role->slug);

        // Create some permissions and attach to role
        $permissions = Permission::factory()->count(3)->create();
        $role->permissions()->attach($permissions);

        echo sprintf("[TEST] Attached %d permissions to role\n", $permissions->count());

        // Make request to get role's permissions
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/permissions");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/roles/%d/permissions\n", $role->id);
        
        $response = $this->handleRequestWithTracking($request);

        // Debug output
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        echo sprintf("[TEST] Response body length: %d bytes\n", strlen($body));
        
        if ($statusCode !== 200) {
            echo "[TEST] ❌ ERROR Response body:\n";
            echo $body . "\n";
        } else {
            echo "[TEST] ✅ SUCCESS Response body (first 500 chars):\n";
            echo substr($body, 0, 500) . "...\n";
        }

        // Assert response status & body
        $this->assertResponseStatus(200, $response, 'Role permissions nested endpoint should return 200');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        
        // Check if it's a Sprunje response format
        if (isset($data['rows'])) {
            $this->assertArrayHasKey('rows', $data);
            $this->assertArrayHasKey('count', $data);
            $this->assertCount(3, $data['rows'], 'Should return 3 permissions');
        } else {
            // Or a simple array response
            $this->assertCount(3, $data, 'Should return 3 permissions');
        }
    }

    /**
     * Test GET /api/crud6/permissions/1/roles returns roles for a permission
     * 
     * This is the reverse nested endpoint.
     */
    public function testPermissionRolesNestedEndpoint(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/permissions/{id}/roles\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_permissions']);

        // Create a permission
        /** @var Permission */
        $permission = Permission::factory()->create([
            'slug' => 'test_permission',
            'name' => 'Test Permission',
        ]);

        echo sprintf("[TEST] Created permission: id=%d, slug=%s\n", $permission->id, $permission->slug);

        // Create some roles and attach to permission
        $roles = Role::factory()->count(2)->create();
        $permission->roles()->attach($roles);

        echo sprintf("[TEST] Attached %d roles to permission\n", $roles->count());

        // Make request to get permission's roles
        $request = $this->createJsonRequest('GET', "/api/crud6/permissions/{$permission->id}/roles");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/permissions/%d/roles\n", $permission->id);
        
        $response = $this->handleRequestWithTracking($request);

        // Debug output
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        echo sprintf("[TEST] Response body length: %d bytes\n", strlen($body));
        
        if ($statusCode !== 200) {
            echo "[TEST] ❌ ERROR Response body:\n";
            echo $body . "\n";
        } else {
            echo "[TEST] ✅ SUCCESS Response body (first 500 chars):\n";
            echo substr($body, 0, 500) . "...\n";
        }

        // Assert response status & body
        $this->assertResponseStatus(200, $response, 'Permission roles nested endpoint should return 200');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        
        // Check if it's a Sprunje response format
        if (isset($data['rows'])) {
            $this->assertArrayHasKey('rows', $data);
            $this->assertArrayHasKey('count', $data);
            $this->assertCount(2, $data['rows'], 'Should return 2 roles');
        } else {
            // Or a simple array response
            $this->assertCount(2, $data, 'Should return 2 roles');
        }
    }

    /**
     * Test GET /api/crud6/roles/1 returns a single role
     * 
     * This is the basic detail endpoint for roles.
     */
    public function testRoleDetailEndpoint(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/roles/{id}\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_roles']);

        // Create a role
        /** @var Role */
        $role = Role::factory()->create([
            'slug' => 'test_role',
            'name' => 'Test Role',
            'description' => 'A test role',
        ]);

        echo sprintf("[TEST] Created role: id=%d, slug=%s\n", $role->id, $role->slug);

        // Make request to get single role
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/roles/%d\n", $role->id);
        
        $response = $this->handleRequestWithTracking($request);

        // Debug output
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        echo sprintf("[TEST] Response body length: %d bytes\n", strlen($body));
        
        if ($statusCode !== 200) {
            echo "[TEST] ❌ ERROR Response body:\n";
            echo $body . "\n";
        } else {
            echo "[TEST] ✅ SUCCESS Response body:\n";
            echo $body . "\n";
        }

        // Assert response status & body
        $this->assertResponseStatus(200, $response, 'Role detail endpoint should return 200');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($role->id, $data['id']);
        $this->assertEquals('test_role', $data['slug']);
    }
}
