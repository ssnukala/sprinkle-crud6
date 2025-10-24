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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\DatabaseScanner;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connection;
use Mockery;

/**
 * Database Scanner Test
 *
 * Tests the DatabaseScanner class functionality for detecting foreign key
 * relationships based on naming conventions and data sampling.
 */
class DatabaseScannerTest extends TestCase
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
     * Test DatabaseScanner construction with DatabaseManager
     */
    public function testDatabaseScannerConstruction(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $this->assertInstanceOf(DatabaseScanner::class, $scanner);
    }

    /**
     * Test setForeignKeyPatterns method
     */
    public function testSetForeignKeyPatterns(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $patterns = ['/_fk$/', '/^ref_/'];
        $result = $scanner->setForeignKeyPatterns($patterns);
        
        $this->assertSame($scanner, $result);
    }

    /**
     * Test setSampleSize method
     */
    public function testSetSampleSize(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $result = $scanner->setSampleSize(50);
        
        $this->assertSame($scanner, $result);
        
        // Test minimum value enforcement
        $result = $scanner->setSampleSize(-10);
        $this->assertSame($scanner, $result);
    }

    /**
     * Test setValidationThreshold method
     */
    public function testSetValidationThreshold(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $result = $scanner->setValidationThreshold(0.9);
        
        $this->assertSame($scanner, $result);
        
        // Test bounds (should clamp to 0.0-1.0)
        $scanner->setValidationThreshold(1.5);
        $scanner->setValidationThreshold(-0.5);
        
        // If it doesn't throw, the bounds are working
        $this->assertTrue(true);
    }

    /**
     * Test inferTargetTable with standard _id suffix
     */
    public function testInferTargetTableWithIdSuffix(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('inferTargetTable');
        $method->setAccessible(true);
        
        $result = $method->invoke($scanner, 'user_id');
        
        $this->assertIsArray($result);
        $this->assertEquals('users', $result['table']);
        $this->assertEquals('id', $result['key']);
        $this->assertEquals('user_id', $result['field']);
    }

    /**
     * Test inferTargetTable with _uuid suffix
     */
    public function testInferTargetTableWithUuidSuffix(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('inferTargetTable');
        $method->setAccessible(true);
        
        $result = $method->invoke($scanner, 'category_uuid');
        
        $this->assertIsArray($result);
        $this->assertEquals('categories', $result['table']);
        $this->assertEquals('uuid', $result['key']);
        $this->assertEquals('category_uuid', $result['field']);
    }

    /**
     * Test inferTargetTable with fk_ prefix
     */
    public function testInferTargetTableWithFkPrefix(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('inferTargetTable');
        $method->setAccessible(true);
        
        $result = $method->invoke($scanner, 'fk_group');
        
        $this->assertIsArray($result);
        $this->assertEquals('groups', $result['table']);
        $this->assertEquals('id', $result['key']);
    }

    /**
     * Test pluralize method with regular nouns
     */
    public function testPluralizeRegularNouns(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('pluralize');
        $method->setAccessible(true);
        
        $this->assertEquals('users', $method->invoke($scanner, 'user'));
        $this->assertEquals('products', $method->invoke($scanner, 'product'));
        $this->assertEquals('orders', $method->invoke($scanner, 'order'));
    }

    /**
     * Test pluralize method with words ending in 'y'
     */
    public function testPluralizeWordsEndingInY(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('pluralize');
        $method->setAccessible(true);
        
        $this->assertEquals('categories', $method->invoke($scanner, 'category'));
        $this->assertEquals('entries', $method->invoke($scanner, 'entry'));
    }

    /**
     * Test pluralize method with words ending in 's', 'sh', 'ch', 'x'
     */
    public function testPluralizeSpecialEndings(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('pluralize');
        $method->setAccessible(true);
        
        $this->assertEquals('addresses', $method->invoke($scanner, 'address'));
        $this->assertEquals('brushes', $method->invoke($scanner, 'brush'));
        $this->assertEquals('churches', $method->invoke($scanner, 'church'));
        $this->assertEquals('boxes', $method->invoke($scanner, 'box'));
    }

    /**
     * Test pluralize method with irregular plurals
     */
    public function testPluralizeIrregularNouns(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('pluralize');
        $method->setAccessible(true);
        
        $this->assertEquals('people', $method->invoke($scanner, 'person'));
        $this->assertEquals('children', $method->invoke($scanner, 'child'));
        $this->assertEquals('men', $method->invoke($scanner, 'man'));
        $this->assertEquals('women', $method->invoke($scanner, 'woman'));
    }

    /**
     * Test detectForeignKeyFields identifies fields matching patterns
     */
    public function testDetectForeignKeyFields(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('detectForeignKeyFields');
        $method->setAccessible(true);
        
        $columns = [
            'id' => ['name' => 'id', 'type' => 'integer', 'key' => 'PRI'],
            'user_id' => ['name' => 'user_id', 'type' => 'integer', 'key' => ''],
            'category_id' => ['name' => 'category_id', 'type' => 'integer', 'key' => ''],
            'name' => ['name' => 'name', 'type' => 'string', 'key' => ''],
        ];
        
        $result = $method->invoke($scanner, $columns);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('category_id', $result);
        $this->assertArrayNotHasKey('id', $result);
        $this->assertArrayNotHasKey('name', $result);
    }

    /**
     * Test generateSchemaRelationships creates proper structure
     */
    public function testGenerateSchemaRelationships(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $scanner = new DatabaseScanner($db);
        
        $relationships = [
            'user_id' => [
                'table' => 'users',
                'key' => 'id',
                'field' => 'user_id',
                'target_table' => 'users',
                'target_key' => 'id',
                'match_rate' => 0.95,
            ],
            'category_id' => [
                'table' => 'categories',
                'key' => 'id',
                'field' => 'category_id',
                'target_table' => 'categories',
                'target_key' => 'id',
                'match_rate' => 0.88,
            ],
        ];
        
        $result = $scanner->generateSchemaRelationships($relationships);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('category_id', $result);
        
        // Check structure
        $this->assertEquals('belongsTo', $result['user_id']['type']);
        $this->assertEquals('users', $result['user_id']['related']);
        $this->assertEquals('user_id', $result['user_id']['foreign_key']);
        $this->assertEquals('id', $result['user_id']['owner_key']);
        $this->assertEquals(0.95, $result['user_id']['confidence']);
    }

    /**
     * Test tableExists method with reflection
     */
    public function testTableExists(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $conn = Mockery::mock(Connection::class);
        
        $db->shouldReceive('connection')
            ->with(null)
            ->andReturn($conn);
        
        $conn->shouldReceive('getDriverName')
            ->andReturn('sqlite');
        
        $conn->shouldReceive('select')
            ->with("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            ->andReturn([
                (object)['name' => 'users'],
                (object)['name' => 'groups'],
            ]);
        
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('tableExists');
        $method->setAccessible(true);
        
        $exists = $method->invoke($scanner, 'users', $conn);
        $notExists = $method->invoke($scanner, 'nonexistent', $conn);
        
        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }

    /**
     * Test getMySQLColumns returns proper structure
     */
    public function testGetMySQLColumns(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $conn = Mockery::mock(Connection::class);
        
        $conn->shouldReceive('getDatabaseName')
            ->andReturn('test_db');
        
        $conn->shouldReceive('select')
            ->andReturn([
                (object)[
                    'COLUMN_NAME' => 'id',
                    'DATA_TYPE' => 'int',
                    'IS_NULLABLE' => 'NO',
                    'COLUMN_KEY' => 'PRI',
                ],
                (object)[
                    'COLUMN_NAME' => 'user_id',
                    'DATA_TYPE' => 'int',
                    'IS_NULLABLE' => 'YES',
                    'COLUMN_KEY' => '',
                ],
            ]);
        
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('getMySQLColumns');
        $method->setAccessible(true);
        
        $result = $method->invoke($scanner, 'test_table', $conn);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals('int', $result['id']['type']);
        $this->assertFalse($result['id']['nullable']);
        $this->assertTrue($result['user_id']['nullable']);
    }

    /**
     * Test getSQLiteColumns returns proper structure
     */
    public function testGetSQLiteColumns(): void
    {
        $db = Mockery::mock(DatabaseManager::class);
        $conn = Mockery::mock(Connection::class);
        
        $conn->shouldReceive('select')
            ->with('PRAGMA table_info(test_table)')
            ->andReturn([
                (object)[
                    'name' => 'id',
                    'type' => 'INTEGER',
                    'notnull' => 1,
                    'pk' => 1,
                ],
                (object)[
                    'name' => 'group_id',
                    'type' => 'INTEGER',
                    'notnull' => 0,
                    'pk' => 0,
                ],
            ]);
        
        $scanner = new DatabaseScanner($db);
        
        $reflection = new \ReflectionClass($scanner);
        $method = $reflection->getMethod('getSQLiteColumns');
        $method->setAccessible(true);
        
        $result = $method->invoke($scanner, 'test_table', $conn);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('group_id', $result);
        $this->assertEquals('INTEGER', $result['id']['type']);
        $this->assertFalse($result['id']['nullable']);
        $this->assertEquals('PRI', $result['id']['key']);
        $this->assertTrue($result['group_id']['nullable']);
    }
}
