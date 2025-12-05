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

use Psr\SimpleCache\CacheInterface;
use UserFrosting\Config\Config;
use UserFrosting\I18n\Translator;
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
 * ## Caching Strategy
 * 
 * The service implements a two-tier caching system:
 * 1. **In-memory cache**: Active during the request lifecycle (always enabled)
 * 2. **Persistent cache**: Optional PSR-16 compatible cache for production (redis, file, etc.)
 * 
 * To enable persistent caching, inject a PSR-16 CacheInterface implementation.
 * 
 * @example
 * ```php
 * // With PSR-16 cache (production)
 * $schemaService = new SchemaService($locator, $config, $logger, $translator, $cache);
 * 
 * // Without cache (development)
 * $schemaService = new SchemaService($locator, $config);
 * ```
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
     * @var int Cache TTL in seconds (default: 1 hour)
     */
    protected int $cacheTtl = 3600;
    
    /**
     * @var string Cache key prefix
     */
    protected string $cachePrefix = 'crud6_schema_';
    
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
     * @param ResourceLocatorInterface  $locator    Resource locator for finding schema files
     * @param Config                    $config     Configuration repository
     * @param DebugLoggerInterface|null $logger     Debug logger for diagnostics (optional)
     * @param Translator|null           $translator Translator for i18n (optional)
     * @param CacheInterface|null       $cache      PSR-16 cache for persistent caching (optional)
     */
    public function __construct(
        protected ResourceLocatorInterface $locator,
        protected Config $config,
        protected ?DebugLoggerInterface $logger = null,
        protected ?Translator $translator = null,
        protected ?CacheInterface $cache = null
    ) {
        // Load cache TTL from config if available
        $this->cacheTtl = $this->config->get('crud6.cache_ttl', 3600);
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
     * Uses DebugLoggerInterface when available. Follows UserFrosting 6 standards
     * by only logging through the proper logger interface.
     * Only logs when debug_mode config is true and logger is available.
     * 
     * @param string $message Debug message
     * @param array  $context Context data for structured logging
     * 
     * @return void
     */
    protected function debugLog(string $message, array $context = []): void
    {
        if (!$this->isDebugMode() || $this->logger === null) {
            return;
        }

        $this->logger->debug($message, $context);
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
                    "Line:186 Schema for model '{$model}' is missing required field: {$field}"
                );
            }
        }

        // Validate that model name matches
        if ($schema['model'] !== $model) {
            throw new \RuntimeException(
                "Line:194 Schema model name '{$schema['model']}' does not match requested model '{$model}'"
            );
        }

        // Validate fields structure
        if (!is_array($schema['fields']) || empty($schema['fields'])) {
            throw new \RuntimeException(
                "Line:201 Schema for model '{$model}' must have a non-empty 'fields' array"
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
     * Converts visibility flags (editable, viewable, listable) to show_in array
     * for consistent internal representation.
     * 
     * Supported contexts:
     * - 'list': Field appears in list/table view
     * - 'create': Field appears in create form
     * - 'edit': Field appears in edit form
     * - 'form': Shorthand for both create and edit (expanded to both)
     * - 'detail': Field appears in detail/view page
     * 
     * Special handling:
     * - Password fields: Default to ['create', 'edit'] (not viewable for security)
     * - Non-editable fields: Added to 'detail' but removed from 'create'/'edit'
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
                
                // Derive convenience flags from show_in
                $field['listable'] = in_array('list', $field['show_in']);
                $field['editable'] = in_array('create', $field['show_in']) || in_array('edit', $field['show_in']);
                $field['viewable'] = in_array('detail', $field['show_in']);
                continue;
            }

            // Otherwise, create show_in from flags (or defaults)
            $showIn = [];
            
            // Default visibility based on flags or sensible defaults
            $listable = $field['listable'] ?? true;
            $editable = $field['editable'] ?? true;
            $viewable = $field['viewable'] ?? true;

            // Build show_in array
            if ($listable) {
                $showIn[] = 'list';
            }
            
            // Special handling for password fields
            if ($fieldType === 'password') {
                // Password fields default to create and edit only (not viewable for security)
                if ($editable) {
                    $showIn[] = 'create';
                    $showIn[] = 'edit';
                }
                // Never show password in detail view (security)
            } else {
                // Regular fields: add create/edit if editable
                if ($editable) {
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

            // Set convenience flags
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
     * Implements a two-tier caching strategy:
     * 1. In-memory cache (request lifecycle)
     * 2. PSR-16 persistent cache (optional, for production)
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
        $persistentCacheKey = $this->cachePrefix . $cacheKey;
        
        // 1. Check in-memory cache first (fastest)
        if (isset($this->schemaCache[$cacheKey])) {
            $this->debugLog("[CRUD6 SchemaService] ✅ Using CACHED schema (in-memory)", [
                'model' => $model,
                'connection' => $connection ?? 'null',
                'cache_key' => $cacheKey,
                'timestamp' => date('Y-m-d H:i:s.u'),
            ]);
            return $this->schemaCache[$cacheKey];
        }
        
        // 2. Check PSR-16 persistent cache (if available)
        if ($this->cache !== null && $this->isPersistentCacheEnabled()) {
            try {
                $cached = $this->cache->get($persistentCacheKey);
                if ($cached !== null) {
                    $this->debugLog("[CRUD6 SchemaService] ✅ Using CACHED schema (PSR-16)", [
                        'model' => $model,
                        'connection' => $connection ?? 'null',
                        'cache_key' => $persistentCacheKey,
                    ]);
                    // Store in memory cache for this request
                    $this->schemaCache[$cacheKey] = $cached;
                    return $cached;
                }
            } catch (\Exception $e) {
                $this->debugLog("[CRUD6 SchemaService] PSR-16 cache error", [
                    'error' => $e->getMessage(),
                ]);
            }
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

        // Add default CRUD actions if not disabled
        $schema = $this->addDefaultActions($schema);

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

        // Store in in-memory cache for future requests during this request lifecycle
        $this->schemaCache[$cacheKey] = $schema;
        
        // Store in PSR-16 persistent cache (if available and enabled)
        if ($this->cache !== null && $this->isPersistentCacheEnabled()) {
            try {
                $this->cache->set($persistentCacheKey, $schema, $this->cacheTtl);
                $this->debugLog("[CRUD6 SchemaService] Schema saved to PSR-16 cache", [
                    'model' => $model,
                    'cache_key' => $persistentCacheKey,
                    'ttl' => $this->cacheTtl,
                ]);
            } catch (\Exception $e) {
                $this->debugLog("[CRUD6 SchemaService] Failed to save to PSR-16 cache", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $schema;
    }
    
    /**
     * Check if persistent caching is enabled.
     * 
     * Checks the config for crud6.cache_enabled setting.
     * Default is false (development mode).
     * 
     * @return bool True if persistent caching is enabled
     */
    protected function isPersistentCacheEnabled(): bool
    {
        return $this->config->get('crud6.cache_enabled', false);
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
     * Clears both in-memory cache and PSR-16 persistent cache (if enabled).
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
        $persistentCacheKey = $this->cachePrefix . $cacheKey;
        
        // Clear in-memory cache
        unset($this->schemaCache[$cacheKey]);
        
        // Clear PSR-16 cache if available
        if ($this->cache !== null) {
            try {
                $this->cache->delete($persistentCacheKey);
            } catch (\Exception $e) {
                $this->debugLog("[CRUD6 SchemaService] Failed to clear PSR-16 cache", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->debugLog("[CRUD6 SchemaService] Cache cleared for model", [
            'model' => $model,
            'connection' => $connection ?? 'null',
            'cache_key' => $cacheKey,
        ]);
    }
    
    /**
     * Clear all cached schemas.
     * 
     * Clears both in-memory cache and PSR-16 persistent cache (if enabled).
     * Note: PSR-16 clear() clears ALL cache entries, not just schema entries.
     * For selective clearing, use clearCache() for each model.
     * 
     * Useful for testing or when multiple schema files are updated.
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        $count = count($this->schemaCache);
        $this->schemaCache = [];
        
        // Note: We don't call $this->cache->clear() as it would clear ALL cache
        // entries, not just schema entries. Use clearCache() for each model instead.
        
        $this->debugLog("[CRUD6 SchemaService] All in-memory schema cache cleared", [
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

                // Include actions scoped for list view
                if (isset($schema['actions'])) {
                    $data['actions'] = $this->filterActionsByScope($schema['actions'], 'list');
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
                            'editable' => $field['editable'] ?? true,
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
                    $data['actions'] = $this->filterActionsByScope($schema['actions'], 'detail');
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
                // Fallback if show_in not set
                $showInContext = ($field['editable'] ?? true) !== false;
            }
            
            if ($showInContext) {
                $data['fields'][$fieldKey] = [
                    'type' => $field['type'] ?? 'string',
                    'label' => $field['label'] ?? $fieldKey,
                    'required' => $field['required'] ?? false,
                    'editable' => $field['editable'] ?? true,
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

    /**
     * Translate all translatable fields in a schema.
     * 
     * This method recursively processes the schema and translates:
     * - title, singular_title, description (top-level)
     * - Field labels and descriptions
     * - Action labels and confirm messages
     * - Relationship titles
     * - Detail titles
     * 
     * Translation keys are identified by checking if the value looks like a 
     * translation key (contains only uppercase letters, numbers, dots, and underscores).
     * 
     * @param array $schema The schema to translate
     * 
     * @return array The translated schema
     */
    public function translateSchema(array $schema): array
    {
        if ($this->translator === null) {
            $this->debugLog("[CRUD6 SchemaService] No translator available, returning untranslated schema");
            return $schema;
        }

        $this->debugLog("[CRUD6 SchemaService] translateSchema() called", [
            'model' => $schema['model'] ?? 'unknown',
        ]);

        // Recursively translate all string values that look like translation keys
        $schema = $this->translateArrayRecursive($schema);

        $this->debugLog("[CRUD6 SchemaService] Schema translation complete", [
            'model' => $schema['model'] ?? 'unknown',
        ]);

        return $schema;
    }

    /**
     * Recursively translate all string values in an array that look like translation keys.
     * 
     * This method traverses the entire array structure and translates any string value
     * that matches the translation key pattern (uppercase with dots).
     * 
     * @param array $data The array to translate
     * 
     * @return array The array with all translation keys translated
     */
    protected function translateArrayRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively translate nested arrays
                $data[$key] = $this->translateArrayRecursive($value);
            } elseif (is_string($value)) {
                // Translate string values that look like translation keys
                $data[$key] = $this->translateValue($value);
            }
            // Non-string, non-array values are left as-is
        }
        
        return $data;
    }

    /**
     * Translate a value if it looks like a translation key.
     * 
     * A translation key is identified by:
     * - Contains only uppercase letters, numbers, dots, and underscores
     * - Contains at least one dot (e.g., "USER.1", "CRUD6.ACTION.TOGGLE_ENABLED")
     * 
     * Values that don't match this pattern are returned as-is (plain text labels).
     * 
     * @param string $value The value to potentially translate
     * 
     * @return string The translated value, or original if not a translation key
     */
    protected function translateValue(string $value): string
    {
        // Check if value looks like a translation key
        // Translation keys: contain uppercase letters, dots, underscores, numbers
        // Must contain at least one dot to distinguish from plain text
        if (preg_match('/^[A-Z][A-Z0-9_.]+\.[A-Z0-9_.]+$/', $value)) {
            $translated = $this->translator->translate($value);
            
            // If translation returns the same key, the key doesn't exist
            // In this case, return the original value
            if ($translated === $value) {
                $this->debugLog("[CRUD6 SchemaService] Translation key not found", [
                    'key' => $value,
                ]);
            }
            
            return $translated;
        }
        
        // Not a translation key, return as-is
        return $value;
    }

    /**
     * Normalize toggle actions to ensure they have confirmation modals.
     * 
     * Toggle actions (field_update with toggle: true) should always show a confirmation
     * before changing the value. This method adds default confirm messages and modal
     * config if not already present.
     * 
     * @param array $actions Actions array from schema
     * @param array $schema  Full schema for field label lookups
     * 
     * @return array Normalized actions array
     */
    protected function normalizeToggleActions(array $actions, array $schema): array
    {
        foreach ($actions as &$action) {
            // Only process field_update actions with toggle enabled
            if (($action['type'] ?? '') !== 'field_update' || !($action['toggle'] ?? false)) {
                continue;
            }

            // Get field name and field configuration
            $fieldName = $action['field'] ?? null;
            if (!$fieldName) {
                continue;
            }

            $fieldConfig = $schema['fields'][$fieldName] ?? null;
            $fieldLabel = $fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));

            // Add default confirm message if not present
            if (!isset($action['confirm'])) {
                $action['confirm'] = "Are you sure you want to toggle <strong>{$fieldLabel}</strong> for <strong>{{" . ($schema['title_field'] ?? 'id') . "}}</strong>?";
            }

            // Add default modal config if not present
            if (!isset($action['modal_config'])) {
                $action['modal_config'] = [
                    'type' => 'confirm',
                    'buttons' => 'yes_no',
                ];
            } elseif (!isset($action['modal_config']['type'])) {
                // Ensure modal type is set to confirm for toggles
                $action['modal_config']['type'] = 'confirm';
            }

            $this->debugLog("[CRUD6 SchemaService] Normalized toggle action", [
                'key' => $action['key'] ?? 'unknown',
                'field' => $fieldName,
                'has_confirm' => isset($action['confirm']),
            ]);
        }

        return $actions;
    }

    /**
     * Add default CRUD actions to schema if not already defined.
     * 
     * This method intelligently adds standard CRUD actions (create, edit, delete)
     * to schemas that don't already define them. Each action is scoped appropriately:
     * 
     * - 'create': Appears in list view (scope: 'list')
     * - 'edit': Appears in detail view (scope: 'detail')
     * - 'delete': Appears in detail view (scope: 'detail')
     * 
     * Actions are only added if:
     * 1. Schema doesn't have an existing action with the same key
     * 2. Schema permissions allow the operation
     * 3. Schema hasn't explicitly set `default_actions: false`
     * 
     * Schemas can override default actions in two ways:
     * 1. Set `"default_actions": false` to disable all defaults
     * 2. Define custom actions with keys matching default keys (create_action, edit_action, delete_action)
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with default actions added
     */
    protected function addDefaultActions(array $schema): array
    {
        // Check if default actions are disabled
        if (isset($schema['default_actions']) && $schema['default_actions'] === false) {
            $this->debugLog("[CRUD6 SchemaService] Default actions disabled for model", [
                'model' => $schema['model'] ?? 'unknown',
            ]);
            return $schema;
        }

        // Initialize actions array if not present
        if (!isset($schema['actions'])) {
            $schema['actions'] = [];
        }

        // Normalize toggle actions to ensure they have confirmation
        $schema['actions'] = $this->normalizeToggleActions($schema['actions'], $schema);

        // Get existing action keys for duplicate detection
        $existingKeys = array_column($schema['actions'], 'key');

        // Get model label for translations
        $modelLabel = $schema['singular_title'] ?? $schema['title'] ?? ucfirst($schema['model']);

        // Default actions with scope filtering
        $defaultActions = [];

        // Create action - appears in list view
        if (!in_array('create_action', $existingKeys) && $this->hasSchemaPermission($schema, 'create')) {
            $defaultActions[] = [
                'key' => 'create_action',
                'label' => "CRUD6.CREATE",
                'icon' => 'plus',
                'type' => 'form',
                'style' => 'primary',
                'permission' => $schema['permissions']['create'] ?? 'create',
                'scope' => ['list'],
                'modal_config' => [
                    'type' => 'form',
                    'title' => "CRUD6.CREATE",
                ],
            ];
        }

        // Edit action - appears in detail view
        if (!in_array('edit_action', $existingKeys) && $this->hasSchemaPermission($schema, 'update')) {
            $defaultActions[] = [
                'key' => 'edit_action',
                'label' => "CRUD6.EDIT",
                'icon' => 'pen-to-square',
                'type' => 'form',
                'style' => 'primary',
                'permission' => $schema['permissions']['update'] ?? 'update',
                'scope' => ['detail'],
                'modal_config' => [
                    'type' => 'form',
                    'title' => "CRUD6.EDIT",
                ],
            ];
        }

        // Delete action - appears in detail view
        if (!in_array('delete_action', $existingKeys) && $this->hasSchemaPermission($schema, 'delete')) {
            $defaultActions[] = [
                'key' => 'delete_action',
                'label' => "CRUD6.DELETE",
                'icon' => 'trash',
                'type' => 'delete',
                'style' => 'danger',
                'permission' => $schema['permissions']['delete'] ?? 'delete',
                'scope' => ['detail'],
                'confirm' => "CRUD6.DELETE_CONFIRM",
                'modal_config' => [
                    'type' => 'confirm',
                    'buttons' => 'yes_no',
                    'warning' => 'WARNING_CANNOT_UNDONE',
                ],
            ];
        }

        // Prepend default actions (so custom actions appear after)
        if (!empty($defaultActions)) {
            $schema['actions'] = array_merge($defaultActions, $schema['actions']);
            
            $this->debugLog("[CRUD6 SchemaService] Added default actions", [
                'model' => $schema['model'] ?? 'unknown',
                'actions_added' => array_column($defaultActions, 'key'),
            ]);
        }

        return $schema;
    }

    /**
     * Check if schema has permission for an operation.
     * 
     * @param array  $schema    The schema array
     * @param string $operation The operation (create, read, update, delete)
     * 
     * @return bool True if permission exists
     */
    protected function hasSchemaPermission(array $schema, string $operation): bool
    {
        return isset($schema['permissions'][$operation]);
    }

    /**
     * Filter actions by scope.
     * 
     * Returns only actions that should appear in the specified scope (list or detail).
     * Actions without a scope are included in all scopes for backward compatibility.
     * 
     * @param array  $actions Actions array from schema
     * @param string $scope   The scope to filter by ('list' or 'detail')
     * 
     * @return array Filtered actions array
     */
    public function filterActionsByScope(array $actions, string $scope): array
    {
        return array_values(array_filter($actions, function ($action) use ($scope) {
            // Include actions without scope (backward compatibility)
            if (!isset($action['scope'])) {
                return true;
            }

            // Check if scope matches
            if (is_array($action['scope'])) {
                return in_array($scope, $action['scope']);
            }

            return $action['scope'] === $scope;
        }));
    }
}