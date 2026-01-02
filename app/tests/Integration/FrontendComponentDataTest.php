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
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Frontend Component Data Integration Test
 *
 * Tests that frontend Vue components receive the correct data from API endpoints.
 * This addresses complex mocking requirements in Vitest tests by validating
 * the actual API layer that components depend on.
 * 
 * Components tested:
 * - PageList.vue - List view requiring paginated data
 * - PageRow.vue - Detail view requiring single record data
 * - Form.vue - Create/edit forms requiring schema
 * - Info.vue - Info display requiring record data and schema
 * - UnifiedModal.vue - Modals requiring schema and action definitions
 * - Details.vue - Related records display
 * 
 * Each test validates:
 * 1. API endpoint returns expected response structure
 * 2. Response includes all fields component needs
 * 3. Data types match component expectations
 * 4. Error cases return appropriate responses
 */
class FrontendComponentDataTest extends CRUD6TestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;

    /**
     * Setup test database
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->seedDatabase();
    }

    /**
     * Test PageList component data requirements
     * 
     * PageList.vue mounts and calls:
     * - GET /api/crud6/{model} for list data
     * - GET /api/crud6/{model}/schema?context=list for schema
     * 
     * This test validates both endpoints return proper data structure
     */
    public function testPageListComponentData(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6']);

        // Create test users
        User::factory()->count(3)->create();

        // Test 1: PageList calls list endpoint on mount
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        // Verify response structure matches PageList expectations
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('rows', $data, 'PageList requires "rows" array');
        $this->assertArrayHasKey('count', $data, 'PageList requires "count" for pagination');
        $this->assertIsArray($data['rows']);
        $this->assertGreaterThan(0, count($data['rows']));

        // Verify each row has required fields
        foreach ($data['rows'] as $row) {
            $this->assertArrayHasKey('id', $row, 'Each row needs id for routing');
            $this->assertArrayHasKey('user_name', $row);
            $this->assertIsInt($row['id']);
            $this->assertIsString($row['user_name']);
        }

        // Test 2: PageList loads schema for column configuration
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=list');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $schema = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('fields', $schema, 'Schema must have fields for columns');
        $this->assertArrayHasKey('title', $schema, 'Schema needs title for page heading');
        
        // Verify list_fields configuration
        if (isset($schema['list_fields'])) {
            $this->assertIsArray($schema['list_fields']);
        }
    }

    /**
     * Test PageRow component data requirements
     * 
     * PageRow.vue mounts and calls:
     * - GET /api/crud6/{model}/{id} for record data
     * - GET /api/crud6/{model}/schema?context=detail for schema
     * 
     * This test validates single record endpoint
     */
    public function testPageRowComponentData(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $testUser = User::factory()->create([
            'user_name' => 'page_row_test',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        $this->actAsUser($admin, permissions: ['uri_crud6']);

        // Test 1: PageRow calls getRow on mount
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$testUser->id}");
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        // Verify response structure for Info component
        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('user_name', $data);
        $this->assertEquals('page_row_test', $data['user_name']);
        $this->assertEquals('Test', $data['first_name']);

        // Test 2: PageRow loads schema for field display
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=detail');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $schema = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('fields', $schema);
        
        // Verify viewable fields are properly configured
        foreach ($schema['fields'] as $fieldName => $field) {
            $this->assertIsArray($field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('label', $field);
            
            // If field is in the record, Info component can display it
            if (isset($data[$fieldName])) {
                $this->assertTrue(
                    $field['viewable'] ?? true,
                    "Field {$fieldName} in data should be viewable"
                );
            }
        }
    }

    /**
     * Test Form component data requirements
     * 
     * Form.vue needs:
     * - GET /api/crud6/{model}/schema?context=form for field definitions
     * - POST /api/crud6/{model} for create
     * - PUT /api/crud6/{model}/{id} for update
     * 
     * This test validates form submission endpoints
     */
    public function testFormComponentSubmission(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'create_user']);

        // Test 1: Form loads schema on mount
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=form');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $schema = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('fields', $schema);
        
        // Verify editable fields are present for form
        $editableFieldsFound = false;
        foreach ($schema['fields'] as $fieldName => $field) {
            if ($field['editable'] ?? true) {
                $editableFieldsFound = true;
                $this->assertArrayHasKey('type', $field, "Editable field {$fieldName} needs type");
                $this->assertArrayHasKey('label', $field, "Editable field {$fieldName} needs label");
            }
        }
        $this->assertTrue($editableFieldsFound, 'Schema must have at least one editable field');

        // Test 2: Form submits create request
        $formData = [
            'user_name' => 'form_test_user',
            'first_name' => 'Form',
            'last_name' => 'Test',
            'email' => 'form.test@example.com',
            'password' => 'TestPassword123!'
        ];

        $request = $this->createJsonRequest('POST', '/api/crud6/users', $formData);
        $response = $this->handleRequest($request);
        
        // Form expects 201 Created with record data
        $this->assertResponseStatus(201, $response);
        $responseBody = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $responseBody, 'Form needs created record data');
        $this->assertArrayHasKey('id', $responseBody['data'], 'Form needs ID to redirect');

        // Verify user was created
        $this->assertDatabaseHas('users', ['user_name' => 'form_test_user']);

        // Test 3: Form submits update request
        $userId = $responseBody['data']['id'];
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ];

        $request = $this->createJsonRequest('PUT', "/api/crud6/users/{$userId}", $updateData);
        $response = $this->handleRequest($request);
        
        // Form expects 200 OK
        $this->assertResponseStatus(200, $response);
        
        // Verify update was applied
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ]);
    }

    /**
     * Test Info component data requirements
     * 
     * Info.vue displays record data with actions.
     * Needs same data as PageRow plus action definitions from schema.
     * 
     * This test validates Info gets complete data including actions
     */
    public function testInfoComponentDataWithActions(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $testUser = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user', 'delete_user']);

        // Info component needs record data
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$testUser->id}");
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $data);

        // Info component needs schema with actions
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=detail');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $schema = json_decode((string) $response->getBody(), true);
        
        // Verify actions are defined for Info buttons
        if (isset($schema['actions'])) {
            $this->assertIsArray($schema['actions']);
            
            foreach ($schema['actions'] as $actionKey => $action) {
                $this->assertArrayHasKey('label', $action, "Action {$actionKey} needs label");
                $this->assertArrayHasKey('type', $action, "Action {$actionKey} needs type");
            }
        }
    }

    /**
     * Test UnifiedModal component requirements
     * 
     * UnifiedModal.vue handles:
     * - Edit forms (needs schema + record data)
     * - Delete confirmations (needs record data)
     * - Custom actions (needs action config + record data)
     * 
     * This test validates modal data requirements
     */
    public function testUnifiedModalDataRequirements(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $testUser = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6', 'update_user', 'delete_user']);

        // Test 1: Modal opening for edit needs record data
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$testUser->id}");
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        
        $userData = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('user_name', $userData);

        // Test 2: Modal needs schema for form fields
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=form');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        
        $schema = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('fields', $schema);

        // Test 3: Modal delete action
        $request = $this->createJsonRequest('DELETE', "/api/crud6/users/{$testUser->id}");
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        
        // Verify user was deleted
        $this->assertDatabaseMissing('users', ['id' => $testUser->id]);
    }

    /**
     * Test Details component data requirements
     * 
     * Details.vue displays related records.
     * Needs GET /api/crud6/{parent}/{id}/{related} endpoint
     * 
     * This test validates relationship endpoints
     */
    public function testDetailsComponentRelationshipData(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6']);

        // Details component calls relationship endpoint
        $request = $this->createJsonRequest('GET', "/api/crud6/users/{$admin->id}/roles");
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('rows', $data, 'Details needs rows array');
        $this->assertIsArray($data['rows']);

        // Verify related records have required fields
        if (count($data['rows']) > 0) {
            foreach ($data['rows'] as $row) {
                $this->assertArrayHasKey('id', $row);
                $this->assertIsInt($row['id']);
            }
        }
    }

    /**
     * Test schema multi-context support
     * 
     * Components request different schema contexts:
     * - Form: context=form (editable fields)
     * - Info: context=detail (viewable fields)
     * - PageList: context=list (list columns)
     * 
     * This test validates context-specific schemas
     */
    public function testSchemaMultiContextSupport(): void
    {
        /** @var User */
        $admin = User::factory()->create();
        $this->actAsUser($admin, permissions: ['uri_crud6']);

        // Test form context
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=form');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        $formSchema = json_decode((string) $response->getBody(), true);

        // Test detail context
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=detail');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        $detailSchema = json_decode((string) $response->getBody(), true);

        // Test list context
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema?context=list');
        $response = $this->handleRequest($request);
        $this->assertResponseStatus(200, $response);
        $listSchema = json_decode((string) $response->getBody(), true);

        // Verify all contexts have fields
        $this->assertArrayHasKey('fields', $formSchema);
        $this->assertArrayHasKey('fields', $detailSchema);
        $this->assertArrayHasKey('fields', $listSchema);

        // Verify contexts may have different fields based on editable/viewable
        $this->assertIsArray($formSchema['fields']);
        $this->assertIsArray($detailSchema['fields']);
        $this->assertIsArray($listSchema['fields']);
    }
}
