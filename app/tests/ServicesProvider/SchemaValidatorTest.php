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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator;

/**
 * Schema Validator Test.
 *
 * Tests the SchemaValidator class functionality.
 */
class SchemaValidatorTest extends TestCase
{
    /**
     * Test validate with valid schema.
     */
    public function testValidateWithValidSchema(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => ['field1' => []],
        ];

        // This should not throw an exception
        $validator->validate($schema, 'test_model');
        
        $this->assertTrue(true); // If we reach here, validation passed
    }

    /**
     * Test validate throws exception for missing model field.
     */
    public function testValidateThrowsExceptionForMissingModel(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'table' => 'test_table',
            'fields' => ['field1' => []],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("missing required field: model");
        
        $validator->validate($schema, 'test_model');
    }

    /**
     * Test validate throws exception for missing table field.
     */
    public function testValidateThrowsExceptionForMissingTable(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'model' => 'test_model',
            'fields' => ['field1' => []],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("missing required field: table");
        
        $validator->validate($schema, 'test_model');
    }

    /**
     * Test validate throws exception for missing fields.
     */
    public function testValidateThrowsExceptionForMissingFields(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("missing required field: fields");
        
        $validator->validate($schema, 'test_model');
    }

    /**
     * Test validate throws exception for model name mismatch.
     */
    public function testValidateThrowsExceptionForModelMismatch(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'model' => 'wrong_model',
            'table' => 'test_table',
            'fields' => ['field1' => []],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("does not match requested model");
        
        $validator->validate($schema, 'test_model');
    }

    /**
     * Test validate throws exception for empty fields array.
     */
    public function testValidateThrowsExceptionForEmptyFields(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("must have a non-empty 'fields' array");
        
        $validator->validate($schema, 'test_model');
    }

    /**
     * Test hasPermission returns true when permission exists.
     */
    public function testHasPermissionReturnsTrueWhenExists(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'permissions' => [
                'create' => 'model.create',
                'read' => 'model.read',
            ],
        ];

        $this->assertTrue($validator->hasPermission($schema, 'create'));
        $this->assertTrue($validator->hasPermission($schema, 'read'));
    }

    /**
     * Test hasPermission returns false when permission doesn't exist.
     */
    public function testHasPermissionReturnsFalseWhenNotExists(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [
            'permissions' => [
                'create' => 'model.create',
            ],
        ];

        $this->assertFalse($validator->hasPermission($schema, 'delete'));
    }

    /**
     * Test hasPermission returns false when no permissions defined.
     */
    public function testHasPermissionReturnsFalseWhenNoPermissions(): void
    {
        $validator = new SchemaValidator();
        
        $schema = [];

        $this->assertFalse($validator->hasPermission($schema, 'create'));
    }
}
