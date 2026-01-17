<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Testing;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaBuilder;

/**
 * Tests for SchemaBuilder helper class.
 * 
 * Demonstrates usage patterns and validates schema generation.
 */
class SchemaBuilderTest extends TestCase
{
    /**
     * Test basic schema creation.
     */
    public function testBasicSchemaCreation(): void
    {
        $schema = SchemaBuilder::create('test_model', 'test_table')->build();

        $this->assertArrayHasKey('model', $schema);
        $this->assertArrayHasKey('table', $schema);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertEquals('test_model', $schema['model']);
        $this->assertEquals('test_table', $schema['table']);
        $this->assertEquals('id', $schema['primary_key']);
    }

    /**
     * Test adding string fields.
     */
    public function testAddStringField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addStringField('user_name', required: true, sortable: true, filterable: true, listable: true)
            ->build();

        $this->assertArrayHasKey('user_name', $schema['fields']);
        $field = $schema['fields']['user_name'];
        
        $this->assertEquals('string', $field['type']);
        $this->assertTrue($field['required']);
        $this->assertTrue($field['sortable']);
        $this->assertTrue($field['filterable']);
        $this->assertContains('list', $field['show_in']);
        $this->assertContains('form', $field['show_in']);
    }

    /**
     * Test adding integer fields.
     */
    public function testAddIntegerField(): void
    {
        $schema = SchemaBuilder::create('products', 'products')
            ->addIntegerField('id', autoIncrement: true, readonly: true)
            ->addIntegerField('quantity', required: true, listable: true)
            ->build();

        $this->assertArrayHasKey('id', $schema['fields']);
        $this->assertArrayHasKey('quantity', $schema['fields']);
        
        $idField = $schema['fields']['id'];
        $this->assertTrue($idField['auto_increment'] ?? false);
        $this->assertTrue($idField['readonly'] ?? false);
        
        $qtyField = $schema['fields']['quantity'];
        $this->assertTrue($qtyField['required']);
    }

    /**
     * Test adding boolean fields.
     */
    public function testAddBooleanField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addBooleanField('flag_enabled', listable: true, default: true)
            ->build();

        $this->assertArrayHasKey('flag_enabled', $schema['fields']);
        $field = $schema['fields']['flag_enabled'];
        
        $this->assertEquals('boolean', $field['type']);
        $this->assertTrue($field['default']);
    }

    /**
     * Test adding datetime fields.
     */
    public function testAddDateTimeField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addDateTimeField('created_at', readonly: true)
            ->build();

        $this->assertArrayHasKey('created_at', $schema['fields']);
        $field = $schema['fields']['created_at'];
        
        $this->assertEquals('datetime', $field['type']);
        $this->assertTrue($field['readonly']);
        $this->assertEquals('Y-m-d H:i:s', $field['date_format']);
    }

    /**
     * Test adding email fields.
     */
    public function testAddEmailField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addEmailField('email', required: true, unique: true, listable: true)
            ->build();

        $this->assertArrayHasKey('email', $schema['fields']);
        $field = $schema['fields']['email'];
        
        $this->assertEquals('string', $field['type']);
        $this->assertTrue($field['validation']['email']);
        $this->assertTrue($field['validation']['unique']);
        $this->assertTrue($field['validation']['required']);
    }

    /**
     * Test adding password fields.
     */
    public function testAddPasswordField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addPasswordField('password', required: true, minLength: 10)
            ->build();

        $this->assertArrayHasKey('password', $schema['fields']);
        $field = $schema['fields']['password'];
        
        $this->assertEquals('string', $field['type']);
        $this->assertEquals(10, $field['validation']['length']['min']);
        $this->assertTrue($field['validation']['required']);
        $this->assertContains('form', $field['show_in']);
        $this->assertNotContains('list', $field['show_in']);
    }

    /**
     * Test setting permissions.
     */
    public function testAddPermissions(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->addPermissions([
                'read' => 'uri_users',
                'create' => 'create_user',
                'update' => 'update_user',
                'delete' => 'delete_user',
            ])
            ->build();

        $this->assertArrayHasKey('permissions', $schema);
        $this->assertEquals('uri_users', $schema['permissions']['read']);
        $this->assertEquals('create_user', $schema['permissions']['create']);
        $this->assertEquals('update_user', $schema['permissions']['update']);
        $this->assertEquals('delete_user', $schema['permissions']['delete']);
    }

    /**
     * Test setting primary key.
     */
    public function testSetPrimaryKey(): void
    {
        $schema = SchemaBuilder::create('test', 'test')
            ->setPrimaryKey('uuid')
            ->build();

        $this->assertEquals('uuid', $schema['primary_key']);
    }

    /**
     * Test setting title field.
     */
    public function testSetTitleField(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->setTitleField('user_name')
            ->build();

        $this->assertEquals('user_name', $schema['title_field']);
    }

    /**
     * Test setting default sort.
     */
    public function testSetDefaultSort(): void
    {
        $schema = SchemaBuilder::create('users', 'users')
            ->setDefaultSort('created_at', 'desc')
            ->build();

        $this->assertEquals(['created_at' => 'desc'], $schema['default_sort']);
    }

    /**
     * Test adding relationships.
     */
    public function testAddDetail(): void
    {
        $schema = SchemaBuilder::create('groups', 'groups')
            ->addDetail('users', 'group_id', ['user_name', 'email'], 'CRUD6.GROUP.USERS')
            ->build();

        $this->assertArrayHasKey('details', $schema);
        $this->assertCount(1, $schema['details']);
        
        $detail = $schema['details'][0];
        $this->assertEquals('users', $detail['model']);
        $this->assertEquals('group_id', $detail['foreign_key']);
        $this->assertEquals(['user_name', 'email'], $detail['list_fields']);
        $this->assertEquals('CRUD6.GROUP.USERS', $detail['title']);
    }

    /**
     * Test fluent API chaining.
     */
    public function testFluentApiChaining(): void
    {
        $schema = SchemaBuilder::create('products', 'products')
            ->setPrimaryKey('id')
            ->setTitleField('name')
            ->setDefaultSort('name', 'asc')
            ->addPermissions(['read' => 'uri_products'])
            ->addStringField('name', required: true, listable: true)
            ->addIntegerField('quantity', listable: true)
            ->build();

        $this->assertEquals('products', $schema['model']);
        $this->assertEquals('id', $schema['primary_key']);
        $this->assertEquals('name', $schema['title_field']);
        $this->assertArrayHasKey('name', $schema['fields']);
        $this->assertArrayHasKey('quantity', $schema['fields']);
    }

    /**
     * Test JSON export.
     */
    public function testToJson(): void
    {
        $json = SchemaBuilder::create('test', 'test')
            ->addStringField('name', required: true)
            ->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('test', $decoded['model']);
    }

    /**
     * Test user schema helper.
     */
    public function testUserSchemaHelper(): void
    {
        $schema = SchemaBuilder::userSchema();

        $this->assertEquals('users', $schema['model']);
        $this->assertEquals('users', $schema['table']);
        $this->assertArrayHasKey('user_name', $schema['fields']);
        $this->assertArrayHasKey('email', $schema['fields']);
        $this->assertArrayHasKey('password', $schema['fields']);
        $this->assertArrayHasKey('permissions', $schema);
    }

    /**
     * Test group schema helper.
     */
    public function testGroupSchemaHelper(): void
    {
        $schema = SchemaBuilder::groupSchema();

        $this->assertEquals('groups', $schema['model']);
        $this->assertEquals('groups', $schema['table']);
        $this->assertArrayHasKey('name', $schema['fields']);
        $this->assertArrayHasKey('slug', $schema['fields']);
        $this->assertArrayHasKey('description', $schema['fields']);
    }

    /**
     * Test product schema helper.
     */
    public function testProductSchemaHelper(): void
    {
        $schema = SchemaBuilder::productSchema();

        $this->assertEquals('products', $schema['model']);
        $this->assertEquals('products', $schema['table']);
        $this->assertArrayHasKey('sku', $schema['fields']);
        $this->assertArrayHasKey('name', $schema['fields']);
        $this->assertArrayHasKey('price', $schema['fields']);
        $this->assertArrayHasKey('quantity', $schema['fields']);
    }

    /**
     * Test adding custom field with full control.
     */
    public function testAddCustomField(): void
    {
        $schema = SchemaBuilder::create('test', 'test')
            ->addCustomField('custom_field', [
                'type' => 'custom_type',
                'label' => 'Custom Label',
                'custom_property' => 'custom_value',
            ])
            ->build();

        $this->assertArrayHasKey('custom_field', $schema['fields']);
        $field = $schema['fields']['custom_field'];
        $this->assertEquals('custom_type', $field['type']);
        $this->assertEquals('Custom Label', $field['label']);
        $this->assertEquals('custom_value', $field['custom_property']);
    }

    /**
     * Test complex schema with multiple field types.
     */
    public function testComplexSchema(): void
    {
        $schema = SchemaBuilder::create('orders', 'orders')
            ->setPrimaryKey('order_id')
            ->setTitleField('order_number')
            ->setDefaultSort('created_at', 'desc')
            ->addPermissions([
                'read' => 'view_orders',
                'create' => 'create_order',
                'update' => 'update_order',
                'delete' => 'delete_order',
            ])
            ->addIntegerField('order_id', autoIncrement: true, readonly: true)
            ->addStringField('order_number', required: true, unique: true, listable: true)
            ->addCustomField('total', [
                'type' => 'decimal',
                'label' => 'CRUD6.ORDERS.TOTAL',
                'required' => true,
                'listable' => true,
            ])
            ->addDateTimeField('order_date', required: true, listable: true)
            ->addStringField('status', listable: true)
            ->addTextField('notes')
            ->addDetail('order_items', 'order_id', ['product_name', 'quantity', 'price'])
            ->build();

        // Verify structure
        $this->assertEquals('orders', $schema['model']);
        $this->assertEquals('order_id', $schema['primary_key']);
        $this->assertEquals('order_number', $schema['title_field']);
        
        // Verify fields
        $this->assertCount(6, $schema['fields']);
        $this->assertArrayHasKey('order_number', $schema['fields']);
        $this->assertArrayHasKey('total', $schema['fields']);
        
        // Verify relationships
        $this->assertCount(1, $schema['details']);
        
        // Verify permissions
        $this->assertCount(4, $schema['permissions']);
    }

    /**
     * Test that schema builder creates valid JSON structure.
     */
    public function testSchemaValidJsonStructure(): void
    {
        $schema = SchemaBuilder::create('test', 'test')
            ->addStringField('name')
            ->build();

        // Validate it can be encoded and decoded
        $json = json_encode($schema);
        $this->assertNotFalse($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals($schema, $decoded);
    }

    /**
     * Test setting database connection.
     */
    public function testSetConnection(): void
    {
        $schema = SchemaBuilder::create('test', 'test')
            ->setConnection('analytics_db')
            ->build();

        $this->assertEquals('analytics_db', $schema['connection']);
    }
}
