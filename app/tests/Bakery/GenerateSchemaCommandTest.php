<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Bakery;

use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;
use UserFrosting\Sprinkle\CRUD6\Bakery\GenerateSchemaCommand;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Testing\BakeryTester;

/**
 * Test GenerateSchemaCommand bakery command.
 *
 * Tests the crud6:generate command that scans database tables
 * and generates CRUD6 schema files in the <database>/<model>.json format.
 *
 * This is the key functionality of the sprinkle - scanning databases
 * and generating schema files for CRUD operations.
 *
 * @author Srinivas Nukala
 */
class GenerateSchemaCommandTest extends CRUD6TestCase
{
    use RefreshDatabase;

    /**
     * @var string Test schema output directory
     */
    protected string $testSchemaDir;

    /**
     * Setup test database and schema directory
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();

        // Create a temporary test schema directory
        $this->testSchemaDir = sys_get_temp_dir() . '/learntegrate-schema-test-' . uniqid();
        mkdir($this->testSchemaDir, 0755, true);

        // Create test tables
        $this->createTestTables();
    }

    /**
     * Test generate command creates schema files
     */
    public function testGenerateCommandCreatesSchemaFiles(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products,test_categories',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should display success message
        $this->assertStringContainsString('Schema Generator', $output);
        $this->assertStringContainsString('Generated', $output);
        
        // Should show progress for each table
        $this->assertStringContainsString('test_products', $output);
        $this->assertStringContainsString('test_categories', $output);
        
        // Verify schema files were created
        $this->assertFileExists($this->testSchemaDir . '/test_products.json');
        $this->assertFileExists($this->testSchemaDir . '/test_categories.json');
    }

    /**
     * Test generated schema files have correct structure
     */
    public function testGeneratedSchemaHasCorrectStructure(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        
        // Read and parse schema file
        $schemaPath = $this->testSchemaDir . '/test_products.json';
        $this->assertFileExists($schemaPath);
        
        $schemaContent = file_get_contents($schemaPath);
        $this->assertJson($schemaContent);
        
        $schema = json_decode($schemaContent, true);
        
        // Verify required schema structure (aligned with sprinkle-crud6 format)
        $this->assertArrayHasKey('model', $schema);
        $this->assertArrayHasKey('title', $schema);
        $this->assertArrayHasKey('singular_title', $schema);
        $this->assertArrayHasKey('description', $schema);
        $this->assertArrayHasKey('table', $schema);
        $this->assertArrayHasKey('permissions', $schema);
        $this->assertArrayHasKey('default_sort', $schema);
        $this->assertArrayHasKey('fields', $schema);
        
        // Verify crud_options is NOT present (not part of new format)
        $this->assertArrayNotHasKey('crud_options', $schema);
        
        // Verify model and table name
        $this->assertEquals('test_products', $schema['model']);
        $this->assertEquals('test_products', $schema['table']);
        
        // Verify fields exist
        $this->assertArrayHasKey('id', $schema['fields']);
        $this->assertArrayHasKey('name', $schema['fields']);
        $this->assertArrayHasKey('price', $schema['fields']);
        $this->assertArrayHasKey('stock', $schema['fields']);
        
        // Verify field structure (new format)
        $nameField = $schema['fields']['name'];
        $this->assertArrayHasKey('type', $nameField);
        $this->assertArrayHasKey('label', $nameField);
        $this->assertArrayHasKey('sortable', $nameField);
        $this->assertArrayHasKey('filterable', $nameField);
        $this->assertArrayHasKey('searchable', $nameField);
        $this->assertArrayHasKey('listable', $nameField);
        
        // Verify old structure is NOT present
        $this->assertArrayNotHasKey('name', $nameField);
        $this->assertArrayNotHasKey('database_type', $nameField);
        $this->assertArrayNotHasKey('display', $nameField);
        $this->assertArrayNotHasKey('nullable', $nameField);
        
        // Verify permissions structure
        $this->assertArrayHasKey('read', $schema['permissions']);
        $this->assertArrayHasKey('create', $schema['permissions']);
        $this->assertArrayHasKey('update', $schema['permissions']);
        $this->assertArrayHasKey('delete', $schema['permissions']);
    }

    /**
     * Test generate command with all tables (no filter)
     */
    public function testGenerateCommandWithAllTables(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should generate schemas for multiple tables
        $this->assertStringContainsString('Generated', $output);
        
        // Should have created at least our test tables plus UF tables
        $files = glob($this->testSchemaDir . '/*.json');
        $this->assertGreaterThan(2, count($files), 'Should generate schemas for multiple tables');
    }

