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

use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\DatabaseScanner;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

/**
 * Test DatabaseScanner class with focus on implicit relationship detection.
 *
 * Tests the new functionality for detecting foreign key relationships
 * based on naming conventions and data sampling.
 *
 * @author Srinivas Nukala
 */
class DatabaseScannerTest extends CRUD6TestCase
{
    /**
     * Test that explicit foreign keys are detected correctly.
     */
    public function testExplicitForeignKeyDetection(): void
    {
        // Create sample table metadata with explicit foreign keys
        $tablesMetadata = [
            'users' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [
                    'posts_user_id_fk' => [
                        'name' => 'posts_user_id_fk',
                        'localColumns' => ['user_id'],
                        'foreignTable' => 'users',
                        'foreignColumns' => ['id'],
                        'onUpdate' => null,
                        'onDelete' => null,
                    ],
                ],
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, false, 0);

        // Verify explicit relationship is detected
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertNotEmpty($relationships['posts']['references']);
        
        $reference = $relationships['posts']['references'][0];
        $this->assertEquals('users', $reference['table']);
        $this->assertEquals('user_id', $reference['localKey']);
        $this->assertEquals('id', $reference['foreignKey']);
        $this->assertEquals('explicit', $reference['type']);
    }

    /**
     * Test implicit foreign key detection based on naming convention.
     */
    public function testImplicitForeignKeyDetectionByNaming(): void
    {
        // Create sample table metadata WITHOUT explicit foreign keys
        $tablesMetadata = [
            'users' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [], // No explicit foreign keys!
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        // Enable implicit detection, disable sampling
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // Verify implicit relationship is detected
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertNotEmpty($relationships['posts']['references']);
        
        $reference = $relationships['posts']['references'][0];
        $this->assertEquals('users', $reference['table']);
        $this->assertEquals('user_id', $reference['localKey']);
        $this->assertEquals('id', $reference['foreignKey']);
        $this->assertEquals('implicit', $reference['type']);
    }

    /**
     * Test that implicit detection respects table name variations.
     */
    public function testImplicitDetectionWithTableNameVariations(): void
    {
        $tablesMetadata = [
            'categories' => [
                'name' => 'categories',
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'products' => [
                'name' => 'products',
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
                    'category_id' => [
                        'name' => 'category_id',
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
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // Verify that 'category_id' is detected as referring to 'categories' table
        $this->assertArrayHasKey('products', $relationships);
        $this->assertNotEmpty($relationships['products']['references']);
        
        $reference = $relationships['products']['references'][0];
        $this->assertEquals('categories', $reference['table']);
        $this->assertEquals('category_id', $reference['localKey']);
    }

    /**
     * Test that non-integer columns are not considered as foreign keys.
     */
    public function testNonIntegerColumnsNotDetectedAsFK(): void
    {
        $tablesMetadata = [
            'users' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                        'type' => 'string', // String, not integer!
                        'length' => 50,
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
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // String column should NOT be detected as foreign key
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertEmpty($relationships['posts']['references']);
    }

    /**
     * Test that primary key columns are not detected as foreign keys.
     */
    public function testPrimaryKeyNotDetectedAsFK(): void
    {
        $tablesMetadata = [
            'id' => [
                'name' => 'id',
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // Primary key 'id' should NOT be detected as foreign key
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertEmpty($relationships['posts']['references']);
    }

    /**
     * Test mixed explicit and implicit relationships.
     */
    public function testMixedExplicitAndImplicitRelationships(): void
    {
        $tablesMetadata = [
            'users' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'categories' => [
                'name' => 'categories',
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                    'category_id' => [
                        'name' => 'category_id',
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
                // Only user_id has explicit FK, category_id does not
                'foreignKeys' => [
                    'posts_user_id_fk' => [
                        'name' => 'posts_user_id_fk',
                        'localColumns' => ['user_id'],
                        'foreignTable' => 'users',
                        'foreignColumns' => ['id'],
                        'onUpdate' => null,
                        'onDelete' => null,
                    ],
                ],
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // Should have both explicit and implicit relationships
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertCount(2, $relationships['posts']['references']);
        
        // Find explicit and implicit relationships
        $explicitRefs = array_filter($relationships['posts']['references'], fn($r) => $r['type'] === 'explicit');
        $implicitRefs = array_filter($relationships['posts']['references'], fn($r) => $r['type'] === 'implicit');
        
        $this->assertCount(1, $explicitRefs, 'Should have 1 explicit relationship');
        $this->assertCount(1, $implicitRefs, 'Should have 1 implicit relationship');
        
        // Verify explicit relationship
        $explicitRef = array_values($explicitRefs)[0];
        $this->assertEquals('users', $explicitRef['table']);
        $this->assertEquals('user_id', $explicitRef['localKey']);
        
        // Verify implicit relationship
        $implicitRef = array_values($implicitRefs)[0];
        $this->assertEquals('categories', $implicitRef['table']);
        $this->assertEquals('category_id', $implicitRef['localKey']);
    }

    /**
     * Test camelCase column naming pattern.
     */
    public function testCamelCaseNamingPattern(): void
    {
        $tablesMetadata = [
            'users' => [
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
                ],
                'indexes' => [],
                'foreignKeys' => [],
                'primaryKey' => ['id'],
            ],
            'posts' => [
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
                    'userId' => [
                        'name' => 'userId',
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
                'primaryKey' => ['id'],
            ],
        ];

        $scanner = $this->ci->get(DatabaseScanner::class);
        $relationships = $scanner->detectRelationships($tablesMetadata, true, 0);

        // Verify camelCase pattern is detected
        $this->assertArrayHasKey('posts', $relationships);
        $this->assertNotEmpty($relationships['posts']['references']);
        
        $reference = $relationships['posts']['references'][0];
        $this->assertEquals('users', $reference['table']);
        $this->assertEquals('userId', $reference['localKey']);
    }
}
