<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Bakery\Helper;

use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\SchemaGenerator;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

/**
 * Test SchemaGenerator class.
 * 
 * Tests schema generation functionality including field type mapping,
 * validation rules, and JSON schema output.
 *
 * @author Srinivas Nukala
 */
class SchemaGeneratorTest extends CRUD6TestCase
{
    /**
     * Test schema generator can be instantiated
     */
    public function testSchemaGeneratorCanBeInstantiated(): void
    {
        $schemaDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $generator = new SchemaGenerator($schemaDir);

        $this->assertInstanceOf(SchemaGenerator::class, $generator);
        $this->assertEquals($schemaDir, $generator->getSchemaDirectory());
    }

    /**
     * Test schema generation
     */
    public function testSchemaGeneration(): void
    {
        $schemaDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $generator = new SchemaGenerator($schemaDir);

        $tableMetadata = [
            'name' => 'test_table',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'name' => [
                    'name' => 'name',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => 'User name',
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        $schema = $generator->generateSchema($tableMetadata);

        $this->assertIsString($schema);
        $this->assertJson($schema);

        $decoded = json_decode($schema, true);
        $this->assertArrayHasKey('model', $decoded);
        $this->assertArrayHasKey('table', $decoded);
        $this->assertArrayHasKey('fields', $decoded);
        $this->assertEquals('test_table', $decoded['table']);
        $this->assertEquals('test_table', $decoded['model']);

        // Cleanup
        if (is_dir($schemaDir)) {
            array_map('unlink', glob("$schemaDir/*"));
            rmdir($schemaDir);
        }
    }

    /**
     * Test field type mapping
     */
    public function testFieldTypeMapping(): void
    {
        $schemaDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $generator = new SchemaGenerator($schemaDir);

        $tableMetadata = [
            'name' => 'test_types',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'email' => [
                    'name' => 'email',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'active' => [
                    'name' => 'active',
                    'type' => 'boolean',
                    'length' => null,
                    'nullable' => false,
                    'default' => true,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'created_at' => [
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'length' => null,
                    'nullable' => true,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        $schema = json_decode($generator->generateSchema($tableMetadata), true);

        // Check field types are properly mapped
        $this->assertEquals('integer', $schema['fields']['id']['type']);
        $this->assertEquals('string', $schema['fields']['email']['type']);
        $this->assertEquals('boolean', $schema['fields']['active']['type']);
        $this->assertEquals('datetime', $schema['fields']['created_at']['type']);

        // Check validation rules
        $this->assertTrue($schema['fields']['email']['validation']['email'] ?? false);
        $this->assertTrue($schema['fields']['active']['validation']['required'] ?? false);

        // Cleanup
        if (is_dir($schemaDir)) {
            array_map('unlink', glob("$schemaDir/*"));
            rmdir($schemaDir);
        }
    }

    /**
     * Test that detail sections get correct list_fields from related schemas
     */
    public function testDetailSectionUsesActualFieldsFromRelatedSchema(): void
    {
        $schemaDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $generator = new SchemaGenerator($schemaDir);

        // Create parent table (users) with a related table (posts) that references it
        $usersMetadata = [
            'name' => 'users',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'username' => [
                    'name' => 'username',
                    'type' => 'string',
                    'length' => 50,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'email' => [
                    'name' => 'email',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        // Create child table (posts) with different fields
        $postsMetadata = [
            'name' => 'posts',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'user_id' => [
                    'name' => 'user_id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'title' => [
                    'name' => 'title',
                    'type' => 'string',
                    'length' => 255,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'content' => [
                    'name' => 'content',
                    'type' => 'text',
                    'length' => null,
                    'nullable' => true,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'published' => [
                    'name' => 'published',
                    'type' => 'boolean',
                    'length' => null,
                    'nullable' => false,
                    'default' => false,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'created_at' => [
                    'name' => 'created_at',
                    'type' => 'datetime',
                    'length' => null,
                    'nullable' => true,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        // Define relationships using simplified structure
        // posts table has a foreign key (user_id) that references users table
        $relationships = [
            'users' => [
                'references' => [],
            ],
            'posts' => [
                'references' => [
                    [
                        'table' => 'users',
                        'localKey' => 'user_id',   // FK column in posts table
                        'foreignKey' => 'id',      // Column in users table
                    ],
                ],
            ],
        ];

        // Generate schemas using the two-phase approach
        $tablesMetadata = [
            'users' => $usersMetadata,
            'posts' => $postsMetadata,
        ];
        
        $generatedFiles = $generator->generateSchemas($tablesMetadata, $relationships);

        // Load the generated users schema
        $usersSchemaPath = $schemaDir . '/users.json';
        $this->assertFileExists($usersSchemaPath);
        
        $usersSchema = json_decode(file_get_contents($usersSchemaPath), true);
        
        // Verify the detail section exists
        $this->assertArrayHasKey('detail', $usersSchema);
        $this->assertEquals('posts', $usersSchema['detail']['model']);
        $this->assertEquals('user_id', $usersSchema['detail']['foreign_key']);
        
        // The key test: list_fields should NOT contain hardcoded fields like 'email' or 'status'
        // that don't exist in the posts table
        $listFields = $usersSchema['detail']['list_fields'];
        
        // Should NOT contain 'email' or 'status' (which are in users but not in posts)
        $this->assertNotContains('email', $listFields, 'list_fields should not contain fields from users table');
        $this->assertNotContains('status', $listFields, 'list_fields should not contain non-existent fields');
        
        // Should contain actual fields from posts table
        $this->assertContains('id', $listFields, 'list_fields should contain id from posts');
        $this->assertContains('title', $listFields, 'list_fields should contain title from posts');
        
        // Verify the fields are actually listable in the posts schema
        $postsSchemaPath = $schemaDir . '/posts.json';
        $postsSchema = json_decode(file_get_contents($postsSchemaPath), true);
        
        foreach ($listFields as $fieldName) {
            $this->assertArrayHasKey($fieldName, $postsSchema['fields'], "Field $fieldName should exist in posts schema");
            $this->assertTrue($postsSchema['fields'][$fieldName]['listable'], "Field $fieldName should be listable");
        }

        // Cleanup
        if (is_dir($schemaDir)) {
            array_map('unlink', glob("$schemaDir/*"));
            rmdir($schemaDir);
        }
    }

    /**
     * Test junction table scenario like permission_roles.
     * 
     * Junction tables typically have only 'references' (to both parent tables),
     * but no tables reference them. This tests that the schema generation
     * works correctly for such tables.
     */
    public function testJunctionTableWithOnlyReferences(): void
    {
        $schemaDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $generator = new SchemaGenerator($schemaDir);

        // Create permissions table
        $permissionsMetadata = [
            'name' => 'permissions',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'slug' => [
                    'name' => 'slug',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        // Create roles table
        $rolesMetadata = [
            'name' => 'roles',
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'slug' => [
                    'name' => 'slug',
                    'type' => 'string',
                    'length' => 100,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['id'],
        ];

        // Create junction table (permission_roles)
        $permissionRolesMetadata = [
            'name' => 'permission_roles',
            'columns' => [
                'permission_id' => [
                    'name' => 'permission_id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
                'role_id' => [
                    'name' => 'role_id',
                    'type' => 'integer',
                    'length' => null,
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'unsigned' => false,
                    'comment' => null,
                ],
            ],
            'indexes' => [],
            'foreignKeys' => [],
            'primaryKey' => ['permission_id', 'role_id'],
        ];

        // Define relationships - permission_roles only has 'references', nothing references it
        $relationships = [
            'permissions' => [
                'references' => [],
            ],
            'roles' => [
                'references' => [],
            ],
            'permission_roles' => [
                'references' => [
                    [
                        'table' => 'permissions',
                        'localKey' => 'permission_id',
                        'foreignKey' => 'id',
                    ],
                    [
                        'table' => 'roles',
                        'localKey' => 'role_id',
                        'foreignKey' => 'id',
                    ],
                ],
            ],
        ];

        // Generate schemas
        $tablesMetadata = [
            'permissions' => $permissionsMetadata,
            'roles' => $rolesMetadata,
            'permission_roles' => $permissionRolesMetadata,
        ];
        
        $generatedFiles = $generator->generateSchemas($tablesMetadata, $relationships);

        // Load the generated permission_roles schema
        $permissionRolesSchemaPath = $schemaDir . '/permission_roles.json';
        $this->assertFileExists($permissionRolesSchemaPath);
        
        $permissionRolesSchema = json_decode(file_get_contents($permissionRolesSchemaPath), true);
        
        // Junction table should NOT have a detail section (nothing references it)
        $this->assertArrayNotHasKey('detail', $permissionRolesSchema, 
            'Junction table should not have detail section since nothing references it');
        
        // Verify it has the expected fields
        $this->assertArrayHasKey('permission_id', $permissionRolesSchema['fields']);
        $this->assertArrayHasKey('role_id', $permissionRolesSchema['fields']);
        
        // Now verify that permissions and roles tables DO have detail sections
        // pointing to permission_roles (since it references them)
        $permissionsSchemaPath = $schemaDir . '/permissions.json';
        $permissionsSchema = json_decode(file_get_contents($permissionsSchemaPath), true);
        
        $this->assertArrayHasKey('detail', $permissionsSchema,
            'Permissions table should have detail section since permission_roles references it');
        $this->assertEquals('permission_roles', $permissionsSchema['detail']['model']);
        
        $rolesSchemaPath = $schemaDir . '/roles.json';
        $rolesSchema = json_decode(file_get_contents($rolesSchemaPath), true);
        
        $this->assertArrayHasKey('detail', $rolesSchema,
            'Roles table should have detail section since permission_roles references it');
        $this->assertEquals('permission_roles', $rolesSchema['detail']['model']);

        // Cleanup
        if (is_dir($schemaDir)) {
            array_map('unlink', glob("$schemaDir/*"));
            rmdir($schemaDir);
        }
    }

    /**
     * Test schema generator creates files in database subfolder
     */
    public function testSchemaGeneratorWithDatabaseSubfolder(): void
    {
        $baseDir = sys_get_temp_dir() . '/learntegrate-test-' . uniqid();
        $dbName = 'testdb';
        $schemaDir = $baseDir . '/' . $dbName;
        
        $generator = new SchemaGenerator($schemaDir);

        $tableMetadata = [
            'name' => 'test_table',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => true,
                    'length' => null,
                ],
                [
                    'name' => 'name',
                    'type' => 'string',
                    'nullable' => false,
                    'default' => null,
                    'autoincrement' => false,
                    'length' => 100,
                ],
            ],
            'primaryKey' => ['id'],
            'foreignKeys' => [],
        ];

        $filePath = $generator->saveSchema('test_table', $generator->generateSchema($tableMetadata));
        
        // Verify the file was created in the database subfolder
        $expectedPath = $schemaDir . '/test_table.json';
        $this->assertFileExists($expectedPath);
        $this->assertEquals($expectedPath, $filePath);
        
        // Verify the directory structure includes the database subfolder
        $this->assertStringContainsString($dbName, $filePath);
        
        // Verify the schema is valid
        $schemaContent = file_get_contents($filePath);
        $this->assertJson($schemaContent);
        $schema = json_decode($schemaContent, true);
        $this->assertEquals('test_table', $schema['model']);
        
        // Cleanup
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (is_dir($schemaDir)) {
            rmdir($schemaDir);
        }
        if (is_dir($baseDir)) {
            rmdir($baseDir);
        }
    }
}
