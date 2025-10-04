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
}