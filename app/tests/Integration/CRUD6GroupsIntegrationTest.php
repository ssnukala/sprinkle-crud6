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
use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Groups Integration Test
 *
 * Tests the CRUD6 API endpoints for groups model:
 * - GET /api/crud6/groups (list all groups)
 * - GET /api/crud6/groups/1 (get single group)
 * 
 * Also verifies that frontend routes are accessible:
 * - /crud6/groups (list page)
 * - /crud6/groups/1 (detail page)
 * 
 * Now includes API call tracking to detect redundant calls.
 */
class CRUD6GroupsIntegrationTest extends CRUD6TestCase
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
                echo "API Call Tracking Summary for " . $this->getName() . "\n";
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
     * Test GET /api/crud6/groups returns 401 for guest users
     */
    public function testGroupsListApiRequiresAuthentication(): void
    {
        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/groups returns 403 for users without permission
     */
    public function testGroupsListApiRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/groups returns list of groups for authorized users
     */
    public function testGroupsListApiReturnsGroups(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        Group::factory()->count(3)->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        $body = (string) $response->getBody();
        $this->assertNotSame('[]', $body, 'Should return groups data');
    }

    /**
     * Test GET /api/crud6/groups/1 returns 401 for guest users
     */
    public function testSingleGroupApiRequiresAuthentication(): void
    {
        /** @var Group */
        $group = Group::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/groups/1 returns 403 for users without permission
     */
    public function testSingleGroupApiRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        /** @var Group */
        $group = Group::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/groups/1 returns group data for authorized users
     */
    public function testSingleGroupApiReturnsGroup(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        $group = Group::factory()->create([
            'slug' => 'test-group',
            'name' => 'Test Group',
        ]);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        // Verify response contains group data
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('id', $body, 'Response should contain id');
        $this->assertArrayHasKey('slug', $body, 'Response should contain slug');
        $this->assertArrayHasKey('name', $body, 'Response should contain name');
        $this->assertEquals($group->id, $body['id'], 'ID should match');
        $this->assertEquals('test-group', $body['slug'], 'Slug should match');
        $this->assertEquals('Test Group', $body['name'], 'Name should match');
    }

    /**
     * Test GET /api/crud6/groups/999 returns 404 for non-existent group
     */
    public function testSingleGroupApiReturns404ForNonExistent(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/999999');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status
        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test frontend route /crud6/groups is accessible (may redirect to login)
     * 
     * Note: This test verifies the route exists but doesn't test the full
     * authenticated experience, which requires frontend asset building.
     */
    public function testFrontendGroupsListRouteExists(): void
    {
        $this->markTestSkipped('Frontend routes not implemented yet - API-only functionality');
        
        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/crud6/groups');
        $response = $this->handleRequestWithTracking($request);

        // Should either return the page (200) or redirect to login (302)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [200, 302, 401]),
            "Frontend route should exist and return 200, 302, or 401, got {$statusCode}"
        );
    }

    /**
     * Test frontend route /crud6/groups/1 is accessible (may redirect to login)
     * 
     * Note: This test verifies the route exists but doesn't test the full
     * authenticated experience, which requires frontend asset building.
     */
    public function testFrontendSingleGroupRouteExists(): void
    {
        $this->markTestSkipped('Frontend routes not implemented yet - API-only functionality');
        
        /** @var Group */
        $group = Group::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/crud6/groups/' . $group->id);
        $response = $this->handleRequestWithTracking($request);

        // Should either return the page (200) or redirect to login (302)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [200, 302, 401]),
            "Frontend route should exist and return 200, 302, or 401, got {$statusCode}"
        );
    }

    /**
     * Test GET /api/crud6/groups/{id}/users returns 401 for guest users
     */
    public function testGroupUsersApiRequiresAuthentication(): void
    {
        /** @var Group */
        $group = Group::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id . '/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/groups/{id}/users returns 403 for users without permission
     */
    public function testGroupUsersApiRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        /** @var Group */
        $group = Group::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id . '/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/groups/{id}/users returns list of users for authorized users
     */
    public function testGroupUsersApiReturnsUsers(): void
    {
        /** @var Group */
        $group = Group::factory()->create();

        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create some users in the group
        User::factory()->count(3)->create([
            'group_id' => $group->id,
        ]);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id . '/users?size=10&page=0');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJsonStructure([
            'count',
            'count_filtered',
            'rows',
            'listable',
            'sortable',
            'filterable',
        ], $response);

        // Verify the response contains users from the group
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('rows', $body, 'Response should contain rows');
        
        // Debug: Log the actual data returned
        error_log("testGroupUsersApiReturnsUsers - Full response: " . json_encode($body, JSON_PRETTY_PRINT));
        error_log("testGroupUsersApiReturnsUsers - Rows count: " . count($body['rows']) . ", expected: 3");
        if (isset($body['rows']) && count($body['rows']) > 0) {
            error_log("testGroupUsersApiReturnsUsers - First row: " . json_encode($body['rows'][0], JSON_PRETTY_PRINT));
        }
        
        $this->assertCount(3, $body['rows'], 'Should return 3 users from the group');
    }

    /**
     * Test GET /api/crud6/groups/999/users returns 404 for non-existent group
     */
    public function testGroupUsersApiReturns404ForNonExistent(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/999999/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status
        $this->assertResponseStatus(404, $response);
    }
}
