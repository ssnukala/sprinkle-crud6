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
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD6\Routes\CRUD6Routes;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\CRUD6ModelService;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaServiceProvider;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;

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
class CRUD6 implements SprinkleRecipe, SeedRecipe
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
        ];
    }
}
