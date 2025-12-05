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
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * Schema Service.
 * 
 * Orchestrates schema operations by delegating to specialized service classes.
 * This refactored version maintains backward compatibility while improving
 * maintainability through separation of concerns.
 * 
 * All specialized services are injected through the DI container following
 * UserFrosting 6 patterns, ensuring proper dependency management and testability.
 * 
 * Schema files are loaded from the 'schema://crud6/' location and can be organized
 * by database connection in subdirectories for multi-database support.
 * 
 * ## Caching Strategy
 * 
 * The service implements a two-tier caching system through SchemaCache:
 * 1. **In-memory cache**: Active during the request lifecycle (always enabled)
 * 2. **Persistent cache**: Optional PSR-16 compatible cache for production (redis, file, etc.)
 * 
 * ## Service Components
 * 
 * - **SchemaLoader**: File path resolution, JSON loading, default values
 * - **SchemaValidator**: Structure validation, permission checks
 * - **SchemaNormalizer**: ORM attributes, lookups, visibility flags, boolean types
 * - **SchemaCache**: Two-tier caching (in-memory + PSR-16)
 * - **SchemaFilter**: Context filtering, related schema loading
 * - **SchemaTranslator**: i18n translation
 * - **SchemaActionManager**: Default actions, toggle normalization
 * 
 * @see \UserFrosting\Sprinkle\Core\ServicesProvider\CacheService
 */
class SchemaService
{

    /**
     * Constructor for SchemaService.
     * 
     * All specialized service components are injected through the DI container
     * following UserFrosting 6 patterns for better testability and maintainability.
     * 
     * @param ResourceLocatorInterface  $locator      Resource locator for finding schema files
     * @param Config                    $config       Configuration repository
     * @param DebugLoggerInterface      $logger       Debug logger for diagnostics
     * @param Translator                $i18n         Translator for i18n
     * @param SchemaLoader              $loader       Schema file loader
     * @param SchemaValidator           $validator    Schema validator
     * @param SchemaNormalizer          $normalizer   Schema normalizer
     * @param SchemaCache               $cache        Schema cache handler
     * @param SchemaFilter              $filter       Schema context filter
     * @param SchemaTranslator          $translator   Schema translator
     * @param SchemaActionManager       $actionManager Action manager
     */
    public function __construct(
        protected ResourceLocatorInterface $locator,
        protected Config $config,
        protected DebugLoggerInterface $logger,
        protected Translator $i18n,
        protected SchemaLoader $loader,
        protected SchemaValidator $validator,
        protected SchemaNormalizer $normalizer,
        protected SchemaCache $cache,
        protected SchemaFilter $filter,
        protected SchemaTranslator $translator,
        protected SchemaActionManager $actionManager
    ) {
        // All services are now injected through constructor
        // No need to instantiate them here
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
     * Uses DebugLoggerInterface following UserFrosting 6 standards.
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

        $this->logger->debug($message, $context);
    }

    /**
     * Get schema configuration for a model.
     * 
     * Implements a two-tier caching strategy and delegates to specialized services.
     *
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name for path-based lookup
     * 
     * @return array The schema configuration
     * 
     * @throws \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException
     */
    public function getSchema(string $model, ?string $connection = null): array
    {
        // DEBUG: Log every schema load attempt to track duplicate calls
        $this->debugLog("[CRUD6 SchemaService] getSchema() called", [
            'model' => $model,
            'connection' => $connection ?? 'null',
            'timestamp' => date('Y-m-d H:i:s.u'),
            'caller' => $this->getCallerInfo(),
        ]);

        // Check cache first
        $cached = $this->cache->get($model, $connection);
        if ($cached !== null) {
            return $cached;
        }

        // Load schema from file
        $schema = $this->loader->loadSchema($model, $connection);

        if ($schema === null) {
            $this->debugLog("[CRUD6 SchemaService] Schema file not found for model", [
                'model' => $model,
            ]);
            throw new \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException("Schema file not found for model: {$model}");
        }

        // Validate schema structure
        $this->validator->validate($schema, $model);

        // Apply default values
        $schema = $this->loader->applyDefaults($schema);

        // Normalize schema
        $schema = $this->normalizer->normalize($schema);

        // Add default CRUD actions if not disabled
        $schema = $this->actionManager->addDefaultActions($schema);

        // If schema was loaded from connection-based path and doesn't have explicit connection, set it
        if ($connection !== null && !isset($schema['connection'])) {
            $schema['connection'] = $connection;
        }

        $this->debugLog("[CRUD6 SchemaService] Schema loaded successfully and CACHED", [
            'model' => $model,
            'table' => $schema['table'] ?? 'unknown',
            'field_count' => count($schema['fields'] ?? []),
        ]);

        // Store in cache
        $this->cache->set($schema, $model, $connection);

        return $schema;
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
     * Delegates to cache service.
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return void
     */
    public function clearCache(string $model, ?string $connection = null): void
    {
        $this->cache->clear($model, $connection);
    }

    /**
     * Clear all cached schemas.
     * 
     * Delegates to cache service.
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        $this->cache->clearAll();
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
     * Delegates to filter service.
     * 
     * @param array       $schema  The complete schema array
     * @param string|null $context The context to filter by
     * 
     * @return array The filtered schema appropriate for the context(s)
     */
    public function filterSchemaForContext(array $schema, ?string $context = null): array
    {
        return $this->filter->filterForContext($schema, $context);
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
     * Delegates to translator service.
     * 
     * @param array $schema The schema to translate
     * 
     * @return array The translated schema
     */
    public function translateSchema(array $schema): array
    {
        return $this->translator->translate($schema);
    }

    /**
     * Filter actions by scope.
     * 
     * Delegates to action manager.
     * 
     * @param array  $actions Actions array from schema
     * @param string $scope   The scope to filter by ('list' or 'detail')
     * 
     * @return array Filtered actions array
     */
    public function filterActionsByScope(array $actions, string $scope): array
    {
        return $this->actionManager->filterActionsByScope($actions, $scope);
    }
}
