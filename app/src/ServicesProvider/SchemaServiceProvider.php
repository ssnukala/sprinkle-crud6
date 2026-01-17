<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
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
 * Registers the SchemaService and its specialized service components with the DI container.
 * Follows the UserFrosting 6 service provider pattern from sprinkle-core.
 * 
 * All service classes are registered in the container to support proper dependency injection
 * and follow the Single Responsibility Principle.
 * 
 * @see \UserFrosting\Sprinkle\Core\ServicesProvider\CacheService
 */
class SchemaServiceProvider implements ServicesProviderInterface
{
    /**
     * Register SchemaService and related services with the DI container.
     * 
     * Registers all specialized service classes used by SchemaService:
     * - SchemaLoader: File loading and default values
     * - SchemaValidator: Schema structure validation
     * - SchemaNormalizer: Schema normalization operations
     * - SchemaCache: Two-tier caching strategy
     * - SchemaFilter: Context filtering
     * - SchemaTranslator: i18n translation
     * - SchemaActionManager: Action management
     * 
     * @return array<string, mixed> Service definitions for the container
     */
    public function register(): array
    {
        return [
            // Register specialized service classes using autowire
            SchemaLoader::class => \DI\autowire(SchemaLoader::class),
            SchemaValidator::class => \DI\autowire(SchemaValidator::class),
            SchemaNormalizer::class => \DI\autowire(SchemaNormalizer::class),
            SchemaFilter::class => \DI\autowire(SchemaFilter::class),
            SchemaTranslator::class => \DI\autowire(SchemaTranslator::class),
            
            // SchemaCache requires explicit factory due to optional PSR-16 cache
            SchemaCache::class => function (ContainerInterface $c) {
                $config = $c->get(Config::class);
                $logger = $c->get(DebugLoggerInterface::class);
                
                // Cache is optional - try to get it but don't fail if not available
                $cache = null;
                if ($c->has(CacheInterface::class)) {
                    try {
                        $cache = $c->get(CacheInterface::class);
                    } catch (\Exception $e) {
                        // Cache not available, continue without it
                    }
                }
                
                return new SchemaCache($config, $logger, $cache);
            },
            
            // SchemaActionManager requires SchemaValidator dependency
            SchemaActionManager::class => function (ContainerInterface $c) {
                return new SchemaActionManager(
                    $c->get(SchemaValidator::class),
                    $c->get(DebugLoggerInterface::class)
                );
            },
            
            // Main SchemaService orchestrates all specialized services
            SchemaService::class => function (ContainerInterface $c) {
                return new SchemaService(
                    $c->get(ResourceLocatorInterface::class),
                    $c->get(Config::class),
                    $c->get(DebugLoggerInterface::class),
                    $c->get(Translator::class),
                    $c->get(SchemaLoader::class),
                    $c->get(SchemaValidator::class),
                    $c->get(SchemaNormalizer::class),
                    $c->get(SchemaCache::class),
                    $c->get(SchemaFilter::class),
                    $c->get(SchemaTranslator::class),
                    $c->get(SchemaActionManager::class)
                );
            },
        ];
    }
}
