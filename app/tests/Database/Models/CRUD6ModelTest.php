<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Database\Models;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model;

/**
 * CRUD6Model Test
 *
 * Tests the generic CRUD6Model functionality including dynamic configuration,
 * schema-based setup, and various model operations.
 */
class CRUD6ModelTest extends TestCase
{
    /**
     * Test basic model instantiation
     */
    public function testModelInstantiation(): void
    {
        $model = new CRUD6Model();
        
        $this->assertInstanceOf(CRUD6Model::class, $model);
        $this->assertEquals('CRUD6_NOT_SET', $model->getTable());
        $this->assertEmpty($model->getFillable());
        $this->assertFalse($model->timestamps);
    }

    /**
     * Test configuring model from schema
     */
    public function testConfigureFromSchema(): void
    {
        $schema = [
            'model' => 'users',
            'table' => 'users',
            'timestamps' => true,
            'soft_delete' => false,
            'fields' => [
                'id' => [
                    'type' => 'integer',
                    'auto_increment' => true,
                    'readonly' => true
                ],
                'user_name' => [
                    'type' => 'string',
                    'required' => true
                ],
                'email' => [
                    'type' => 'string',
                    'required' => true
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'default' => true
                ],
                'metadata' => [
                    'type' => 'json'
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'readonly' => true
                ],
                'updated_at' => [
                    'type' => 'datetime',
                    'readonly' => true
                ]
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        $this->assertEquals('users', $model->getTable());
        $this->assertTrue($model->timestamps);
        
        // Check fillable attributes (should exclude auto_increment and readonly)
        $fillable = $model->getFillable();
        $this->assertContains('user_name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('metadata', $fillable);
        $this->assertNotContains('id', $fillable); // auto_increment
        $this->assertNotContains('created_at', $fillable); // readonly
        $this->assertNotContains('updated_at', $fillable); // readonly

        // Check that model is properly configured (table name and timestamps)
        $this->assertEquals('users', $model->getTable());
        $this->assertTrue($model->timestamps);
    }

    /**
     * Test soft delete configuration
     */
    public function testSoftDeleteConfiguration(): void
    {
        $schema = [
            'model' => 'products',
            'table' => 'products',
            'soft_delete' => true,
            'fields' => [
                'id' => ['type' => 'integer', 'auto_increment' => true],
                'name' => ['type' => 'string'],
                'deleted_at' => ['type' => 'datetime', 'nullable' => true]
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        $this->assertEquals('deleted_at', $model->getDeletedAtColumn());
        $this->assertFalse($model->isSoftDeleted()); // Should be false for new model
    }

    /**
     * Test field type casting configuration
     */
    public function testFieldTypeCasting(): void
    {
        $schema = [
            'model' => 'test_table',
            'table' => 'test_table',
            'fields' => [
                'id' => ['type' => 'integer'],
                'price' => ['type' => 'decimal'],
                'is_active' => ['type' => 'boolean'],
                'metadata' => ['type' => 'json'],
                'birth_date' => ['type' => 'date'],
                'created_at' => ['type' => 'datetime'],
                'name' => ['type' => 'string']
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        // Access the casts using reflection since it's protected
        $reflection = new \ReflectionClass($model);
        $castsProperty = $reflection->getProperty('casts');
        $castsProperty->setAccessible(true);
        $casts = $castsProperty->getValue($model);

        $this->assertEquals('integer', $casts['id']);
        $this->assertEquals('float', $casts['price']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('array', $casts['metadata']);
        $this->assertEquals('date', $casts['birth_date']);
        $this->assertEquals('datetime', $casts['created_at']);
        $this->assertArrayNotHasKey('name', $casts); // String types don't need casting
    }

    /**
     * Test manual table and fillable configuration
     */
    public function testManualConfiguration(): void
    {
        $model = new CRUD6Model();
        
        // Test table setting
        $model->setTable('custom_table');
        $this->assertEquals('custom_table', $model->getTable());
        
        // Test fillable setting
        $fillable = ['name', 'email', 'status'];
        $model->setFillable($fillable);
        $this->assertEquals($fillable, $model->getFillable());
        
        // Test casts setting
        $casts = ['is_active' => 'boolean', 'metadata' => 'array'];
        $model->setCasts($casts);
        
        $reflection = new \ReflectionClass($model);
        $castsProperty = $reflection->getProperty('casts');
        $castsProperty->setAccessible(true);
        $actualCasts = $castsProperty->getValue($model);
        
        $this->assertArrayHasKey('is_active', $actualCasts);
        $this->assertArrayHasKey('metadata', $actualCasts);
        $this->assertEquals('boolean', $actualCasts['is_active']);
        $this->assertEquals('array', $actualCasts['metadata']);
    }

    /**
     * Test different field type to cast mappings
     */
    public function testFieldTypeToCastMapping(): void
    {
        $model = new CRUD6Model();
        $reflection = new \ReflectionClass($model);
        $method = $reflection->getMethod('mapFieldTypeToCast');
        $method->setAccessible(true);

        $this->assertEquals('integer', $method->invoke($model, 'integer'));
        $this->assertEquals('float', $method->invoke($model, 'float'));
        $this->assertEquals('float', $method->invoke($model, 'decimal'));
        $this->assertEquals('boolean', $method->invoke($model, 'boolean'));
        $this->assertEquals('array', $method->invoke($model, 'json'));
        $this->assertEquals('date', $method->invoke($model, 'date'));
        $this->assertEquals('datetime', $method->invoke($model, 'datetime'));
        $this->assertNull($method->invoke($model, 'string'));
        $this->assertNull($method->invoke($model, 'text'));
        $this->assertNull($method->invoke($model, 'unknown_type'));
    }

    /**
     * Test database connection configuration from schema
     */
    public function testConnectionConfigurationFromSchema(): void
    {
        $schema = [
            'model' => 'users',
            'table' => 'users',
            'connection' => 'mysql_secondary',
            'fields' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string']
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        // Access the connection property using reflection since it's protected
        $reflection = new \ReflectionClass($model);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($model);

        $this->assertEquals('mysql_secondary', $connection);
    }

    /**
     * Test manual connection configuration
     */
    public function testManualConnectionConfiguration(): void
    {
        $model = new CRUD6Model();
        $model->setConnection('custom_db');

        // Access the connection property using reflection
        $reflection = new \ReflectionClass($model);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($model);

        $this->assertEquals('custom_db', $connection);
    }

    /**
     * Test connection override (schema connection overridden by setConnection)
     */
    public function testConnectionOverride(): void
    {
        $schema = [
            'model' => 'users',
            'table' => 'users',
            'connection' => 'mysql_primary',
            'fields' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string']
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        // Override the connection
        $model->setConnection('mysql_override');

        // Access the connection property using reflection
        $reflection = new \ReflectionClass($model);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($model);

        $this->assertEquals('mysql_override', $connection);
    }

    /**
     * Test null connection (use default)
     */
    public function testNullConnection(): void
    {
        $model = new CRUD6Model();
        $model->setConnection(null);

        // Access the connection property using reflection
        $reflection = new \ReflectionClass($model);
        $connectionProperty = $reflection->getProperty('connection');
        $connectionProperty->setAccessible(true);
        $connection = $connectionProperty->getValue($model);

        $this->assertNull($connection);
    }

    /**
     * Test getDeletedAtColumn returns null for empty string
     * 
     * This test validates the fix for the SQL error:
     * SQLSTATE[HY000]: General error: 1 no such column: groups.
     * (Connection: memory, SQL: select count(*) as aggregate from "groups" where "groups"."" is null)
     * 
     * The issue was that getDeletedAtColumn() could return an empty string "",
     * which would cause the query builder to generate invalid SQL with an empty column name.
     */
    public function testGetDeletedAtColumnReturnsNullForEmptyString(): void
    {
        $model = new CRUD6Model();
        
        // Set deleted_at to empty string using reflection (simulating corrupted state)
        $reflection = new \ReflectionClass($model);
        $deletedAtProperty = $reflection->getProperty('deleted_at');
        $deletedAtProperty->setAccessible(true);
        $deletedAtProperty->setValue($model, '');
        
        // getDeletedAtColumn should return null, not empty string
        $this->assertNull($model->getDeletedAtColumn(), 
            'getDeletedAtColumn() should return null when deleted_at is empty string');
    }

    /**
     * Test getDeletedAtColumn with soft delete disabled
     */
    public function testGetDeletedAtColumnWhenSoftDeleteDisabled(): void
    {
        $schema = [
            'model' => 'test_table',
            'table' => 'test_table',
            'soft_delete' => false, // Explicitly disabled
            'fields' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string']
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        // Should return null when soft delete is disabled
        $this->assertNull($model->getDeletedAtColumn(),
            'getDeletedAtColumn() should return null when soft_delete is false');
        
        // Should not have soft deletes enabled
        $this->assertFalse($model->hasSoftDeletes(),
            'hasSoftDeletes() should return false when soft_delete is false');
    }

    /**
     * Test getDeletedAtColumn with soft delete enabled
     */
    public function testGetDeletedAtColumnWhenSoftDeleteEnabled(): void
    {
        $schema = [
            'model' => 'test_table',
            'table' => 'test_table',
            'soft_delete' => true, // Enabled
            'fields' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string']
            ]
        ];

        $model = new CRUD6Model();
        $model->configureFromSchema($schema);

        // Should return 'deleted_at' when soft delete is enabled
        $this->assertEquals('deleted_at', $model->getDeletedAtColumn(),
            'getDeletedAtColumn() should return "deleted_at" when soft_delete is true');
        
        // Should have soft deletes enabled
        $this->assertTrue($model->hasSoftDeletes(),
            'hasSoftDeletes() should return true when soft_delete is true');
    }

    /**
     * Test that empty string in static config is also handled correctly
     */
    public function testGetDeletedAtColumnWithEmptyStringInStaticConfig(): void
    {
        $model = new CRUD6Model();
        
        // Manually set up a scenario where static config has empty string
        $reflection = new \ReflectionClass($model);
        $staticSchemaConfigProperty = $reflection->getProperty('staticSchemaConfig');
        $staticSchemaConfigProperty->setAccessible(true);
        
        $model->setTable('test_table');
        
        // Set static config with empty deleted_at
        $staticSchemaConfigProperty->setValue(null, [
            'test_table' => [
                'fillable' => ['name'],
                'casts' => [],
                'timestamps' => false,
                'deleted_at' => '', // Empty string in static config
            ]
        ]);
        
        // Should still return null
        $this->assertNull($model->getDeletedAtColumn(),
            'getDeletedAtColumn() should return null when static config has empty string');
    }

    /**
     * Test getQualifiedDeletedAtColumn returns null when soft deletes are disabled.
     * 
     * This test verifies the fix for the SQL error: WHERE "table"."" IS NULL
     * which was caused by getQualifiedDeletedAtColumn() not checking for null/empty.
     */
    public function testGetQualifiedDeletedAtColumnWithNullColumn(): void
    {
        $model = new CRUD6Model();
        $model->setTable('test_table');
        
        // When getDeletedAtColumn returns null, getQualifiedDeletedAtColumn should also return null
        $this->assertNull($model->getQualifiedDeletedAtColumn(),
            'getQualifiedDeletedAtColumn() should return null when soft deletes are disabled');
    }

    /**
     * Test getQualifiedDeletedAtColumn returns table-qualified column when soft deletes are enabled.
     */
    public function testGetQualifiedDeletedAtColumnWithValidColumn(): void
    {
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'soft_delete' => true,
            'timestamps' => false,
            'fields' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
            ]
        ];
        
        $model = new CRUD6Model();
        $model->configureFromSchema($schema);
        
        // Should return table-qualified column name
        $this->assertEquals('test_table.deleted_at', $model->getQualifiedDeletedAtColumn(),
            'getQualifiedDeletedAtColumn() should return qualified column name when soft deletes are enabled');
    }

    /**
     * Test getQualifiedDeletedAtColumn with empty string (edge case).
     * 
     * This ensures that even if somehow an empty string gets through,
     * getQualifiedDeletedAtColumn will return null instead of generating
     * invalid SQL like: WHERE "table"."" IS NULL
     */
    public function testGetQualifiedDeletedAtColumnWithEmptyString(): void
    {
        $model = new CRUD6Model();
        $model->setTable('test_table');
        
        // Force deleted_at to empty string using reflection (simulating corrupted state)
        $reflection = new \ReflectionClass($model);
        $deletedAtProperty = $reflection->getProperty('deleted_at');
        $deletedAtProperty->setAccessible(true);
        $deletedAtProperty->setValue($model, ''); // Force empty string
        
        // getQualifiedDeletedAtColumn should return null, not "test_table."
        $this->assertNull($model->getQualifiedDeletedAtColumn(),
            'getQualifiedDeletedAtColumn() should return null when deleted_at is empty string');
    }
}