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

use PHPUnit\Framework\TestCase;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Base;
use UserFrosting\Sprinkle\CRUD6\Controller\SprunjeAction;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Admin\Sprunje\UserSprunje;

/**
 * Listable Fields Test
 *
 * Tests that only fields explicitly marked as listable: true are shown in lists.
 * This prevents sensitive fields (password, timestamps, etc.) from being exposed.
 */
class ListableFieldsTest extends TestCase
{
    /**
     * Test Base controller getListableFields only returns explicit listable: true fields
     */
    public function testBaseGetListableFieldsOnlyExplicit(): void
    {
        $controller = $this->createBaseController();
        
        // Mock schema with various field configurations
        $schemaService = $this->createMock(SchemaService::class);
        $schemaService->method('getSchema')->willReturn([
            'model' => 'users',
            'table' => 'users',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'user_name' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'email' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'password' => [
                    'type' => 'string',
                    // No listable attribute - should NOT be included (sensitive field)
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                    // No listable attribute - should NOT be included
                ],
                'updated_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                    'listable' => false,  // Explicitly false - should NOT be included
                ],
                'first_name' => [
                    'type' => 'string',
                    // No listable attribute - should NOT be included by default
                ],
                'last_name' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
            ]
        ]);
        
        // Replace the schemaService in the controller
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('schemaService');
        $property->setAccessible(true);
        $property->setValue($controller, $schemaService);
        
        $listableFields = $this->invokeMethod($controller, 'getListableFields', ['users']);
        
        $this->assertIsArray($listableFields);
        
        // Should include only fields with explicit listable: true
        $this->assertContains('id', $listableFields);
        $this->assertContains('user_name', $listableFields);
        $this->assertContains('email', $listableFields);
        $this->assertContains('last_name', $listableFields);
        
        // Should NOT include fields without listable or with listable: false
        $this->assertNotContains('password', $listableFields, 'Password field should not be listable by default');
        $this->assertNotContains('created_at', $listableFields, 'Created_at field should not be listable by default');
        $this->assertNotContains('updated_at', $listableFields, 'Updated_at field with listable: false should not be listable');
        $this->assertNotContains('first_name', $listableFields, 'Fields without listable attribute should not be listable by default');
    }
    
    /**
     * Test SprunjeAction getListableFieldsFromSchema only returns explicit listable: true fields
     */
    public function testSprunjeActionGetListableFieldsFromSchemaOnlyExplicit(): void
    {
        $controller = $this->createSprunjeAction();
        
        $schema = [
            'model' => 'users',
            'table' => 'users',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'user_name' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'email' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
                'password' => [
                    'type' => 'string',
                    // No listable attribute - should NOT be included (sensitive field)
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                    // No listable attribute - should NOT be included even if readonly
                ],
                'updated_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                    'listable' => false,  // Explicitly false - should NOT be included
                ],
                'first_name' => [
                    'type' => 'string',
                    // No listable attribute - should NOT be included by default
                ],
                'last_name' => [
                    'type' => 'string',
                    'listable' => true,  // Explicitly true - should be included
                ],
            ]
        ];
        
        $listableFields = $this->invokeMethod($controller, 'getListableFieldsFromSchema', [$schema]);
        
        $this->assertIsArray($listableFields);
        
        // Should include only fields with explicit listable: true
        $this->assertContains('id', $listableFields);
        $this->assertContains('user_name', $listableFields);
        $this->assertContains('email', $listableFields);
        $this->assertContains('last_name', $listableFields);
        
        // Should NOT include fields without listable or with listable: false
        $this->assertNotContains('password', $listableFields, 'Password field should not be listable by default');
        $this->assertNotContains('created_at', $listableFields, 'Created_at field should not be listable by default even if readonly');
        $this->assertNotContains('updated_at', $listableFields, 'Updated_at field with listable: false should not be listable');
        $this->assertNotContains('first_name', $listableFields, 'Fields without listable attribute should not be listable by default');
    }
    
    /**
     * Test that readonly fields are NOT automatically listable
     */
    public function testReadonlyFieldsNotAutomaticallyListable(): void
    {
        $controller = $this->createSprunjeAction();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'readonly' => true,
                    'listable' => true,  // Must be explicit
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                    // No listable - should NOT be shown
                ],
                'status' => [
                    'type' => 'string',
                    'readonly' => true,
                    // No listable - should NOT be shown
                ],
            ]
        ];
        
        $listableFields = $this->invokeMethod($controller, 'getListableFieldsFromSchema', [$schema]);
        
        // Only id should be listable (explicit listable: true)
        $this->assertCount(1, $listableFields);
        $this->assertContains('id', $listableFields);
        $this->assertNotContains('created_at', $listableFields);
        $this->assertNotContains('status', $listableFields);
    }
    
    /**
     * Test empty schema returns empty array
     */
    public function testEmptySchemaReturnsEmptyArray(): void
    {
        $controller = $this->createSprunjeAction();
        
        $schema = [
            'model' => 'test',
            'fields' => []
        ];
        
        $listableFields = $this->invokeMethod($controller, 'getListableFieldsFromSchema', [$schema]);
        
        $this->assertIsArray($listableFields);
        $this->assertEmpty($listableFields);
    }
    
    /**
     * Test schema without fields key returns empty array
     */
    public function testSchemaWithoutFieldsReturnsEmptyArray(): void
    {
        $controller = $this->createSprunjeAction();
        
        $schema = [
            'model' => 'test',
        ];
        
        $listableFields = $this->invokeMethod($controller, 'getListableFieldsFromSchema', [$schema]);
        
        $this->assertIsArray($listableFields);
        $this->assertEmpty($listableFields);
    }
    
    /**
     * Create a mock Base controller instance for testing
     */
    private function createBaseController(): Base
    {
        $authorizer = $this->createMock(AuthorizationManager::class);
        $authenticator = $this->createMock(Authenticator::class);
        $logger = $this->createMock(DebugLoggerInterface::class);
        $schemaService = $this->createMock(SchemaService::class);
        $config = $this->createMock(Config::class);
        
        // Create an anonymous class that extends Base for testing
        return new class($authorizer, $authenticator, $logger, $schemaService, $config) extends Base {
            // Make protected methods accessible for testing
        };
    }
    
    /**
     * Create a mock SprunjeAction instance for testing
     */
    private function createSprunjeAction(): SprunjeAction
    {
        $authorizer = $this->createMock(AuthorizationManager::class);
        $authenticator = $this->createMock(Authenticator::class);
        $logger = $this->createMock(DebugLoggerInterface::class);
        $translator = $this->createMock(Translator::class);
        $sprunje = $this->createMock(CRUD6Sprunje::class);
        $schemaService = $this->createMock(SchemaService::class);
        $userSprunje = $this->createMock(UserSprunje::class);
        
        return new SprunjeAction(
            $authorizer,
            $authenticator,
            $logger,
            $translator,
            $sprunje,
            $schemaService,
            $userSprunje
        );
    }
    
    /**
     * Helper method to invoke protected/private methods
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}
