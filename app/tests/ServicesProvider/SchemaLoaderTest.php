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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader;

/**
 * Schema Loader Test.
 *
 * Tests the SchemaLoader class functionality.
 */
class SchemaLoaderTest extends TestCase
{
    /**
     * Create a mock Config instance for testing.
     * 
     * @return Config
     */
    protected function createMockConfig(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'crud6.schema_path') {
                    return $default;
                }
                return $default;
            });
        return $config;
    }

    /**
     * Test getSchemaFilePath with connection returns connection-based path.
     */
    public function testGetSchemaFilePathWithConnection(): void
    {
        $config = $this->createMockConfig();
        $loader = new SchemaLoader($config);

        $path = $loader->getSchemaFilePath('users', 'db1');
        $this->assertEquals('schema://crud6/db1/users.json', $path);

        $path = $loader->getSchemaFilePath('products', 'analytics');
        $this->assertEquals('schema://crud6/analytics/products.json', $path);
    }

    /**
     * Test getSchemaFilePath without connection returns default path.
     */
    public function testGetSchemaFilePathWithoutConnection(): void
    {
        $config = $this->createMockConfig();
        $loader = new SchemaLoader($config);

        $path = $loader->getSchemaFilePath('users');
        $this->assertEquals('schema://crud6/users.json', $path);
    }

    /**
     * Test applyDefaults sets default values for missing schema attributes.
     */
    public function testApplyDefaultsSetsDefaultValues(): void
    {
        $config = $this->createMockConfig();
        $loader = new SchemaLoader($config);

        // Test schema without any defaults
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => []
        ];

        $result = $loader->applyDefaults($schema);

        $this->assertEquals('id', $result['primary_key']);
        $this->assertTrue($result['timestamps']);
        $this->assertFalse($result['soft_delete']);
    }

    /**
     * Test applyDefaults preserves existing values.
     */
    public function testApplyDefaultsPreservesExistingValues(): void
    {
        $config = $this->createMockConfig();
        $loader = new SchemaLoader($config);

        // Test schema with explicit values
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'primary_key' => 'uuid',
            'timestamps' => false,
            'soft_delete' => true,
            'fields' => []
        ];

        $result = $loader->applyDefaults($schema);

        $this->assertEquals('uuid', $result['primary_key']);
        $this->assertFalse($result['timestamps']);
        $this->assertTrue($result['soft_delete']);
    }

    /**
     * Test applyDefaults with partial overrides.
     */
    public function testApplyDefaultsWithPartialOverrides(): void
    {
        $config = $this->createMockConfig();
        $loader = new SchemaLoader($config);

        // Test schema with only some values set
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'primary_key' => 'custom_id',
            'fields' => []
        ];

        $result = $loader->applyDefaults($schema);

        $this->assertEquals('custom_id', $result['primary_key']);
        $this->assertTrue($result['timestamps']); // Default
        $this->assertFalse($result['soft_delete']); // Default
    }
}
