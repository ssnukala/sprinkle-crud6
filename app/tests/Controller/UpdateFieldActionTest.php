<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Update Field Action Integration Test
 *
 * Tests PUT /api/crud6/{model}/{id}/{field} endpoint for updating single fields.
 * 
 * Features tested:
 * - Authentication and authorization
 * - Boolean field updates (toggle functionality)
 * - Fields without validation rules
 * - Readonly field protection
 * - Non-existent field protection
 * - Response format
 */
class UpdateFieldActionTest extends CRUD6TestCase
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
     * Test PUT /api/crud6/users/{id}/{field} requires authentication
     */
    public function testUpdateFieldRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/first_name", [
            'first_name' => 'NewName',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test PUT /api/crud6/users/{id}/{field} requires permission
     */
    public function testUpdateFieldRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/first_name", [
            'first_name' => 'NewName',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse("Access Denied", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test that boolean fields without validation rules are updated correctly.
     * 
     * This is the core fix - when a boolean field like 'flag_enabled' has no
     * validation rules in the schema, the RequestDataTransformer might skip it,
     * but UpdateFieldAction should still update it.
     */
    public function testBooleanFieldWithoutValidationRulesIsUpdated(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'flag_enabled' => true,
        ]);

        // Toggle to false
        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/flag_enabled", [
            'flag_enabled' => false,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        // Verify in database
        $testUser->refresh();
        $this->assertFalse($testUser->flag_enabled, 'Boolean field should be updated to false');

        // Toggle back to true
        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/flag_enabled", [
            'flag_enabled' => true,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        $testUser->refresh();
        $this->assertTrue($testUser->flag_enabled, 'Boolean field should be updated to true');
    }

    /**
     * Test updating a text field works correctly
     */
    public function testUpdateTextFieldSuccess(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'first_name' => 'Original',
        ]);

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/first_name", [
            'first_name' => 'Updated',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        // Verify in database
        $testUser->refresh();
        $this->assertEquals('Updated', $testUser->first_name);
    }

    /**
     * Test that non-existent field is rejected
     */
    public function testRejectsUpdateToNonExistentField(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/nonexistent_field", [
            'nonexistent_field' => 'value',
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return error (500 or 400)
        $this->assertContains($response->getStatusCode(), [400, 500],
            'Non-existent field should be rejected');
    }

    /**
     * Test that readonly fields cannot be updated
     */
    public function testRejectsUpdateToReadonlyField(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();

        // Try to update 'id' which should be readonly
        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/id", [
            'id' => 99999,
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return error
        $this->assertContains($response->getStatusCode(), [400, 500],
            'Readonly field should be rejected');
    }

    /**
     * Test updating non-existent user returns 404
     */
    public function testUpdateFieldNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        $request = $this->createJsonRequest('PUT', '/api/crud6/users/999999/first_name', [
            'first_name' => 'Test',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test updating flag_verified boolean field
     */
    public function testUpdateFlagVerifiedField(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create([
            'flag_verified' => false,
        ]);

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/flag_verified", [
            'flag_verified' => true,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        $testUser->refresh();
        $this->assertTrue($testUser->flag_verified);
    }
}

