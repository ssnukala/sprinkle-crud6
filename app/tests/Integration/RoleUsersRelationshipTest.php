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
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Role-Users Relationship Test
 *
 * Tests the CRUD6 API endpoint for role -> users many-to-many relationship:
 * - GET /api/crud6/roles/{id}/users (list users for a role)
 * 
 * This test was created to verify the fix for the ambiguous column 'id' issue.
 * 
 * The error occurred when UserSprunje (which joins with activities table) was used
 * with an unqualified 'id' column in the WHERE clause, causing MySQL error:
 * "Column 'id' in where clause is ambiguous"
 * 
 * The fix ensures that:
 * 1. For many-to-many relationships, the proper JOIN with pivot table is used
 * 2. For direct relationships, column names are qualified with table names
 */
class RoleUsersRelationshipTest extends CRUD6TestCase
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
     * Cleanup after each test
     */
    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    /**
     * Test GET /api/crud6/roles/{id}/users returns users for a role
     * 
     * This is the critical test that reproduces the ambiguous column error
     * reported in the issue. The error occurred because:
     * 
     * 1. roles.json schema defines a many-to-many relationship with users
     * 2. The detail config did not specify a foreign_key
     * 3. The code defaulted to using 'id' as the foreign key
     * 4. UserSprunje joins with activities table, making 'id' ambiguous
     * 
     * The fix changes the query from:
     *   WHERE id = 1  (ambiguous)
     * To:
     *   JOIN role_users ON users.id = role_users.user_id 
     *   WHERE role_users.role_id = 1  (qualified)
     */
    public function testRoleUsersNestedEndpointHandlesAmbiguousColumn(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/roles/{id}/users (ambiguous column fix)\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create a role
        /** @var Role */
        $role = Role::factory()->create([
            'slug' => 'test_role',
            'name' => 'Test Role',
        ]);

        echo sprintf("[TEST] Created role: id=%d, slug=%s\n", $role->id, $role->slug);

        // Create some users and attach to role
        $users = User::factory()->count(3)->create();
        $role->users()->attach($users);

        echo sprintf("[TEST] Attached %d users to role\n", $users->count());

        // Make request to get role's users
        // This is where the ambiguous column error would occur before the fix
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/users");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/roles/%d/users\n", $role->id);
        
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
            echo "[TEST] ✅ SUCCESS - No ambiguous column error\n";
            echo "[TEST] Response body (first 500 chars):\n";
            echo substr($body, 0, 500) . "...\n";
        }

        // Assert response status & body
        $this->assertResponseStatus(200, $response, 'Role users nested endpoint should return 200 (no ambiguous column error)');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        
        // Debug: Log the actual data structure received
        error_log("testRoleUsersNestedEndpointHandlesAmbiguousColumn - Full response data: " . json_encode($data, JSON_PRETTY_PRINT));
        error_log("testRoleUsersNestedEndpointHandlesAmbiguousColumn - Expected user IDs: " . json_encode(array_map(fn($u) => $u->id, $users->all())));
        
        // Check if it's a Sprunje response format
        if (isset($data['rows'])) {
            $this->assertArrayHasKey('rows', $data);
            $this->assertArrayHasKey('count', $data);
            
            // Debug: Log actual vs expected count
            error_log("testRoleUsersNestedEndpointHandlesAmbiguousColumn - Sprunje format: rows count = " . count($data['rows']) . ", expected = 3");
            
            $this->assertCount(3, $data['rows'], 'Should return 3 users');
            
            // Verify users are returned correctly
            $returnedUserIds = array_column($data['rows'], 'id');
            foreach ($users as $user) {
                $this->assertContains($user->id, $returnedUserIds, 'All attached users should be in response');
            }
        } else {
            // Or a simple array response
            error_log("testRoleUsersNestedEndpointHandlesAmbiguousColumn - Simple array format: count = " . count($data) . ", expected = 3");
            
            $this->assertCount(3, $data, 'Should return 3 users');
        }
        
        echo "[TEST] ✅ Test passed - ambiguous column issue is fixed\n";
    }

    /**
     * Test GET /api/crud6/roles/{id}/users with empty result
     * 
     * Tests that the query works correctly even when there are no users attached
     */
    public function testRoleUsersNestedEndpointWithNoUsers(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/roles/{id}/users with no users attached\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create a role without any users
        /** @var Role */
        $role = Role::factory()->create([
            'slug' => 'empty_role',
            'name' => 'Empty Role',
        ]);

        echo sprintf("[TEST] Created role: id=%d, slug=%s (no users attached)\n", $role->id, $role->slug);

        // Make request to get role's users (should return empty)
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/users");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/roles/%d/users\n", $role->id);
        
        $response = $this->handleRequestWithTracking($request);

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        
        // Should still return 200 with empty result
        $this->assertResponseStatus(200, $response, 'Should return 200 even with no users');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        
        // Check if it's a Sprunje response format
        if (isset($data['rows'])) {
            $this->assertArrayHasKey('rows', $data);
            $this->assertEmpty($data['rows'], 'Should return empty rows array');
            $this->assertEquals(0, $data['count_filtered'] ?? $data['count'], 'Count should be 0');
        } else {
            // Or a simple array response
            $this->assertEmpty($data, 'Should return empty array');
        }
        
        echo "[TEST] ✅ Test passed - empty result handled correctly\n";
    }

    /**
     * Test GET /api/crud6/roles/{id}/users with pagination
     * 
     * Verifies that the qualified column names work correctly with pagination
     */
    public function testRoleUsersNestedEndpointWithPagination(): void
    {
        echo "\n[TEST] Testing GET /api/crud6/roles/{id}/users with pagination\n";
        
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create a role
        /** @var Role */
        $role = Role::factory()->create([
            'slug' => 'paginated_role',
            'name' => 'Paginated Role',
        ]);

        // Create many users and attach to role
        $users = User::factory()->count(25)->create();
        $role->users()->attach($users);

        echo sprintf("[TEST] Created role with %d users\n", $users->count());

        // Request first page
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/users?size=10&page=0");
        
        echo sprintf("[TEST] Making request: GET /api/crud6/roles/%d/users?size=10&page=0\n", $role->id);
        
        $response = $this->handleRequestWithTracking($request);

        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        
        echo sprintf("[TEST] Response status: %d\n", $statusCode);
        
        $this->assertResponseStatus(200, $response, 'Pagination should work with qualified columns');
        $this->assertJson($body);
        
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        
        if (isset($data['rows'])) {
            $this->assertArrayHasKey('rows', $data);
            $this->assertCount(10, $data['rows'], 'Should return 10 rows (first page)');
            $this->assertEquals(25, $data['count'], 'Total count should be 25');
        }
        
        echo "[TEST] ✅ Test passed - pagination works with qualified columns\n";
    }
}
