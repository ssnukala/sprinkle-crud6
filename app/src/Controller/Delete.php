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
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Database\Connection;
use UserFrosting\Alert\AlertStream;

/**
 * Delete Action for CRUD6 operations
 * 
 * Handles deleting records for any model based on JSON schema configuration.
 * Based on UserFrosting sprinkle-admin patterns.
 */
class Delete
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected AlertStream $alert
    ) {
    }

    /**
     * Delete a record
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->handle($request);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     */
    protected function handle(ServerRequestInterface $request): void
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $recordId = $this->getRecordId($request);
        
        $this->validateAccess($schema, 'delete');
        
        $this->logger->debug("CRUD6: Deleting record ID: {$recordId} for model: {$model}");
        
        try {
            $table = $this->getTableName($schema);
            $primaryKey = $schema['primary_key'] ?? 'id';
            
            // Check if record exists
            $record = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            
            if (!$record) {
                throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException("Record with ID {$recordId} not found for {$model}");
            }
            
            // Perform deletion
            $deletedCount = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->delete();
            
            if ($deletedCount === 0) {
                throw new \Exception("Failed to delete record with ID {$recordId} for {$model}");
            }
            
            $this->alert->addMessage('success', 'Record deleted successfully', [
                'model' => $model,
                'id' => $recordId
            ]);
            
            $this->logger->debug("CRUD6: Successfully deleted record ID: {$recordId} for model: {$model}");
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to delete record ID: {$recordId} for model: {$model}. Error: " . $e->getMessage());
            throw $e;
        }
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
     * Get the record ID from route arguments
     */
    protected function getRecordId(ServerRequestInterface $request): int
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        
        if ($route === null) {
            throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException('Route not found');
        }

        $id = $route->getArgument('id');
        if ($id === null) {
            throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException('Record ID not found in route');
        }

        return (int) $id;
    }

    /**
     * Validate access based on schema permissions
     */
    protected function validateAccess(array $schema, string $action = 'delete'): void
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
}