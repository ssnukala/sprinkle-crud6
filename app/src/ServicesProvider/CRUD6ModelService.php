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
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model;

/**
 * Map models interface to the class.
 *
 * Note both class are map using class-string, since Models are not instantiated
 * by the container in the Eloquent world.
 */
class CRUD6ModelService implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            CRUD6ModelInterface::class      => \DI\autowire(CRUD6Model::class)
        ];
    }
}
