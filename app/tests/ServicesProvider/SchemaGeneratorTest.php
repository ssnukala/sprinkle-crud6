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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaGenerator;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connection;
use Mockery;

/**
 * Schema Generator Test
 *
 * Tests the SchemaGenerator class functionality for generating complete
 * schema definitions from database introspection.
 */
class SchemaGeneratorTest extends TestCase
{
    /**
     * Clean up Mockery after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test SchemaGenerator construction
     */
    public function testSchemaGeneratorConstruction(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        
        $generator = new SchemaGenerator($db, $scanner);
        
        $this->assertInstanceOf(SchemaGenerator::class, $generator);
    }

    /**
     * Test mapDatabaseType converts database types to schema types
     */
    public function testMapDatabaseType(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('mapDatabaseType');
        $method->setAccessible(true);
        
        // Test integer types
        $this->assertEquals('integer', $method->invoke($generator, 'int'));
        $this->assertEquals('integer', $method->invoke($generator, 'bigint'));
        $this->assertEquals('integer', $method->invoke($generator, 'smallint'));
        
        // Test string types
        $this->assertEquals('string', $method->invoke($generator, 'varchar'));
        $this->assertEquals('string', $method->invoke($generator, 'varchar(255)'));
        $this->assertEquals('text', $method->invoke($generator, 'text'));
        
        // Test decimal types
        $this->assertEquals('decimal', $method->invoke($generator, 'decimal'));
        $this->assertEquals('float', $method->invoke($generator, 'float'));
        
        // Test boolean types
        $this->assertEquals('boolean', $method->invoke($generator, 'boolean'));
        
        // Test date types
        $this->assertEquals('date', $method->invoke($generator, 'date'));
        $this->assertEquals('datetime', $method->invoke($generator, 'datetime'));
        
        // Test JSON types
        $this->assertEquals('json', $method->invoke($generator, 'json'));
    }

    /**
     * Test generateLabel creates human-readable labels
     */
    public function testGenerateLabel(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateLabel');
        $method->setAccessible(true);
        
        // Test common fields
        $this->assertEquals('ID', $method->invoke($generator, 'id'));
        $this->assertEquals('Email Address', $method->invoke($generator, 'email'));
        $this->assertEquals('Created At', $method->invoke($generator, 'created_at'));
        
        // Test foreign keys (should remove _id)
        $this->assertEquals('User', $method->invoke($generator, 'user_id'));
        $this->assertEquals('Category', $method->invoke($generator, 'category_id'));
        
        // Test underscored names
        $this->assertEquals('First Name', $method->invoke($generator, 'first_name'));
        $this->assertEquals('Product Name', $method->invoke($generator, 'product_name'));
    }

    /**
     * Test generateTitle creates table titles
     */
    public function testGenerateTitle(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateTitle');
        $method->setAccessible(true);
        
        $this->assertEquals('Users Management', $method->invoke($generator, 'users'));
        $this->assertEquals('Product Categories Management', $method->invoke($generator, 'product_categories'));
    }

    /**
     * Test generateSingularTitle creates singular titles
     */
    public function testGenerateSingularTitle(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateSingularTitle');
        $method->setAccessible(true);
        
        $this->assertEquals('User', $method->invoke($generator, 'users'));
        $this->assertEquals('Product', $method->invoke($generator, 'products'));
        $this->assertEquals('Category', $method->invoke($generator, 'categories'));
    }

    /**
     * Test detectPrimaryKey finds primary key column
     */
    public function testDetectPrimaryKey(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('detectPrimaryKey');
        $method->setAccessible(true);
        
        $columns = [
            'id' => ['name' => 'id', 'key' => 'PRI'],
            'name' => ['name' => 'name', 'key' => ''],
        ];
        
        $this->assertEquals('id', $method->invoke($generator, $columns));
        
        // Test with custom primary key
        $columns = [
            'uuid' => ['name' => 'uuid', 'key' => 'PRI'],
            'name' => ['name' => 'name', 'key' => ''],
        ];
        
        $this->assertEquals('uuid', $method->invoke($generator, $columns));
        
        // Test with no primary key (should default to 'id')
        $columns = [
            'name' => ['name' => 'name', 'key' => ''],
        ];
        
        $this->assertEquals('id', $method->invoke($generator, $columns));
    }

    /**
     * Test detectTimestamps identifies timestamp columns
     */
    public function testDetectTimestamps(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('detectTimestamps');
        $method->setAccessible(true);
        
        // Test with both timestamp columns
        $columns = [
            'id' => ['name' => 'id'],
            'created_at' => ['name' => 'created_at'],
            'updated_at' => ['name' => 'updated_at'],
        ];
        
        $this->assertTrue($method->invoke($generator, $columns));
        
        // Test with only created_at
        $columns = [
            'id' => ['name' => 'id'],
            'created_at' => ['name' => 'created_at'],
        ];
        
        $this->assertFalse($method->invoke($generator, $columns));
        
        // Test with no timestamps
        $columns = [
            'id' => ['name' => 'id'],
            'name' => ['name' => 'name'],
        ];
        
        $this->assertFalse($method->invoke($generator, $columns));
    }

