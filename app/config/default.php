<?php

declare(strict_types=1);

/*
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/LICENSE.md (MIT License)
 */

return [
    'crud6' => [
        /**
         * Debug mode for CRUD6 operations.
         * 
         * When enabled, CRUD6 will log detailed debug information about:
         * - Schema loading and caching
         * - Controller invocations and parameters
         * - Request processing and data transformations
         * - Validation and error handling
         * 
         * Set to true to enable debug logging, false to disable.
         * Can be overridden via CRUD6_DEBUG_MODE environment variable.
         * 
         * @var bool
         */
        'debug_mode' => filter_var(getenv('CRUD6_DEBUG_MODE') ?: true, FILTER_VALIDATE_BOOLEAN),

        /**
         * Schema cache Time-To-Live (TTL) in seconds.
         * 
         * Controls how long schema data is cached in persistent cache (PSR-16).
         * Only applies when a cache implementation is injected into SchemaService.
         * 
         * In-memory cache (active during request) is always enabled.
         * 
         * Default: 3600 seconds (1 hour)
         * 
         * @var int
         */
        'cache_ttl' => 3600,
    ]
];
