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
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
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
     * @param ResourceLocatorInterface  $locator Resource locator for finding schema files
     * @param Config                    $config  Configuration repository
     * @param DebugLoggerInterface|null $logger  Debug logger for diagnostics (optional)
     */
    public function __construct(
        protected ResourceLocatorInterface $locator,
        protected Config $config,
        protected ?DebugLoggerInterface $logger = null
    ) {
    }

    /**
     * Check if debug mode is enabled.
     * 
     * @return bool True if debug mode is enabled
     */
    protected function isDebugMode(): bool
    {
        return $this->config->get('crud6.debug_mode', false);
    }

    /**
     * Log debug message if debug mode is enabled.
     * 
     * Uses DebugLoggerInterface if available, falls back to error_log() otherwise.
     * Only logs when debug_mode config is true.
     * 
     * @param string $message Debug message
     * @param array  $context Context data for structured logging
     * 
     * @return void
     */
    protected function debugLog(string $message, array $context = []): void
    {
        if (!$this->isDebugMode()) {
            return;
        }

        if ($this->logger !== null) {
            $this->logger->debug($message, $context);
        } else {
            // Fallback to error_log if logger not available
            $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
            error_log($message . $contextStr);
        }
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
     * Normalize lookup attributes for smartlookup fields.
     * 
     * Supports both nested and flat lookup structures:
     * - Nested: lookup: {model, id, desc}
     * - Flat: lookup_model, lookup_id, lookup_desc (legacy)
     * 
     * Converts nested structure to flat for backward compatibility.
     * Also supports shorthand attributes: model, id, desc as fallbacks.
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized lookup attributes
     */
    protected function normalizeLookupAttributes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            // Only process smartlookup fields
            if (($field['type'] ?? '') !== 'smartlookup') {
                continue;
            }

            // If nested 'lookup' object exists, expand it to flat attributes
            if (isset($field['lookup']) && is_array($field['lookup'])) {
                // Map nested lookup.model to lookup_model (if not already set)
                if (isset($field['lookup']['model']) && !isset($field['lookup_model'])) {
                    $field['lookup_model'] = $field['lookup']['model'];
                }
                
                // Map nested lookup.id to lookup_id (if not already set)
                if (isset($field['lookup']['id']) && !isset($field['lookup_id'])) {
                    $field['lookup_id'] = $field['lookup']['id'];
                }
                
                // Map nested lookup.desc to lookup_desc (if not already set)
                if (isset($field['lookup']['desc']) && !isset($field['lookup_desc'])) {
                    $field['lookup_desc'] = $field['lookup']['desc'];
                }
            }

            // Provide fallbacks to shorthand attributes if lookup_* not set
            // This supports both old shorthand format and ensures consistency
            if (!isset($field['lookup_model']) && isset($field['model'])) {
                $field['lookup_model'] = $field['model'];
            }
            
            if (!isset($field['lookup_id']) && isset($field['id'])) {
                $field['lookup_id'] = $field['id'];
            }
            
            if (!isset($field['lookup_desc']) && isset($field['desc'])) {
                $field['lookup_desc'] = $field['desc'];
            }
        }

        return $schema;
    }

    /**
     * Normalize visibility flags to show_in array.
     * 
     * Supports both new show_in array and legacy flags (editable, viewable, listable).
     * Converts legacy flags to show_in for internal consistency.
     * 
     * Supported contexts:
     * - 'list': Field appears in list/table view
     * - 'create': Field appears in create form
     * - 'edit': Field appears in edit form
     * - 'form': Shorthand for both create and edit (expanded to both)
     * - 'detail': Field appears in detail/view page
     * 
     * Special handling:
     * - Password fields: Default to ['create', 'edit'] (not viewable)
     * - Readonly fields: Added to 'detail' but removed from 'create'/'edit'
     * - 'form' is expanded to ['create', 'edit'] for granular control
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized visibility flags
     */
    protected function normalizeVisibilityFlags(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            $fieldType = $field['type'] ?? 'string';
            
            // If show_in already exists, normalize it
            if (isset($field['show_in']) && is_array($field['show_in'])) {
                // Expand 'form' to 'create' and 'edit' for granular control
                $normalizedShowIn = [];
                foreach ($field['show_in'] as $context) {
                    if ($context === 'form') {
                        $normalizedShowIn[] = 'create';
                        $normalizedShowIn[] = 'edit';
                    } else {
                        $normalizedShowIn[] = $context;
                    }
                }
                $field['show_in'] = array_unique($normalizedShowIn);
                
                // Derive legacy flags from show_in for backward compatibility
                $field['listable'] = in_array('list', $field['show_in']);
                $field['editable'] = in_array('create', $field['show_in']) || in_array('edit', $field['show_in']);
                $field['viewable'] = in_array('detail', $field['show_in']);
                continue;
            }

            // Otherwise, create show_in from legacy flags (or defaults)
            $showIn = [];
            
            // Default visibility based on legacy flags or sensible defaults
            $listable = $field['listable'] ?? true;
            $editable = $field['editable'] ?? true;
            $viewable = $field['viewable'] ?? true;
            $readonly = $field['readonly'] ?? false;

            // Build show_in array
            if ($listable) {
                $showIn[] = 'list';
            }
            
            // Special handling for password fields
            if ($fieldType === 'password') {
                // Password fields default to create and edit only (not viewable)
                if ($editable && !$readonly) {
                    $showIn[] = 'create';
                    $showIn[] = 'edit';
                }
                // Never show password in detail view (security)
            } else {
                // Regular fields: add create/edit if editable
                if ($editable && !$readonly) {
                    $showIn[] = 'create';
                    $showIn[] = 'edit';
                }
                
                // Add detail view if viewable
                if ($viewable) {
                    $showIn[] = 'detail';
                }
            }

            // Set the show_in array
            $field['show_in'] = $showIn;

            // Keep legacy flags for backward compatibility
            $field['listable'] = $listable;
            $field['editable'] = $editable;
            $field['viewable'] = $viewable;
        }

        return $schema;
    }

    /**
     * Normalize boolean field types with UI specification.
     * 
     * Supports both new format (type: boolean, ui: toggle) and legacy format (type: boolean-tgl).
     * Converts legacy type suffixes to ui property for internal consistency.
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized boolean types
     */
    protected function normalizeBooleanTypes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            $type = $field['type'] ?? 'string';

            // Check if it's a legacy boolean type with UI suffix
            if (preg_match('/^boolean-(tgl|chk|sel|yn)$/', $type, $matches)) {
                $uiType = $matches[1];
                
                // Normalize to standard boolean type
                $field['type'] = 'boolean';
                
                // Set UI type if not already specified
                if (!isset($field['ui'])) {
                    $uiMap = [
                        'tgl' => 'toggle',
                        'chk' => 'checkbox',
                        'sel' => 'select',
                        'yn' => 'select',
                    ];
                    $field['ui'] = $uiMap[$uiType] ?? 'checkbox';
                }
            } elseif ($type === 'boolean' && !isset($field['ui'])) {
                // Set default UI for boolean fields without explicit UI
                $field['ui'] = 'checkbox';
            }
        }

        return $schema;
    }

    /**
     * Normalize ORM-style attributes to CRUD6 format.
     * 
     * Supports attributes from popular ORMs (Laravel, Sequelize, TypeORM, Django, Prisma):
     * - nullable → required (inverted)
     * - autoIncrement → auto_increment
     * - references → lookup configuration
     * - validate → validation
     * - ui → extract UI-specific attributes
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized ORM attributes
     */
    protected function normalizeORMAttributes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            // 1. Normalize nullable → required (Laravel/Sequelize/TypeORM/Prisma pattern)
            if (isset($field['nullable']) && !isset($field['required'])) {
                $field['required'] = !$field['nullable'];
            }
            // Also support the reverse: required → nullable
            if (isset($field['required']) && !isset($field['nullable'])) {
                $field['nullable'] = !$field['required'];
            }

            // 2. Normalize autoIncrement → auto_increment (Sequelize/TypeORM/Prisma pattern)
            if (isset($field['autoIncrement']) && !isset($field['auto_increment'])) {
                $field['auto_increment'] = $field['autoIncrement'];
            }

            // 3. Normalize primaryKey → primary (TypeORM pattern)
            if (isset($field['primaryKey']) && !isset($field['primary'])) {
                $field['primary'] = $field['primaryKey'];
            }

            // 4. Normalize unique constraint (all ORMs support this)
            if (isset($field['unique']) && !isset($field['validation']['unique'])) {
                $field['validation'] = $field['validation'] ?? [];
                $field['validation']['unique'] = $field['unique'];
            }

            // 5. Normalize length to validation (Sequelize/Django pattern)
            if (isset($field['length']) && !isset($field['validation']['length'])) {
                $field['validation'] = $field['validation'] ?? [];
                $field['validation']['length'] = [
                    'max' => $field['length']
                ];
            }

            // 6. Normalize validate → validation (Sequelize pattern)
            if (isset($field['validate']) && !isset($field['validation'])) {
                $field['validation'] = $field['validate'];
            }

            // 7. Normalize references → lookup (Prisma/TypeORM pattern)
            if (isset($field['references']) && is_array($field['references'])) {
                // Convert references to lookup format
                if (!isset($field['lookup'])) {
                    $field['lookup'] = [
                        'model' => $field['references']['model'] ?? $field['references']['table'] ?? null,
                        'id' => $field['references']['key'] ?? $field['references']['id'] ?? 'id',
                        'desc' => $field['references']['display'] ?? $field['references']['desc'] ?? 'name',
                    ];
                }
                
                // If type not set and references exists, assume smartlookup
                if (!isset($field['type']) || $field['type'] === 'integer') {
                    // Only change to smartlookup if explicitly requested or references.display is set
                    if (isset($field['references']['display']) || isset($field['references']['desc'])) {
                        $field['type'] = 'smartlookup';
                    }
                }
            }

            // 8. Extract UI configuration from nested ui object
            if (isset($field['ui']) && is_array($field['ui'])) {
                $uiConfig = $field['ui'];
                
                // Extract label
                if (isset($uiConfig['label']) && !isset($field['label'])) {
                    $field['label'] = $uiConfig['label'];
                }
                
                // Extract show_in
                if (isset($uiConfig['show_in']) && !isset($field['show_in'])) {
                    $field['show_in'] = $uiConfig['show_in'];
                }
                
                // Extract sortable
                if (isset($uiConfig['sortable']) && !isset($field['sortable'])) {
                    $field['sortable'] = $uiConfig['sortable'];
                }
                
                // Extract filterable
                if (isset($uiConfig['filterable']) && !isset($field['filterable'])) {
                    $field['filterable'] = $uiConfig['filterable'];
                }
                
                // Extract widget/type as UI hint (for booleans, etc.)
                if (isset($uiConfig['widget']) && $field['type'] === 'boolean') {
                    $field['ui'] = $uiConfig['widget'];  // Override with widget name
                } elseif (isset($uiConfig['type']) && $uiConfig['type'] === 'lookup') {
                    // UI type hint for lookup fields
                    if ($field['type'] === 'integer' || !isset($field['type'])) {
                        $field['type'] = 'smartlookup';
                    }
                }
            }

            // 9. Normalize default/defaultValue (all ORMs support default)
            if (isset($field['defaultValue']) && !isset($field['default'])) {
                $field['default'] = $field['defaultValue'];
            }
        }

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
            $this->debugLog("[CRUD6 SchemaService] ✅ Using CACHED schema (in-memory)", [
                'model' => $model,
                'connection' => $connection ?? 'null',
                'cache_key' => $cacheKey,
                'timestamp' => date('Y-m-d H:i:s.u'),
            ]);
            return $this->schemaCache[$cacheKey];
        }
        
        // DEBUG: Log every schema load attempt to track duplicate calls
        $this->debugLog("[CRUD6 SchemaService] getSchema() called", [
            'model' => $model,
            'connection' => $connection ?? 'null',
            'cache_key' => $cacheKey,
            'timestamp' => date('Y-m-d H:i:s.u'),
            'caller' => $this->getCallerInfo(),
        ]);
        
        $schema = null;
        $schemaPath = null;

        // If connection is specified, try connection-based path first
        if ($connection !== null) {
            $schemaPath = $this->getSchemaFilePath($model, $connection);
            $this->debugLog("[CRUD6 SchemaService] Trying connection-based path", [
                'path' => $schemaPath,
            ]);
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        // If not found in connection-based path, try default path
        if ($schema === null) {
            $schemaPath = $this->getSchemaFilePath($model);
            $this->debugLog("[CRUD6 SchemaService] Trying default path", [
                'path' => $schemaPath,
            ]);
            $loader = new YamlFileLoader($schemaPath);
            $schema = $loader->load(false);
        }

        if ($schema === null) {
            $this->debugLog("[CRUD6 SchemaService] Schema file not found for model", [
                'model' => $model,
            ]);
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Schema file not found for model: {$model}");
        }

        // Validate schema structure
        $this->validateSchema($schema, $model);

        // Apply default values
        $schema = $this->applyDefaults($schema);

        // Normalize ORM-style attributes first (before other normalizations)
        $schema = $this->normalizeORMAttributes($schema);

        // Normalize lookup attributes for smartlookup fields
        $schema = $this->normalizeLookupAttributes($schema);

        // Normalize visibility flags to show_in array
        $schema = $this->normalizeVisibilityFlags($schema);

        // Normalize boolean field types with UI specification
        $schema = $this->normalizeBooleanTypes($schema);

        // If schema was loaded from connection-based path and doesn't have explicit connection, set it
        if ($connection !== null && !isset($schema['connection'])) {
            $schema['connection'] = $connection;
        }

        $this->debugLog("[CRUD6 SchemaService] Schema loaded successfully and CACHED", [
            'model' => $model,
            'table' => $schema['table'] ?? 'unknown',
            'field_count' => count($schema['fields'] ?? []),
            'cache_key' => $cacheKey,
        ]);

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
        
        $this->debugLog("[CRUD6 SchemaService] Cache cleared for model", [
            'model' => $model,
            'connection' => $connection ?? 'null',
            'cache_key' => $cacheKey,
        ]);
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
        
        $this->debugLog("[CRUD6 SchemaService] All schema cache cleared", [
            'entries_removed' => $count,
        ]);
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
        $this->debugLog("[CRUD6 SchemaService] filterSchemaForContext() called", [
            'model' => $schema['model'] ?? 'unknown',
            'context' => $context ?? 'null/full',
            'timestamp' => date('Y-m-d H:i:s.u'),
        ]);
        
        // If no context or 'full', return complete schema (backward compatible)
        if ($context === null || $context === 'full') {
            $this->debugLog("[CRUD6 SchemaService] Returning full schema (no filtering)");
            return $schema;
        }

        // Check if multiple contexts are requested (comma-separated)
        if (strpos($context, ',') !== false) {
            $contexts = array_map('trim', explode(',', $context));
            $this->debugLog("[CRUD6 SchemaService] Multiple contexts requested", [
                'contexts' => implode(', ', $contexts),
            ]);
            return $this->filterSchemaForMultipleContexts($schema, $contexts);
        }

        // Single context - use existing logic
        $this->debugLog("[CRUD6 SchemaService] Single context filtering", [
            'context' => $context,
        ]);
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
                    // Check show_in array for 'list' context
                    $showInList = isset($field['show_in']) 
                        ? in_array('list', $field['show_in']) 
                        : ($field['listable'] ?? true);
                    
                    if ($showInList) {
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

            case 'create':
                // For create forms: fields visible during creation
                return $this->getFormContextData($schema, 'create');

            case 'edit':
                // For edit forms: fields visible during editing
                return $this->getFormContextData($schema, 'edit');

            case 'form':
                // For create/edit forms (backward compatible): fields visible in both
                // This merges fields from both create and edit contexts
                $createData = $this->getFormContextData($schema, 'create');
                $editData = $this->getFormContextData($schema, 'edit');
                
                // Merge fields from both contexts
                $mergedFields = [];
                foreach ($createData['fields'] as $key => $field) {
                    $mergedFields[$key] = $field;
                }
                foreach ($editData['fields'] as $key => $field) {
                    if (!isset($mergedFields[$key])) {
                        $mergedFields[$key] = $field;
                    }
                }
                
                return ['fields' => $mergedFields];

            case 'detail':
                // For detail/view pages: only viewable fields with full display properties
                $data = ['fields' => []];
                
                foreach ($schema['fields'] as $fieldKey => $field) {
                    // Check show_in array for 'detail' context
                    $showInDetail = isset($field['show_in']) 
                        ? in_array('detail', $field['show_in']) 
                        : ($field['viewable'] ?? true);
                    
                    if ($showInDetail) {
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

    /**
     * Get form context data for create or edit context.
     * 
     * Helper method to extract fields visible in create or edit forms.
     * 
     * @param array  $schema  The complete schema array
     * @param string $context Either 'create' or 'edit'
     * 
     * @return array Form data with fields for the specified context
     */
    protected function getFormContextData(array $schema, string $context): array
    {
        $data = ['fields' => []];
        
        foreach ($schema['fields'] as $fieldKey => $field) {
            // Check show_in array for the specific context (create or edit)
            $showInContext = false;
            
            if (isset($field['show_in']) && is_array($field['show_in'])) {
                $showInContext = in_array($context, $field['show_in']);
            } else {
                // Fallback to legacy flags if show_in not set
                $showInContext = ($field['editable'] ?? true) !== false && !($field['readonly'] ?? false);
            }
            
            if ($showInContext) {
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
                
                // Include show_in for reference
                if (isset($field['show_in'])) {
                    $data['fields'][$fieldKey]['show_in'] = $field['show_in'];
                }

                // Include smartlookup configuration if present
                if (($field['type'] ?? '') === 'smartlookup') {
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
    }

    /**
     * Load related model schemas and include them in the response.
     * 
     * This method loads schemas for all models referenced in the 'details' 
     * and 'relationships' sections of the main schema, consolidating multiple
     * schema requests into a single response.
     * 
     * @param array       $schema     The main model schema
     * @param string|null $context    The context for related schemas (default: 'list')
     * @param string|null $connection The database connection (optional)
     * 
     * @return array Array of related schemas keyed by model name
     */
    public function loadRelatedSchemas(array $schema, ?string $context = 'list', ?string $connection = null): array
    {
        $relatedSchemas = [];
        
        $this->debugLog("[CRUD6 SchemaService] Loading related schemas", [
            'model' => $schema['model'] ?? 'unknown',
            'context' => $context ?? 'list',
        ]);

        // Collect unique model names from details and relationships
        $relatedModels = [];

        // Get models from 'details' array
        if (isset($schema['details']) && is_array($schema['details'])) {
            foreach ($schema['details'] as $detail) {
                if (isset($detail['model'])) {
                    $relatedModels[$detail['model']] = true;
                }
            }
        }

        // Get models from 'relationships' array  
        if (isset($schema['relationships']) && is_array($schema['relationships'])) {
            foreach ($schema['relationships'] as $relationship) {
                if (isset($relationship['name'])) {
                    $relatedModels[$relationship['name']] = true;
                }
            }
        }

        $this->debugLog("[CRUD6 SchemaService] Found related models", [
            'count' => count($relatedModels),
            'models' => array_keys($relatedModels),
        ]);

        // Load schema for each related model
        foreach (array_keys($relatedModels) as $modelName) {
            try {
                $relatedSchema = $this->getSchema($modelName, $connection);
                
                // Filter the related schema for the specified context
                $filteredRelatedSchema = $this->filterSchemaForContext($relatedSchema, $context);
                
                $relatedSchemas[$modelName] = $filteredRelatedSchema;
                
                $this->debugLog("[CRUD6 SchemaService] Loaded related schema", [
                    'model' => $modelName,
                    'fieldCount' => count($filteredRelatedSchema['fields'] ?? []),
                ]);
            } catch (\Exception $e) {
                // Log error but continue loading other schemas
                $this->debugLog("[CRUD6 SchemaService] Failed to load related schema", [
                    'model' => $modelName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $relatedSchemas;
    }

    /**
     * Filter schema and optionally include related model schemas.
     * 
     * Enhanced version of filterSchemaForContext that can also include
     * related model schemas in a single consolidated response.
     * 
     * @param array       $schema              The main model schema
     * @param string|null $context             The context for filtering
     * @param bool        $includeRelated      Whether to include related model schemas
     * @param string|null $relatedContext      Context for related schemas (default: 'list')
     * @param string|null $connection          Database connection (optional)
     * 
     * @return array Filtered schema with optional related_schemas section
     */
    public function filterSchemaWithRelated(
        array $schema,
        ?string $context = null,
        bool $includeRelated = false,
        ?string $relatedContext = 'list',
        ?string $connection = null
    ): array {
        // Get the filtered main schema
        $filtered = $this->filterSchemaForContext($schema, $context);

        // If requested, include related model schemas
        if ($includeRelated) {
            $this->debugLog("[CRUD6 SchemaService] Including related schemas in response", [
                'model' => $schema['model'] ?? 'unknown',
                'relatedContext' => $relatedContext ?? 'list',
            ]);
            
            $relatedSchemas = $this->loadRelatedSchemas($schema, $relatedContext, $connection);
            
            if (!empty($relatedSchemas)) {
                $filtered['related_schemas'] = $relatedSchemas;
                
                $this->debugLog("[CRUD6 SchemaService] Added related schemas to response", [
                    'count' => count($relatedSchemas),
                    'models' => array_keys($relatedSchemas),
                ]);
            }
        }

        return $filtered;
    }
}