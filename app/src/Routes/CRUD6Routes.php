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
use UserFrosting\Sprinkle\CRUD6\Controller\ConfigAction;
use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction;
use UserFrosting\Sprinkle\CRUD6\Controller\DeleteAction;
use UserFrosting\Sprinkle\CRUD6\Controller\EditAction;
use UserFrosting\Sprinkle\CRUD6\Controller\RelationshipAction;
use UserFrosting\Sprinkle\CRUD6\Controller\SprunjeAction;
use UserFrosting\Sprinkle\CRUD6\Controller\UpdateFieldAction;
use UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector;

/**
 * CRUD6 Routes.
 * 
 * Provides API routing for any model defined in JSON schema.
 * Follows the UserFrosting 6 route definition pattern from sprinkle-core.
 * 
 * RESTful API endpoints:
 * - GET    /api/crud6/{model}/schema                   - Get schema metadata
 * - GET    /api/crud6/{model}                          - List records (Sprunje with filter/sort/paginate)
 * - POST   /api/crud6/{model}                          - Create new record
 * - GET    /api/crud6/{model}/{id}                     - Read single record (EditAction)
 * - PUT    /api/crud6/{model}/{id}                     - Update record (EditAction)
 * - PUT    /api/crud6/{model}/{id}/{field}             - Update single field (UpdateFieldAction)
 * - DELETE /api/crud6/{model}/{id}                     - Delete record
 * - GET    /api/crud6/{model}/{id}/{relation}          - Get related data (one-to-many)
 * - POST   /api/crud6/{model}/{id}/{relation}          - Attach relationship (many-to-many)
 * - DELETE /api/crud6/{model}/{id}/{relation}          - Detach relationship (many-to-many)
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
        // CRUD6 Config endpoint (public, no auth required)
        $app->get('/api/crud6/config', ConfigAction::class)
            ->setName('api.crud6.config')
            ->add(NoCache::class);

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
            // Read single record with relation - GET for listing related data
            $group->get('/{id}/{relation}', SprunjeAction::class);
            // Manage many-to-many relationships - POST to attach, DELETE to detach
            $group->post('/{id}/{relation}', RelationshipAction::class)
                ->setName('api.crud6.relationship.attach');
            $group->delete('/{id}/{relation}', RelationshipAction::class)
                ->setName('api.crud6.relationship.detach');

            // Read single record (GET) and Update record (PUT) - both handled by EditAction
            $group->get('/{id}', EditAction::class)
                ->setName('api.crud6.read');
            $group->put('/{id}', EditAction::class)
                ->setName('api.crud6.update');
            // Update single field (PUT)
            $group->put('/{id}/{field}', UpdateFieldAction::class)
                ->setName('api.crud6.update_field');
            // Delete record
            $group->delete('/{id}', DeleteAction::class)
                ->setName('api.crud6.delete');
        })->add(CRUD6Injector::class)->add(AuthGuard::class)->add(NoCache::class);
    }
}
