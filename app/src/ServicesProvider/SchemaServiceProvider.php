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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Schema service provider.
 *
 * Registers the SchemaService for loading and managing JSON schema files.
 */
class SchemaServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            SchemaService::class => \DI\autowire(SchemaService::class),
        ];
    }
}
