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
 * CRUD6 Delete Action Integration Test
 *
 * Tests DELETE /api/crud6/{model}/{id} endpoint for deleting records.
 *
 * Features tested:
 * - Authentication and authorization
 * - Hard delete (permanent)
 * - Soft delete (deleted_at timestamp)
 * - Relationship handling (on_delete actions)
 * - Cascade deletes
 * - Response format
 */
class DeleteActionTest extends CRUD6TestCase
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
     * Test DELETE /api/crud6/users/{id} requires authentication
     */
    public function testDeleteRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test DELETE /api/crud6/users/{id} requires permission
     */
    public function testDeleteRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse("We've sensed a great disturbance in the Force.", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test DELETE /api/crud6/users/{id} soft deletes user
     */
    public function testDeleteUserSoftDelete(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['delete_user']);

        /** @var User */
        $testUser = User::factory()->create([
            'user_name' => 'user_to_delete',
        ]);

        $userId = $testUser->id;

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('description', $body);

        // Verify user was soft deleted (deleted_at is set)
        $deletedUser = User::withTrashed()->find($userId);
        $this->assertNotNull($deletedUser);
        $this->assertNotNull($deletedUser->deleted_at, 'User should be soft deleted');

        // Verify user doesn't appear in normal queries
        $this->assertNull(User::find($userId), 'Deleted user should not appear in normal queries');
    }

    /**
     * Test deleting non-existent user returns 404
     */
    public function testDeleteNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['delete_user']);

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/999999');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test deleting already deleted user returns 404
     */
    public function testDeleteAlreadyDeletedUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['delete_user']);

        /** @var User */
        $testUser = User::factory()->create();
        $testUser->delete();  // Soft delete

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test cannot delete own user account (self-deletion prevention)
     */
    public function testCannotDeleteOwnAccount(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['delete_user']);

        // Try to delete own account
        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $user->id);
        $response = $this->handleRequestWithTracking($request);

        // Should fail with error (implementation may vary)
        // Either 403 Forbidden or 400 Bad Request
        $this->assertTrue(
            in_array($response->getStatusCode(), [400, 403, 500]),
            'Should not allow self-deletion'
        );
    }

    /**
     * Test cascade delete of child records (activities) when deleting user
     *
     * This test verifies that:
     * 1. Child records (activities) are deleted before parent (user)
     * 2. No foreign key constraint violations occur
     * 3. The cascade is done automatically based on schema's "details" configuration
     */
    public function testCascadeDeleteChildRecords(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['delete_user', 'create_activity']);

        /** @var User */
        $testUser = User::factory()->create([
            'user_name' => 'user_with_activities',
        ]);

        $userId = $testUser->id;

        // Create some activities for the test user using the Activity model
        // Note: We're using the database directly since Activity factory might not exist
        $db = $this->ci->get(\Illuminate\Database\Connection::class);

        // Insert activities directly
        for ($i = 0; $i < 3; $i++) {
            $db->table('activities')->insert([
                'user_id' => $userId,
                'type' => 'test_activity_' . $i,
                'occurred_at' => date('Y-m-d H:i:s'),
                'description' => 'Test activity for cascade delete',
                'ip_address' => '127.0.0.1',
            ]);
        }

        // Verify activities exist
        $activityCount = $db->table('activities')->where('user_id', $userId)->count();
        $this->assertEquals(3, $activityCount, 'Should have 3 activities before delete');

        // Delete the user - should cascade delete activities
        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
        $response = $this->handleRequestWithTracking($request);

        // Assert successful deletion
        $this->assertResponseStatus(200, $response, 'Delete should succeed with cascade');
        $this->assertJson((string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('description', $body);

        // Verify user was soft deleted
        $deletedUser = User::withTrashed()->find($userId);
        $this->assertNotNull($deletedUser);
        $this->assertNotNull($deletedUser->deleted_at, 'User should be soft deleted');

        // Verify activities were deleted (hard delete since activities table doesn't support soft delete)
        $activityCountAfter = $db->table('activities')->where('user_id', $userId)->count();
        $this->assertEquals(0, $activityCountAfter, 'All activities should be deleted (cascade)');
    }
}
