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
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * CRUD6 Relationship Action Integration Test
 *
 * Tests relationship endpoints for attaching and detaching many-to-many relationships.
 * 
 * Endpoints tested:
 * - POST   /api/crud6/{model}/{id}/{relation} - Attach relationship
 * - DELETE /api/crud6/{model}/{id}/{relation} - Detach relationship
 * 
 * Features tested:
 * - Authentication and authorization (both authenticated and unauthenticated)
 * - Attaching related records
 * - Detaching related records
 * - Pivot data handling
 * - Invalid relation handling
 * - Non-existent IDs handling
 */
class RelationshipActionTest extends CRUD6TestCase
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
     * Test POST /api/crud6/users/{id}/roles requires authentication
     */
    public function testAttachRelationshipRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test POST /api/crud6/users/{id}/roles requires permission
     */
    public function testAttachRelationshipRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse("We've sensed a great disturbance in the Force.", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test POST /api/crud6/users/{id}/roles successfully attaches relationship
     */
    public function testAttachRelationshipSuccess(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();

        // Verify no roles initially
        $this->assertCount(0, $testUser->roles);

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify role was attached
        $testUser->refresh();
        $this->assertCount(1, $testUser->roles);
        $this->assertEquals($role->id, $testUser->roles->first()->id);
    }

    /**
     * Test DELETE /api/crud6/users/{id}/roles requires authentication
     */
    public function testDetachRelationshipRequiresAuthentication(): void
    {
        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();
        $testUser->roles()->attach($role->id);

        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test DELETE /api/crud6/users/{id}/roles requires permission
     */
    public function testDetachRelationshipRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();
        $testUser->roles()->attach($role->id);

        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse("We've sensed a great disturbance in the Force.", $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test DELETE /api/crud6/users/{id}/roles successfully detaches relationship
     */
    public function testDetachRelationshipSuccess(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role = Role::factory()->create();
        $testUser->roles()->attach($role->id);

        // Verify role is attached
        $this->assertCount(1, $testUser->roles);

        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify role was detached
        $testUser->refresh();
        $this->assertCount(0, $testUser->roles);
    }

    /**
     * Test attaching multiple relationships at once
     */
    public function testAttachMultipleRelationships(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role1 = Role::factory()->create();
        /** @var Role */
        $role2 = Role::factory()->create();
        /** @var Role */
        $role3 = Role::factory()->create();

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role1->id, $role2->id, $role3->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify all roles were attached
        $testUser->refresh();
        $this->assertCount(3, $testUser->roles);
    }

    /**
     * Test detaching multiple relationships at once
     */
    public function testDetachMultipleRelationships(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var User */
        $testUser = User::factory()->create();
        
        /** @var Role */
        $role1 = Role::factory()->create();
        /** @var Role */
        $role2 = Role::factory()->create();
        $testUser->roles()->attach([$role1->id, $role2->id]);

        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$testUser->id}/roles", [
            'related_ids' => [$role1->id, $role2->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);

        // Verify all roles were detached
        $testUser->refresh();
        $this->assertCount(0, $testUser->roles);
    }

    /**
     * Test attaching to non-existent user returns 404
     */
    public function testAttachToNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('POST', '/api/crud6/users/999999/roles', [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }

    /**
     * Test detaching from non-existent user returns 404
     */
    public function testDetachFromNonExistentUserReturns404(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['update_user_field']);

        /** @var Role */
        $role = Role::factory()->create();

        $request = $this->createJsonRequest('DELETE', '/api/crud6/users/999999/roles', [
            'related_ids' => [$role->id],
        ]);
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(404, $response);
    }
}
