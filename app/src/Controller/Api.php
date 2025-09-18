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
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;

/**
 * API Controller for CRUD6 operations
 * 
 * Handles API endpoints for listing records with pagination, sorting, and filtering.
 * Based on UserFrosting sprinkle-admin patterns.
 */
class Api
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected CRUD6Sprunje $sprunje
    ) {
    }

    /**
     * API endpoint for listing records with pagination, sorting, and filtering
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        
        $this->validateAccess($schema, 'read');
        
        $this->logger->debug("CRUD6: API list for model: {$model}");
        
        // Get query parameters
        $params = $request->getQueryParams();
        
        // Setup sprunje with schema configuration
        $this->sprunje->setupSprunje(
            $this->getTableName($schema),
            $this->getSortableFields($schema),
            $this->getFilterableFields($schema),
            $schema
        );
        
        // Set query parameters for pagination, sorting, filtering
        $this->sprunje->setOptions($params);
        
        return $this->sprunje->toResponse($response);
    }

    /**
     * Get the model name from request attributes
     */
    protected function getModel(ServerRequestInterface $request): string
    {
        $model = $request->getAttribute('crud6_model');
        if ($model === null) {
            throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException('Model not found in request attributes');
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
            throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException('Schema not found in request attributes');
        }
        return $schema;
    }

    /**
     * Validate access based on schema permissions
     */
    protected function validateAccess(array $schema, string $action = 'read'): void
    {
        // Default permission is model-based
        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
        
        if (!$this->authenticator->checkAccess($permission)) {
            throw new \UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException("Access denied for {$action} on {$schema['model']}");
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
}