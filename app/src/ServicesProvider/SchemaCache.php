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
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Schema Cache.
 * 
 * Implements a two-tier caching system for schema data:
 * 1. In-memory cache (active during request lifecycle)
 * 2. PSR-16 persistent cache (optional, for production)
 */
class SchemaCache
{
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
     * Constructor.
     * 
     * Logger is injected through DI container. PSR-16 cache is optional and
     * injected by the service provider factory.
     * 
     * @param Config               $config Configuration repository
     * @param DebugLoggerInterface $logger Debug logger for diagnostics
     * @param CacheInterface|null  $cache  PSR-16 cache for persistent caching (optional)
     */
    public function __construct(
        protected Config $config,
        protected DebugLoggerInterface $logger,
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
     * Get schema from cache.
     * 
     * Checks both in-memory and PSR-16 persistent cache.
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return array|null The cached schema, or null if not found
     */
    public function get(string $model, ?string $connection = null): ?array
    {
        $cacheKey = $this->getCacheKey($model, $connection);
        $persistentCacheKey = $this->cachePrefix . $cacheKey;
        
        // 1. Check in-memory cache first (fastest)
        if (isset($this->schemaCache[$cacheKey])) {
            $this->debugLog("[CRUD6 SchemaCache] ✅ Using CACHED schema (in-memory)", [
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
                    $this->debugLog("[CRUD6 SchemaCache] ✅ Using CACHED schema (PSR-16)", [
                        'model' => $model,
                        'connection' => $connection ?? 'null',
                        'cache_key' => $persistentCacheKey,
                    ]);
                    // Store in memory cache for this request
                    $this->schemaCache[$cacheKey] = $cached;
                    return $cached;
                }
            } catch (\Exception $e) {
                $this->debugLog("[CRUD6 SchemaCache] PSR-16 cache error", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return null;
    }

    /**
     * Store schema in cache.
     * 
     * Stores in both in-memory and PSR-16 persistent cache (if enabled).
     * 
     * @param array       $schema     The schema to cache
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return void
     */
    public function set(array $schema, string $model, ?string $connection = null): void
    {
        $cacheKey = $this->getCacheKey($model, $connection);
        $persistentCacheKey = $this->cachePrefix . $cacheKey;
        
        // Store in in-memory cache for future requests during this request lifecycle
        $this->schemaCache[$cacheKey] = $schema;
        
        // Store in PSR-16 persistent cache (if available and enabled)
        if ($this->cache !== null && $this->isPersistentCacheEnabled()) {
            try {
                $this->cache->set($persistentCacheKey, $schema, $this->cacheTtl);
                $this->debugLog("[CRUD6 SchemaCache] Schema saved to PSR-16 cache", [
                    'model' => $model,
                    'cache_key' => $persistentCacheKey,
                    'ttl' => $this->cacheTtl,
                ]);
            } catch (\Exception $e) {
                $this->debugLog("[CRUD6 SchemaCache] Failed to save to PSR-16 cache", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->debugLog("[CRUD6 SchemaCache] Schema cached", [
            'model' => $model,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Clear cached schema for a specific model.
     * 
     * Clears both in-memory cache and PSR-16 persistent cache (if enabled).
     * 
     * @param string      $model      The model name
     * @param string|null $connection Optional connection name
     * 
     * @return void
     */
    public function clear(string $model, ?string $connection = null): void
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
                $this->debugLog("[CRUD6 SchemaCache] Failed to clear PSR-16 cache", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $this->debugLog("[CRUD6 SchemaCache] Cache cleared for model", [
            'model' => $model,
            'connection' => $connection ?? 'null',
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Clear all cached schemas.
     * 
     * Clears in-memory cache only. Does not clear PSR-16 cache to avoid
     * affecting other cache entries. Use clear() for each model instead.
     * 
     * @return void
     */
    public function clearAll(): void
    {
        $count = count($this->schemaCache);
        $this->schemaCache = [];
        
        $this->debugLog("[CRUD6 SchemaCache] All in-memory schema cache cleared", [
            'entries_removed' => $count,
        ]);
    }

    /**
     * Check if persistent caching is enabled.
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
}
