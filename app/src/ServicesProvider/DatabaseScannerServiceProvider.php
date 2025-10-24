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

/**
 * Database Scanner Service Provider.
 * 
 * Registers the DatabaseScanner service for dependency injection.
 */
class DatabaseScannerServiceProvider implements ServicesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(): array
    {
        return [
            DatabaseScanner::class => \DI\autowire(DatabaseScanner::class),
        ];
    }
}
