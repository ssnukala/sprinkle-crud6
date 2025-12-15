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
 * Redundant API Calls Integration Test.
 *
 * Tests to detect and flag redundant schema API calls and other CRUD6 API calls
 * during integration testing. This helps identify performance issues and unnecessary
 * API requests that could be optimized.
 *
 * Features tested:
 * - Detection of duplicate schema API calls to /api/crud6/{model}/schema
 * - Detection of redundant CRUD6 API calls to any /api/crud6/* endpoint
 * - Detailed reporting of when and where redundant calls occur
 * - Call stack traces for debugging
 */
class RedundantApiCallsTest extends CRUD6TestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    /**
     * Setup test database and API tracking
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->seedDatabase();
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
     * Test that a single API call to list groups doesn't create redundant calls
     */
    public function testSingleListCallNoRedundantCalls(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        Group::factory()->count(3)->create();


        // Make a single API call
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Check for redundant calls
        $summary = $this->getApiCallSummary();
        $this->assertEquals(1, $summary['total'], 'Should have exactly 1 API call');
        $this->assertEquals(1, $summary['unique'], 'Should have 1 unique API call');
        $this->assertEquals(0, $summary['redundant'], 'Should have 0 redundant calls');

        // Assert no redundant calls
        $this->assertNoRedundantApiCalls();
    }

    /**
     * Test that schema calls are tracked correctly
     */
    public function testSchemaCallTracking(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);


        // Make a schema API call (this endpoint doesn't exist yet, but we're testing tracking)
        // Note: This will return 404 but we're testing the tracking, not the endpoint
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/schema');
        $response = $this->handleRequestWithTracking($request);

        // Check that schema call was tracked
        $schemaCalls = $this->getSchemaApiCalls();
        $this->assertCount(1, $schemaCalls, 'Should track 1 schema call');
        $this->assertStringContainsString('/schema', $schemaCalls[0]['uri']);
    }

    /**
     * Test detection of redundant calls when making duplicate requests
     */
    public function testDetectsRedundantApiCalls(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        $group = Group::factory()->create();


        // Make the same API call twice
        $uri = '/api/crud6/groups/' . $group->id;
        
        $request1 = $this->createJsonRequest('GET', $uri);
        $response1 = $this->handleRequestWithTracking($request1);
        $this->assertResponseStatus(200, $response1);

        $request2 = $this->createJsonRequest('GET', $uri);
        $response2 = $this->handleRequestWithTracking($request2);
        $this->assertResponseStatus(200, $response2);

        // Check summary
        $summary = $this->getApiCallSummary();
        $this->assertEquals(2, $summary['total'], 'Should have 2 total API calls');
        $this->assertEquals(1, $summary['unique'], 'Should have 1 unique API call');
        $this->assertEquals(1, $summary['redundant'], 'Should detect 1 redundant call group');

        // Get redundant calls
        $redundantCalls = $this->getRedundantApiCalls();
        $this->assertNotEmpty($redundantCalls, 'Should detect redundant calls');

        // Verify the redundant call details
        $redundantKey = array_key_first($redundantCalls);
        $this->assertEquals(2, $redundantCalls[$redundantKey]['count'], 'Should have 2 calls to same endpoint');
    }

    /**
     * Test that assertApiCallCount works correctly
     */
    public function testAssertApiCallCount(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        Group::factory()->count(3)->create();


        // Make the API call
        $uri = '/api/crud6/groups';
        $request = $this->createJsonRequest('GET', $uri);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Assert call count
        $this->assertApiCallCount($uri, 1, 'Should have exactly 1 call to /api/crud6/groups');
    }

    /**
     * Test tracking multiple different API calls
     */
    public function testTrackingMultipleDifferentCalls(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        $group1 = Group::factory()->create();
        /** @var Group */
        $group2 = Group::factory()->create();


        // Make calls to different endpoints
        $request1 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group1->id);
        $response1 = $this->handleRequestWithTracking($request1);
        $this->assertResponseStatus(200, $response1);

        $request2 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group2->id);
        $response2 = $this->handleRequestWithTracking($request2);
        $this->assertResponseStatus(200, $response2);

        $request3 = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response3 = $this->handleRequestWithTracking($request3);
        $this->assertResponseStatus(200, $response3);

        // Check summary
        $summary = $this->getApiCallSummary();
        $this->assertEquals(3, $summary['total'], 'Should have 3 total API calls');
        $this->assertEquals(3, $summary['unique'], 'Should have 3 unique API calls');
        $this->assertEquals(0, $summary['redundant'], 'Should have 0 redundant calls');

        // Assert no redundant calls
        $this->assertNoRedundantApiCalls();
    }

    /**
     * Test that CRUD6 calls are correctly identified
     */
    public function testCRUD6CallIdentification(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        Group::factory()->count(2)->create();


        // Make CRUD6 API calls
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Check that it was identified as a CRUD6 call
        $summary = $this->getApiCallSummary();
        $this->assertEquals(1, $summary['crud6_calls'], 'Should identify as CRUD6 call');
    }

    /**
     * Test scenario: Complex workflow that might generate redundant calls
     * 
     * This test simulates a common workflow where a frontend might load:
     * 1. Group list
     * 2. Individual group details
     * 3. Related users for the group
     * 
     * It should NOT make redundant calls to the same endpoints
     */
    public function testComplexWorkflowNoRedundantCalls(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        $group = Group::factory()->create();
        
        // Create users in the group
        User::factory()->count(3)->create([
            'group_id' => $group->id,
        ]);


        // Step 1: Load groups list
        $request1 = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response1 = $this->handleRequestWithTracking($request1);
        $this->assertResponseStatus(200, $response1);

        // Step 2: Load individual group
        $request2 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id);
        $response2 = $this->handleRequestWithTracking($request2);
        $this->assertResponseStatus(200, $response2);

        // Step 3: Load related users
        $request3 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id . '/users?size=10&page=0');
        $response3 = $this->handleRequestWithTracking($request3);
        $this->assertResponseStatus(200, $response3);

        // Verify workflow
        $summary = $this->getApiCallSummary();
        $this->assertEquals(3, $summary['total'], 'Should have 3 total API calls');
        $this->assertEquals(3, $summary['unique'], 'Should have 3 unique API calls');
        
        // The key assertion: no redundant calls in this workflow
        $this->assertNoRedundantApiCalls(
            'Complex workflow should not make redundant API calls. Each step should call unique endpoints.'
        );
    }

    /**
     * Test that the tracker can be reset
     */
    public function testTrackerReset(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        Group::factory()->create();


        // Make a call
        $request = $this->createJsonRequest('GET', '/api/crud6/groups');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        $summary1 = $this->getApiCallSummary();
        $this->assertEquals(1, $summary1['total']);

        // Reset tracker
        $tracker = $this->getApiCallTracker();
        $this->assertNotNull($tracker);
        $tracker->reset();

        // Verify reset
        $summary2 = $this->getApiCallSummary();
        $this->assertEquals(0, $summary2['total'], 'Tracker should be reset to 0 calls');
    }

    /**
     * Test that redundant calls generate a useful report
     */
    public function testRedundantCallsReport(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var Group */
        $group = Group::factory()->create();


        // Make the same call multiple times
        $uri = '/api/crud6/groups/' . $group->id;
        for ($i = 0; $i < 3; $i++) {
            $request = $this->createJsonRequest('GET', $uri);
            $response = $this->handleRequestWithTracking($request);
            $this->assertResponseStatus(200, $response);
        }

        // Get the tracker and generate report
        $tracker = $this->getApiCallTracker();
        $this->assertNotNull($tracker);
        
        $report = $tracker->getRedundantCallsReport();
        
        // Verify report contains useful information
        $this->assertStringContainsString('Redundant API Calls Detected', $report);
        $this->assertStringContainsString($uri, $report);
        $this->assertStringContainsString('Called 3 times', $report);
    }
}
