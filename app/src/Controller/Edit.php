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
 * Edit Action for CRUD6 operations
 * 
 * Handles updating existing records for any model based on JSON schema configuration.
 * Based on UserFrosting sprinkle-admin patterns.
 */
class Edit
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
     * Update an existing record
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
        
        $this->validateAccess($schema, 'update');
        
        $this->logger->debug("CRUD6: Updating record ID: {$recordId} for model: {$model}");
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate input data
        $this->validateInputData($schema, $data);
        
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
            
            // Prepare update data
            $updateData = $this->prepareUpdateData($schema, $data);
            
            if (empty($updateData)) {
                $this->logger->debug("CRUD6: No valid update data provided for record ID: {$recordId}");
                return;
            }
            
            // Perform update
            $updatedCount = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->update($updateData);
            
            $this->alert->addMessage('success', 'Record updated successfully', [
                'model' => $model,
                'id' => $recordId
            ]);
            
            $this->logger->debug("CRUD6: Successfully updated record ID: {$recordId} for model: {$model}");
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to update record ID: {$recordId} for model: {$model}. Error: " . $e->getMessage());
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
    protected function validateAccess(array $schema, string $action = 'update'): void
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
     * Validate input data against schema
     */
    protected function validateInputData(array $schema, array $data): void
    {
        $errors = [];
        $fields = $schema['fields'];
        
        foreach ($data as $fieldName => $value) {
            // Skip if field not defined in schema
            if (!isset($fields[$fieldName])) {
                continue;
            }
            
            $fieldConfig = $fields[$fieldName];
            
            // Skip read-only and auto-increment fields
            if ($fieldConfig['readonly'] ?? false || 
                $fieldConfig['auto_increment'] ?? false) {
                $errors[] = "Field '{$fieldName}' is read-only and cannot be updated";
            }
        }
        
        if (count($errors) > 0) {
            throw new \UserFrosting\Framework\Exception\ValidationException($errors);
        }
    }

    /**
     * Prepare data for database update
     */
    protected function prepareUpdateData(array $schema, array $data): array
    {
        $updateData = [];
        $fields = $schema['fields'];
        
        foreach ($data as $fieldName => $value) {
            // Skip if field not defined in schema
            if (!isset($fields[$fieldName])) {
                continue;
            }
            
            $fieldConfig = $fields[$fieldName];
            
            // Skip read-only and auto-increment fields
            if ($fieldConfig['readonly'] ?? false || 
                $fieldConfig['auto_increment'] ?? false) {
                continue;
            }
            
            $updateData[$fieldName] = $this->transformFieldValue($fieldConfig, $value);
        }
        
        // Add updated timestamp if configured
        if ($schema['timestamps'] ?? false) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $updateData;
    }

    /**
     * Transform field value based on field type
     */
    protected function transformFieldValue(array $fieldConfig, $value)
    {
        $type = $fieldConfig['type'] ?? 'string';
        
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return is_string($value) ? $value : json_encode($value);
            case 'date':
            case 'datetime':
                return $value; // Assume already in correct format
            default:
                return (string) $value;
        }
    }
}