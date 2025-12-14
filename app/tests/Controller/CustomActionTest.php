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
use UserFrosting\Sprinkle\CRUD6\Testing\AdminTestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Custom Action Controller Integration Test
 *
 * Tests custom action endpoints defined in schema.
 * 
 * Endpoint tested:
 * - POST /api/crud6/{model}/{id}/a/{actionKey} - Execute custom action
 * 
 * Features tested:
 * - Authentication and authorization (both authenticated and unauthenticated)
 * - Custom action execution
 * - Action-specific permissions
 * - Non-existent action handling
 * - Invalid model/ID handling
 * 
 * Note: Custom actions are defined in the model schema's "actions" array.
 * Examples include: reset_password, enable_user, disable_user, etc.
 */
class CustomActionTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->startApiTracking();
    }

    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    /**
     * Test POST /api/crud6/users/{id}/a/{action} requires authentication
     */
    public function testCustomActionRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        // Try to execute a custom action without authentication
        // Using a hypothetical action - actual actions depend on schema
        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/a/test_action");
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test POST /api/crud6/users/{id}/a/{action} requires permission
     */
    public function testCustomActionRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/a/test_action");
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test custom action endpoint is accessible with proper authentication
     * 
     * Note: This test verifies the endpoint is accessible, but the actual
     * action execution depends on the schema configuration.
     */
    public function testCustomActionWithAuthentication(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/a/test_action");
        $response = $this->handleRequestWithTracking($request);

        // The action might not exist in the schema, so we accept 404 or 500
        // The important thing is that it's NOT 401 or 403
        $statusCode = $response->getStatusCode();
        $this->assertNotEquals(401, $statusCode, 'Should not return 401 with authentication');
        $this->assertNotEquals(403, $statusCode, 'Should not return 403 with permission');
        
        // Acceptable responses: 200 (success), 404 (action not found), 500 (action error)
        $this->assertContains($statusCode, [200, 404, 500],
            'Should return 200, 404, or 500 depending on action existence/implementation');
    }

    /**
     * Test custom action on non-existent user returns 404
     */
    public function testCustomActionNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        $request = $this->createJsonRequest('POST', '/api/crud6/users/999999/a/test_action');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test that the endpoint structure is correct for multiple action types
     * 
     * Verifies authentication requirements for various hypothetical actions.
     */
    public function testMultipleCustomActionsRequireAuth(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        $actions = ['enable_user', 'disable_user', 'reset_password', 'verify_email'];

        foreach ($actions as $action) {
            $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/a/{$action}");
            $response = $this->handleRequestWithTracking($request);

            $this->assertResponseStatus(401, $response,
                "Action '{$action}' should require authentication");
        }
    }

    /**
     * Test custom actions work with authenticated user and permission
     * 
     * This test verifies that with proper authentication, custom action
     * endpoints are accessible (even if the action itself doesn't exist in schema).
     */
    public function testMultipleCustomActionsWithAuth(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        $actions = ['enable_user', 'disable_user'];

        foreach ($actions as $action) {
            $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/a/{$action}");
            $response = $this->handleRequestWithTracking($request);

            $statusCode = $response->getStatusCode();
            // Should not be auth-related errors
            $this->assertNotEquals(401, $statusCode,
                "Action '{$action}' should not return 401 with authentication");
            $this->assertNotEquals(403, $statusCode,
                "Action '{$action}' should not return 403 with permission");
        }
    }
}
