<?php

declare(strict_types=1);

/*
 * LearnIntegrate Sprinkle
 *
 * @link      https://github.com/ssnukala/sprinkle-learntegrate
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-learntegrate/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Bakery;

use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;
use UserFrosting\Sprinkle\CRUD6\Bakery\ScanDatabaseCommand;
use UserFrosting\Sprinkle\LearnIntegrate\Tests\AdminTestCase;
use UserFrosting\Testing\BakeryTester;

/**
 * Test ScanDatabaseCommand bakery command.
 *
 * Tests the learntegrate:scan command that scans database tables
 * and displays their structure.
 *
 * @author Srinivas Nukala
 */
class ScanDatabaseCommandTest extends AdminTestCase
{
    use RefreshDatabase;

    /**
     * Setup test database
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();

        // Create test tables
        $this->createTestTables();
    }

    /**
     * Test scan command without arguments
     */
    public function testScanCommandWithoutArguments(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        $result = BakeryTester::runCommand($command);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should display database scanner title
        $this->assertStringContainsString('Database Scanner', $output);
        
        // Should list some standard UserFrosting tables
        $this->assertStringContainsString('users', $output);
        $this->assertStringContainsString('groups', $output);
    }

    /**
     * Test scan command with specific tables filter
     */
    public function testScanCommandWithTableFilter(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        $result = BakeryTester::runCommand($command, ['--tables' => 'users,groups']);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should display filtered tables
        $this->assertStringContainsString('users', $output);
        $this->assertStringContainsString('groups', $output);
    }

    /**
     * Test scan command with JSON output format
     */
    public function testScanCommandWithJsonOutput(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        $result = BakeryTester::runCommand($command, ['--output' => 'json']);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Output should contain JSON - extract it
        // The command outputs some formatted text first, then JSON
        $this->assertStringContainsString('Database Scanner', $output);
        $this->assertStringContainsString('"tables"', $output);
        $this->assertStringContainsString('"relationships"', $output);
        
        // Find the JSON part (starts with { and ends with })
        if (preg_match('/\{.*\}/s', $output, $matches)) {
            $jsonOutput = $matches[0];
            $data = json_decode($jsonOutput, true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('tables', $data);
            $this->assertArrayHasKey('relationships', $data);
        } else {
            $this->fail('Could not find JSON in output');
        }
    }

    /**
     * Test scan command with database option displays connection info
     */
    public function testScanCommandWithDatabaseOption(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        
        // Test with default database (empty database parameter should use default)
        $result = BakeryTester::runCommand($command, ['--database' => '']);
        
        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should indicate using default connection
        $this->assertStringContainsString('Using default database connection', $output);
    }

    /**
     * Test scan command without database option shows default connection
     */
    public function testScanCommandWithoutDatabaseOptionShowsDefault(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        $result = BakeryTester::runCommand($command);
        
        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should indicate using default connection
        $this->assertStringContainsString('Using default database connection', $output);
    }

    /**
     * Test scan command can detect table columns
     */
    public function testScanCommandDetectsColumns(): void
    {
        $command = $this->ci->get(ScanDatabaseCommand::class);
        $result = BakeryTester::runCommand($command, ['--tables' => 'test_products']);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should detect columns in test table
        $this->assertStringContainsString('test_products', $output);
    }

    /**
     * Create test tables for scanning
     */
    protected function createTestTables(): void
    {
        $db = $this->ci->get(\Illuminate\Database\Capsule\Manager::class);
        $schema = $db->schema();

        // Create test_products table
        if (!$schema->hasTable('test_products')) {
            $schema->create('test_products', function ($table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->integer('stock')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // Create test_categories table
        if (!$schema->hasTable('test_categories')) {
            $schema->create('test_categories', function ($table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->timestamps();
            });
        }
    }

    /**
     * Cleanup test tables
     */
    public function tearDown(): void
    {
        $db = $this->ci->get(\Illuminate\Database\Capsule\Manager::class);
        $schema = $db->schema();

        // Drop test tables
        $schema->dropIfExists('test_products');
        $schema->dropIfExists('test_categories');

        parent::tearDown();
    }
}
