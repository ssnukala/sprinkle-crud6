<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6;

use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\BakeryRecipe;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD6\Routes\CRUD6Routes;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\BakeryServicesProvider;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\CRUD6ModelService;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\FieldTypeServiceProvider;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaServiceProvider;
use UserFrosting\Sprinkle\CRUD6\Bakery\GenerateSchemaCommand;
use UserFrosting\Sprinkle\CRUD6\Bakery\ScanDatabaseCommand;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;

/**
 * CRUD6 Sprinkle - Generic API CRUD Layer for UserFrosting 6
 *
 * Provides dynamic CRUD operations on any database table using JSON schema definitions.
 * This is the main sprinkle class that registers all CRUD6 components with UserFrosting 6.
 * 
 * ## Key Features
 * 
 * - **JSON-Based Schema Definitions**: Define models declaratively with simple JSON files
 * - **RESTful API Endpoints**: Complete REST API for all CRUD operations (`/api/crud6/{model}`)
 * - **Dynamic Routing**: Automatic route generation for any model with validation
 * - **Sortable & Filterable Queries**: Built-in support for data tables with Sprunje
 * - **Frontend-Agnostic**: Works with any frontend (includes Vue.js components)
 * - **Permission-Based Access Control**: Schema-driven permissions with UserFrosting's authorization
 * - **Relationship Management**: Support for one-to-many and many-to-many relationships
 * - **Soft Delete Support**: Optional soft delete functionality per model
 * - **Multi-Database Support**: Connect to multiple databases with URL syntax
 * - **Field Type System**: Extensible field type registry for custom data types
 * 
 * ## Installation
 * 
 * Add to your sprinkles configuration in your app's main sprinkle class:
 * ```php
 * use UserFrosting\Sprinkle\CRUD6\CRUD6;
 * 
 * public function getSprinkles(): array
 * {
 *     return [
 *         Core::class,
 *         Account::class,
 *         Admin::class,
 *         CRUD6::class, // Add this line
 *     ];
 * }
 * ```
 * 
 * ## Usage Example
 * 
 * 1. Create a schema file at `app/schema/crud6/products.json`:
 * ```json
 * {
 *   "model": "products",
 *   "table": "products",
 *   "title": "Products",
 *   "permissions": {
 *     "read": "uri_products",
 *     "create": "create_product"
 *   },
 *   "fields": {
 *     "id": {"type": "integer", "auto_increment": true},
 *     "name": {"type": "string", "required": true},
 *     "price": {"type": "decimal"}
 *   }
 * }
 * ```
 * 
 * 2. Access via API:
 * - GET `/api/crud6/products` - List all products
 * - POST `/api/crud6/products` - Create new product
 * - GET `/api/crud6/products/5` - Get product #5
 * - PUT `/api/crud6/products/5` - Update product #5
 * - DELETE `/api/crud6/products/5` - Delete product #5
 * 
 * @see https://github.com/ssnukala/sprinkle-crud6 Project repository
 * @see https://learn.userfrosting.com/ UserFrosting documentation
 */
class CRUD6 implements SprinkleRecipe, MigrationRecipe, SeedRecipe, BakeryRecipe
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
        return [];
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
            FieldTypeServiceProvider::class,
            BakeryServicesProvider::class,
        ];
    }

    /**
     * Return an array of all registered Bakery Commands.
     *
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function getBakeryCommands(): array
    {
        return [
            GenerateSchemaCommand::class,
            ScanDatabaseCommand::class,
        ];
    }
}
