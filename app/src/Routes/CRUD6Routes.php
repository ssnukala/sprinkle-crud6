<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Route;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\BaseListController;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\BaseCreateController;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\BaseReadController;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\BaseUpdateController;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\BaseDeleteController;
use UserFrosting\Sprinkle\CRUD6\Controller\Base\ModelBasedController;
use UserFrosting\Sprinkle\CRUD6\Middlewares\SchemaInjector;

/**
 * Routes for CRUD6 operations.
 * 
 * Provides API routing for any model defined in JSON schema:
 * 
 * Traditional Query Builder API:
 * - GET /api/crud6/{model} - List data API
 * - POST /api/crud6/{model} - Create record
 * - GET /api/crud6/{model}/{id} - Read single record
 * - PUT /api/crud6/{model}/{id} - Update record
 * - DELETE /api/crud6/{model}/{id} - Delete record
 * 
 * Generic Model API (using Eloquent ORM):
 * - GET /api/crud6-model/{model} - List data using generic model
 * - POST /api/crud6-model/{model} - Create record using generic model
 * - GET /api/crud6-model/{model}/{id} - Read single record using generic model
 */
class CRUD6Routes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        // API routes for CRUD operations
        $app->group('/api/crud6/{model}', function (RouteCollectorProxy $group) {
            // List with optional filtering, sorting, pagination
            $group->get('', [BaseListController::class, 'apiList'])
                ->setName('api.crud6.list');
            
            // Create new record
            $group->post('', BaseCreateController::class)
                ->setName('api.crud6.create');
            
            // Read single record
            $group->get('/{id}', BaseReadController::class)
                ->setName('api.crud6.read');
            
            // Update record
            $group->put('/{id}', BaseUpdateController::class)
                ->setName('api.crud6.update');
            
            // Delete record
            $group->delete('/{id}', BaseDeleteController::class)
                ->setName('api.crud6.delete');
        })->add(SchemaInjector::class)->add(AuthGuard::class)->add(NoCache::class);

        // Model-based API routes (demonstrating generic model usage)
        $app->group('/api/crud6-model/{model}', function (RouteCollectorProxy $group) {
            // List using generic model
            $group->get('', [ModelBasedController::class, 'listRecords'])
                ->setName('api.crud6.model.list');
            
            // Create using generic model
            $group->post('', [ModelBasedController::class, 'createRecord'])
                ->setName('api.crud6.model.create');
            
            // Read single record using generic model
            $group->get('/{id}', [ModelBasedController::class, 'readRecord'])
                ->setName('api.crud6.model.read');
        })->add(SchemaInjector::class)->add(AuthGuard::class)->add(NoCache::class);
    }
}