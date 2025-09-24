<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;
use UserFrosting\Sprinkle\CRUD6\Controller\ApiAction;
use UserFrosting\Sprinkle\CRUD6\Controller\DeleteAction;
use UserFrosting\Sprinkle\CRUD6\Controller\EditAction;
use UserFrosting\Sprinkle\CRUD6\Controller\SprunjeAction;
use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction;
use UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector;

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
        // CRUD6 API routes (Action-based, matching sprinkle-admin pattern)
        $app->group('/api/crud6/{model}', function (RouteCollectorProxy $group) {
            // API meta/schema endpoint (must be before /{id} routes)
            $group->get('/schema', ApiAction::class)
                ->setName('api.crud6.schema');
            // Sprunje listing (filter/sort/paginate)
            $group->get('', SprunjeAction::class)
                ->setName('api.crud6.list');
            // Create new record
            $group->post('', CreateAction::class)
                ->setName('api.crud6.create');
            // Read single record
            $group->get('/{id}/{relation}', SprunjeAction::class);

            $group->get('/{id}', EditAction::class)
                ->setName('api.crud6.read');
            // Update record (reuse EditAction for update)
            $group->put('/{id}', EditAction::class)
                ->setName('api.crud6.update');
            // Delete record
            $group->delete('/{id}', DeleteAction::class)
                ->setName('api.crud6.delete');
        })->add(CRUD6Injector::class)->add(AuthGuard::class)->add(NoCache::class);
    }
}
