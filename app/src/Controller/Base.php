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
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Base controller for CRUD6 operations
 * 
 * Provides common functionality for all CRUD6 controllers.
 * Controllers receive a configured CRUD6ModelInterface instance that contains
 * the schema configuration and database access.
 */
abstract class Base
{
    protected array $cachedSchema = [];
    protected array $routeParams = [];

    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService
    ) {}


    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response)
    {
        // Common logic here, e.g. logging, validation, etc.
        $modelName = $this->getModelNameFromRequest($request);
        $this->cachedSchema[$modelName] = $crudSchema;
        $this->validateAccess($modelName, 'read');
        $this->logger->debug("Line 52 : Base::__invoke called for model: {$modelName}");
        // You can set up other shared state here
    }

    /**
     * Validate user access permissions for CRUD operations.
     *
     * @param string $modelName The model name
     * @param string $action The action to validate (read, create, edit, delete)
     */
    protected function validateAccess(string $modelName, string $action = 'read'): void
    {
        $schema = $this->getSchema($modelName);
        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";

        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
        }
    }

    /**
     * Get the schema for the model name.
     */
    protected function getSchema(string $modelName): array
    {
        if (!isset($this->cachedSchema[$modelName])) {
            $this->cachedSchema[$modelName] = $this->schemaService->getSchema($modelName);
        }
        return $this->cachedSchema[$modelName];
    }

    /**
     * Get the schema fields from the model name.
     * this can just return the cached fields if already loaded in cachedSchema.
     */
    protected function getFields(string $modelName): array
    {
        $schema = $this->getSchema($modelName);
        return $schema['fields'] ?? [];
    }

    /**
     * Get sortable fields from the model schema.
     */
    protected function getSortableFields(string $modelName): array
    {
        $sortable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            if ($field['sortable'] ?? false) {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }

    /**
     * Get filterable fields from the model schema.
     */
    protected function getFilterableFields(string $modelName): array
    {
        $filterable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            if ($field['filterable'] ?? false) {
                $filterable[] = $name;
            }
        }
        return $filterable;
    }

    /**
     * Get listable fields from the model schema.
     */
    protected function getListableFields(string $modelName): array
    {
        $listable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            if ($field['listable'] ?? false) {
                $listable[] = $name;
            }
        }
        return $listable;
    }

    protected function getValidationRules(string $modelName): array
    {
        $schema = $this->schemaService->getSchema($modelName);
        $rules = [];
        foreach ($schema['fields'] as $name => $field) {
            if (isset($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }
        return $rules;
    }

    protected function getParameter(ServerRequestInterface $request, string $key, $default = null): mixed
    {
        // if routeParams are already set (injected), use them first if not fetch from request and set routeParams and return
        if (count($this->routeParams) === 0) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $this->routeParams = $route?->getArguments() ?? [];
        }
        $routeParam = $this->routeParams[$key] ?? $default;
        return $routeParam;
    }

    /**
     * Get model name from the request route
     */
    protected function getModelNameFromRequest(ServerRequestInterface $request): string
    {
        return $this->getParameter($request, 'model'); // to set routeParams if not already set
    }

    /**
     * Get the schema from the request attributes.
     */
    protected function getSchemaFromRequest(ServerRequestInterface $request): array
    {
        return $this->getParameter($request, 'crudSchema'); // to set routeParams if not already set
    }
}
