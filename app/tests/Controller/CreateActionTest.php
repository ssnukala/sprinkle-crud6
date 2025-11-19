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
 * CRUD6 Create Action Integration Test
 *
 * Tests POST /api/crud6/{model} endpoint for creating new records.
 * 
 * Features tested:
 * - Authentication and authorization
 * - Field validation
 * - Data transformation
 * - Database insertion
 * - Relationship handling (on_create actions)
 * - Response format
 */
class CreateActionTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;
    use TracksApiCalls;

    /**
     * Setup test database
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->startApiTracking();
    }

    /**
     * Cleanup after test
     */
    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }

    /**
     * Test POST /api/crud6/users requires authentication
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test POST /api/crud6/users requires permission
     */
    public function testCreateRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test POST /api/crud6/users creates a new user
     */
    public function testCreateUserSuccess(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_user']);

        $userData = [
            'user_name' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'TestPassword123',
        ];

        $request = $this->createJsonRequest('POST', '/api/crud6/users', $userData);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('data', $body);
        
        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'user_name' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    /**
     * Test validation errors are returned
     */
    public function testCreateUserWithValidationErrors(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_user']);

        // Missing required fields
        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'newuser',
            // Missing first_name, last_name, email
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return validation error
        $this->assertResponseStatus(400, $response);
        
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('description', $body);
    }

    /**
     * Test duplicate values are rejected (unique validation)
     */
    public function testCreateUserWithDuplicateUsername(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_user']);

        /** @var User */
        $existingUser = User::factory()->create([
            'user_name' => 'existing_user',
        ]);

        // Try to create user with same username
        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'existing_user',  // Duplicate
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'different@example.com',
            'password' => 'TestPassword123',
        ]);
        $response = $this->handleRequestWithTracking($request);

        // Should return validation error
        $this->assertResponseStatus(400, $response);
    }

    /**
     * Test password field is hashed before saving
     */
    public function testCreateUserHashesPassword(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_user']);

        $plainPassword = 'TestPassword123';
        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => $plainPassword,
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        // Verify password was hashed (not stored as plain text)
        $newUser = User::where('user_name', 'newuser')->first();
        $this->assertNotNull($newUser);
        $this->assertNotEquals($plainPassword, $newUser->password);
        $this->assertStringStartsWith('$', $newUser->password); // Bcrypt hash starts with $
    }

    /**
     * Test default values are applied from schema
     */
    public function testCreateUserAppliesDefaultValues(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_user']);

        // Don't specify flag_enabled or flag_verified (should use defaults from schema)
        $request = $this->createJsonRequest('POST', '/api/crud6/users', [
            'user_name' => 'newuser',
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'TestPassword123',
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        
        // Verify defaults were applied (both should be true per schema)
        $newUser = User::where('user_name', 'newuser')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue((bool) $newUser->flag_enabled, 'Default flag_enabled should be true');
        $this->assertTrue((bool) $newUser->flag_verified, 'Default flag_verified should be true');
    }
}
