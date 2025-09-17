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
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD6\Routes\CRUD6Routes;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\CRUD6ServicesProvider;

/**
 * CRUD6 Sprinkle - Generic API CRUD Layer for UserFrosting 6
 *
 * Provides dynamic CRUD operations on any database table using JSON schema definitions.
 * Features:
 * - JSON-based schema definitions for flexible table configuration
 * - Dynamic routing for any model (/crud6/{model})
 * - RESTful API endpoints for CRUD operations
 * - Automatic form generation and validation
 * - Sortable and filterable data tables
 */
class CRUD6 implements SprinkleRecipe
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
    public function getServices(): array
    {
        return [
            CRUD6ServicesProvider::class,
        ];
    }
}