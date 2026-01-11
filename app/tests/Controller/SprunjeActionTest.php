<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Sprunje Action Integration Test
 *
 * Tests GET /api/crud6/{model} endpoint for listing records with filtering, sorting, and pagination.
 * 
 * Features tested:
 * - Authentication and authorization
 * - Pagination (size, page)
 * - Sorting (sorts parameter)
 * - Filtering (filters parameter)
 * - Response format (Sprunje format)
 * - Column visibility (listable fields)
 */
class SprunjeActionTest extends CRUD6TestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->seedDatabase();
        $this->startApiTracking();
    }

    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    /**
     * Test GET /api/crud6/users requires authentication
     */
    public function testListRequiresAuthentication(): void
    {
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/users requires permission
     */
    public function testListRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/users returns paginated list
     */
    public function testListUsersReturnsPaginatedData(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create test users
        User::factory()->count(15)->create();

        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=0');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        $this->assertJsonStructure([
            'count',
            'count_filtered',
            'rows',
            'listable',
            'sortable',
            'filterable',
        ], $response);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertCount(10, $body['rows'], 'Should return 10 rows per page');
        $this->assertGreaterThanOrEqual(15, $body['count'], 'Total count should include all users');
    }

    /**
     * Test pagination page parameter
     */
    public function testListUsersPaginationWorks(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create test users
        User::factory()->count(25)->create();

        // Get page 0
        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=0');
        $response = $this->handleRequestWithTracking($request);
        $body1 = json_decode((string) $response->getBody(), true);
        
        // Get page 1
        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=1');
        $response = $this->handleRequestWithTracking($request);
        $body2 = json_decode((string) $response->getBody(), true);

        // Verify different rows returned
        $this->assertNotEquals($body1['rows'][0]['id'], $body2['rows'][0]['id'], 'Pages should return different data');
    }

    /**
     * Test sorting users by field
     */
    public function testListUsersSortingWorks(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create users with specific names
        User::factory()->create(['user_name' => 'zebra']);
        User::factory()->create(['user_name' => 'apple']);
        User::factory()->create(['user_name' => 'banana']);

        // Sort ascending by user_name
        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=0&sorts[user_name]=asc');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Find our test users in the results
        $userNames = array_column($body['rows'], 'user_name');
        
        // Verify apple comes before banana
        $appleIndex = array_search('apple', $userNames);
        $bananaIndex = array_search('banana', $userNames);
        $zebraIndex = array_search('zebra', $userNames);
        
        if ($appleIndex !== false && $bananaIndex !== false) {
            $this->assertLessThan($bananaIndex, $appleIndex, 'apple should come before banana');
        }
        
        if ($bananaIndex !== false && $zebraIndex !== false) {
            $this->assertLessThan($zebraIndex, $bananaIndex, 'banana should come before zebra');
        }
    }

    /**
     * Test filtering users
     */
    public function testListUsersFilteringWorks(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create users with specific attributes
        User::factory()->create([
            'user_name' => 'test_user_1',
            'flag_enabled' => true,
        ]);
        User::factory()->create([
            'user_name' => 'test_user_2',
            'flag_enabled' => false,
        ]);

        // Filter for enabled users only
        $request = $this->createJsonRequest('GET', '/api/crud6/users?filters[flag_enabled]=1');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Verify all returned users are enabled
        foreach ($body['rows'] as $row) {
            if (isset($row['flag_enabled'])) {
                $this->assertTrue((bool) $row['flag_enabled'], 'All filtered users should be enabled');
            }
        }
    }

    /**
     * Test search functionality
     */
    public function testListUsersSearchWorks(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create users with specific names
        User::factory()->create(['user_name' => 'searchable_user']);
        User::factory()->create(['user_name' => 'another_user']);

        // Search for "searchable"
        $request = $this->createJsonRequest('GET', '/api/crud6/users?filters[user_name]=searchable');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Verify searchable_user is in results
        $userNames = array_column($body['rows'], 'user_name');
        $this->assertContains('searchable_user', $userNames, 'Search should find the searchable user');
    }

    /**
     * Test empty result set
     */
    public function testListUsersReturnsEmptyForNoMatches(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Filter for impossible condition
        $request = $this->createJsonRequest('GET', '/api/crud6/users?filters[user_name]=nonexistent_user_xyz');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(0, $body['count_filtered'], 'Filtered count should be 0');
        $this->assertEmpty($body['rows'], 'Rows should be empty');
    }

    /**
     * Test response contains metadata
     */
    public function testListUsersContainsMetadata(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=0');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Verify Sprunje metadata is present
        $this->assertArrayHasKey('count', $body, 'Should include total count');
        $this->assertArrayHasKey('count_filtered', $body, 'Should include filtered count');
        $this->assertArrayHasKey('rows', $body, 'Should include rows array');
        $this->assertArrayHasKey('listable', $body, 'Should include listable columns');
        $this->assertArrayHasKey('sortable', $body, 'Should include sortable columns');
        $this->assertArrayHasKey('filterable', $body, 'Should include filterable columns');
    }

    /**
     * Test only listable fields are returned
     */
    public function testListUsersReturnsOnlyListableFields(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        User::factory()->create();

        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=10&page=0');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        if (!empty($body['rows'])) {
            $firstRow = $body['rows'][0];
            
            // Password should NOT be in listable fields
            $this->assertArrayNotHasKey('password', $firstRow, 'Password should not be in list view');
            
            // But user_name should be
            $this->assertArrayHasKey('user_name', $firstRow, 'user_name should be in list view');
        }
    }
}
