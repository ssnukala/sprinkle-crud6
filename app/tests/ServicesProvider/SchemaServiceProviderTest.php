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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaServiceProvider;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Schema Service Provider Test
 *
 * Tests the SchemaServiceProvider class to ensure it follows UserFrosting 6
 * service provider patterns and properly registers the SchemaService.
 */
class SchemaServiceProviderTest extends TestCase
{
    /**
     * Test that SchemaServiceProvider implements ServicesProviderInterface
     */
    public function testImplementsServicesProviderInterface(): void
    {
        $provider = new SchemaServiceProvider();
        
        $this->assertInstanceOf(\UserFrosting\ServicesProvider\ServicesProviderInterface::class, $provider);
    }
    
    /**
     * Test that register() returns an array
     */
    public function testRegisterReturnsArray(): void
    {
        $provider = new SchemaServiceProvider();
        $services = $provider->register();
        
        $this->assertIsArray($services);
    }
    
    /**
     * Test that SchemaService is registered in the service provider
     */
    public function testSchemaServiceIsRegistered(): void
    {
        $provider = new SchemaServiceProvider();
        $services = $provider->register();
        
        $this->assertArrayHasKey(SchemaService::class, $services);
    }
    
    /**
     * Test that SchemaService is registered with autowire
     */
    public function testSchemaServiceIsAutowired(): void
    {
        $provider = new SchemaServiceProvider();
        $services = $provider->register();
        
        $this->assertArrayHasKey(SchemaService::class, $services);
        
        // Check that the value is a DI\Definition
        $definition = $services[SchemaService::class];
        $this->assertInstanceOf(\DI\Definition\Helper\AutowireDefinitionHelper::class, $definition);
    }
}
