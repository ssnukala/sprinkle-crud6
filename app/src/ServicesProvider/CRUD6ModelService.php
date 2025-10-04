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
 * CRUD6 Model Service Provider.
 * 
 * Maps the CRUD6ModelInterface to its concrete implementation.
 * Follows the UserFrosting 6 service provider pattern from sprinkle-core.
 * 
 * Note: Both interface and class are mapped using class-string since Models
 * are not instantiated by the container in the Eloquent world.
 * 
 * @see \UserFrosting\Sprinkle\Account\ServicesProvider\ModelsService
 */
class CRUD6ModelService implements ServicesProviderInterface
{
    /**
     * Register CRUD6 model mappings with the DI container.
     * 
     * @return array<string, mixed> Service definitions for the container
     */
    public function register(): array
    {
        return [
            CRUD6ModelInterface::class      => \DI\autowire(CRUD6Model::class)
        ];
    }
}
