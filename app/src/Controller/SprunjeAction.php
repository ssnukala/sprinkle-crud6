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

/**
 * SprunjeAction for CRUD6 operations
 * 
 * Handles reading single records for any model based on JSON schema configuration.
 * Based on UserFrosting sprinkle-admin patterns.
 */
class SprunjeAction
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db
    ) {
    }

    /**
     * Read a single record
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->handle($request);
        $payload = json_encode($data, JSON_THROW_ON_ERROR);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     */
    protected function handle(ServerRequestInterface $request): array
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $recordId = $this->getRecordId($request);
        
        $this->validateAccess($schema, 'read');
        
        $this->logger->debug("CRUD6: Reading record ID: {$recordId} for model: {$model}");
        
        try {
            $table = $this->getTableName($schema);
            $primaryKey = $schema['primary_key'] ?? 'id';
            
            $record = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            
            if (!$record) {
                $errorData = [
                    'error' => 'Record not found',
                    'model' => $model,
                    'id' => $recordId
                ];
                throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException('Record not found');
            }
            
            // Transform record data based on field types
            $transformedRecord = $this->transformRecord($schema, (array) $record);
            
            $this->logger->debug("CRUD6: Successfully retrieved record ID: {$recordId} for model: {$model}");
            
            return [
                'data' => $transformedRecord,
                'model' => $model
            ];
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to read record ID: {$recordId} for model: {$model}. Error: " . $e->getMessage());
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
     * Transform record data based on field types
     */
    protected function transformRecord(array $schema, array $record): array
    {
        $transformedRecord = [];
        $fields = $schema['fields'];
        
        foreach ($record as $fieldName => $value) {
            if (isset($fields[$fieldName])) {
                $fieldConfig = $fields[$fieldName];
                $transformedRecord[$fieldName] = $this->transformFieldValue($fieldConfig, $value);
            } else {
                // Include fields not in schema as-is
                $transformedRecord[$fieldName] = $value;
            }
        }
        
        return $transformedRecord;
    }

    /**
     * Transform field value based on field type for output
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
                return is_string($value) ? json_decode($value, true) : $value;
            case 'date':
                return $value; // Keep as string in ISO format
            case 'datetime':
                return $value; // Keep as string in ISO format
            default:
                return (string) $value;
        }
    }
}