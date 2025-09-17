<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use UserFrosting\ServicesProvider\ServicesProviderInterface;
use DI\Container;

/**
 * CRUD6 Services Provider
 * 
 * Registers services for the CRUD6 sprinkle.
 */
class CRUD6ServicesProvider implements ServicesProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(SchemaService::class, function (Container $container) {
            return new SchemaService($container);
        });
    }
}