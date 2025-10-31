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
 * Implements in-memory caching to prevent loading the same schema file multiple times
 * during a single request lifecycle. Cache keys are based on model name and connection.
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
     * In-memory cache of loaded schemas.
     * 
     * Cache key format: "{model}:{connection}" or "{model}:default"
     * This prevents loading the same schema file multiple times during a request.
     * 
     * @var array<string, array>
     */
    private array $schemaCache = [];

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
        // Generate cache key
        $cacheKey = $this->getCacheKey($model, $connection);
        
        // Check cache first
        if (isset($this->schemaCache[$cacheKey])) {
            error_log(sprintf(
                "[CRUD6 SchemaService] ✅ Using CACHED schema (in-memory) - model: %s, connection: %s, cache_key: %s, timestamp: %s",
                $model,
                $connection ?? 'null',
                $cacheKey,
                date('Y-m-d H:i:s.u')
            ));
            return $this->schemaCache[$cacheKey];
        }
        
        // DEBUG: Log every schema load attempt to track duplicate calls
        error_log(sprintf(
            "[CRUD6 SchemaService] getSchema() called - model: %s, connection: %s, cache_key: %s, timestamp: %s, caller: %s",
            $model,
            $connection ?? 'null',
            $cacheKey,
            date('Y-m-d H:i:s.u'),
            $this->getCallerInfo()
        ));
        
        $schema = null;
        $schemaPath = null;

        // If connection is specified, try connection-based path first
        if ($connection !== null) {
            $schemaPath = $this->getSchemaFilePath($model, $connection);
            error_log(sprintf(
                "[CRUD6 SchemaService] Trying connection-based path: %s",
                $schemaPath
            ));
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        // If not found in connection-based path, try default path
        if ($schema === null) {
            $schemaPath = $this->getSchemaFilePath($model);
            error_log(sprintf(
                "[CRUD6 SchemaService] Trying default path: %s",
                $schemaPath
            ));
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        if ($schema === null) {
            error_log(sprintf(
                "[CRUD6 SchemaService] Schema file not found for model: %s",
                $model
            ));
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

        error_log(sprintf(
            "[CRUD6 SchemaService] Schema loaded successfully and CACHED - model: %s, table: %s, field_count: %d, cache_key: %s",
            $model,
            $schema['table'] ?? 'unknown',
            count($schema['fields'] ?? []),
            $cacheKey
        ));

        // Store in cache for future requests during this request lifecycle
        $this->schemaCache[$cacheKey] = $schema;

        return $schema;
    }
    
    /**
     * Generate cache key for a model and connection.
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return string Cache key in format "model:connection" or "model:default"
     */
    private function getCacheKey(string $model, ?string $connection = null): string
    {
        return sprintf('%s:%s', $model, $connection ?? 'default');
    }
    
    /**
     * Get caller information for debugging.
     * 
     * Returns information about who called getSchema() to help track down duplicate calls.
     * 
     * @return string Caller information
     */
    private function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $callers = [];
        
        // Skip the first entry (this method) and get the next 2 callers
        for ($i = 2; $i < count($trace) && $i < 4; $i++) {
            $frame = $trace[$i];
            $class = $frame['class'] ?? 'unknown';
            $function = $frame['function'] ?? 'unknown';
            $line = $trace[$i - 1]['line'] ?? '?';
            $callers[] = sprintf("%s::%s():%s", basename($class), $function, $line);
        }
        
        return implode(' <- ', $callers);
    }
    
    /**
     * Clear cached schema for a specific model.
     * 
     * Useful for testing or when schema files are updated during runtime.
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return void
     */
    public function clearCache(string $model, ?string $connection = null): void
    {
        $cacheKey = $this->getCacheKey($model, $connection);
        unset($this->schemaCache[$cacheKey]);
        
        error_log(sprintf(
            "[CRUD6 SchemaService] Cache cleared for model: %s, connection: %s, cache_key: %s",
            $model,
            $connection ?? 'null',
            $cacheKey
        ));
    }
    
    /**
     * Clear all cached schemas.
     * 
     * Useful for testing or when multiple schema files are updated.
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        $count = count($this->schemaCache);
        $this->schemaCache = [];
        
        error_log(sprintf(
            "[CRUD6 SchemaService] All schema cache cleared - %d entries removed",
            $count
        ));
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
     * Filter schema for a specific context or multiple contexts.
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
     * Multiple contexts can be specified as comma-separated values (e.g., 'list,form').
     * When multiple contexts are provided, returns a combined schema with separate
     * sections for each context under a 'contexts' key.
     * 
     * @param array       $schema  The complete schema array
     * @param string|null $context The context ('list', 'form', 'detail', 'meta', comma-separated for multiple, or null for full)
     * 
     * @return array The filtered schema appropriate for the context(s)
     */
    public function filterSchemaForContext(array $schema, ?string $context = null): array
    {
        // DEBUG: Log context filtering to track which contexts are being requested
        error_log(sprintf(
            "[CRUD6 SchemaService] filterSchemaForContext() called - model: %s, context: %s, timestamp: %s",
            $schema['model'] ?? 'unknown',
            $context ?? 'null/full',
            date('Y-m-d H:i:s.u')
        ));
        
        // If no context or 'full', return complete schema (backward compatible)
        if ($context === null || $context === 'full') {
            error_log("[CRUD6 SchemaService] Returning full schema (no filtering)");
            return $schema;
        }

        // Check if multiple contexts are requested (comma-separated)
        if (strpos($context, ',') !== false) {
            $contexts = array_map('trim', explode(',', $context));
            error_log(sprintf(
                "[CRUD6 SchemaService] Multiple contexts requested: %s",
                implode(', ', $contexts)
            ));
            return $this->filterSchemaForMultipleContexts($schema, $contexts);
        }

        // Single context - use existing logic
        error_log(sprintf(
            "[CRUD6 SchemaService] Single context filtering: %s",
            $context
        ));
        return $this->filterSchemaForSingleContext($schema, $context);
    }

    /**
     * Filter schema for multiple contexts.
     * 
     * Returns a combined schema with separate sections for each requested context.
     * This reduces API calls by providing all needed schema information in one response.
     * 
     * @param array $schema   The complete schema array
     * @param array $contexts Array of context names to include
     * 
     * @return array Combined schema with contexts section
     */
    protected function filterSchemaForMultipleContexts(array $schema, array $contexts): array
    {
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

        // Add contexts section with filtered data for each context
        $filtered['contexts'] = [];
        foreach ($contexts as $context) {
            $contextData = $this->getContextSpecificData($schema, $context);
            if ($contextData !== null) {
                $filtered['contexts'][$context] = $contextData;
            }
        }

        return $filtered;
    }

    /**
     * Filter schema for a single context (legacy behavior).
     * 
     * @param array  $schema  The complete schema array
     * @param string $context The context name
     * 
     * @return array The filtered schema appropriate for the context
     */
    protected function filterSchemaForSingleContext(array $schema, string $context): array
    {
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

        // Get context-specific data
        $contextData = $this->getContextSpecificData($schema, $context);
        
        // If context data is null (unknown context), return full schema for safety
        if ($contextData === null) {
            return $schema;
        }

        // Merge context-specific data into filtered schema
        return array_merge($filtered, $contextData);
    }

    /**
     * Get context-specific data (fields and related configuration).
     * 
     * @param array  $schema  The complete schema array
     * @param string $context The context name
     * 
     * @return array|null Context-specific data, or null for unknown contexts
     */
    protected function getContextSpecificData(array $schema, string $context): ?array
    {
        switch ($context) {
            case 'meta':
                // Minimal metadata only - no field information
                // Just model identification and permissions (already in base)
                return [];

            case 'list':
                // For list/table views: only listable fields with display properties
                $data = [
                    'fields' => [],
                    'default_sort' => $schema['default_sort'] ?? [],
                ];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Include field if it's explicitly listable or listable is not set (default true)
                    if (($field['listable'] ?? true) === true) {
                        $data['fields'][$fieldKey] = [
                            'type' => $field['type'] ?? 'string',
                            'label' => $field['label'] ?? $fieldKey,
                            'sortable' => $field['sortable'] ?? false,
                            'filterable' => $field['filterable'] ?? false,
                        ];

                        // Include width if specified
                        if (isset($field['width'])) {
                            $data['fields'][$fieldKey]['width'] = $field['width'];
                        }

                        // Include field_template if specified (for custom rendering)
                        if (isset($field['field_template'])) {
                            $data['fields'][$fieldKey]['field_template'] = $field['field_template'];
                        }

                        // Include filter_type if field is filterable
                        if (isset($field['filter_type']) && ($field['filterable'] ?? false)) {
                            $data['fields'][$fieldKey]['filter_type'] = $field['filter_type'];
                        }
                    }
                }
                return $data;

            case 'form':
                // For create/edit forms: editable fields with validation
                $data = ['fields' => []];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Include field if it's explicitly editable or editable is not set (default true)
                    if (($field['editable'] ?? true) !== false) {
                        $data['fields'][$fieldKey] = [
                            'type' => $field['type'] ?? 'string',
                            'label' => $field['label'] ?? $fieldKey,
                            'required' => $field['required'] ?? false,
                            'readonly' => $field['readonly'] ?? false,
                        ];

                        // Include validation rules if present
                        if (isset($field['validation'])) {
                            $data['fields'][$fieldKey]['validation'] = $field['validation'];
                        }

                        // Include placeholder if present
                        if (isset($field['placeholder'])) {
                            $data['fields'][$fieldKey]['placeholder'] = $field['placeholder'];
                        }

                        // Include description if present (helpful hint text)
                        if (isset($field['description'])) {
                            $data['fields'][$fieldKey]['description'] = $field['description'];
                        }

                        // Include default value if present
                        if (isset($field['default'])) {
                            $data['fields'][$fieldKey]['default'] = $field['default'];
                        }

                        // Include icon if present
                        if (isset($field['icon'])) {
                            $data['fields'][$fieldKey]['icon'] = $field['icon'];
                        }

                        // Include rows for textarea fields
                        if (isset($field['rows'])) {
                            $data['fields'][$fieldKey]['rows'] = $field['rows'];
                        }

                        // Include editable flag (may differ from parent default)
                        if (isset($field['editable'])) {
                            $data['fields'][$fieldKey]['editable'] = $field['editable'];
                        }

                        // Include smartlookup configuration if present
                        if ($field['type'] === 'smartlookup') {
                            if (isset($field['lookup_model'])) {
                                $data['fields'][$fieldKey]['lookup_model'] = $field['lookup_model'];
                            }
                            if (isset($field['lookup_id'])) {
                                $data['fields'][$fieldKey]['lookup_id'] = $field['lookup_id'];
                            }
                            if (isset($field['lookup_desc'])) {
                                $data['fields'][$fieldKey]['lookup_desc'] = $field['lookup_desc'];
                            }
                            if (isset($field['model'])) {
                                $data['fields'][$fieldKey]['model'] = $field['model'];
                            }
                            if (isset($field['id'])) {
                                $data['fields'][$fieldKey]['id'] = $field['id'];
                            }
                            if (isset($field['desc'])) {
                                $data['fields'][$fieldKey]['desc'] = $field['desc'];
                            }
                        }
                    }
                }
                return $data;

            case 'detail':
                // For detail/view pages: only viewable fields with full display properties
                $data = ['fields' => []];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Include field if it's explicitly viewable or viewable is not set (default true)
                    if (($field['viewable'] ?? true) === true) {
                        $data['fields'][$fieldKey] = [
                            'type' => $field['type'] ?? 'string',
                            'label' => $field['label'] ?? $fieldKey,
                            'readonly' => $field['readonly'] ?? false,
                        ];

                        // Include description if present
                        if (isset($field['description'])) {
                            $data['fields'][$fieldKey]['description'] = $field['description'];
                        }

                        // Include field_template if specified
                        if (isset($field['field_template'])) {
                            $data['fields'][$fieldKey]['field_template'] = $field['field_template'];
                        }

                        // Include editable flag for detail pages that allow inline editing
                        if (isset($field['editable'])) {
                            $data['fields'][$fieldKey]['editable'] = $field['editable'];
                        }

                        // Include default value for display purposes
                        if (isset($field['default'])) {
                            $data['fields'][$fieldKey]['default'] = $field['default'];
                        }
                    }
                }

                // Include detail configuration if present (for related data - singular, legacy)
                if (isset($schema['detail'])) {
                    $data['detail'] = $schema['detail'];
                }

                // Include details configuration if present (for related data - plural, new format)
                if (isset($schema['details'])) {
                    $data['details'] = $schema['details'];
                }

                // Include actions configuration if present (for custom action buttons)
                if (isset($schema['actions'])) {
                    $data['actions'] = $schema['actions'];
                }

                // Include relationships configuration if present (for data fetching)
                if (isset($schema['relationships'])) {
                    $data['relationships'] = $schema['relationships'];
                }

                // Include detail_editable configuration if present
                if (isset($schema['detail_editable'])) {
                    $data['detail_editable'] = $schema['detail_editable'];
                }

                // Include render_mode if present
                if (isset($schema['render_mode'])) {
                    $data['render_mode'] = $schema['render_mode'];
                }

                // Include title_field if present (for displaying record name)
                if (isset($schema['title_field'])) {
                    $data['title_field'] = $schema['title_field'];
                }
                
                return $data;

            default:
                // Unknown context - return null to signal fallback to full schema
                return null;
        }
    }
}