    /**
     * Test generate command with CRUD options
     * 
     * Note: CRUD options are no longer part of the generated schema format.
     * This test verifies that the schema is generated without errors
     * even when CRUD options are specified in the command.
     */
    public function testGenerateCommandWithCrudOptions(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
            '--no-create' => true,
            '--no-delete' => true,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        
        // Read and parse schema file
        $schemaPath = $this->testSchemaDir . '/test_products.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify crud_options are NOT in the generated schema (new format)
        $this->assertArrayNotHasKey('crud_options', $schema);
        
        // Verify the schema is still valid
        $this->assertArrayHasKey('model', $schema);
        $this->assertArrayHasKey('fields', $schema);
        $this->assertArrayHasKey('permissions', $schema);
    }

    /**
     * Test generate command detects field types correctly
     */
    public function testGenerateCommandDetectsFieldTypes(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        
        // Read and parse schema file
        $schemaPath = $this->testSchemaDir . '/test_products.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify field type mapping (new schema types)
        $this->assertEquals('integer', $schema['fields']['id']['type']);
        $this->assertEquals('string', $schema['fields']['name']['type']);
        $this->assertEquals('decimal', $schema['fields']['price']['type']);
        $this->assertEquals('integer', $schema['fields']['stock']['type']);
        $this->assertEquals('boolean', $schema['fields']['active']['type']);
    }

    /**
     * Test generate command creates proper validation rules
     */
    public function testGenerateCommandCreatesValidationRules(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        
        // Read and parse schema file
        $schemaPath = $this->testSchemaDir . '/test_products.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify validation rules exist as object (not array)
        $this->assertArrayHasKey('name', $schema['fields']);
        $nameField = $schema['fields']['name'];
        
        // Validation should be an object, not an array
        $this->assertArrayHasKey('validation', $nameField);
        $this->assertIsArray($nameField['validation']);
        
        // Check for required validation
        $this->assertArrayHasKey('required', $nameField['validation']);
        $this->assertTrue($nameField['validation']['required']);
        
        // Check for length validation with min/max structure (if length is available from database)
        // Note: SQLite doesn't preserve length information for string columns, so this may not always be present
        if (isset($nameField['validation']['length'])) {
            $this->assertArrayHasKey('min', $nameField['validation']['length']);
            $this->assertArrayHasKey('max', $nameField['validation']['length']);
        }
        
        // Verify timestamp fields are readonly
        if (isset($schema['fields']['created_at'])) {
            $createdAtField = $schema['fields']['created_at'];
            $this->assertTrue($createdAtField['readonly'] ?? false, 'created_at should be readonly');
        }
        
        if (isset($schema['fields']['updated_at'])) {
            $updatedAtField = $schema['fields']['updated_at'];
            $this->assertTrue($updatedAtField['readonly'] ?? false, 'updated_at should be readonly');
        }
    }

