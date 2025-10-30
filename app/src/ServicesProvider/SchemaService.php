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
     * Filter schema for a specific context.
     * 
     * Returns only the schema properties needed for a specific frontend context,
     * reducing payload size and preventing exposure of sensitive information.
     * 
     * Supported contexts:
     * - 'list': Fields for listing/table view (listable fields only)
     * - 'form': Fields for create/edit forms (editable fields with validation)
     * - 'detail': Full field information for detail/view pages
     * - 'meta': Just model metadata (no field details)
     * - null/'full': Complete schema (backward compatible, but not recommended)
     * 
     * @param array       $schema  The complete schema array
     * @param string|null $context The context ('list', 'form', 'detail', 'meta', or null for full)
     * 
     * @return array The filtered schema appropriate for the context
     */
    public function filterSchemaForContext(array $schema, ?string $context = null): array
    {
        // If no context or 'full', return complete schema (backward compatible)
        if ($context === null || $context === 'full') {
            return $schema;
        }

        // Start with base metadata that all contexts need
        $filtered = [
            'model' => $schema['model'],
            'title' => $schema['title'] ?? ucfirst($schema['model']),
            'singular_title' => $schema['singular_title'] ?? $schema['title'] ?? ucfirst($schema['model']),
            'primary_key' => $schema['primary_key'] ?? 'id',
        ];

        // Add description if present
        if (isset($schema['description'])) {
            $filtered['description'] = $schema['description'];
        }

        // Add permissions if present (needed for permission checks)
        if (isset($schema['permissions'])) {
            $filtered['permissions'] = $schema['permissions'];
        }

        switch ($context) {
            case 'meta':
                // Minimal metadata only - no field information
                // Just model identification and permissions
                break;

            case 'list':
                // For list/table views: only listable fields with display properties
                $filtered['fields'] = [];
                $filtered['default_sort'] = $schema['default_sort'] ?? [];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Include field if it's explicitly listable or listable is not set (default true)
                    if (($field['listable'] ?? true) === true) {
                        $filtered['fields'][$fieldKey] = [
                            'type' => $field['type'] ?? 'string',
                            'label' => $field['label'] ?? $fieldKey,
                            'sortable' => $field['sortable'] ?? false,
                            'filterable' => $field['filterable'] ?? false,
                        ];

                        // Include width if specified
                        if (isset($field['width'])) {
                            $filtered['fields'][$fieldKey]['width'] = $field['width'];
                        }

                        // Include field_template if specified (for custom rendering)
                        if (isset($field['field_template'])) {
                            $filtered['fields'][$fieldKey]['field_template'] = $field['field_template'];
                        }

                        // Include filter_type if field is filterable
                        if (isset($field['filter_type']) && ($field['filterable'] ?? false)) {
                            $filtered['fields'][$fieldKey]['filter_type'] = $field['filter_type'];
                        }
                    }
                }
                break;

            case 'form':
                // For create/edit forms: editable fields with validation
                $filtered['fields'] = [];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Include field if it's explicitly editable or editable is not set (default true)
                    if (($field['editable'] ?? true) !== false) {
                        $filtered['fields'][$fieldKey] = [
                            'type' => $field['type'] ?? 'string',
                            'label' => $field['label'] ?? $fieldKey,
                            'required' => $field['required'] ?? false,
                            'readonly' => $field['readonly'] ?? false,
                        ];

                        // Include validation rules if present
                        if (isset($field['validation'])) {
                            $filtered['fields'][$fieldKey]['validation'] = $field['validation'];
                        }

                        // Include placeholder if present
                        if (isset($field['placeholder'])) {
                            $filtered['fields'][$fieldKey]['placeholder'] = $field['placeholder'];
                        }

                        // Include description if present (helpful hint text)
                        if (isset($field['description'])) {
                            $filtered['fields'][$fieldKey]['description'] = $field['description'];
                        }

                        // Include default value if present
                        if (isset($field['default'])) {
                            $filtered['fields'][$fieldKey]['default'] = $field['default'];
                        }

                        // Include icon if present
                        if (isset($field['icon'])) {
                            $filtered['fields'][$fieldKey]['icon'] = $field['icon'];
                        }

                        // Include rows for textarea fields
                        if (isset($field['rows'])) {
                            $filtered['fields'][$fieldKey]['rows'] = $field['rows'];
                        }

                        // Include smartlookup configuration if present
                        if ($field['type'] === 'smartlookup') {
                            if (isset($field['lookup_model'])) {
                                $filtered['fields'][$fieldKey]['lookup_model'] = $field['lookup_model'];
                            }
                            if (isset($field['lookup_id'])) {
                                $filtered['fields'][$fieldKey]['lookup_id'] = $field['lookup_id'];
                            }
                            if (isset($field['lookup_desc'])) {
                                $filtered['fields'][$fieldKey]['lookup_desc'] = $field['lookup_desc'];
                            }
                            if (isset($field['model'])) {
                                $filtered['fields'][$fieldKey]['model'] = $field['model'];
                            }
                            if (isset($field['id'])) {
                                $filtered['fields'][$fieldKey]['id'] = $field['id'];
                            }
                            if (isset($field['desc'])) {
                                $filtered['fields'][$fieldKey]['desc'] = $field['desc'];
                            }
                        }
                    }
                }
                break;

            case 'detail':
                // For detail/view pages: all fields with full display properties
                $filtered['fields'] = [];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    $filtered['fields'][$fieldKey] = [
                        'type' => $field['type'] ?? 'string',
                        'label' => $field['label'] ?? $fieldKey,
                        'readonly' => $field['readonly'] ?? false,
                    ];

                    // Include description if present
                    if (isset($field['description'])) {
                        $filtered['fields'][$fieldKey]['description'] = $field['description'];
                    }

                    // Include field_template if specified
                    if (isset($field['field_template'])) {
                        $filtered['fields'][$fieldKey]['field_template'] = $field['field_template'];
                    }

                    // Include editable flag for detail pages that allow inline editing
                    if (isset($field['editable'])) {
                        $filtered['fields'][$fieldKey]['editable'] = $field['editable'];
                    }

                    // Include default value for display purposes
                    if (isset($field['default'])) {
                        $filtered['fields'][$fieldKey]['default'] = $field['default'];
                    }
                }

                // Include detail configuration if present (for related data)
                if (isset($schema['detail'])) {
                    $filtered['detail'] = $schema['detail'];
                }

                // Include detail_editable configuration if present
                if (isset($schema['detail_editable'])) {
                    $filtered['detail_editable'] = $schema['detail_editable'];
                }

                // Include render_mode if present
                if (isset($schema['render_mode'])) {
                    $filtered['render_mode'] = $schema['render_mode'];
                }

                // Include title_field if present (for displaying record name)
                if (isset($schema['title_field'])) {
                    $filtered['title_field'] = $schema['title_field'];
                }
                break;

            default:
                // Unknown context - return full schema for safety
                return $schema;
        }

        return $filtered;
    }
}