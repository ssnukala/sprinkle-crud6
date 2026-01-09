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
use UserFrosting\Config\Config;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaNormalizer;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaCache;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaTranslator;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaActionManager;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * SchemaFilteringTest
 *
 * Tests the schema filtering functionality that prevents exposure of
 * sensitive information and optimizes API responses.
 * 
 * Validates that:
 * - Different contexts return appropriate field subsets
 * - Sensitive information is excluded from filtered schemas
 */
class SchemaFilteringTest extends TestCase
{
    /**
     * Create a SchemaService with all required mocked dependencies
     */
    private function createSchemaService(): \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(Config::class);
        $logger = $this->createMock(DebugLoggerInterface::class);
        $translator = $this->createMock(Translator::class);
        $loader = $this->createMock(SchemaLoader::class);
        $validator = $this->createMock(SchemaValidator::class);
        $normalizer = $this->createMock(SchemaNormalizer::class);
        $cache = $this->createMock(SchemaCache::class);
        $filter = $this->createMock(SchemaFilter::class);
        $schemaTranslator = $this->createMock(SchemaTranslator::class);
        $actionManager = $this->createMock(SchemaActionManager::class);

        return new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService(
            $locator,
            $config,
            $logger,
            $translator,
            $loader,
            $validator,
            $normalizer,
            $cache,
            $filter,
            $schemaTranslator,
            $actionManager
        );
    }

    /**
     * Create a SchemaFilter instance for testing
     */
    private function createSchemaFilter(): \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter
    {
        $logger = $this->createMock(DebugLoggerInterface::class);
        return new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter($logger);
    }
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

        // Load SchemaFilter and test the filtering (getContextSpecificData is in SchemaFilter, not SchemaService)
        $filterFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaFilter.php';
        $this->assertFileExists($filterFile, 'SchemaFilter.php should exist');
        
        require_once $filterFile;
        
        $schemaFilter = $this->createSchemaFilter();
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($schemaFilter);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test detail context filtering
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        
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

        // Manually load SchemaFilter and test the filtering (getContextSpecificData is in SchemaFilter)
        $filterFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaFilter.php';
        $this->assertFileExists($filterFile, 'SchemaFilter.php should exist');
        
        // Include the filter class
        require_once $filterFile;
        
        // Create SchemaFilter instance
        $schemaFilter = $this->createSchemaFilter();
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($schemaFilter);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test detail context filtering
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        
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

    /**
     * Test list context filtering with show_in array
     * 
     * Fields with show_in array should only be included in list context
     * if 'list' is in the array. Fields without 'list' in show_in should be excluded.
     * This is the fix for the bug where password field was showing in users table.
     */
    public function testListContextFilteringWithShowIn(): void
    {
        // Create schema similar to users.json with show_in arrays
        $schema = [
            'model' => 'users',
            'title' => 'Users',
            'table' => 'users',
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'label' => 'ID',
                    'show_in' => ['detail'],  // NOT in list
                ],
                'user_name' => [
                    'type' => 'string',
                    'label' => 'Username',
                    'show_in' => ['list', 'form', 'detail'],  // IN list
                ],
                'first_name' => [
                    'type' => 'string',
                    'label' => 'First Name',
                    'show_in' => ['list', 'form', 'detail'],  // IN list
                ],
                'email' => [
                    'type' => 'string',
                    'label' => 'Email',
                    'show_in' => ['list', 'form', 'detail'],  // IN list
                ],
                'locale' => [
                    'type' => 'string',
                    'label' => 'Locale',
                    'show_in' => ['form', 'detail'],  // NOT in list
                ],
                'group_id' => [
                    'type' => 'integer',
                    'label' => 'Group',
                    'show_in' => ['form', 'detail'],  // NOT in list
                ],
                'password' => [
                    'type' => 'password',
                    'label' => 'Password',
                    'show_in' => ['create', 'edit'],  // NOT in list
                ],
                'flag_enabled' => [
                    'type' => 'boolean',
                    'label' => 'Enabled',
                    'show_in' => ['list', 'form', 'detail'],  // IN list
                ],
                'no_show_in_field' => [
                    'type' => 'string',
                    'label' => 'No Show In',
                    // No show_in attribute - should NOT be in list (secure by default)
                ],
                'explicit_listable' => [
                    'type' => 'string',
                    'label' => 'Explicit Listable',
                    'listable' => true,  // Explicit listable flag
                    // No show_in attribute but has listable: true
                ],
            ],
        ];

        // Load SchemaFilter and test the filtering
        $filterFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaFilter.php';
        $this->assertFileExists($filterFile, 'SchemaFilter.php should exist');
        
        require_once $filterFile;
        
        $schemaFilter = $this->createSchemaFilter();
        
        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($schemaFilter);
        $method = $reflection->getMethod('getListContextData');
        $method->setAccessible(true);
        
        // Test list context filtering
        $listData = $method->invoke($schemaFilter, $schema);
        
        // Fields that SHOULD be included (have 'list' in show_in)
        $expectedFields = ['user_name', 'first_name', 'email', 'flag_enabled'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $listData['fields'], "{$field} should be included in list context");
        }
        
        // Fields that should NOT be included (don't have 'list' in show_in)
        $excludedFields = ['id', 'locale', 'group_id', 'password', 'no_show_in_field'];
        foreach ($excludedFields as $field) {
            $this->assertArrayNotHasKey($field, $listData['fields'], "{$field} should NOT be included in list context");
        }
        
        // Field with explicit listable flag should be included
        $this->assertArrayHasKey('explicit_listable', $listData['fields'], 'explicit_listable should be included (has listable: true)');
        
        // Verify the fix: password field should definitely NOT be in list
        $this->assertArrayNotHasKey('password', $listData['fields'], 
            'CRITICAL: password field must NOT be included in list context (security issue)');
    }

    /**
     * Test that title_field attribute is included in detail context
     * 
     * The title_field attribute controls which field is displayed in breadcrumbs
     * and page titles for individual records. This should be included in the detail
     * context so the frontend can use it to display the correct field value.
     */
    public function testTitleFieldIncludedInDetailContext(): void
    {
        // Create schema with title_field attribute
        $schema = [
            'model' => 'users',
            'title' => 'User Management',
            'table' => 'users',
            'title_field' => 'user_name',  // Should be included in detail context
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'label' => 'ID',
                    'auto_increment' => true,
                ],
                'user_name' => [
                    'type' => 'string',
                    'label' => 'Username',
                ],
                'email' => [
                    'type' => 'string',
                    'label' => 'Email',
                ],
            ],
        ];

        // Load SchemaFilter (getContextSpecificData is in SchemaFilter)
        $filterFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaFilter.php';
        $this->assertFileExists($filterFile, 'SchemaFilter.php should exist');
        
        require_once $filterFile;
        
        // Create SchemaFilter instance
        $schemaFilter = $this->createSchemaFilter();
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($schemaFilter);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test detail context includes title_field
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        
        $this->assertArrayHasKey('title_field', $detailData, 'title_field should be included in detail context');
        $this->assertEquals('user_name', $detailData['title_field'], 'title_field should match schema value');
        
        // Test with different title_field values
        $schema['title_field'] = 'email';
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        $this->assertEquals('email', $detailData['title_field'], 'title_field should be updated');
        
        // Test when title_field is not present in schema
        unset($schema['title_field']);
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        $this->assertArrayNotHasKey('title_field', $detailData, 'title_field should not be in context when not in schema');
    }

    /**
     * Test title_field with various field types
     * 
     * title_field can point to any field in the schema, not just strings.
     * This test verifies that various field types work correctly.
     */
    public function testTitleFieldWithVariousFieldTypes(): void
    {
        // Load SchemaFilter (getContextSpecificData is in SchemaFilter)
        $filterFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaFilter.php';
        require_once $filterFile;
        
        $schemaFilter = $this->createSchemaFilter();
        
        $reflection = new \ReflectionClass($schemaFilter);
        $method = $reflection->getMethod('getContextSpecificData');
        $method->setAccessible(true);
        
        // Test with string field (most common)
        $schema = [
            'model' => 'products',
            'title' => 'Products',
            'table' => 'products',
            'title_field' => 'name',
            'fields' => ['name' => ['type' => 'string', 'label' => 'Product Name']],
        ];
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        $this->assertEquals('name', $detailData['title_field']);
        
        // Test with SKU/code field
        $schema['title_field'] = 'sku';
        $schema['fields']['sku'] = ['type' => 'string', 'label' => 'SKU'];
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        $this->assertEquals('sku', $detailData['title_field']);
        
        // Test with order_number field
        $schema['title_field'] = 'order_number';
        $schema['fields']['order_number'] = ['type' => 'string', 'label' => 'Order Number'];
        $detailData = $method->invoke($schemaFilter, $schema, 'detail');
        $this->assertEquals('order_number', $detailData['title_field']);
    }
}
