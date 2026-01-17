<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Middlewares;

use PHPUnit\Framework\TestCase;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * CRUD6Injector Test
 *
 * Tests the CRUD6Injector middleware functionality including model and connection parsing.
 */
class CRUD6InjectorTest extends TestCase
{
    /**
     * Create a real CRUD6Injector instance with mocked dependencies
     */
    private function createInjector(): CRUD6Injector
    {
        $crudModel = $this->createMock(CRUD6ModelInterface::class);
        $debugLogger = $this->createMock(DebugLoggerInterface::class);
        $schemaService = $this->createMock(SchemaService::class);
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturn(false);
        
        return new CRUD6Injector($crudModel, $debugLogger, $schemaService, $config);
    }
    
    /**
     * Test parsing model name without connection
     */
    public function testParseModelNameWithoutConnection(): void
    {
        $injector = $this->createInjector();

        $reflection = new \ReflectionClass($injector);
        $method = $reflection->getMethod('parseModelAndConnection');
        $method->setAccessible(true);

        // Parse "users" (no connection)
        $method->invoke($injector, 'users');

        $modelNameProperty = $reflection->getProperty('currentModelName');
        $modelNameProperty->setAccessible(true);
        $modelName = $modelNameProperty->getValue($injector);

        $connectionProperty = $reflection->getProperty('currentConnectionName');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($injector);

        $this->assertEquals('users', $modelName);
        $this->assertNull($connection);
    }

    /**
     * Test parsing model name with connection
     */
    public function testParseModelNameWithConnection(): void
    {
        $injector = $this->createInjector();

        $reflection = new \ReflectionClass($injector);
        $method = $reflection->getMethod('parseModelAndConnection');
        $method->setAccessible(true);

        // Parse "users@db1" (with connection)
        $method->invoke($injector, 'users@db1');

        $modelNameProperty = $reflection->getProperty('currentModelName');
        $modelNameProperty->setAccessible(true);
        $modelName = $modelNameProperty->getValue($injector);

        $connectionProperty = $reflection->getProperty('currentConnectionName');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($injector);

        $this->assertEquals('users', $modelName);
        $this->assertEquals('db1', $connection);
    }

    /**
     * Test parsing model name with multiple @ symbols (only first @ is used)
     */
    public function testParseModelNameWithMultipleAtSymbols(): void
    {
        $injector = $this->createInjector();

        $reflection = new \ReflectionClass($injector);
        $method = $reflection->getMethod('parseModelAndConnection');
        $method->setAccessible(true);

        // Parse "users@db1@backup" (with multiple @ symbols)
        $method->invoke($injector, 'users@db1@backup');

        $modelNameProperty = $reflection->getProperty('currentModelName');
        $modelNameProperty->setAccessible(true);
        $modelName = $modelNameProperty->getValue($injector);

        $connectionProperty = $reflection->getProperty('currentConnectionName');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($injector);

        $this->assertEquals('users', $modelName);
        $this->assertEquals('db1@backup', $connection); // Everything after first @ is connection
    }

    /**
     * Test validate model name
     */
    public function testValidateModelName(): void
    {
        $injector = $this->createInjector();

        $reflection = new \ReflectionClass($injector);
        $method = $reflection->getMethod('validateModelName');
        $method->setAccessible(true);

        // Valid model names
        $this->assertTrue($method->invoke($injector, 'users'));
        $this->assertTrue($method->invoke($injector, 'user_profiles'));
        $this->assertTrue($method->invoke($injector, 'Users123'));
        $this->assertTrue($method->invoke($injector, 'table_2024'));

        // Invalid model names (with special characters)
        $this->assertFalse($method->invoke($injector, 'users@db1')); // @ not allowed
        $this->assertFalse($method->invoke($injector, 'users-table')); // - not allowed
        $this->assertFalse($method->invoke($injector, 'users.table')); // . not allowed
        $this->assertFalse($method->invoke($injector, 'users/table')); // / not allowed
        $this->assertFalse($method->invoke($injector, 'users table')); // space not allowed
    }
}
