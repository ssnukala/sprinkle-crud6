<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaValidationException;

/**
 * Schema Validator.
 * 
 * Handles validation of schema structure to ensure all required fields are present
 * and properly formatted.
 * 
 * Uses UserFrosting framework exceptions for consistency with framework patterns.
 */
class SchemaValidator
{
    /**
     * Validate schema structure.
     * 
     * Ensures schema has all required fields and valid structure.
     * Required fields: model, table, fields
     * 
     * @param array  $schema The schema array to validate
     * @param string $model  The model name for error messages
     * 
     * @return void
     * 
     * @throws SchemaValidationException If schema validation fails
     */
    public function validate(array $schema, string $model): void
    {
        $requiredFields = ['model', 'table', 'fields'];

        foreach ($requiredFields as $field) {
            if (!isset($schema[$field])) {
                throw new SchemaValidationException(
                    "Schema for model '{$model}' is missing required field: {$field}"
                );
            }
        }

        // Validate that model name matches
        if ($schema['model'] !== $model) {
            throw new SchemaValidationException(
                "Schema model name '{$schema['model']}' does not match requested model '{$model}'"
            );
        }

        // Validate fields structure
        if (!is_array($schema['fields']) || empty($schema['fields'])) {
            throw new SchemaValidationException(
                "Schema for model '{$model}' must have a non-empty 'fields' array"
            );
        }
    }

    /**
     * Check if schema has permission for an operation.
     * 
     * @param array  $schema    The schema array
     * @param string $operation The operation (create, read, update, delete)
     * 
     * @return bool True if permission exists
     */
    public function hasPermission(array $schema, string $operation): bool
    {
        return isset($schema['permissions'][$operation]);
    }
}
