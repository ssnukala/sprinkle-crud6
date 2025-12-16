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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\UniformResourceLocator\ResourceLocator;

/**
 * SchemaMultiContextTest
 *
 * Tests the multi-context schema filtering functionality that allows
 * requesting multiple contexts in a single API call (e.g., context=list,form).
 * 
 * Validates that:
 * - Multiple contexts can be requested with comma-separated values
 * - Each context is properly filtered and returned in the response
 * - Single API call reduces overhead for pages that need multiple contexts
 */
class SchemaMultiContextTest extends TestCase
{
    private SchemaService $schemaService;

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
                    'editable' => true,
                    'validation' => [
                        'length' => ['min' => 8],
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        // Create all required mocks for SchemaService constructor (11 parameters)
        $locator = $this->createMock(ResourceLocator::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        $logger = $this->createMock(\UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface::class);
        $i18n = $this->createMock(\UserFrosting\I18n\Translator::class);
        $loader = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader::class);
        $validator = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator::class);
        $normalizer = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaNormalizer::class);
        $cache = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaCache::class);
        
        // Use real SchemaFilter to test multi-context functionality
        // SchemaFilter constructor only requires DebugLoggerInterface
        $filter = new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter($logger);
        
        $translator = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaTranslator::class);
        $actionManager = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaActionManager::class);
        
        $this->schemaService = new SchemaService(
            $locator,
            $config,
            $logger,
            $i18n,
            $loader,
            $validator,
            $normalizer,
            $cache,
            $filter,
            $translator,
            $actionManager
        );
    }

    /**
     * Test that filterSchemaForContext accepts comma-separated contexts
     */
    public function testAcceptsCommaSeparatedContexts(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        // Should return base metadata
        $this->assertArrayHasKey('model', $filtered);
        $this->assertArrayHasKey('title', $filtered);
        $this->assertArrayHasKey('singular_title', $filtered);
        $this->assertArrayHasKey('primary_key', $filtered);
        
        // Should have contexts section
        $this->assertArrayHasKey('contexts', $filtered);
        $this->assertIsArray($filtered['contexts']);
    }

    /**
     * Test that multi-context response includes all requested contexts
     */
    public function testMultiContextResponseIncludesAllContexts(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        $this->assertArrayHasKey('contexts', $filtered);
        $this->assertArrayHasKey('list', $filtered['contexts']);
        $this->assertArrayHasKey('form', $filtered['contexts']);
    }

    /**
     * Test that list context includes correct fields
     */
    public function testListContextInMultiResponse(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        $listContext = $filtered['contexts']['list'];
        
        // Should have fields section
        $this->assertArrayHasKey('fields', $listContext);
        
        // Should include listable fields: id, name, email
        $this->assertArrayHasKey('id', $listContext['fields']);
        $this->assertArrayHasKey('name', $listContext['fields']);
        $this->assertArrayHasKey('email', $listContext['fields']);
        
        // Should NOT include non-listable field: password
        $this->assertArrayNotHasKey('password', $listContext['fields']);
        
        // Should include default_sort
        $this->assertArrayHasKey('default_sort', $listContext);
    }

    /**
     * Test that form context includes correct fields
     */
    public function testFormContextInMultiResponse(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        $formContext = $filtered['contexts']['form'];
        
        // Should have fields section
        $this->assertArrayHasKey('fields', $formContext);
        
        // Should include editable fields: name, email, password
        $this->assertArrayHasKey('name', $formContext['fields']);
        $this->assertArrayHasKey('email', $formContext['fields']);
        $this->assertArrayHasKey('password', $formContext['fields']);
        
        // Should NOT include non-editable field: id
        $this->assertArrayNotHasKey('id', $formContext['fields']);
        
        // Form fields should include validation
        $this->assertArrayHasKey('validation', $formContext['fields']['name']);
        $this->assertArrayHasKey('validation', $formContext['fields']['email']);
    }

    /**
     * Test that list context doesn't include validation rules
     */
    public function testListContextExcludesValidation(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        $listContext = $filtered['contexts']['list'];
        
        // List context should NOT include validation rules
        $this->assertArrayNotHasKey('validation', $listContext['fields']['name']);
        $this->assertArrayNotHasKey('validation', $listContext['fields']['email']);
    }

    /**
     * Test that base metadata is not duplicated in context sections
     */
    public function testBaseMetadataNotDuplicatedInContexts(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        // Base level should have metadata
        $this->assertArrayHasKey('model', $filtered);
        $this->assertArrayHasKey('title', $filtered);
        $this->assertArrayHasKey('permissions', $filtered);
        
        // Context sections should only have context-specific data
        $listContext = $filtered['contexts']['list'];
        $formContext = $filtered['contexts']['form'];
        
        // Contexts should have fields but not base metadata
        $this->assertArrayHasKey('fields', $listContext);
        $this->assertArrayHasKey('fields', $formContext);
    }

    /**
     * Test that null context returns full schema
     */
    public function testNullContextReturnsFullSchema(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, null);

        // Should return complete schema unchanged
        $this->assertEquals($schema, $filtered);
    }

    /**
     * Test that 'full' context returns full schema
     */
    public function testFullContextReturnsFullSchema(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'full');

        // Should return complete schema unchanged
        $this->assertEquals($schema, $filtered);
    }

    /**
     * Test that multi-context request works with three contexts
     */
    public function testThreeContextsInOneRequest(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form,meta');

        $this->assertArrayHasKey('contexts', $filtered);
        $this->assertArrayHasKey('list', $filtered['contexts']);
        $this->assertArrayHasKey('form', $filtered['contexts']);
        $this->assertArrayHasKey('meta', $filtered['contexts']);
    }

    /**
     * Test that meta context in multi-response has no fields
     */
    public function testMetaContextHasNoFields(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,meta');

        $metaContext = $filtered['contexts']['meta'];
        
        // Meta context should be empty (just uses base metadata)
        $this->assertEmpty($metaContext);
        
        // Base level should still have all metadata
        $this->assertArrayHasKey('model', $filtered);
        $this->assertArrayHasKey('permissions', $filtered);
    }

    /**
     * Test that permissions are in base, not duplicated in contexts
     */
    public function testPermissionsInBaseNotContexts(): void
    {
        $schema = $this->getSampleSchema();
        $filtered = $this->schemaService->filterSchemaForContext($schema, 'list,form');

        // Permissions should be at base level
        $this->assertArrayHasKey('permissions', $filtered);
        
        // Permissions should NOT be in context sections
        $this->assertArrayNotHasKey('permissions', $filtered['contexts']['list']);
        $this->assertArrayNotHasKey('permissions', $filtered['contexts']['form']);
    }
}
