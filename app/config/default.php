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
         * 
         * @var bool
         */
        'debug_mode' => true,
    ]
];
