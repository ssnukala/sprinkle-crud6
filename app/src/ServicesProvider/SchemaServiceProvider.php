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
 * Schema Service Provider.
 * 
 * Registers the SchemaService for loading and managing JSON schema files.
 * Follows the UserFrosting 6 service provider pattern from sprinkle-core.
 * 
 * @see \UserFrosting\Sprinkle\Core\ServicesProvider\CacheService
 */
class SchemaServiceProvider implements ServicesProviderInterface
{
    /**
     * Register SchemaService with the DI container.
     * 
     * @return array<string, mixed> Service definitions for the container
     */
    public function register(): array
    {
        return [
            SchemaService::class => \DI\autowire(SchemaService::class),
        ];
    }
}
