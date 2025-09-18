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
use DI\NotFoundException as DINotFoundException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException;

/**
 * Schema Service
 * 
 * Handles loading, caching, and validation of JSON schema files
 * for CRUD6 operations.
 */
class SchemaService
{
    protected array $schemaCache = [];
    protected string $schemaPath;

    public function __construct(
        protected Container $container
    ) {
        // Default schema path - can be overridden via configuration
        try {
            $this->schemaPath = $this->container->get('config.schema_path') ?? 'app/schema/crud6';
        } catch (DINotFoundException $e) {
            $this->schemaPath = 'app/schema/crud6';
        }
    }

    /**
     * Get schema configuration for a model
     *
     * @param string $model The model name
     * @return array The schema configuration
     * @throws SchemaNotFoundException
     */
    public function getSchema(string $model): array
    {
        // Check cache first
        if (isset($this->schemaCache[$model])) {
            return $this->schemaCache[$model];
        }

        $schemaFile = $this->getSchemaFilePath($model);
        
        if (!file_exists($schemaFile)) {
            throw new SchemaNotFoundException(
                "Schema file not found: {$schemaFile}"
            );
        }

        $schema = json_decode(file_get_contents($schemaFile), true);
        
        if ($schema === null) {
            throw new NotFoundException("Invalid JSON in schema file: {$schemaFile}");
        }

        // Validate schema structure
        $this->validateSchema($schema, $model);
        
        // Cache the schema
        $this->schemaCache[$model] = $schema;
        
        return $schema;
    }

    /**
     * Get the file path for a model's schema
     */
    protected function getSchemaFilePath(string $model): string
    {
        return rtrim($this->schemaPath, '/') . "/{$model}.json";
    }

    /**
     * Validate schema structure
     */
    protected function validateSchema(array $schema, string $model): void
    {
        $requiredFields = ['model', 'table', 'fields'];
        
        foreach ($requiredFields as $field) {
            if (!isset($schema[$field])) {
                throw new NotFoundException(
                    "Schema for model '{$model}' is missing required field: {$field}"
                );
            }
        }

        // Validate that model name matches
        if ($schema['model'] !== $model) {
            throw new NotFoundException(
                "Schema model name '{$schema['model']}' does not match requested model '{$model}'"
            );
        }

        // Validate fields structure
        if (!is_array($schema['fields']) || empty($schema['fields'])) {
            throw new NotFoundException(
                "Schema for model '{$model}' must have a non-empty 'fields' array"
            );
        }
    }

    /**
     * Get a configured CRUD6Model instance for a model
     *
     * @param string $model The model name
     * @return \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
     * @throws SchemaNotFoundException
     */
    public function getModelInstance(string $model): \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
    {
        $schema = $this->getSchema($model);
        
        $modelInstance = new \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model();
        $modelInstance->configureFromSchema($schema);
        
        return $modelInstance;
    }

    /**
     * Clear schema cache
     */
    public function clearCache(): void
    {
        $this->schemaCache = [];
    }

    /**
     * Set custom schema path
     */
    public function setSchemaPath(string $path): void
    {
        $this->schemaPath = $path;
        $this->clearCache();
    }
}