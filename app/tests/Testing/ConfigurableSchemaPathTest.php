<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Testing;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

/**
 * Test the configurable test schema directories feature.
 * 
 * Validates that:
 * - Default schema directory is used when not configured
 * - Environment variable configuration works
 * - Multiple directories can be specified
 * - Path normalization works correctly
 * - Relative paths are resolved to absolute paths
 * 
 * Note: This test extends CRUD6TestCase instead of TestCase to access
 * the protected methods being tested.
 */
class ConfigurableSchemaPathTest extends CRUD6TestCase
{
    /**
     * Test default schema directory is used when not configured
     */
    public function testDefaultSchemaDirectory(): void
    {
        // Get schema directories (should default to examples/schema)
        $dirs = $this->getTestSchemaDirs();
        
        $this->assertIsArray($dirs);
        $this->assertCount(1, $dirs);
        $this->assertStringEndsWith('examples/schema', $dirs[0]);
    }

    /**
     * Test environment variable configuration
     */
    public function testEnvironmentVariableConfiguration(): void
    {
        // Set environment variable with multiple paths
        $testPaths = 'examples/schema,app/schema/crud6';
        putenv("TEST_SCHEMA_DIRS={$testPaths}");
        
        // Get schema directories
        $dirs = $this->getTestSchemaDirs();
        
        // Clean up environment
        putenv('TEST_SCHEMA_DIRS');
        
        $this->assertIsArray($dirs);
        // Note: Only directories that exist will be returned
        $this->assertGreaterThanOrEqual(1, count($dirs));
    }

    /**
     * Test path normalization with absolute paths
     */
    public function testPathNormalizationAbsolute(): void
    {
        // Test with absolute path
        $dirs = $this->normalizeTestSchemaDirs([
            __DIR__ . '/../../../examples/schema',
        ]);
        
        $this->assertIsArray($dirs);
        // Only existing directories are returned
        if (is_dir(__DIR__ . '/../../../examples/schema')) {
            $this->assertCount(1, $dirs);
            $this->assertStringEndsWith('examples/schema', $dirs[0]);
        }
    }

    /**
     * Test that non-existent directories are filtered out
     */
    public function testNonExistentDirectoriesFiltered(): void
    {
        // Mix of existing and non-existing paths
        $dirs = $this->normalizeTestSchemaDirs([
            __DIR__ . '/../../../examples/schema',
            '/nonexistent/path/to/schemas',
            '/another/fake/path',
        ]);
        
        $this->assertIsArray($dirs);
        // Only existing directory should be included
        foreach ($dirs as $dir) {
            $this->assertTrue(is_dir($dir), "Directory should exist: {$dir}");
        }
    }

    /**
     * Test custom test case with overridden schema directories
     */
    public function testCustomTestCaseOverride(): void
    {
        // Create anonymous test case class that overrides getTestSchemaDirs
        $testCase = new class('test') extends CRUD6TestCase {
            protected function getTestSchemaDirs(): array
            {
                return [
                    __DIR__ . '/../../../../examples/schema',
                    '/custom/path',
                ];
            }
        };
        
        $dirs = $testCase->getTestSchemaDirs();
        
        $this->assertIsArray($dirs);
        $this->assertCount(2, $dirs);
    }

    /**
     * Test schema discovery from configured directories
     */
    public function testSchemaDiscoveryFromConfiguredDirs(): void
    {
        // Get the examples/schema directory
        $examplesDir = __DIR__ . '/../../../examples/schema';
        
        if (!is_dir($examplesDir)) {
            $this->markTestSkipped('examples/schema directory not found');
            return;
        }
        
        // Check if JSON schema files exist
        $schemaFiles = glob($examplesDir . '/*.json');
        
        $this->assertIsArray($schemaFiles);
        $this->assertGreaterThan(0, count($schemaFiles), 'Should find at least one schema file in examples/schema');
        
        // Verify we can extract model names from files
        $modelNames = array_map(fn($file) => basename($file, '.json'), $schemaFiles);
        $this->assertContains('users', $modelNames, 'Should find users.json schema');
    }

    /**
     * Test relative path resolution
     */
    public function testRelativePathResolution(): void
    {
        // Test with relative paths (relative to project root)
        $dirs = $this->normalizeTestSchemaDirs([
            'examples/schema',
        ]);
        
        $this->assertIsArray($dirs);
        // Should resolve to absolute path if directory exists
        foreach ($dirs as $dir) {
            $this->assertTrue(str_starts_with($dir, '/'), "Path should be absolute: {$dir}");
        }
    }

    /**
     * Test empty configuration
     */
    public function testEmptyConfiguration(): void
    {
        // Set empty environment variable
        putenv('TEST_SCHEMA_DIRS=');
        
        $dirs = $this->getTestSchemaDirs();
        
        // Clean up environment
        putenv('TEST_SCHEMA_DIRS');
        
        // Should fall back to default
        $this->assertIsArray($dirs);
        $this->assertCount(1, $dirs);
        $this->assertStringEndsWith('examples/schema', $dirs[0]);
    }

    /**
     * Test comma-separated paths with spaces
     */
    public function testCommaSeparatedPathsWithSpaces(): void
    {
        // Set environment variable with spaces around commas
        putenv('TEST_SCHEMA_DIRS=examples/schema , app/schema/crud6 , vendor/test/schema');
        
        $dirs = $this->getTestSchemaDirs();
        
        // Clean up environment
        putenv('TEST_SCHEMA_DIRS');
        
        $this->assertIsArray($dirs);
        // Verify paths are trimmed (no leading/trailing spaces)
        foreach ($dirs as $dir) {
            $this->assertStringNotContainsString(' ', basename($dir));
        }
    }
}
