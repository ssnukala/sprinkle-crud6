<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use UserFrosting\Config\Config;
use UserFrosting\ServicesProvider\ServicesProviderInterface;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\DatabaseScanner;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\SchemaGenerator;

/**
 * Bakery Services Provider
 *
 * Registers bakery helper services for database scanning and schema generation.
 * Follows the service provider pattern from sprinkle-core.
 *
 * @author Srinivas Nukala
 */
class BakeryServicesProvider implements ServicesProviderInterface
{
    /**
     * Register services with the DI container.
     *
     * Uses type-hinted parameters for dependency injection following UF6 patterns.
     * PHP-DI automatically resolves and injects dependencies.
     *
     * @return array<string, mixed> Service definitions for the container
     */
    public function register(): array
    {
        return [
            // DatabaseScanner uses autowiring - PHP-DI will inject dependencies automatically
            DatabaseScanner::class => \DI\autowire(DatabaseScanner::class),
            
            // SchemaGenerator with config-based initialization
            SchemaGenerator::class => function (Config $config) {
                // Get schema directory from config, with fallback
                $schemaDir = $config->get('crud6.schema_directory', 'app/schema/crud6');

                // Get CRUD options from config
                $crudOptions = $config->get('crud6.crud_options', [
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => true,
                    'list' => true,
                ]);

                return new SchemaGenerator($schemaDir, $crudOptions);
            },
        ];
    }
}
