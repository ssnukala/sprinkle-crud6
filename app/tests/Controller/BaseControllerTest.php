<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use PHPUnit\Framework\TestCase;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Base;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Base Controller Test
 *
 * Tests the Base controller's editable fields and validation rules logic.
 * Uses modern CRUD6 schema structure with readonly attribute (not editable).
 */
class BaseControllerTest extends TestCase
{
    /**
     * Test getEditableFields with readonly attribute
     */
    public function testGetEditableFieldsWithReadonlyAttribute(): void
    {
        $controller = $this->createBaseController();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                    'readonly' => true,
                ],
                'description' => [
                    'type' => 'text',
                ],
            ]
        ];
        
        $editableFields = $this->invokeMethod($controller, 'getEditableFields', [$schema]);
        
        $this->assertIsArray($editableFields);
        $this->assertContains('name', $editableFields);
        $this->assertContains('description', $editableFields);
        $this->assertNotContains('id', $editableFields);
        $this->assertNotContains('status', $editableFields);
    }
    
    /**
     * Test getEditableFields with readonly attribute
     */
    public function testGetEditableFieldsWithReadonly(): void
    {
        $controller = $this->createBaseController();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'readonly' => true,
                ],
                'name' => [
                    'type' => 'string',
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'readonly' => true,
                ],
                'description' => [
                    'type' => 'text',
                ],
            ]
        ];
        
        $editableFields = $this->invokeMethod($controller, 'getEditableFields', [$schema]);
        
        $this->assertIsArray($editableFields);
        $this->assertContains('name', $editableFields);
        $this->assertContains('description', $editableFields);
        $this->assertNotContains('id', $editableFields);
        $this->assertNotContains('created_at', $editableFields);
    }
    
    /**
     * Test getEditableFields with auto_increment and computed fields
     */
    public function testGetEditableFieldsWithAutoIncrementAndComputed(): void
    {
        $controller = $this->createBaseController();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'string',
                ],
                'full_name' => [
                    'type' => 'string',
                    'computed' => true,
                ],
                'status' => [
                    'type' => 'string',
                ],
            ]
        ];
        
        $editableFields = $this->invokeMethod($controller, 'getEditableFields', [$schema]);
        
        $this->assertIsArray($editableFields);
        $this->assertContains('name', $editableFields);
        $this->assertContains('status', $editableFields);
        $this->assertNotContains('id', $editableFields);
        $this->assertNotContains('full_name', $editableFields);
    }
    
    /**
     * Test getValidationRules includes only editable fields
     */
    public function testGetValidationRulesOnlyIncludesEditableFields(): void
    {
        $controller = $this->createBaseController();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'auto_increment' => true,
                    'validation' => ['required' => true],
                ],
                'name' => [
                    'type' => 'string',
                    'validation' => [
                        'required' => true,
                        'length' => ['min' => 3, 'max' => 50]
                    ],
                ],
                'readonly_field' => [
                    'type' => 'string',
                    'readonly' => true,
                    'validation' => ['required' => true],
                ],
                'description' => [
                    'type' => 'text',
                ],
            ]
        ];
        
        $validationRules = $this->invokeMethod($controller, 'getValidationRules', [$schema]);
        
        $this->assertIsArray($validationRules);
        $this->assertArrayHasKey('name', $validationRules);
        $this->assertArrayHasKey('description', $validationRules);
        $this->assertArrayNotHasKey('id', $validationRules);
        $this->assertArrayNotHasKey('readonly_field', $validationRules);
        
        // Verify validation rules are preserved for fields that have them
        $this->assertEquals([
            'required' => true,
            'length' => ['min' => 3, 'max' => 50]
        ], $validationRules['name']);
        
        // Verify editable fields without validation rules have empty array
        $this->assertEquals([], $validationRules['description']);
    }
    
    /**
     * Test getValidationRules includes editable fields without validation
     */
    public function testGetValidationRulesIncludesEditableFieldsWithoutValidation(): void
    {
        $controller = $this->createBaseController();
        
        $schema = [
            'model' => 'test',
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'validation' => ['required' => true],
                ],
                'description' => [
                    'type' => 'text',
                    // No validation rules
                ],
                'notes' => [
                    'type' => 'text',
                    // No validation rules, defaults to editable (no readonly flag)
                ],
            ]
        ];
        
        $validationRules = $this->invokeMethod($controller, 'getValidationRules', [$schema]);
        
        $this->assertIsArray($validationRules);
        $this->assertArrayHasKey('name', $validationRules);
        $this->assertArrayHasKey('description', $validationRules);
        $this->assertArrayHasKey('notes', $validationRules);
        
        $this->assertEquals(['required' => true], $validationRules['name']);
        $this->assertEquals([], $validationRules['description']);
        $this->assertEquals([], $validationRules['notes']);
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
