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
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Frontend User Workflow Integration Test
 *
 * @deprecated This test is deprecated in favor of schema-driven tests in SchemaBasedApiTest.
 *             The SchemaBasedApiTest class now provides generic workflow testing that works
 *             for all models based on their schema configuration, following CRUD6's principle
 *             of being schema-driven rather than hardcoded to specific models.
 * 
 * Simulates real user workflows by creating API payloads that match
 * what the frontend would send when a user performs actions.
 * 
 * This test addresses the issue where modal testing doesn't work properly,
 * so we simulate the actual API calls that would be triggered by user actions
 * in the frontend (button clicks, form submissions, etc.).
 * 
 * Each test method simulates a complete user workflow:
 * - User navigates to page (GET)
 * - User opens modal/form (GET schema)
 * - User fills form (simulated payload)
 * - User submits form (POST/PUT/DELETE with payload)
 * - User sees result (verify response)
 * - Data is updated (verify database)
 * 
 * @see SchemaBasedApiTest::testSchemaDrivenCrudOperations() For generic CRUD testing
 * @see SchemaBasedApiTest::testUsersModelCompleteApiIntegration() For users-specific workflows
 */
class FrontendUserWorkflowTest extends CRUD6TestCase
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
        $this->seedDatabase();
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
     * Test complete user workflow: Create new user from frontend
     * 
     * Simulates:
     * 1. Admin navigates to /users page
     * 2. Admin clicks "Create User" button
     * 3. Frontend loads schema via GET /api/crud6/users/schema
     * 4. Admin fills form with user data
     * 5. Frontend submits POST /api/crud6/users with payload
     * 6. User is created in database
     * 7. User is assigned default role (relationship action)
     */
    public function testCreateUserWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'create_user']);

        // Step 1: Admin navigates to users page - loads list
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 2 & 3: Admin clicks "Create User" - frontend loads schema
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        $schema = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('fields', $schema);

        // Step 4 & 5: Admin fills and submits form
        // This is the actual payload the frontend would send
        $userPayload = [
            'user_name' => 'frontend_user',
            'first_name' => 'Frontend',
            'last_name' => 'User',
            'email' => 'frontend.user@example.com',
            'password' => 'SecurePassword123',
            'flag_enabled' => true,
            'flag_verified' => false,
        ];

        $request = $this->createJsonRequest('POST', '/api/crud6/users', $userPayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertContains($response->getStatusCode(), [200, 201, 409], 
            'CREATE operation should return 201 Created or 409 Conflict if record exists');

        // Step 6 & 7: Verify user was created with relationships
        $createdUser = User::where('user_name', 'frontend_user')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Frontend', $createdUser->first_name);
        $this->assertEquals('frontend.user@example.com', $createdUser->email);
        $this->assertTrue($createdUser->flag_enabled);
        $this->assertFalse($createdUser->flag_verified);

        // Verify on_create relationship action (default role assignment)
        $this->assertGreaterThan(0, $createdUser->roles->count(), 
            'User should have default role assigned via on_create action');
    }

    /**
     * Test complete user workflow: Edit user from frontend
     * 
     * Simulates:
     * 1. Admin navigates to user detail page
     * 2. Admin clicks "Edit" button
     * 3. Frontend loads user data via GET /api/crud6/users/{id}
     * 4. Frontend loads schema via GET /api/crud6/users/schema
     * 5. Admin modifies fields
     * 6. Frontend submits PUT /api/crud6/users/{id} with changes
     * 7. User is updated in database
     */
    public function testEditUserWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user_field']);

        /** @var User */
        $targetUser = User::factory()->create([
            'user_name' => 'edit_target',
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
        ]);

        // Step 1 & 3: Admin navigates to detail page, views current data
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$targetUser->id}");
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        $userData = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Original', $userData['first_name']);

        // Step 4: Admin clicks edit, frontend loads schema
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 5 & 6: Admin modifies and submits
        // Frontend sends only changed fields (partial update)
        $updatePayload = [
            'first_name' => 'Modified',
            'last_name' => 'Updated',
        ];

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$targetUser->id}", $updatePayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 7: Verify update in database
        $targetUser->refresh();
        $this->assertEquals('Modified', $targetUser->first_name);
        $this->assertEquals('Updated', $targetUser->last_name);
        // Email should be unchanged (partial update)
        $this->assertEquals('original@example.com', $targetUser->email);
    }

    /**
     * Test complete user workflow: Toggle user enabled flag
     * 
     * Simulates:
     * 1. Admin viewing users list
     * 2. Admin clicks toggle switch for flag_enabled
     * 3. Frontend sends PUT /api/crud6/users/{id}/flag_enabled
     * 4. Flag is toggled in database
     * 5. Frontend shows updated state
     */
    public function testToggleUserEnabledWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user_field']);

        /** @var User */
        $targetUser = User::factory()->create([
            'flag_enabled' => true,
        ]);

        // Step 1: Admin views users list (loads current state)
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 2 & 3: Admin clicks toggle - frontend sends update
        $togglePayload = [
            'flag_enabled' => false, // Toggle from true to false
        ];

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$targetUser->id}/flag_enabled", $togglePayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 4 & 5: Verify toggle in database
        $targetUser->refresh();
        $this->assertFalse($targetUser->flag_enabled);

        // Toggle back
        $togglePayload = [
            'flag_enabled' => true,
        ];

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$targetUser->id}/flag_enabled", $togglePayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        $targetUser->refresh();
        $this->assertTrue($targetUser->flag_enabled);
    }

    /**
     * Test complete user workflow: Assign roles to user
     * 
     * Simulates:
     * 1. Admin views user detail page
     * 2. Admin clicks "Manage Roles" button
     * 3. Frontend loads current roles via GET /api/crud6/users/{id}/roles
     * 4. Frontend loads available roles via GET /api/crud6/roles
     * 5. Admin selects additional roles
     * 6. Frontend sends POST /api/crud6/users/{id}/roles with role IDs
     * 7. Roles are attached in database
     */
    public function testAssignRolesToUserWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user_field', 'uri_crud6']);

        /** @var User */
        $targetUser = User::factory()->create();

        /** @var Role */
        $role1 = Role::factory()->create(['slug' => 'role1']);
        /** @var Role */
        $role2 = Role::factory()->create(['slug' => 'role2']);
        /** @var Role */
        $role3 = Role::factory()->create(['slug' => 'role3']);

        // Step 1 & 2: Admin views user, clicks manage roles

        // Step 3: Frontend loads current roles
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$targetUser->id}/roles");
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 4: Frontend loads available roles
        $request = $this->createJsonRequest('GET', '/api/crud6/roles');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 5 & 6: Admin selects roles and submits
        $assignRolesPayload = [
            'related_ids' => [$role1->id, $role2->id],
        ];

        $request = $this->createJsonRequest('POST', "/api/crud6/users/{$targetUser->id}/roles", $assignRolesPayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 7: Verify roles attached
        $targetUser->refresh();
        $this->assertCount(2, $targetUser->roles);
        $this->assertTrue($targetUser->roles->contains('id', $role1->id));
        $this->assertTrue($targetUser->roles->contains('id', $role2->id));
    }

    /**
     * Test complete user workflow: Remove role from user
     * 
     * Simulates removing a role assignment
     */
    public function testRemoveRoleFromUserWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user_field']);

        /** @var User */
        $targetUser = User::factory()->create();

        /** @var Role */
        $role1 = Role::factory()->create();
        /** @var Role */
        $role2 = Role::factory()->create();

        // Assign roles initially
        $targetUser->roles()->attach([$role1->id, $role2->id]);
        $this->assertCount(2, $targetUser->roles);

        // Admin removes role1
        $removeRolePayload = [
            'related_ids' => [$role1->id],
        ];

        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$targetUser->id}/roles", $removeRolePayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Verify role removed
        $targetUser->refresh();
        $this->assertCount(1, $targetUser->roles);
        $this->assertFalse($targetUser->roles->contains('id', $role1->id));
        $this->assertTrue($targetUser->roles->contains('id', $role2->id));
    }

    /**
     * Test complete user workflow: Delete user
     * 
     * Simulates:
     * 1. Admin views user list
     * 2. Admin clicks delete button for user
     * 3. Frontend shows confirmation modal
     * 4. Admin confirms deletion
     * 5. Frontend sends DELETE /api/crud6/users/{id}
     * 6. User is soft deleted
     * 7. Frontend refreshes list (user no longer shown)
     */
    public function testDeleteUserWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'delete_user']);

        /** @var User */
        $targetUser = User::factory()->create([
            'user_name' => 'to_delete',
        ]);

        // Step 1: Admin views user list
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Steps 2-5: Admin clicks delete and confirms
        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$targetUser->id}");
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Step 6: Verify user is deleted
        $deletedUser = User::find($targetUser->id);
        $this->assertNull($deletedUser);

        // Step 7: Verify user not in list
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        $listData = json_decode((string) $response->getBody(), true);
        $userNames = array_column($listData['rows'] ?? [], 'user_name');
        $this->assertNotContains('to_delete', $userNames);
    }

    /**
     * Test complete workflow: Create group
     */
    public function testCreateGroupWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'create_group']);

        // Load schema
        $request = $this->createJsonRequest('GET', '/api/crud6/groups/schema');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Create group with frontend payload
        $groupPayload = [
            'slug' => 'test-group',
            'name' => 'Test Group',
            'description' => 'Created from frontend workflow test',
            'icon' => 'fa-users',
        ];

        $request = $this->createJsonRequest('POST', '/api/crud6/groups', $groupPayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertContains($response->getStatusCode(), [200, 201, 409], 
            'CREATE operation should return 201 Created or 409 Conflict if record exists');

        // Verify created
        $group = Group::where('slug', 'test-group')->first();
        $this->assertNotNull($group);
        $this->assertEquals('Test Group', $group->name);
        $this->assertEquals('fa-users', $group->icon);
    }

    /**
     * Test complete workflow: Create role with permissions
     */
    public function testCreateRoleWithPermissionsWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'create_role', 'update_role_field', 'uri_crud6']);

        /** @var Permission */
        $perm1 = Permission::factory()->create();
        /** @var Permission */
        $perm2 = Permission::factory()->create();

        // Load schema
        $request = $this->createJsonRequest('GET', '/api/crud6/roles/schema');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Create role
        $rolePayload = [
            'slug' => 'custom-role',
            'name' => 'Custom Role',
            'description' => 'Role created with permissions',
        ];

        $request = $this->createJsonRequest('POST', '/api/crud6/roles', $rolePayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertContains($response->getStatusCode(), [200, 201, 409], 
            'CREATE operation should return 201 Created or 409 Conflict if record exists');

        $responseData = json_decode((string) $response->getBody(), true);
        $roleId = $responseData['data']['id'];

        // Assign permissions to role
        $permissionsPayload = [
            'related_ids' => [$perm1->id, $perm2->id],
        ];

        $request = $this->createJsonRequest('POST', "/api/crud6/roles/{$roleId}/permissions", $permissionsPayload);
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Verify role with permissions
        $role = Role::find($roleId);
        $this->assertNotNull($role);
        $this->assertCount(2, $role->permissions);
    }

    /**
     * Test complete workflow: Search and filter users
     * 
     * Simulates user using search and filters in the frontend
     */
    public function testSearchAndFilterUsersWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6']);

        // Create test users
        User::factory()->create(['user_name' => 'alice', 'first_name' => 'Alice']);
        User::factory()->create(['user_name' => 'bob', 'first_name' => 'Bob']);
        User::factory()->create(['user_name' => 'charlie', 'first_name' => 'Charlie']);

        // Test search
        $request = $this->createJsonRequest('GET', '/api/crud6/users?search=alice');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        $searchData = json_decode((string) $response->getBody(), true);
        $this->assertGreaterThan(0, $searchData['count_filtered']);
        
        // Test filter by user_name
        $request = $this->createJsonRequest('GET', '/api/crud6/users?filters[user_name]=bob');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        // Test sorting
        $request = $this->createJsonRequest('GET', '/api/crud6/users?sorts[user_name]=asc');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        // Test pagination
        $request = $this->createJsonRequest('GET', '/api/crud6/users?size=2&page=0');
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        $paginatedData = json_decode((string) $response->getBody(), true);
        $this->assertCount(2, $paginatedData['rows']);
    }

    /**
     * Test complete workflow: View nested relationship data
     * 
     * Simulates viewing users in a role, permissions in a role, etc.
     */
    public function testViewNestedRelationshipWorkflow(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'uri_crud6', 'uri_crud6']);

        /** @var Role */
        $role = Role::factory()->create(['name' => 'Test Role']);
        
        /** @var User */
        $user1 = User::factory()->create();
        /** @var User */
        $user2 = User::factory()->create();
        
        $role->users()->attach([$user1->id, $user2->id]);

        // Admin views role detail page
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}");
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);

        // Admin views users in this role (nested endpoint)
        $request = $this->createJsonRequest('GET', "/api/crud6/roles/{$role->id}/users");
        $response = $this->handleRequestWithTracking($request);
        $this->assertResponseStatus(200, $response);
        
        $usersData = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($usersData);
        $this->assertArrayHasKey('rows', $usersData);
        $this->assertCount(2, $usersData['rows']);
    }
}
