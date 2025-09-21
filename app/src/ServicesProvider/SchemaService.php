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

use DI\Container;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;

//use UserFrosting\Fortress\Transformer\RequestDataTransformer;

/**
 * Schema Service
 * 
 * Handles loading, caching, and validation of JSON schema files
 * for CRUD6 operations.
 */
class SchemaService
{
    protected string $schemaPath;

    public function __construct(
        protected Container $container
    ) {
        // Get schema path from config or use default
        $configPath = null;
        if ($container->has('config.schema_path')) {
            $configPath = $container->get('config.schema_path');
        }
        
        $this->schemaPath = $configPath ?: 'app/schema/crud6';
    }

    /**
     * Get schema configuration for a model
     *
     * @param string $model The model name
     * @return array The schema configuration
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException
     */

    /**
     * Get the file path for a model's schema
     */
    protected function getSchemaFilePath(string $model): string
    {
        // Handle both stream wrapper and direct file paths
        $schemaPath = rtrim($this->schemaPath, '/') . "/{$model}.json";
        
        // If using schema:// stream wrapper, resolve to actual file path
        if (str_starts_with($schemaPath, 'schema://')) {
            // For CRUD6, schemas are in app/schema/crud6/ directory
            $relativePath = str_replace('schema://', '', $schemaPath);
            // Use the directory where this class is located to find the app directory
            $appDir = dirname(dirname(__DIR__));
            $schemaPath = $appDir . '/schema/' . $relativePath;
        } elseif (!str_starts_with($schemaPath, '/')) {
            // If it's a relative path, resolve it relative to the project root
            // SchemaService is in app/src/ServicesProvider, so go up 3 levels to project root
            $projectRoot = dirname(dirname(dirname(__DIR__)));
            $schemaPath = $projectRoot . '/' . $schemaPath;
        }
        
        return $schemaPath;
    }


    /**
     * Validate schema structure
     */
    protected function validateSchema(array $schema, string $model): void
    {
        $requiredFields = ['model', 'table', 'fields'];

        foreach ($requiredFields as $field) {
            if (!isset($schema[$field])) {
                throw new \RuntimeException(
                    "Schema for model '{$model}' is missing required field: {$field}"
                );
            }
        }

        // Validate that model name matches
        if ($schema['model'] !== $model) {
            throw new \RuntimeException(
                "Schema model name '{$schema['model']}' does not match requested model '{$model}'"
            );
        }

        // Validate fields structure
        if (!is_array($schema['fields']) || empty($schema['fields'])) {
            throw new \RuntimeException(
                "Schema for model '{$model}' must have a non-empty 'fields' array"
            );
        }
    }


    public function getSchema(string $model): array
    {
        $schemaPath = $this->getSchemaFilePath($model);

        // Load JSON schema file
        if (!file_exists($schemaPath)) {
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Schema file not found for model: {$model} at path: {$schemaPath}");
        }

        $schemaContent = file_get_contents($schemaPath);
        if ($schemaContent === false) {
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Could not read schema file for model: {$model}");
        }

        $schema = json_decode($schemaContent, true);
        if ($schema === null) {
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Invalid JSON in schema file for model: {$model}. Error: " . json_last_error_msg());
        }

        // Validate schema structure
        $this->validateSchema($schema, $model);

        return $schema;
    }

    /**
     * Get a configured CRUD6Model instance for a model
     *
     * @param string $model The model name
     * @return \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException
     */
    public function getModelInstance(string $model): \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
    {
        $schema = $this->getSchema($model);

        $modelInstance = new \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model();
        $modelInstance->configureFromSchema($schema);

        return $modelInstance;
    }
}