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

use UserFrosting\Config\Config;
use UserFrosting\Support\Repository\Loader\YamlFileLoader;

/**
 * Schema Loader.
 * 
 * Handles loading of JSON schema files from the file system using
 * the resource locator pattern and UserFrosting's YamlFileLoader.
 * 
 * The schema path is configurable through the Config service, allowing
 * flexibility in schema file organization.
 */
class SchemaLoader
{
    /**
     * @var string Base path for schema files (configurable)
     */
    protected string $schemaPath;

    /**
     * Constructor.
     * 
     * Schema path can be configured via 'crud6.schema_path' config key.
     * Defaults to 'schema://crud6/' if not configured.
     * 
     * @param Config $config Configuration repository
     */
    public function __construct(Config $config)
    {
        $this->schemaPath = $config->get('crud6.schema_path', 'schema://crud6/');
    }

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
    public function getSchemaFilePath(string $model, ?string $connection = null): string
    {
        // If connection is specified, try connection-based path first
        if ($connection !== null) {
            return rtrim($this->schemaPath, '/') . "/{$connection}/{$model}.json";
        }
        
        return rtrim($this->schemaPath, '/') . "/{$model}.json";
    }

    /**
     * Load schema from file.
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return array|null The loaded schema, or null if not found
     */
    public function loadSchema(string $model, ?string $connection = null): ?array
    {
        $schema = null;

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

        return $schema;
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
    public function applyDefaults(array $schema): array
    {
        // Apply default values if not set
        $schema['primary_key'] = $schema['primary_key'] ?? 'id';
        $schema['timestamps'] = $schema['timestamps'] ?? true;
        $schema['soft_delete'] = $schema['soft_delete'] ?? false;

        return $schema;
    }
}
