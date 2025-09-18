<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Base controller for CRUD6 operations
 * 
 * Provides common functionality for all CRUD6 controllers including
 * schema access, authentication, and authorization.
 */
abstract class Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger
    ) {
    }

    protected function getModel(ServerRequestInterface $request): string
    {
        $model = $request->getAttribute('crud6_model');
        if ($model === null) {
            throw new NotFoundException('Model not found in request attributes');
        }
        return $model;
    }

    protected function getSchema(ServerRequestInterface $request): array
    {
        $schema = $request->getAttribute('crud6_schema');
        if ($schema === null) {
            throw new NotFoundException('Schema not found in request attributes');
        }
        return $schema;
    }

    protected function getRecordId(ServerRequestInterface $request): int
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        if ($route === null) {
            throw new NotFoundException('Route not found');
        }
        $id = $route->getArgument('id');
        if ($id === null) {
            throw new NotFoundException('Record ID not found in route');
        }
        return (int) $id;
    }

    protected function validateAccess(array $schema, string $action = 'read'): void
    {
        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
        }
    }

    protected function getTableName(array $schema): string
    {
        return $schema['table'];
    }

    protected function getFields(array $schema): array
    {
        return $schema['fields'];
    }

    protected function getSortableFields(array $schema): array
    {
        $sortable = [];
        foreach ($schema['fields'] as $name => $field) {
            if ($field['sortable'] ?? false) {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }

    protected function getFilterableFields(array $schema): array
    {
        $filterable = [];
        foreach ($schema['fields'] as $name => $field) {
            if ($field['filterable'] ?? false) {
                $filterable[] = $name;
            }
        }
        return $filterable;
    }

    protected function getValidationRules(array $schema): array
    {
        $rules = [];
        foreach ($schema['fields'] as $name => $field) {
            if (isset($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }
        return $rules;
    }
}
