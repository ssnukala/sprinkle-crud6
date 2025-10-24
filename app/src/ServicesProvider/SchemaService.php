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
 * Schema Service.
 * 
 * Handles loading, caching, and validation of JSON schema files for CRUD6 operations.
 * Uses ResourceLocatorInterface to locate schema files following the UserFrosting 6
 * pattern for resource loading.
 * 
 * Schema files are loaded from the 'schema://crud6/' location and can be organized
 * by database connection in subdirectories for multi-database support.
 * 
 * @see \UserFrosting\Support\Repository\Loader\YamlFileLoader
 */
class SchemaService
{
    /**
     * @var string Base path for schema files
     */
    protected string $schemaPath = 'schema://crud6/';

    /**
     * Constructor for SchemaService.
     * 
     * @param ResourceLocatorInterface $locator Resource locator for finding schema files
     */
    public function __construct(
        protected ResourceLocatorInterface $locator
    ) {
    }

    /**
     * Get schema configuration for a model.
     * 
     * Loads schema from JSON files and validates structure.
     * Supports connection-based path lookup for multi-database scenarios.
     *
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name for path-based lookup
     * 
     * @return array<string, mixed> The schema configuration array
     * 
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException If schema file not found
     * @throws \RuntimeException If schema validation fails
     */

    /**
     * Get the file path for a model's schema.
     * 
     * Supports connection-based subdirectory lookup:
     * - With connection: schema://crud6/{connection}/{model}.json
     * - Without connection: schema://crud6/{model}.json
     *
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name for path-based lookup
     * 
     * @return string The schema file path
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
     * @throws \RuntimeException If schema validation fails
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
     * Apply default values to schema.
     * 
     * Sets default values for optional schema attributes if not provided:
     * - primary_key: defaults to "id"
     * - timestamps: defaults to true
     * - soft_delete: defaults to false
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with defaults applied
     */
    protected function applyDefaults(array $schema): array
    {
        // Apply default values if not set
        $schema['primary_key'] = $schema['primary_key'] ?? 'id';
        $schema['timestamps'] = $schema['timestamps'] ?? true;
        $schema['soft_delete'] = $schema['soft_delete'] ?? false;

        return $schema;
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

        // Apply default values
        $schema = $this->applyDefaults($schema);

        // If schema was loaded from connection-based path and doesn't have explicit connection, set it
        if ($connection !== null && !isset($schema['connection'])) {
            $schema['connection'] = $connection;
        }

        return $schema;
    }

    /**
     * Get a configured CRUD6Model instance for a model.
     * 
     * Loads schema and returns a fully configured model instance ready for use.
     *
     * @param string $model The model name
     * 
     * @return \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model Configured model instance
     * 
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException If schema file not found
     */
    public function getModelInstance(string $model): \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
    {
        $schema = $this->getSchema($model);

        $modelInstance = new \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model();
        $modelInstance->configureFromSchema($schema);

        return $modelInstance;
    }

    /**
     * Enrich schema with detected relationships from database scanner.
     * 
     * This method takes a schema array and adds relationship information
     * detected by the DatabaseScanner, merging it with any existing relationships.
     * 
     * @param array<string, mixed> $schema              The schema to enrich
     * @param array<string, mixed> $detectedRelationships Relationships detected by scanner
     * @param bool                 $overwrite           Whether to overwrite existing relationships
     * 
     * @return array<string, mixed> Enriched schema
     */
    public function enrichSchemaWithRelationships(
        array $schema,
        array $detectedRelationships,
        bool $overwrite = false
    ): array {
        if (!isset($schema['relationships'])) {
            $schema['relationships'] = [];
        }

        foreach ($detectedRelationships as $field => $relationshipInfo) {
            // Skip if relationship already exists and we're not overwriting
            if (isset($schema['relationships'][$field]) && !$overwrite) {
                continue;
            }

            $schema['relationships'][$field] = $relationshipInfo;
        }

        return $schema;
    }
}