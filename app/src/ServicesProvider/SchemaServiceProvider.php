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

use Psr\SimpleCache\CacheInterface;
use UserFrosting\Config\Config;
use UserFrosting\I18n\Translator;
use UserFrosting\ServicesProvider\ServicesProviderInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * Schema Service Provider.
 * 
 * Registers the SchemaService for loading and managing JSON schema files.
 * Follows the UserFrosting 6 service provider pattern from sprinkle-core.
 * 
 * Explicitly injects all dependencies including Translator to ensure
 * schema translations work correctly.
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
            SchemaService::class => \DI\autowire(SchemaService::class)
                ->constructorParameter('locator', \DI\get(ResourceLocatorInterface::class))
                ->constructorParameter('config', \DI\get(Config::class))
                ->constructorParameter('logger', \DI\get(DebugLoggerInterface::class))
                ->constructorParameter('translator', \DI\get(Translator::class))
                ->constructorParameter('cache', \DI\get(CacheInterface::class)),
        ];
    }
}
