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
use DI\Container;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Schema Service Test
 *
 * Tests the SchemaService class functionality, particularly the dependency
 * injection handling for optional configuration.
 */
class SchemaServiceTest extends TestCase
{
    /**
     * Test SchemaService construction with missing config.schema_path
     */
    public function testSchemaServiceConstructionWithMissingConfig(): void
    {
        // Create a container without the config.schema_path entry
        $container = new Container();
        
        // This should not throw an exception and should use the default path
        $schemaService = new SchemaService($container);
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
        
        // Use reflection to check the default schema path
        $reflection = new \ReflectionClass($schemaService);
        $property = $reflection->getProperty('schemaPath');
        $property->setAccessible(true);
        
        $this->assertEquals('app/schema/crud6', $property->getValue($schemaService));
    }
    
    /**
     * Test SchemaService construction with existing config.schema_path
     */
    public function testSchemaServiceConstructionWithExistingConfig(): void
    {
        // Create a container with the config.schema_path entry
        $container = new Container();
        $container->set('config.schema_path', 'custom/schema/path');
        
        $schemaService = new SchemaService($container);
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
        
        // Use reflection to check the custom schema path
        $reflection = new \ReflectionClass($schemaService);
        $property = $reflection->getProperty('schemaPath');
        $property->setAccessible(true);
        
        $this->assertEquals('custom/schema/path', $property->getValue($schemaService));
    }

    /**
     * Test SchemaService construction with null config.schema_path (should use default)
     */
    public function testSchemaServiceConstructionWithNullConfig(): void
    {
        // Create a container with null config.schema_path entry
        $container = new Container();
        $container->set('config.schema_path', null);
        
        $schemaService = new SchemaService($container);
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
        
        // Use reflection to check the default schema path
        $reflection = new \ReflectionClass($schemaService);
        $property = $reflection->getProperty('schemaPath');
        $property->setAccessible(true);
        
        $this->assertEquals('app/schema/crud6', $property->getValue($schemaService));
    }
}