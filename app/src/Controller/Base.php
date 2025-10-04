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
 * Base controller for CRUD6 operations.
 * 
 * Provides common functionality for all CRUD6 controllers following the UserFrosting 6
 * action-based controller pattern from sprinkle-admin. Controllers receive a configured
 * CRUD6ModelInterface instance that contains the schema configuration and database access.
 * 
 * All CRUD6 controllers should extend this base class and implement their specific
 * action in the __invoke method.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserCreateAction
 */
abstract class Base
{
    /**
     * @var array<string, array> Cache of loaded schemas by model name
     */
    protected array $cachedSchema = [];
    
    /**
     * @var array<string, mixed> Route parameters extracted from request
     */
    protected array $routeParams = [];

    /**
     * Constructor for base CRUD6 controller.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager for permission checks
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger for diagnostics
     * @param SchemaService        $schemaService Schema service for loading model definitions
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService
    ) {}


    /**
     * Common invoke logic for CRUD6 controllers.
     * 
     * Sets up common state for all CRUD6 operations including schema caching
     * and access validation. Child controllers should call this parent method
     * before their specific logic.
     * 
     * @param array                  $crudSchema The schema configuration array
     * @param CRUD6ModelInterface    $crudModel  The configured model instance
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return void
     * 
     * @throws ForbiddenException If user lacks required permissions
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): void
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
     * Checks if the current user has permission to perform the specified action
     * on the model. Permission names follow the pattern: crud6.{model}.{action}
     * or can be customized in the schema's permissions configuration.
     *
     * @param string $modelName The model name
     * @param string $action    The action to validate (read, create, edit, delete)
     * 
     * @return void
     * 
     * @throws ForbiddenException If user lacks the required permission
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
     * 
     * Uses caching to avoid repeatedly loading the same schema.
     * 
     * @param string $modelName The model name
     * 
     * @return array<string, mixed> The schema configuration array
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
     * 
     * Returns the cached fields if schema is already loaded.
     * 
     * @param string $modelName The model name
     * 
     * @return array<string, array> The fields configuration
     */
    protected function getFields(string $modelName): array
    {
        $schema = $this->getSchema($modelName);
        return $schema['fields'] ?? [];
    }

    /**
     * Get sortable fields from the model schema.
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of sortable field names
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
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of filterable field names
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
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of listable field names
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

    /**
     * Get validation rules from the model schema.
     * 
     * @param string $modelName The model name
     * 
     * @return array<string, array> Validation rules for each field
     */
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

    /**
     * Get a parameter from the request route.
     * 
     * Caches route parameters on first access for performance.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * @param string                 $key     The parameter key
     * @param mixed                  $default Default value if not found
     * 
     * @return mixed The parameter value or default
     */
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
     * Get model name from the request route.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * 
     * @return string The model name from route parameters
     */
    protected function getModelNameFromRequest(ServerRequestInterface $request): string
    {
        return $this->getParameter($request, 'model'); // to set routeParams if not already set
    }

    /**
     * Get the schema from the request attributes.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * 
     * @return array<string, mixed> The schema configuration array
     */
    protected function getSchemaFromRequest(ServerRequestInterface $request): array
    {
        return $this->getParameter($request, 'crudSchema'); // to set routeParams if not already set
    }
}
