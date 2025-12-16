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
use UserFrosting\Sprinkle\CRUD6\Testing\WithDatabaseSeeds;
use UserFrosting\Testing\TestCase;

/**
 * CRUD6 Test Case Base Class.
 * 
 * Base test case with CRUD6 as main sprinkle.
 * All CRUD6 tests should extend this class to ensure proper sprinkle loading.
 * 
 * This base class includes WithDatabaseSeeds trait to ensure tests that use
 * RefreshDatabase also get necessary seed data automatically.
 * 
 * Follows UserFrosting 6 testing patterns from sprinkle-admin and sprinkle-account.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Tests\AdminTestCase
 * @see \UserFrosting\Sprinkle\Account\Tests\AccountTestCase
 */
class CRUD6TestCase extends TestCase
{
    use WithDatabaseSeeds;

    /**
     * @var string Main sprinkle class for CRUD6 tests
     */
    protected string $mainSprinkle = CRUD6::class;

    /**
     * Setup before each test.
     * Logs database configuration for debugging.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Log database configuration for debugging
        $this->logDatabaseConfiguration();
    }

    /**
     * Log database configuration to help debug connection issues.
     * 
     * This outputs environment variables and connection settings to help
     * diagnose issues like empty database names or incorrect host settings.
     */
    protected function logDatabaseConfiguration(): void
    {
        $dbConfig = [
            'DB_DRIVER' => getenv('DB_DRIVER') ?: $_ENV['DB_DRIVER'] ?? 'NOT SET',
            'DB_HOST' => getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'NOT SET',
            'DB_PORT' => getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 'NOT SET',
            'DB_NAME' => getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'NOT SET',
            'DB_USER' => getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'NOT SET',
            'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***REDACTED***' : 'NOT SET',
            'UF_MODE' => getenv('UF_MODE') ?: $_ENV['UF_MODE'] ?? 'NOT SET',
        ];

        // Log to stdout (captured by PHPUnit)
        fwrite(STDERR, "\n========================================\n");
        fwrite(STDERR, "DATABASE CONFIGURATION DEBUG\n");
        fwrite(STDERR, "========================================\n");
        fwrite(STDERR, "Test Class: " . get_class($this) . "\n");
        fwrite(STDERR, "Test Method: " . $this->getName() . "\n");
        foreach ($dbConfig as $key => $value) {
            fwrite(STDERR, sprintf("  %-15s = %s\n", $key, $value));
        }
        fwrite(STDERR, "========================================\n\n");
    }

    /**
     * Verify database connection is working correctly.
     * 
     * This method can be called in tests to verify the database
     * connection is established with the correct database name.
     * 
     * @return array Database connection information
     */
    protected function verifyDatabaseConnection(): array
    {
        try {
            // Get database connection from container if available
            if (method_exists($this, 'ci') && $this->ci->has('db')) {
                $db = $this->ci->get('db');
                
                $connectionInfo = [
                    'connected' => true,
                    'database' => $db->getDatabaseName(),
                    'driver' => $db->getDriverName(),
                    'host' => $db->getConfig('host'),
                ];

                fwrite(STDERR, "\n[DB CONNECTION VERIFIED]\n");
                fwrite(STDERR, "  Database: {$connectionInfo['database']}\n");
                fwrite(STDERR, "  Driver: {$connectionInfo['driver']}\n");
                fwrite(STDERR, "  Host: {$connectionInfo['host']}\n\n");

                return $connectionInfo;
            }
        } catch (\Exception $e) {
            fwrite(STDERR, "\n[DB CONNECTION ERROR]\n");
            fwrite(STDERR, "  Error: " . $e->getMessage() . "\n\n");
            
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }

        return ['connected' => false, 'reason' => 'Database service not available'];
    }
}
