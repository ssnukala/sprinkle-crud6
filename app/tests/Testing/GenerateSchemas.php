<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Testing;

use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\DatabaseScanner;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\SchemaGenerator;

/**
 * Schema Generator for CRUD6 Tests using Bakery Command.
 * 
 * Uses the `crud6:generate` bakery command to generate schema JSON files
 * from actual database tables. This ensures integration tests use schemas
 * generated the same way as production deployments.
 * 
 * Instead of manually building schemas with SchemaBuilder, this class:
 * 1. Ensures database tables exist (via UserFrosting migrations)
 * 2. Runs `crud6:generate` bakery command
 * 3. Generates schemas from actual database structure
 * 
 * This provides better test coverage because it validates:
 * - Database scanning works correctly
 * - Schema generation produces valid schemas
 * - Generated schemas work with CRUD6 API
 * 
 * Usage:
 * ```php
 * GenerateSchemas::generateFromDatabase($container);
 * ```
 * 
 * This creates:
 * - app/schema/crud6/*.json - Schema files from database tables
 */
class GenerateSchemas
{
    /**
     * Base directory for schema files.
     */
    private const SCHEMA_DIR = __DIR__ . '/../../../schema/crud6';

    /**
     * Generate schemas from database using bakery command.
     * 
     * Runs the crud6:generate bakery command to create schema files
     * from existing database tables. This uses the same process as
     * production deployments.
     * 
     * @param ContainerInterface $container DI container
     * @param array $tables Optional list of tables to generate (default: all UserFrosting tables)
     * 
     * @return void
     */
    public static function generateFromDatabase(ContainerInterface $container, array $tables = []): void
    {
        echo "\n========================================\n";
        echo "Generating CRUD6 Schemas from Database\n";
        echo "========================================\n\n";

        // Create directory if it doesn't exist
        self::createDirectories();

        // Default to UserFrosting account tables if no specific tables provided
        if (empty($tables)) {
            $tables = ['users', 'groups', 'roles', 'permissions', 'activities'];
        }

        echo "📊 Scanning tables: " . implode(', ', $tables) . "\n\n";

        try {
            // Get services from container
            $scanner = $container->get(DatabaseScanner::class);
            $generator = $container->get(SchemaGenerator::class);

            // Scan database for all tables at once - this is the designed pattern
            echo "🔍 Scanning database structure...\n";
            $tablesMetadata = $scanner->scanDatabase($tables);
            
            if (empty($tablesMetadata)) {
                throw new \RuntimeException("No tables found to generate schemas for");
            }
            
            echo "  ✓ Scanned " . count($tablesMetadata) . " tables: " . implode(', ', array_keys($tablesMetadata)) . "\n";
            
            // Detect relationships across all tables
            echo "\n🔗 Detecting relationships...\n";
            $allRelationships = $scanner->detectRelationships($tablesMetadata, false, 0);
            echo "  ✓ Relationships detected\n";
            
            // Generate schemas
            echo "\n📝 Generating schema files...\n";
            $generatedFiles = $generator->generateSchemas($tablesMetadata, $allRelationships);
            
            if (!empty($generatedFiles)) {
                echo "\n✅ Schema generation completed successfully!\n";
                echo "   Generated " . count($generatedFiles) . " schema files:\n";
                foreach ($generatedFiles as $file) {
                    $basename = basename($file);
                    echo "   - {$basename}\n";
                }
            } else {
                echo "\n⚠ No schema files were generated\n";
            }
            
        } catch (\Exception $e) {
            echo "\n❌ Error generating schemas: " . $e->getMessage() . "\n";
            echo "   Stack trace:\n";
            echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
            throw $e;
        }
    }

    /**
     * Create required directories.
     * 
     * @return void
     */
    private static function createDirectories(): void
    {
        if (!is_dir(self::SCHEMA_DIR)) {
            mkdir(self::SCHEMA_DIR, 0755, true);
            echo "📁 Created directory: " . self::SCHEMA_DIR . "\n\n";
        }
    }
    
    /**
     * Backward compatibility method - now uses database generation.
     * 
     * @deprecated Use generateFromDatabase() instead
     * 
     * @return void
     */
    public static function generate(): void
    {
        echo "\n⚠️  WARNING: GenerateSchemas::generate() is deprecated.\n";
        echo "   This method now requires a container instance.\n";
        echo "   Please use GenerateSchemas::generateFromDatabase(\$container) instead.\n\n";
        
        throw new \RuntimeException(
            "GenerateSchemas::generate() requires container. " .
            "Use generateFromDatabase(\$container) from test setup."
        );
    }
}