    /**
     * Test generatePermissionsTemplate creates permission structure
     */
    public function testGeneratePermissionsTemplate(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generatePermissionsTemplate');
        $method->setAccessible(true);
        
        $result = $method->invoke($generator, 'users');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('read', $result);
        $this->assertArrayHasKey('create', $result);
        $this->assertArrayHasKey('update', $result);
        $this->assertArrayHasKey('delete', $result);
        
        $this->assertEquals('uri_users', $result['read']);
        $this->assertEquals('create_user', $result['create']);
        $this->assertEquals('update_user', $result['update']);
        $this->assertEquals('delete_user', $result['delete']);
    }

    /**
     * Test generateDefaultSort creates sort configuration
     */
    public function testGenerateDefaultSort(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateDefaultSort');
        $method->setAccessible(true);
        
        // Test with 'name' field (preferred)
        $fields = [
            'id' => ['sortable' => true],
            'name' => ['sortable' => true],
            'created_at' => ['sortable' => true],
        ];
        
        $result = $method->invoke($generator, $fields);
        $this->assertEquals(['name' => 'asc'], $result);
        
        // Test with 'title' field (also preferred)
        $fields = [
            'id' => ['sortable' => true],
            'title' => ['sortable' => true],
        ];
        
        $result = $method->invoke($generator, $fields);
        $this->assertEquals(['title' => 'asc'], $result);
        
        // Test fallback to 'id'
        $fields = [
            'id' => ['sortable' => true],
            'description' => ['sortable' => false],
        ];
        
        $result = $method->invoke($generator, $fields);
        $this->assertEquals(['id' => 'asc'], $result);
    }

    /**
     * Test parseDefaultValue handles different types
     */
    public function testParseDefaultValue(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('parseDefaultValue');
        $method->setAccessible(true);
        
        // Test integer
        $this->assertEquals(42, $method->invoke($generator, '42', 'integer'));
        
        // Test float
        $this->assertEquals(3.14, $method->invoke($generator, '3.14', 'float'));
        
        // Test boolean
        $this->assertTrue($method->invoke($generator, '1', 'boolean'));
        $this->assertTrue($method->invoke($generator, 'true', 'boolean'));
        $this->assertFalse($method->invoke($generator, '0', 'boolean'));
        
        // Test string
        $this->assertEquals('default', $method->invoke($generator, 'default', 'string'));
        
        // Test JSON
        $result = $method->invoke($generator, '{"key": "value"}', 'json');
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
    }

    /**
     * Test generateFieldDefinition creates proper field structure
     */
    public function testGenerateFieldDefinition(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateFieldDefinition');
        $method->setAccessible(true);
        
        // Test basic string field
        $columnInfo = [
            'type' => 'varchar',
            'nullable' => false,
            'key' => '',
            'default' => null,
            'extra' => '',
            'max_length' => 255,
        ];
        
        $result = $method->invoke($generator, 'name', $columnInfo);
        
        $this->assertEquals('string', $result['type']);
        $this->assertEquals('Name', $result['label']);
        $this->assertTrue($result['required']);
        $this->assertTrue($result['sortable']);
        $this->assertTrue($result['filterable']);
        $this->assertTrue($result['searchable']);
        $this->assertArrayHasKey('validation', $result);
        $this->assertEquals(255, $result['validation']['length']['max']);
        
        // Test primary key field
        $columnInfo = [
            'type' => 'int',
            'nullable' => false,
            'key' => 'PRI',
            'default' => null,
            'extra' => 'auto_increment',
            'max_length' => null,
        ];
        
        $result = $method->invoke($generator, 'id', $columnInfo);
        
        $this->assertEquals('integer', $result['type']);
        $this->assertTrue($result['readonly']);
        $this->assertTrue($result['auto_increment']);
        $this->assertFalse($result['required']);
        
        // Test nullable field
        $columnInfo = [
            'type' => 'varchar',
            'nullable' => true,
            'key' => '',
            'default' => null,
            'extra' => '',
            'max_length' => 100,
        ];
        
        $result = $method->invoke($generator, 'description', $columnInfo);
        
        $this->assertFalse($result['required']);
    }

    /**
     * Test saveSchemaToFile creates JSON file
     */
    public function testSaveSchemaToFile(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = Mockery::mock(DatabaseScanner::class);
        $generator = new SchemaGenerator($db, $scanner);
        
        $schema = [
            'model' => 'test',
            'table' => 'test',
            'fields' => [],
        ];
        
        $tmpFile = sys_get_temp_dir() . '/test_schema_' . uniqid() . '.json';
        
        $result = $generator->saveSchemaToFile($schema, $tmpFile);
        
        $this->assertTrue($result);
        $this->assertFileExists($tmpFile);
        
        $content = file_get_contents($tmpFile);
        $decoded = json_decode($content, true);
        
        $this->assertEquals('test', $decoded['model']);
        
        // Cleanup
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}
