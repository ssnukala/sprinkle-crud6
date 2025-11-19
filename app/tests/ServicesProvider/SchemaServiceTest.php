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
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * Schema Service Test
 *
 * Tests the SchemaService class functionality with ResourceLocatorInterface
 * following UserFrosting 6 patterns.
 */
class SchemaServiceTest extends TestCase
{
    /**
     * Test SchemaService construction with ResourceLocatorInterface
     */
    public function testSchemaServiceConstruction(): void
    {
        // Create a mock ResourceLocatorInterface
        $locator = $this->createMock(ResourceLocatorInterface::class);
        
        // This should not throw an exception
        $schemaService = new SchemaService($locator);
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
        
        // Use reflection to check the default schema path
        $reflection = new \ReflectionClass($schemaService);
        $property = $reflection->getProperty('schemaPath');
        $property->setAccessible(true);
        
        $this->assertEquals('schema://crud6/', $property->getValue($schemaService));
    }
    
    /**
     * Test getSchemaFilePath with connection returns connection-based path
     */
    public function testGetSchemaFilePathWithConnection(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $schemaService = new SchemaService($locator);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('getSchemaFilePath');
        $method->setAccessible(true);

        // Test with connection
        $path = $method->invoke($schemaService, 'users', 'db1');
        $this->assertEquals('schema://crud6/db1/users.json', $path);

        // Test without connection
        $path = $method->invoke($schemaService, 'users', null);
        $this->assertEquals('schema://crud6/users.json', $path);

        // Test with different connection
        $path = $method->invoke($schemaService, 'products', 'analytics');
        $this->assertEquals('schema://crud6/analytics/products.json', $path);
    }

    /**
     * Test getSchemaFilePath without connection returns default path
     */
    public function testGetSchemaFilePathWithoutConnection(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $schemaService = new SchemaService($locator);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('getSchemaFilePath');
        $method->setAccessible(true);

        $path = $method->invoke($schemaService, 'users');
        $this->assertEquals('schema://crud6/users.json', $path);
    }

    /**
     * Test applyDefaults sets default values for missing schema attributes
     */
    public function testApplyDefaultsSetsDefaultValues(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $schemaService = new SchemaService($locator);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('applyDefaults');
        $method->setAccessible(true);

        // Test schema without any defaults
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => []
        ];

        $result = $method->invoke($schemaService, $schema);

        $this->assertEquals('id', $result['primary_key']);
        $this->assertTrue($result['timestamps']);
        $this->assertFalse($result['soft_delete']);
    }

    /**
     * Test applyDefaults preserves existing values
     */
    public function testApplyDefaultsPreservesExistingValues(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $schemaService = new SchemaService($locator);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('applyDefaults');
        $method->setAccessible(true);

        // Test schema with explicit values
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'primary_key' => 'uuid',
            'timestamps' => false,
            'soft_delete' => true,
            'fields' => []
        ];

        $result = $method->invoke($schemaService, $schema);

        $this->assertEquals('uuid', $result['primary_key']);
        $this->assertFalse($result['timestamps']);
        $this->assertTrue($result['soft_delete']);
    }

    /**
     * Test applyDefaults with partial overrides
     */
    public function testApplyDefaultsWithPartialOverrides(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $schemaService = new SchemaService($locator);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('applyDefaults');
        $method->setAccessible(true);

        // Test schema with only some values set
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'primary_key' => 'custom_id',
            'fields' => []
        ];

        $result = $method->invoke($schemaService, $schema);

        $this->assertEquals('custom_id', $result['primary_key']);
        $this->assertTrue($result['timestamps']); // Default
        $this->assertFalse($result['soft_delete']); // Default
    }

    /**
     * Test normalizeBooleanTypes handles all boolean variants correctly
     */
    public function testNormalizeBooleanTypesHandlesAllVariants(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        $schemaService = new SchemaService($locator, $config);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('normalizeBooleanTypes');
        $method->setAccessible(true);

        // Test schema with all boolean type variants
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => [
                'flag_enabled' => [
                    'type' => 'boolean-tgl',
                    'label' => 'Enabled'
                ],
                'is_verified' => [
                    'type' => 'boolean-chk',
                    'label' => 'Verified'
                ],
                'accepts_marketing' => [
                    'type' => 'boolean-yn',
                    'label' => 'Accepts Marketing'
                ],
                'show_in_list' => [
                    'type' => 'boolean-sel',
                    'label' => 'Show in List'
                ],
                'is_admin' => [
                    'type' => 'boolean',
                    'label' => 'Is Admin'
                ],
            ]
        ];

        $result = $method->invoke($schemaService, $schema);

        // All boolean variants should be normalized to 'boolean' type
        $this->assertEquals('boolean', $result['fields']['flag_enabled']['type']);
        $this->assertEquals('boolean', $result['fields']['is_verified']['type']);
        $this->assertEquals('boolean', $result['fields']['accepts_marketing']['type']);
        $this->assertEquals('boolean', $result['fields']['show_in_list']['type']);
        $this->assertEquals('boolean', $result['fields']['is_admin']['type']);

        // Check that UI types are set correctly
        $this->assertEquals('toggle', $result['fields']['flag_enabled']['ui']);
        $this->assertEquals('checkbox', $result['fields']['is_verified']['ui']);
        $this->assertEquals('select', $result['fields']['accepts_marketing']['ui']);
        $this->assertEquals('select', $result['fields']['show_in_list']['ui']);
        $this->assertEquals('checkbox', $result['fields']['is_admin']['ui']);
    }

    /**
     * Test normalizeBooleanTypes preserves explicit UI configuration
     */
    public function testNormalizeBooleanTypesPreservesExplicitUI(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        $schemaService = new SchemaService($locator, $config);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('normalizeBooleanTypes');
        $method->setAccessible(true);

        // Test schema with explicit UI configuration
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => [
                'flag_enabled' => [
                    'type' => 'boolean-tgl',
                    'label' => 'Enabled',
                    'ui' => 'custom'
                ],
            ]
        ];

        $result = $method->invoke($schemaService, $schema);

        // UI should be preserved when explicitly set
        $this->assertEquals('boolean', $result['fields']['flag_enabled']['type']);
        $this->assertEquals('custom', $result['fields']['flag_enabled']['ui']);
    }

    /**
     * Test normalizeBooleanTypes handles empty or missing fields
     */
    public function testNormalizeBooleanTypesHandlesEmptyFields(): void
    {
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        $schemaService = new SchemaService($locator, $config);

        $reflection = new \ReflectionClass($schemaService);
        $method = $reflection->getMethod('normalizeBooleanTypes');
        $method->setAccessible(true);

        // Test schema without fields
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
        ];

        $result = $method->invoke($schemaService, $schema);

        // Should not throw an exception
        $this->assertEquals('test_model', $result['model']);
        $this->assertEquals('test_table', $result['table']);
    }
}