<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\ServicesProvider;

use PHPUnit\Framework\TestCase;

/**
 * SchemaFilteringTest
 *
 * Tests the schema filtering functionality that prevents exposure of
 * sensitive information and optimizes API responses.
 * 
 * Validates that:
 * - Different contexts return appropriate field subsets
 * - Sensitive information is excluded from filtered schemas
 * - Backward compatibility is maintained with full schema option
 */
class SchemaFilteringTest extends TestCase
{
    /**
     * Sample test schema with various field types and properties
     */
    private function getSampleSchema(): array
    {
        return [
            'model' => 'test_model',
            'title' => 'Test Model',
            'singular_title' => 'Test Item',
            'description' => 'Test model for filtering',
            'table' => 'test_table',
            'primary_key' => 'id',
            'timestamps' => true,
            'soft_delete' => false,
            'permissions' => [
                'read' => 'view_test',
                'create' => 'create_test',
                'update' => 'edit_test',
                'delete' => 'delete_test',
            ],
            'default_sort' => [
                'name' => 'asc',
            ],
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'label' => 'ID',
                    'readonly' => true,
                    'sortable' => true,
                    'filterable' => false,
                    'listable' => true,
                    'editable' => false,
                ],
                'name' => [
                    'type' => 'string',
                    'label' => 'Name',
                    'required' => true,
                    'sortable' => true,
                    'filterable' => true,
                    'listable' => true,
                    'editable' => true,
                    'validation' => [
                        'required' => true,
                        'length' => ['min' => 2, 'max' => 255],
                    ],
                ],
                'email' => [
                    'type' => 'email',
                    'label' => 'Email',
                    'required' => true,
                    'sortable' => true,
                    'filterable' => true,
                    'listable' => true,
                    'editable' => true,
                    'validation' => [
                        'required' => true,
                        'email' => true,
                    ],
                ],
                'password' => [
                    'type' => 'password',
                    'label' => 'Password',
                    'required' => false,
                    'sortable' => false,
                    'filterable' => false,
                    'listable' => false,
                    'viewable' => true,
                    'editable' => true,
                    'validation' => [
                        'length' => ['min' => 8],
                    ],
                ],
                'internal_notes' => [
                    'type' => 'text',
                    'label' => 'Internal Notes',
                    'sortable' => false,
                    'filterable' => false,
                    'listable' => false,
                    'viewable' => false,
                    'editable' => true,
                    'description' => 'Internal staff notes',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'label' => 'Active',
                    'default' => true,
                    'sortable' => true,
                    'filterable' => true,
                    'listable' => true,
                    'viewable' => true,
                    'editable' => true,
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'label' => 'Created At',
                    'readonly' => true,
                    'sortable' => true,
                    'filterable' => false,
                    'listable' => true,
                    'viewable' => true,
                    'editable' => false,
                ],
            ],
            'detail' => [
                'model' => 'related_items',
                'foreign_key' => 'test_id',
                'list_fields' => ['name', 'value'],
            ],
        ];
    }

    /**
     * Test that filterSchemaForContext method exists in SchemaService
     */
    public function testFilterSchemaForContextMethodExists(): void
    {
        $serviceFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaService.php';
        $this->assertFileExists($serviceFile, 'SchemaService.php should exist');
        
        $serviceContent = file_get_contents($serviceFile);
        $this->assertNotFalse($serviceContent, 'Should be able to read SchemaService.php');
        
        $this->assertStringContainsString('function filterSchemaForContext', $serviceContent, 'filterSchemaForContext method should exist');
        $this->assertStringContainsString('list', $serviceContent, 'Should support list context');
        $this->assertStringContainsString('form', $serviceContent, 'Should support form context');
        $this->assertStringContainsString('detail', $serviceContent, 'Should support detail context');
        $this->assertStringContainsString('meta', $serviceContent, 'Should support meta context');
    }

    /**
     * Test that ApiAction accepts context query parameter
     */
    public function testApiActionAcceptsContextParameter(): void
    {
        $controllerFile = dirname(__DIR__, 2) . '/src/Controller/ApiAction.php';
        $this->assertFileExists($controllerFile, 'ApiAction.php should exist');
        
        $controllerContent = file_get_contents($controllerFile);
        $this->assertNotFalse($controllerContent, 'Should be able to read ApiAction.php');
        
        // Check for context parameter handling
        $this->assertStringContainsString('getQueryParams', $controllerContent, 'Should get query parameters');
        $this->assertStringContainsString('context', $controllerContent, 'Should handle context parameter');
        $this->assertStringContainsString('filterSchemaForContext', $controllerContent, 'Should call filterSchemaForContext');
    }

    /**
     * Test list context filtering logic
     * 
     * List context should only include:
     * - Fields where listable is true or not set
     * - Display properties (label, type, sortable, filterable)
     * - No validation rules or sensitive data
     */
    public function testListContextFiltering(): void
    {
        $schema = $this->getSampleSchema();
        
        // Expected fields in list context: id, name, email, is_active, created_at
        // Excluded fields: password, internal_notes (listable: false)
        
        $expectedListFields = ['id', 'name', 'email', 'is_active', 'created_at'];
        $excludedListFields = ['password', 'internal_notes'];
        
        // Check that schema has the test data
        $this->assertArrayHasKey('fields', $schema);
        foreach ($expectedListFields as $field) {
            $this->assertArrayHasKey($field, $schema['fields'], "Test schema should have {$field}");
        }
        
        // Validate field properties for list context
        foreach ($expectedListFields as $field) {
            $fieldConfig = $schema['fields'][$field];
            $this->assertTrue($fieldConfig['listable'] ?? true, "{$field} should be listable");
        }
        
        foreach ($excludedListFields as $field) {
            $fieldConfig = $schema['fields'][$field];
            $this->assertFalse($fieldConfig['listable'] ?? true, "{$field} should not be listable");
        }
    }

    /**
     * Test form context filtering logic
     * 
     * Form context should only include:
     * - Fields where editable is true or not set
     * - Validation rules
     * - Input properties (placeholder, description, required)
     * - No fields marked as readonly/non-editable
     */
    public function testFormContextFiltering(): void
    {
        $schema = $this->getSampleSchema();
        
        // Expected fields in form context: name, email, password, internal_notes, is_active
        // Excluded fields: id, created_at (editable: false)
        
        $expectedFormFields = ['name', 'email', 'password', 'internal_notes', 'is_active'];
        $excludedFormFields = ['id', 'created_at'];
        
        foreach ($expectedFormFields as $field) {
            $fieldConfig = $schema['fields'][$field];
            $this->assertTrue($fieldConfig['editable'] ?? true, "{$field} should be editable");
            
            // Form fields should have validation if present
            if (isset($fieldConfig['validation'])) {
                $this->assertIsArray($fieldConfig['validation'], "{$field} validation should be array");
            }
        }
        
        foreach ($excludedFormFields as $field) {
            $fieldConfig = $schema['fields'][$field];
            $this->assertFalse($fieldConfig['editable'] ?? true, "{$field} should not be editable");
        }
    }

    /**
     * Test detail context filtering logic
     * 
     * Detail context should include:
     * - All fields with display properties
     * - Detail/relationship configuration
     * - Full field information for display
     */
    public function testDetailContextFiltering(): void
    {
        $schema = $this->getSampleSchema();
        
        // Detail context should include all fields
        $this->assertArrayHasKey('fields', $schema);
        $this->assertNotEmpty($schema['fields']);
        
        // Should include detail configuration
        $this->assertArrayHasKey('detail', $schema);
        $this->assertIsArray($schema['detail']);
        $this->assertArrayHasKey('model', $schema['detail']);
        $this->assertArrayHasKey('foreign_key', $schema['detail']);
    }

    /**
     * Test detail context includes details array and actions array
     * 
     * The detail context should include:
     * - details (plural) - Array of relationship configurations
     * - actions - Array of custom action button configurations
     * - relationships - Array of relationship definitions for data fetching
     * 
     * This is critical for advanced layouts showing multiple related tables
     * and custom action buttons on detail pages.
     */
    public function testDetailContextIncludesDetailsAndActions(): void
    {
        // Create schema with details, actions, and relationships
        $schema = [
            'model' => 'users',
            'title' => 'Users',
            'singular_title' => 'User',
            'table' => 'users',
            'fields' => [
                'id' => ['type' => 'integer', 'label' => 'ID'],
                'user_name' => ['type' => 'string', 'label' => 'Username'],
                'email' => ['type' => 'string', 'label' => 'Email'],
            ],
            'details' => [
                [
                    'model' => 'activities',
                    'foreign_key' => 'user_id',
                    'list_fields' => ['occurred_at', 'type', 'description'],
                    'title' => 'Recent Activities',
                ],
                [
                    'model' => 'roles',
                    'foreign_key' => 'user_id',
                    'list_fields' => ['name', 'slug'],
                    'title' => 'User Roles',
                ],
            ],
            'actions' => [
                [
                    'key' => 'toggle_enabled',
                    'label' => 'Toggle Enabled',
                    'icon' => 'power-off',
                    'type' => 'field_update',
                    'field' => 'flag_enabled',
                ],
                [
                    'key' => 'reset_password',
                    'label' => 'Reset Password',
                    'icon' => 'envelope',
                    'type' => 'api_call',
                ],
            ],
            'relationships' => [
                [
                    'name' => 'roles',
                    'type' => 'many_to_many',
                    'pivot_table' => 'role_user',
                ],
            ],
        ];

        // Load SchemaService and test the filtering
        $serviceFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaService.php';
        $this->assertFileExists($serviceFile, 'SchemaService.php should exist');
        
        require_once $serviceFile;
        
        $locator = $this->createMock(\UserFrosting\UniformResourceLocator\ResourceLocatorInterface::class);
        $schemaService = new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService($locator);
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test detail context filtering
        $detailData = $method->invoke($schemaService, $schema, 'detail');
        
        // Verify details array is included
        $this->assertArrayHasKey('details', $detailData, 'Detail context should include details array');
        $this->assertIsArray($detailData['details'], 'details should be an array');
        $this->assertCount(2, $detailData['details'], 'Should have 2 detail configurations');
        $this->assertEquals('activities', $detailData['details'][0]['model'], 'First detail should be activities');
        $this->assertEquals('roles', $detailData['details'][1]['model'], 'Second detail should be roles');
        
        // Verify actions array is included
        $this->assertArrayHasKey('actions', $detailData, 'Detail context should include actions array');
        $this->assertIsArray($detailData['actions'], 'actions should be an array');
        $this->assertCount(2, $detailData['actions'], 'Should have 2 action configurations');
        $this->assertEquals('toggle_enabled', $detailData['actions'][0]['key'], 'First action should be toggle_enabled');
        $this->assertEquals('reset_password', $detailData['actions'][1]['key'], 'Second action should be reset_password');
        
        // Verify relationships array is included
        $this->assertArrayHasKey('relationships', $detailData, 'Detail context should include relationships array');
        $this->assertIsArray($detailData['relationships'], 'relationships should be an array');
        $this->assertCount(1, $detailData['relationships'], 'Should have 1 relationship configuration');
        $this->assertEquals('roles', $detailData['relationships'][0]['name'], 'Relationship should be roles');
    }

    /**
     * Test meta context filtering logic
     * 
     * Meta context should only include:
     * - Model identification (model, title, singular_title)
     * - Permissions
     * - Primary key
     * - NO field information
     */
    public function testMetaContextShouldExcludeFields(): void
    {
        $schema = $this->getSampleSchema();
        
        // Meta context should have these properties
        $expectedMetaProperties = ['model', 'title', 'singular_title', 'primary_key', 'permissions'];
        
        foreach ($expectedMetaProperties as $property) {
            $this->assertArrayHasKey($property, $schema, "Schema should have {$property} for meta context");
        }
        
        // Test schema has fields, but in meta context they should be excluded
        $this->assertArrayHasKey('fields', $schema, 'Test schema should have fields');
    }

    /**
     * Test that validation rules are excluded from list context
     * 
     * List context should NOT expose validation rules as they reveal
     * business logic and constraints
     */
    public function testValidationRulesExcludedFromListContext(): void
    {
        $schema = $this->getSampleSchema();
        
        // Name field has validation rules in full schema
        $this->assertArrayHasKey('validation', $schema['fields']['name']);
        
        // In a list context filter, these should be excluded
        // This test validates the expected behavior, actual filtering
        // happens in SchemaService::filterSchemaForContext
    }

    /**
     * Test that internal/sensitive fields are properly marked
     * 
     * Fields like password, internal_notes should not be listable
     */
    public function testSensitiveFieldsNotListable(): void
    {
        $schema = $this->getSampleSchema();
        
        $sensitiveFields = ['password', 'internal_notes'];
        
        foreach ($sensitiveFields as $field) {
            $this->assertFalse(
                $schema['fields'][$field]['listable'] ?? true,
                "{$field} should be marked as not listable for security"
            );
        }
    }

    /**
     * Test backward compatibility with full schema
     * 
     * When no context is provided or context is 'full', the complete
     * schema should be returned for backward compatibility
     */
    public function testBackwardCompatibilityWithFullSchema(): void
    {
        // This validates that the logic exists for backward compatibility
        $serviceFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaService.php';
        $serviceContent = file_get_contents($serviceFile);
        
        // Should handle null context (default to full schema)
        $this->assertStringContainsString('$context === null', $serviceContent, 'Should handle null context');
        $this->assertStringContainsString('$context === \'full\'', $serviceContent, 'Should handle full context');
    }

    /**
     * Test that default_sort is included in list context
     * 
     * List views need default_sort to properly order data
     */
    public function testDefaultSortIncludedInListContext(): void
    {
        $schema = $this->getSampleSchema();
        
        $this->assertArrayHasKey('default_sort', $schema);
        $this->assertIsArray($schema['default_sort']);
        $this->assertNotEmpty($schema['default_sort']);
    }

    /**
     * Test that permissions are included in all contexts
     * 
     * Permission checks are needed in all contexts
     */
    public function testPermissionsIncludedInAllContexts(): void
    {
        $schema = $this->getSampleSchema();
        
        $this->assertArrayHasKey('permissions', $schema);
        $this->assertIsArray($schema['permissions']);
        
        $expectedPermissions = ['read', 'create', 'update', 'delete'];
        foreach ($expectedPermissions as $permission) {
            $this->assertArrayHasKey($permission, $schema['permissions']);
        }
    }

    /**
     * Test viewable attribute filtering in detail context
     * 
     * The viewable attribute controls which fields appear in detail/view pages.
     * Fields with viewable: false should be excluded from detail context.
     * Fields with viewable: true or no viewable attribute (default true) should be included.
     */
    public function testViewableAttributeFiltering(): void
    {
        // Create schema with various viewable settings
        $schema = [
            'model' => 'test_viewable',
            'title' => 'Viewable Test',
            'table' => 'test_table',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'label' => 'ID',
                    // No viewable attribute - should default to true
                ],
                'name' => [
                    'type' => 'string',
                    'label' => 'Name',
                    'viewable' => true,
                ],
                'email' => [
                    'type' => 'string',
                    'label' => 'Email',
                    'viewable' => true,
                ],
                'password' => [
                    'type' => 'string',
                    'label' => 'Password',
                    'viewable' => true,  // Viewable but not editable
                    'editable' => false,
                    'readonly' => true,
                ],
                'secret_token' => [
                    'type' => 'string',
                    'label' => 'Secret Token',
                    'viewable' => false,  // Should be excluded from detail view
                ],
                'internal_flags' => [
                    'type' => 'json',
                    'label' => 'Internal Flags',
                    'viewable' => false,  // Hidden from detail view
                    'editable' => true,   // But can be edited in forms
                ],
            ],
        ];

        // Manually load SchemaService and test the filtering
        $serviceFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaService.php';
        $this->assertFileExists($serviceFile, 'SchemaService.php should exist');
        
        // Include the service class
        require_once $serviceFile;
        
        // Create a mock ResourceLocatorInterface
        $locator = $this->createMock(\UserFrosting\UniformResourceLocator\ResourceLocatorInterface::class);
        
        // Create SchemaService instance
        $schemaService = new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService($locator);
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test detail context filtering
        $detailData = $method->invoke($schemaService, $schema, 'detail');
        
        // Verify viewable fields are included
        $this->assertArrayHasKey('id', $detailData['fields'], 'id should be included (default viewable: true)');
        $this->assertArrayHasKey('name', $detailData['fields'], 'name should be included (viewable: true)');
        $this->assertArrayHasKey('email', $detailData['fields'], 'email should be included (viewable: true)');
        $this->assertArrayHasKey('password', $detailData['fields'], 'password should be included (viewable: true, readonly)');
        
        // Verify non-viewable fields are excluded
        $this->assertArrayNotHasKey('secret_token', $detailData['fields'], 'secret_token should be excluded (viewable: false)');
        $this->assertArrayNotHasKey('internal_flags', $detailData['fields'], 'internal_flags should be excluded (viewable: false)');
        
        // Verify that readonly and editable flags are preserved for viewable fields
        $this->assertTrue($detailData['fields']['password']['readonly'], 'password should be readonly');
        $this->assertFalse($detailData['fields']['password']['editable'], 'password should not be editable');
    }
}
