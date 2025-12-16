<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Database;

use PHPUnit\Framework\TestCase;

/**
 * Database Configuration Test.
 *
 * Verifies that database environment variables are properly configured
 * and accessible during test execution. This helps diagnose issues like
 * the "table_schema = ''" problem where DB_NAME was not being set.
 */
class DatabaseConfigurationTest extends TestCase
{
    /**
     * Test that DB_NAME environment variable is set and not empty.
     * 
     * This is critical - if DB_NAME is not set, SQL queries will use
     * an empty string for table_schema, causing failures.
     */
    public function testDatabaseNameIsSet(): void
    {
        $dbName = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
        
        $this->assertNotNull($dbName, 'DB_NAME environment variable must be set');
        $this->assertNotEmpty($dbName, 'DB_NAME must not be empty');
        $this->assertEquals('userfrosting_test', $dbName, 'DB_NAME should be "userfrosting_test" for tests');
        
        fwrite(STDERR, "\n✅ DB_NAME is correctly set to: {$dbName}\n");
    }

    /**
     * Test that DB_HOST environment variable is set.
     */
    public function testDatabaseHostIsSet(): void
    {
        $dbHost = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
        
        $this->assertNotNull($dbHost, 'DB_HOST environment variable must be set');
        $this->assertNotEmpty($dbHost, 'DB_HOST must not be empty');
        
        fwrite(STDERR, "\n✅ DB_HOST is correctly set to: {$dbHost}\n");
    }

    /**
     * Test that DB_USER environment variable is set.
     */
    public function testDatabaseUserIsSet(): void
    {
        $dbUser = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
        
        $this->assertNotNull($dbUser, 'DB_USER environment variable must be set');
        $this->assertNotEmpty($dbUser, 'DB_USER must not be empty');
        
        fwrite(STDERR, "\n✅ DB_USER is correctly set to: {$dbUser}\n");
    }

    /**
     * Test that DB_PASSWORD environment variable is set.
     */
    public function testDatabasePasswordIsSet(): void
    {
        $dbPassword = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? null;
        
        $this->assertNotNull($dbPassword, 'DB_PASSWORD environment variable must be set (can be empty string for no password)');
        
        fwrite(STDERR, "\n✅ DB_PASSWORD is set (value redacted for security)\n");
    }

    /**
     * Test that DB_DRIVER environment variable is set.
     */
    public function testDatabaseDriverIsSet(): void
    {
        $dbDriver = getenv('DB_DRIVER') ?: $_ENV['DB_DRIVER'] ?? null;
        
        $this->assertNotNull($dbDriver, 'DB_DRIVER environment variable must be set');
        $this->assertEquals('mysql', $dbDriver, 'DB_DRIVER should be "mysql" for tests');
        
        fwrite(STDERR, "\n✅ DB_DRIVER is correctly set to: {$dbDriver}\n");
    }

    /**
     * Test that UF_MODE is set to testing.
     */
    public function testTestingModeIsSet(): void
    {
        $ufMode = getenv('UF_MODE') ?: $_ENV['UF_MODE'] ?? null;
        
        $this->assertNotNull($ufMode, 'UF_MODE environment variable must be set');
        $this->assertEquals('testing', $ufMode, 'UF_MODE should be "testing" for tests');
        
        fwrite(STDERR, "\n✅ UF_MODE is correctly set to: {$ufMode}\n");
    }

    /**
     * Test comprehensive database configuration and output full diagnostic info.
     */
    public function testCompleteDatabaseConfiguration(): void
    {
        $config = [
            'DB_DRIVER' => getenv('DB_DRIVER') ?: $_ENV['DB_DRIVER'] ?? 'NOT SET',
            'DB_HOST' => getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'NOT SET',
            'DB_PORT' => getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 'NOT SET',
            'DB_NAME' => getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'NOT SET',
            'DB_USER' => getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'NOT SET',
            'DB_PASSWORD' => (getenv('DB_PASSWORD') !== false || isset($_ENV['DB_PASSWORD'])) ? '***SET***' : 'NOT SET',
            'UF_MODE' => getenv('UF_MODE') ?: $_ENV['UF_MODE'] ?? 'NOT SET',
            'SMTP_HOST' => getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? 'NOT SET',
        ];

        fwrite(STDERR, "\n========================================\n");
        fwrite(STDERR, "COMPLETE DATABASE CONFIGURATION\n");
        fwrite(STDERR, "========================================\n");
        foreach ($config as $key => $value) {
            fwrite(STDERR, sprintf("  %-15s = %s\n", $key, $value));
        }
        fwrite(STDERR, "========================================\n\n");

        // Verify critical values are not "NOT SET"
        $this->assertNotEquals('NOT SET', $config['DB_NAME'], 'DB_NAME must be configured');
        $this->assertNotEquals('NOT SET', $config['DB_HOST'], 'DB_HOST must be configured');
        $this->assertNotEquals('NOT SET', $config['DB_USER'], 'DB_USER must be configured');
        $this->assertNotEquals('NOT SET', $config['DB_DRIVER'], 'DB_DRIVER must be configured');
    }

    /**
     * Test that phpunit.xml configuration is being read correctly.
     * 
     * This verifies the <php><env> section in phpunit.xml is working.
     */
    public function testPhpunitXmlConfigurationIsLoaded(): void
    {
        // Check if we're running in PHPUnit context
        $this->assertTrue(
            defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__') || class_exists('\PHPUnit\Framework\TestCase'),
            'Test should be running under PHPUnit'
        );

        // Verify environment variables that should come from phpunit.xml
        $dbName = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
        
        if ($dbName === null || $dbName === '') {
            fwrite(STDERR, "\n❌ CRITICAL ERROR: DB_NAME is not set!\n");
            fwrite(STDERR, "This indicates phpunit.xml <php><env> section is not being loaded.\n");
            fwrite(STDERR, "Check that phpunit.xml is in the correct location and properly formatted.\n\n");
            
            $this->fail('DB_NAME not set - phpunit.xml configuration not loaded');
        }

        fwrite(STDERR, "\n✅ phpunit.xml configuration loaded successfully\n");
        fwrite(STDERR, "   DB_NAME = {$dbName}\n\n");
        
        $this->assertTrue(true);
    }
}
