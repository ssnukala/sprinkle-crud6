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
use UserFrosting\Sprinkle\CRUD6\Controller\UpdateAction;
use UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector;

/**
 * CRUD6 Routes.
 * 
 * Provides API routing for any model defined in JSON schema.
 * Follows the UserFrosting 6 route definition pattern from sprinkle-core.
 * 
 * RESTful API endpoints:
 * - GET    /api/crud6/{model}/schema       - Get schema metadata
 * - GET    /api/crud6/{model}              - List records (Sprunje with filter/sort/paginate)
 * - POST   /api/crud6/{model}              - Create new record
 * - GET    /api/crud6/{model}/{id}         - Read single record
 * - PUT    /api/crud6/{model}/{id}         - Update record
 * - DELETE /api/crud6/{model}/{id}         - Delete record
 * - GET    /api/crud6/{model}/{id}/{relation} - Get related data
 * 
 * Database Connection Selection:
 * - /api/crud6/users        - Uses default connection (or schema connection)
 * - /api/crud6/users@db1    - Uses db1 connection (overrides schema connection)
 * 
 * All routes are protected with:
 * - CRUD6Injector middleware (injects model and schema)
 * - AuthGuard middleware (authentication)
 * - NoCache middleware (prevent caching)
 * 
 * @see \UserFrosting\Routes\RouteDefinitionInterface
 * @see \UserFrosting\Sprinkle\Admin\Routes\AdminRoutes
 */
class CRUD6Routes implements RouteDefinitionInterface
{
    /**
     * Register CRUD6 routes with the application.
     * 
     * @param App $app The Slim application instance
     * 
     * @return void
     */
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
            // Update record
            $group->put('/{id}', UpdateAction::class)
                ->setName('api.crud6.update');
            // Delete record
            $group->delete('/{id}', DeleteAction::class)
                ->setName('api.crud6.delete');
        })->add(CRUD6Injector::class)->add(AuthGuard::class)->add(NoCache::class);
    }
}
