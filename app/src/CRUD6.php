<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6;

use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD6\Routes\CRUD6Routes;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\CRUD6ModelService;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaServiceProvider;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\CatalogSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\CategorySeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\CommerceSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\ProductCatalogSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\ProductSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\PurchaseOrderLinesSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\PurchaseOrderSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\SalesOrderLinesSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\SalesOrderSeeder;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\CatalogTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\CategoryTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\CommerceRolesTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\ProductTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\ProductCatalogTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\SalesOrderTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\SalesOrderLinesTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\PurchaseOrderTable;
use UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600\PurchaseOrderLinesTable;

/**
 * CRUD6 Sprinkle - Generic API CRUD Layer for UserFrosting 6
 *
 * Provides dynamic CRUD operations on any database table using JSON schema definitions.
 * Features:
 * - JSON-based schema definitions for flexible table configuration
 * - RESTful API endpoints for CRUD operations (/api/crud6/{model})
 * - Dynamic routing for any model with automatic validation
 * - Sortable and filterable data queries
 * - Frontend-agnostic design for Vue.js integration
 */
class CRUD6 implements SprinkleRecipe, MigrationRecipe, SeedRecipe
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'CRUD6 Sprinkle';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * {@inheritdoc}
     */
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        return [
            CRUD6Routes::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getMigrations(): array
    {
        return [
            // Base tables
            CategoryTable::class,
            CatalogTable::class,
            ProductTable::class,
            
            // Relationship tables
            ProductCatalogTable::class,
            
            // Order tables
            SalesOrderTable::class,
            SalesOrderLinesTable::class,
            PurchaseOrderTable::class,
            PurchaseOrderLinesTable::class,
            
            // Commerce roles
            CommerceRolesTable::class,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function getSeeds(): array
    {
        return [
            DefaultRoles::class,
            DefaultPermissions::class,
            CommerceSeeder::class,
            CategorySeeder::class,
            CatalogSeeder::class,
            ProductSeeder::class,
            ProductCatalogSeeder::class,
            SalesOrderSeeder::class,
            SalesOrderLinesSeeder::class,
            PurchaseOrderSeeder::class,
            PurchaseOrderLinesSeeder::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return [
            CRUD6ModelService::class,
            SchemaServiceProvider::class,
        ];
    }
}
