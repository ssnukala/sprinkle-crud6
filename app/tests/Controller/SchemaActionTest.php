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
 * CRUD6 API/Schema Action Integration Test
 *
 * Tests GET /api/crud6/{model}/schema endpoint for retrieving model schemas.
 * 
 * Features tested:
 * - Authentication and authorization  
 * - Schema retrieval
 * - Context filtering (list, form, detail)
 * - Related schemas
 * - Schema validation
 * - Response format
 */
class SchemaActionTest extends AdminTestCase
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
     * Test GET /api/crud6/users/schema requires authentication
     */
    public function testSchemaRequiresAuthentication(): void
    {
        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    /**
     * Test GET /api/crud6/users/schema requires permission
     */
    public function testSchemaRequiresPermission(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);  // No permissions

        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);

        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    /**
     * Test GET /api/crud6/users/schema returns schema
     */
    public function testSchemaReturnsValidSchema(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_users']);

        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);

        $this->assertResponseStatus(200, $response);
        $this->assertJson((string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        
        // Verify schema structure
        $this->assertArrayHasKey('model', $body, 'Schema should contain model name');
        $this->assertArrayHasKey('table', $body, 'Schema should contain table name');
        $this->assertArrayHasKey('fields', $body, 'Schema should contain fields');
        $this->assertArrayHasKey('primary_key', $body, 'Schema should contain primary key');
        
        $this->assertEquals('users', $body['model']);
        $this->assertEquals('users', $body['table']);
        $this->assertEquals('id', $body['primary_key']);
    }

    /**
     * Test schema fields contain proper structure
     */
    public function testSchemaFieldsHaveProperStructure(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_users']);

        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Check a known field (user_name)
        $this->assertArrayHasKey('user_name', $body['fields']);
        
        $userNameField = $body['fields']['user_name'];
        $this->assertArrayHasKey('type', $userNameField, 'Field should have type');
        $this->assertArrayHasKey('label', $userNameField, 'Field should have label');
        $this->assertEquals('string', $userNameField['type']);
    }

    /**
     * Test schema contains actions
     */
    public function testSchemaContainsActions(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_users']);

        $request = $this->createJsonRequest('GET', '/api/crud6/users/schema');
        $response = $this->handleRequestWithTracking($request);

        $body = json_decode((string) $response->getBody(), true);
        
        // Schema should have actions defined
        if (isset($body['actions'])) {
            $this->assertIsArray($body['actions']);
            
            // Look for toggle_enabled action
            $hasToggleEnabled = false;
            foreach ($body['actions'] as $action) {
                if ($action['key'] === 'toggle_enabled') {
                    $hasToggleEnabled = true;
                    $this->assertEquals('field_update', $action['type']);
                    $this->assertEquals('flag_enabled', $action['field']);
                    $this->assertTrue($action['toggle']);
                }
            }
            
            $this->assertTrue($hasToggleEnabled, 'Schema should contain toggle_enabled action');
        }
    }

    /**
     * Test schema for non-existent model returns error
     */
    public function testSchemaNonExistentModelReturnsError(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_crud6']);

        $request = $this->createJsonRequest('GET', '/api/crud6/nonexistent_model/schema');
        $response = $this->handleRequestWithTracking($request);

        // Should return error (404 or 500)
        $this->assertTrue(
            in_array($response->getStatusCode(), [404, 500]),
            'Should return error for non-existent model'
        );
    }
}
