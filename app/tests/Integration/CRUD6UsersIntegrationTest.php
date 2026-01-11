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
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Users Integration Test
 *
 * Tests the CRUD6 API endpoints for users model:
 * - GET /api/crud6/users (list all users)
 * - GET /api/crud6/users/{id} (get single user)
 * - PUT /api/crud6/users/{id}/flag_enabled (toggle enabled status)
 * - PUT /api/crud6/users/{id}/flag_verified (toggle verified status)
 * 
 * Also verifies that frontend routes are accessible:
 * - /crud6/users (list page)
 * - /crud6/users/{id} (detail page)
 * 
 * Includes API call tracking to detect redundant calls.
 */
class CRUD6UsersIntegrationTest extends CRUD6TestCase
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
     * Test GET /api/crud6/users returns 401 for guest users
     */
    public function testUsersListApiRequiresAuthentication(): void
    {
        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/users returns 403 for users without permission
     */
    public function testUsersListApiRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/users returns list of users for authorized users
     */
    public function testUsersListApiReturnsUsers(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create some test users
        User::factory()->count(3)->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        $body = (string) $response->getBody();
        $this->assertNotSame('[]', $body, 'Should return users data');
    }

    /**
     * Test GET /api/crud6/users/{id} returns 401 for guest users
     */
    public function testSingleUserApiRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/users/{id} returns 403 for users without permission
     */
    public function testSingleUserApiRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        /** @var User */
        $testUser = User::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/users/{id} returns user data for authorized users
     */
    public function testSingleUserApiReturnsUser(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        /** @var User */
        $testUser = User::factory()->create([
            'user_name' => 'test_user',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        // Verify response contains user data
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('id', $body, 'Response should contain id');
        $this->assertArrayHasKey('user_name', $body, 'Response should contain user_name');
        $this->assertArrayHasKey('first_name', $body, 'Response should contain first_name');
        $this->assertArrayHasKey('last_name', $body, 'Response should contain last_name');
        $this->assertArrayHasKey('email', $body, 'Response should contain email');
        $this->assertEquals($testUser->id, $body['id'], 'ID should match');
        $this->assertEquals('test_user', $body['user_name'], 'Username should match');
        $this->assertEquals('Test', $body['first_name'], 'First name should match');
        $this->assertEquals('User', $body['last_name'], 'Last name should match');
        $this->assertEquals('test@example.com', $body['email'], 'Email should match');
    }

    /**
     * Test GET /api/crud6/users/999 returns 404 for non-existent user
     */
    public function testSingleUserApiReturns404ForNonExistent(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/crud6/users/999999');
        $response = $this->handleRequestWithTracking($request);

        // Assert response status
        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test PUT /api/crud6/users/{id}/flag_enabled toggles enabled status
     * 
     * This is the critical test for the boolean toggle fix.
     * Tests that the UpdateFieldAction correctly handles boolean fields
     * with no validation rules.
     */
    public function testToggleFlagEnabledUpdatesUserStatus(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'flag_enabled' => true,
        ]);

        // Verify initial state
        $this->assertTrue((bool) $testUser->flag_enabled, 'User should start as enabled');

        // Toggle to disabled
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/flag_enabled', [
            'flag_enabled' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        // Verify response contains success message
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('title', $body, 'Response should contain title');
        $this->assertArrayHasKey('data', $body, 'Response should contain data');
        
        // Verify the data contains the updated flag_enabled value
        $this->assertArrayHasKey('flag_enabled', $body['data'], 'Response data should contain flag_enabled');
        $this->assertFalse((bool) $body['data']['flag_enabled'], 'flag_enabled should be false in response');
        
        // Verify database was updated
        $testUser->refresh();
        $this->assertFalse((bool) $testUser->flag_enabled, 'User should now be disabled in database');

        // Toggle back to enabled
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/flag_enabled', [
            'flag_enabled' => true,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status
        $this->assertResponseStatus(200, $response);
        
        // Verify database was updated again
        $testUser->refresh();
        $this->assertTrue((bool) $testUser->flag_enabled, 'User should be enabled again in database');
    }

    /**
     * Test PUT /api/crud6/users/{id}/flag_verified toggles verified status
     * 
     * Similar to flag_enabled test, verifies boolean toggle works for flag_verified.
     */
    public function testToggleFlagVerifiedUpdatesUserStatus(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'flag_verified' => true,
        ]);

        // Verify initial state
        $this->assertTrue((bool) $testUser->flag_verified, 'User should start as verified');

        // Toggle to unverified
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/flag_verified', [
            'flag_verified' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());
        
        // Verify response contains the updated flag_verified value
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body, 'Response should contain data');
        $this->assertArrayHasKey('flag_verified', $body['data'], 'Response data should contain flag_verified');
        $this->assertFalse((bool) $body['data']['flag_verified'], 'flag_verified should be false in response');
        
        // Verify database was updated
        $testUser->refresh();
        $this->assertFalse((bool) $testUser->flag_verified, 'User should now be unverified in database');
    }

    /**
     * Test PUT /api/crud6/users/{id}/nonexistent returns error
     * 
     * Verifies that UpdateFieldAction rejects updates to non-existent fields.
     */
    public function testUpdateNonExistentFieldReturnsError(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        // Try to update a non-existent field
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/nonexistent_field', [
            'nonexistent_field' => 'value',
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return an error (500)
        $this->assertResponseStatus(500, $response);
        
        // Verify error message mentions the field doesn't exist
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('description', $body, 'Response should contain description');
        $this->assertStringContainsString('nonexistent_field', $body['description'], 'Error should mention the field name');
        $this->assertStringContainsString('does not exist', $body['description'], 'Error should say field does not exist');
    }

    /**
     * Test PUT /api/crud6/users/{id}/id returns error for readonly field
     * 
     * Verifies that UpdateFieldAction rejects updates to readonly fields like 'id'.
     */
    public function testUpdateReadonlyFieldReturnsError(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        // Try to update the id field (which should be readonly)
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/id', [
            'id' => 999999,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return an error (500)
        $this->assertResponseStatus(500, $response);
        
        // Verify error message mentions readonly
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, 'Response should be an array');
        $this->assertArrayHasKey('description', $body, 'Response should contain description');
        // Error should mention either "readonly" or "not editable"
        $errorMessage = strtolower($body['description']);
        $this->assertTrue(
            str_contains($errorMessage, 'readonly') || str_contains($errorMessage, 'not editable'),
            'Error should mention field is readonly or not editable'
        );
    }

    /**
     * Test PUT /api/crud6/users/{id}/flag_enabled requires authentication
     */
    public function testToggleFlagEnabledRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create(['flag_enabled' => true]);

        // Try to toggle without authentication
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/flag_enabled', [
            'flag_enabled' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should require authentication
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
        
        // Verify database was NOT updated
        $testUser->refresh();
        $this->assertTrue((bool) $testUser->flag_enabled, 'User should still be enabled');
    }

    /**
     * Test PUT /api/crud6/users/{id}/flag_enabled requires permission
     */
    public function testToggleFlagEnabledRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create(['flag_enabled' => true]);

        // Try to toggle without permission
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id . '/flag_enabled', [
            'flag_enabled' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should require permission
        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
        
        // Verify database was NOT updated
        $testUser->refresh();
        $this->assertTrue((bool) $testUser->flag_enabled, 'User should still be enabled');
    }

    /**
     * Test frontend route /crud6/users is accessible (may redirect to login)
     */
    public function testFrontendUsersListRouteExists(): void
    {
        $this->markTestSkipped('Frontend routes not implemented yet - API-only functionality');
        
        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/crud6/users');
        $response = $this->handleRequestWithTracking($request);

        // Should either return the page (200) or redirect to login (302)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [200, 302, 401]),
            "Frontend route should exist and return 200, 302, or 401, got {$statusCode}"
        );
    }

    /**
     * Test frontend route /crud6/users/{id} is accessible (may redirect to login)
     */
    public function testFrontendSingleUserRouteExists(): void
    {
        $this->markTestSkipped('Frontend routes not implemented yet - API-only functionality');
        
        /** @var User */
        $testUser = User::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        // Should either return the page (200) or redirect to login (302)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [200, 302, 401]),
            "Frontend route should exist and return 200, 302, or 401, got {$statusCode}"
        );
    }
}
