<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Config;

use UserFrosting\Config\Config;

/**
 * Centralized CRUD6 configuration class.
 * 
 * Provides type-safe access to CRUD6 configuration values with sensible defaults.
 * This class wraps the UserFrosting Config service and provides getter methods
 * for all CRUD6-specific configuration options.
 * 
 * Configuration keys (set in your app's config files):
 * - crud6.debug_mode: bool - Enable debug logging
 * - crud6.default_page_size: int - Default records per page (default: 25)
 * - crud6.max_page_size: int - Maximum records per page (default: 100)
 * - crud6.schema_path: string - Path to schema files (default: 'schema://crud6/')
 * - crud6.cache_enabled: bool - Enable schema caching (default: false)
 * - crud6.cache_ttl: int - Cache TTL in seconds (default: 3600)
 * 
 * @example
 * ```php
 * // In a controller or service
 * public function __construct(private CRUD6Config $crud6Config) {}
 * 
 * public function someMethod() {
 *     if ($this->crud6Config->isDebugMode()) {
 *         // Debug-only logic
 *     }
 *     
 *     $pageSize = $this->crud6Config->getDefaultPageSize();
 * }
 * ```
 */
class CRUD6Config
{
    /**
     * Default values for CRUD6 configuration.
     */
    private const DEFAULTS = [
        'debug_mode' => false,
        'default_page_size' => 25,
        'max_page_size' => 100,
        'schema_path' => 'schema://crud6/',
        'cache_enabled' => false,
        'cache_ttl' => 3600,
    ];

    /**
     * @param Config $config The UserFrosting configuration service
     */
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Check if debug mode is enabled.
     * 
     * When enabled, additional logging and diagnostics are output.
     * 
     * @return bool True if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return (bool) $this->config->get('crud6.debug_mode', self::DEFAULTS['debug_mode']);
    }

    /**
     * Get the default page size for Sprunje pagination.
     * 
     * @return int The default number of records per page
     */
    public function getDefaultPageSize(): int
    {
        return (int) $this->config->get('crud6.default_page_size', self::DEFAULTS['default_page_size']);
    }

    /**
     * Get the maximum allowed page size for Sprunje pagination.
     * 
     * This limit prevents clients from requesting too many records at once.
     * 
     * @return int The maximum number of records per page
     */
    public function getMaxPageSize(): int
    {
        return (int) $this->config->get('crud6.max_page_size', self::DEFAULTS['max_page_size']);
    }

    /**
     * Get the path to CRUD6 schema files.
     * 
     * Uses UserFrosting's locator syntax (e.g., 'schema://crud6/').
     * 
     * @return string The schema path with locator prefix
     */
    public function getSchemaPath(): string
    {
        return (string) $this->config->get('crud6.schema_path', self::DEFAULTS['schema_path']);
    }

    /**
     * Check if schema caching is enabled.
     * 
     * When enabled, parsed schemas are cached for better performance.
     * Recommended for production environments.
     * 
     * @return bool True if caching is enabled
     */
    public function isCacheEnabled(): bool
    {
        return (bool) $this->config->get('crud6.cache_enabled', self::DEFAULTS['cache_enabled']);
    }

    /**
     * Get the cache Time-To-Live (TTL) in seconds.
     * 
     * Determines how long cached schemas are valid before refresh.
     * 
     * @return int TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return (int) $this->config->get('crud6.cache_ttl', self::DEFAULTS['cache_ttl']);
    }

    /**
     * Get a specific configuration value with type coercion.
     * 
     * Use this for accessing custom configuration values not covered
     * by the dedicated getter methods.
     * 
     * @param string $key     The configuration key (without 'crud6.' prefix)
     * @param mixed  $default The default value if key doesn't exist
     * 
     * @return mixed The configuration value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get("crud6.{$key}", $default);
    }

    /**
     * Get all CRUD6 configuration as an array.
     * 
     * Useful for debugging or passing configuration to other services.
     * 
     * @return array<string, mixed> All CRUD6 configuration values
     */
    public function all(): array
    {
        $config = $this->config->get('crud6', []);
        
        // Merge with defaults for any missing keys
        return array_merge(self::DEFAULTS, is_array($config) ? $config : []);
    }
}
