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
use UserFrosting\Sprinkle\CRUD6\FieldTypes\FieldTypeRegistry;
use UserFrosting\Sprinkle\CRUD6\FieldTypes\Types\CurrencyFieldType;

/**
 * Service provider for the FieldTypeRegistry.
 * 
 * Registers the FieldTypeRegistry as a singleton service and registers
 * all built-in field type handlers.
 * 
 * To register custom field types, extend this provider or create a new
 * provider that retrieves the registry and calls register() on it.
 * 
 * @example
 * ```php
 * // In your custom sprinkle's service provider:
 * class MyFieldTypesServiceProvider implements ServicesProviderInterface
 * {
 *     public function register(): array
 *     {
 *         return [
 *             // Use DI\decorate to extend the existing registry
 *             FieldTypeRegistry::class => \DI\decorate(function (FieldTypeRegistry $registry) {
 *                 $registry->register(new MyCustomFieldType());
 *                 return $registry;
 *             })
 *         ];
 *     }
 * }
 * ```
 */
class FieldTypeServiceProvider implements ServicesProviderInterface
{
    /**
     * Register services.
     * 
     * @return array<string, callable> Service definitions
     */
    public function register(): array
    {
        return [
            FieldTypeRegistry::class => function (): FieldTypeRegistry {
                $registry = new FieldTypeRegistry();
                
                // Register built-in custom field types
                $registry->register(new CurrencyFieldType());
                
                return $registry;
            }
        ];
    }
}