    /**
     * Test generate command without output-dir uses default location
     */
    public function testGenerateCommandWithoutOutputDirUsesDefault(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
        ]);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should show schema directory in output
        $this->assertStringContainsString('Generated', $output);
        $this->assertStringContainsString('schema', $output);
    }

    /**
     * Test generate command with database option displays connection info
     */
    public function testGenerateCommandWithDatabaseOption(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        
        // Test with default database (empty database parameter should use default)
        $result = BakeryTester::runCommand($command, [
            '--database' => '',
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);
        
        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should indicate using default connection
        $this->assertStringContainsString('Using default database connection', $output);
        
        // Should still generate schema
        $this->assertFileExists($this->testSchemaDir . '/test_products.json');
    }

    /**
     * Test generate command without database option shows default connection
     */
    public function testGenerateCommandWithoutDatabaseOptionShowsDefault(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);
        
        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should indicate using default connection
        $this->assertStringContainsString('Using default database connection', $output);
    }

    /**
     * Test generate command with database option creates subfolder
     * 
     * Note: This test doesn't verify the database connection is valid,
     * it only tests that when a database name is provided, a subfolder is created.
     */
    public function testGenerateCommandWithDatabaseOptionCreatesSubfolder(): void
    {
        // Skip this test for now - it requires a valid database connection to be configured
        // The functionality is tested indirectly through manual testing
        $this->markTestSkipped('Test requires configuring multiple database connections');
        
        /* Test code commented out - requires setting up multiple DB connections
        $command = $this->ci->get(GenerateSchemaCommand::class);
        
        // Test with a specific database connection name
        // Use 'testdb' as a database connection name
        $result = BakeryTester::runCommand($command, [
            '--database' => 'testdb',
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);
        
        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should indicate using the specified database connection
        $this->assertStringContainsString('Using database connection: testdb', $output);
        
        // Should create schema in database subfolder
        $expectedPath = $this->testSchemaDir . '/testdb/test_products.json';
        $this->assertFileExists($expectedPath, 'Schema file should be created in database subfolder');
        
        // Verify the schema is valid
        $schemaContent = file_get_contents($expectedPath);
        $this->assertJson($schemaContent);
        $schema = json_decode($schemaContent, true);
        $this->assertEquals('test_products', $schema['model']);
        */
    }

    /**
     * Test generate command without database option does not create subfolder
     */
    public function testGenerateCommandWithoutDatabaseOptionNoSubfolder(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        
        // Test without database option
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products',
            '--output-dir' => $this->testSchemaDir,
        ]);
        
        $this->assertSame(0, $result->getStatusCode());
        
        // Should create schema directly in output directory (no subfolder)
        $expectedPath = $this->testSchemaDir . '/test_products.json';
        $this->assertFileExists($expectedPath, 'Schema file should be created directly in output directory');
        
        // Verify no subdirectories were created (when database option is not provided)
        $dirs = glob($this->testSchemaDir . '/*', GLOB_ONLYDIR);
        $this->assertEmpty($dirs, 'No subdirectories should be created when database option is not provided');
    }

    /**
     * Test generate command detects relationships (foreign keys)
     */
    public function testGenerateCommandDetectsRelationships(): void
    {
        // Create tables with foreign key relationship
        $db = $this->ci->get(\Illuminate\Database\Capsule\Manager::class);
        $schema = $db->schema();

        // Create test_orders table with foreign key to test_products
        if (!$schema->hasTable('test_orders')) {
            $schema->create('test_orders', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('product_id');
                $table->integer('quantity');
                $table->timestamps();
                
                // Foreign key constraint
                $table->foreign('product_id')
                    ->references('id')
                    ->on('test_products')
                    ->onDelete('cascade');
            });
        }

        $command = $this->ci->get(GenerateSchemaCommand::class);
        // Scan both tables so relationships can be detected
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products,test_orders',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        
        // Read and parse schema file
        $schemaPath = $this->testSchemaDir . '/test_products.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify detail section is present (for tables referenced by foreign keys)
        // Note: The new format uses 'detail' instead of 'relationships'
        $this->assertArrayHasKey('detail', $schema);
        $this->assertArrayHasKey('model', $schema['detail']);
        $this->assertArrayHasKey('foreign_key', $schema['detail']);
        $this->assertEquals('test_orders', $schema['detail']['model']);
        $this->assertEquals('product_id', $schema['detail']['foreign_key']);
        
        // Cleanup the test_orders table
        $db->schema()->dropIfExists('test_orders');
    }

    /**
     * Test generate command shows progress for each table
     */
    public function testGenerateCommandShowsProgressPerTable(): void
    {
        $command = $this->ci->get(GenerateSchemaCommand::class);
        $result = BakeryTester::runCommand($command, [
            '--tables' => 'test_products,test_categories',
            '--output-dir' => $this->testSchemaDir,
        ]);

        $this->assertSame(0, $result->getStatusCode());
        $output = $result->getDisplay();
        
        // Should show progress message for each table
        $this->assertStringContainsString('Generated schema for table: test_products', $output);
        $this->assertStringContainsString('Generated schema for table: test_categories', $output);
        
        // Should show success indicators
        $this->assertStringContainsString('✓', $output);
        
        // Should show formatted table list with arrows
        $this->assertStringContainsString('→', $output);
    }

    /**
     * Create test tables for schema generation
     */
    protected function createTestTables(): void
    {
        $db = $this->ci->get(\Illuminate\Database\Capsule\Manager::class);
        $schema = $db->schema();

        // Create test_products table with various field types
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
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Cleanup test tables and schema directory
     */
    public function tearDown(): void
    {
        $db = $this->ci->get(\Illuminate\Database\Capsule\Manager::class);
        $schema = $db->schema();

        // Drop test tables
        $schema->dropIfExists('test_orders');
        $schema->dropIfExists('test_products');
        $schema->dropIfExists('test_categories');

        // Clean up test schema directory
        if (is_dir($this->testSchemaDir)) {
            $files = glob($this->testSchemaDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testSchemaDir);
        }

        parent::tearDown();
    }
}
