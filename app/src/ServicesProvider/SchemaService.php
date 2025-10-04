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

use UserFrosting\Support\Repository\Loader\YamlFileLoader;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * Schema Service
 * 
 * Handles loading, caching, and validation of JSON schema files
 * for CRUD6 operations.
 * 
 * Uses ResourceLocatorInterface to locate schema files following
 * the UserFrosting 6 pattern for resource loading.
 */
class SchemaService
{
    protected string $schemaPath = 'schema://crud6/';

    public function __construct(
        protected ResourceLocatorInterface $locator
    ) {
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
     *
     * @param string $model The model name
     * @param string|null $connection Optional connection name for path-based lookup
     */
    protected function getSchemaFilePath(string $model, ?string $connection = null): string
    {
        // If connection is specified, try connection-based path first
        if ($connection !== null) {
            return rtrim($this->schemaPath, '/') . "/{$connection}/{$model}.json";
        }
        
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


    /**
     * Get schema configuration for a model
     *
     * @param string $model The model name
     * @param string|null $connection Optional connection name for path-based lookup
     * @return array The schema configuration
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException
     */
    public function getSchema(string $model, ?string $connection = null): array
    {
        $schema = null;
        $schemaPath = null;

        // If connection is specified, try connection-based path first
        if ($connection !== null) {
            $schemaPath = $this->getSchemaFilePath($model, $connection);
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        // If not found in connection-based path, try default path
        if ($schema === null) {
            $schemaPath = $this->getSchemaFilePath($model);
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        if ($schema === null) {
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Schema file not found for model: {$model}");
        }

        // Validate schema structure
        $this->validateSchema($schema, $model);

        // If schema was loaded from connection-based path and doesn't have explicit connection, set it
        if ($connection !== null && !isset($schema['connection'])) {
            $schema['connection'] = $connection;
        }

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