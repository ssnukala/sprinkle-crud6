<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Base Controller for CRUD6 operations
 * 
 * Provides common functionality for all CRUD6 controllers including
 * schema access, authentication, and authorization.
 */
abstract class BaseController
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger
    ) {
    }

    /**
     * Get the model name from request attributes
     */
    protected function getModel(ServerRequestInterface $request): string
    {
        $model = $request->getAttribute('crud6_model');
        if ($model === null) {
            throw new \RuntimeException('Model not found in request attributes');
        }
        return $model;
    }

    /**
     * Get the schema from request attributes
     */
    protected function getSchema(ServerRequestInterface $request): array
    {
        $schema = $request->getAttribute('crud6_schema');
        if ($schema === null) {
            throw new \RuntimeException('Schema not found in request attributes');
        }
        return $schema;
    }

    /**
     * Get the record ID from route arguments
     */
    protected function getRecordId(ServerRequestInterface $request): int
    {
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        
        if ($route === null) {
            throw new \RuntimeException('Route not found');
        }

        $id = $route->getArgument('id');
        if ($id === null) {
            throw new \RuntimeException('Record ID not found in route');
        }

        return (int) $id;
    }

    /**
     * Validate access based on schema permissions
     */
    protected function validateAccess(array $schema, string $action = 'read'): void
    {
        // Default permission is model-based
        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
        
        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
        }
    }

    /**
     * Get database table name from schema
     */
    protected function getTableName(array $schema): string
    {
        return $schema['table'];
    }

    /**
     * Get field definitions from schema
     */
    protected function getFields(array $schema): array
    {
        return $schema['fields'];
    }

    /**
     * Get sortable fields from schema
     */
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

    /**
     * Get filterable fields from schema
     */
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

    /**
     * Get validation rules from schema
     */
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