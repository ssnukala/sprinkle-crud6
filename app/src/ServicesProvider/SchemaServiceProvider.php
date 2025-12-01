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

use Psr\Container\ContainerInterface;
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
            SchemaService::class => function (ContainerInterface $c) {
                // Get required dependencies
                $locator = $c->get(ResourceLocatorInterface::class);
                $config = $c->get(Config::class);
                $logger = $c->get(DebugLoggerInterface::class);
                $translator = $c->get(Translator::class);
                
                // Cache is optional - try to get it but don't fail if not available
                $cache = null;
                if ($c->has(CacheInterface::class)) {
                    try {
                        $cache = $c->get(CacheInterface::class);
                    } catch (\Exception $e) {
                        // Cache not available, continue without it
                    }
                }
                
                return new SchemaService($locator, $config, $logger, $translator, $cache);
            },
        ];
    }
}
