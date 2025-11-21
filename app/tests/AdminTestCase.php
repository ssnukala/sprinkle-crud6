<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests;

use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Testing\TestCase;

/**
 * CRUD6 Test Case Base Class.
 * 
 * Base test case with CRUD6 as main sprinkle.
 * All CRUD6 tests should extend this class to ensure proper sprinkle loading.
 * 
 * Follows UserFrosting 6 testing patterns from sprinkle-admin.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Tests\AdminTestCase
 */
class AdminTestCase extends TestCase
{
    /**
     * @var string Main sprinkle class for CRUD6 tests
     */
    protected string $mainSprinkle = CRUD6::class;

    /**
     * Set up test environment.
     * 
     * Creates required runtime directories (sessions, cache, logs) for testing.
     * These directories are managed by UserFrosting at runtime and should not
     * be committed to the repository.
     * 
     * Required by:
     * - SessionService (app/sessions)
     * - CacheService (app/cache)
     * - LoggerInterface (app/logs)
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create runtime directories required by UserFrosting for testing
        // These match the structure expected by SessionService and other core services
        $runtimeDirs = [
            'app/sessions',
            'app/cache',
            'app/logs',
        ];
        
        // Get the base directory of the sprinkle (two levels up from tests directory)
        $baseDir = dirname(__DIR__, 2);
        
        foreach ($runtimeDirs as $dir) {
            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($fullPath)) {
                // Create the directory with recursive flag to handle parent directories
                if (!mkdir($fullPath, 0755, true) && !is_dir($fullPath)) {
                    $error = error_get_last();
                    $errorMsg = $error ? $error['message'] : 'Unknown error';
                    throw new \RuntimeException(sprintf('Directory "%s" was not created: %s', $fullPath, $errorMsg));
                }
            }
        }
    }
}
