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
use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Edit/Update Action Integration Test
 *
 * Tests PUT /api/crud6/{model}/{id} endpoint for updating existing records.
 * 
 * Features tested:
 * - Authentication and authorization
 * - Field validation
 * - Data transformation
 * - Database updates
 * - Partial updates (only changed fields)
 * - Relationship handling (on_update actions)
 * - Response format
 */
class EditActionTest extends AdminTestCase
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
     * Test PUT /api/crud6/users/{id} requires authentication
     */
    public function testUpdateRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test PUT /api/crud6/users/{id} requires permission
     */
    public function testUpdateRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test PUT /api/crud6/users/{id} updates user successfully
     */
    public function testUpdateUserSuccess(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);

        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'first_name' => 'Updated',
            'last_name' => 'NewName',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());

        // Verify database was updated
        $testUser->refresh();
        $this->assertEquals('Updated', $testUser->first_name);
        $this->assertEquals('NewName', $testUser->last_name);
        $this->assertEquals('original@example.com', $testUser->email); // Unchanged
    }

    /**
     * Test partial update (only some fields)
     */
    public function testPartialUpdateOnlyChangesSpecifiedFields(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);

        // Only update first_name
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify only first_name changed
        $testUser->refresh();
        $this->assertEquals('Updated', $testUser->first_name);
        $this->assertEquals('Name', $testUser->last_name); // Unchanged
        $this->assertEquals('original@example.com', $testUser->email); // Unchanged
    }

    /**
     * Test validation errors are returned
     */
    public function testUpdateUserWithValidationErrors(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        // Invalid email format
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'email' => 'not-an-email',
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return validation error
        $this->assertResponseStatus(400, $response);
    }

    /**
     * Test updating to duplicate username is rejected
     */
    public function testUpdateUserWithDuplicateUsernameRejected(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $existingUser = User::factory()->create([
            'user_name' => 'existing_user',
        ]);

        /** @var User */
        $testUser = User::factory()->create([
            'user_name' => 'test_user',
        ]);

        // Try to update to existing username
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'user_name' => 'existing_user',  // Duplicate
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return validation error
        $this->assertResponseStatus(400, $response);
    }

    /**
     * Test password field is hashed when updated
     */
    public function testUpdatePasswordIsHashed(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        $originalPasswordHash = $testUser->password;

        $newPlainPassword = 'NewPassword123';
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'password' => $newPlainPassword,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify password was hashed and changed
        $testUser->refresh();
        $this->assertNotEquals($originalPasswordHash, $testUser->password);
        $this->assertNotEquals($newPlainPassword, $testUser->password);
        $this->assertStringStartsWith('$', $testUser->password); // Bcrypt hash
    }

    /**
     * Test updating non-existent user returns 404
     */
    public function testUpdateNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        $request = $this->createJsonRequest('PUT', '/api/crud6/users/999999', [
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test readonly fields cannot be updated
     */
    public function testReadonlyFieldsCannotBeUpdated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        $originalId = $testUser->id;

        // Try to update the id field (readonly)
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'id' => 999999,
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        // May succeed but id should not change
        // Or may fail with validation error depending on implementation
        $testUser->refresh();
        $this->assertEquals($originalId, $testUser->id, 'ID should not change');
    }

    /**
     * Test empty update request (no fields to update)
     */
    public function testEmptyUpdateRequestSucceeds(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'first_name' => 'Original',
        ]);

        // Send empty update
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, []);
        $response = $this->handleRequestWithTracking($request);

        // Should succeed with no changes
        $this->assertResponseStatus(200, $response);

        $testUser->refresh();
        $this->assertEquals('Original', $testUser->first_name);
    }

    /**
     * Test boolean fields can be updated
     */
    public function testBooleanFieldsCanBeUpdated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'flag_enabled' => true,
            'flag_verified' => true,
        ]);

        // Update both boolean fields
        $request = $this->createJsonRequest('PUT', '/api/crud6/users/' . $testUser->id, [
            'flag_enabled' => false,
            'flag_verified' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        $testUser->refresh();
        $this->assertFalse((bool) $testUser->flag_enabled);
        $this->assertFalse((bool) $testUser->flag_verified);
    }
}
