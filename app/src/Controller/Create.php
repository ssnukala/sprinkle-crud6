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
use UserFrosting\Sprinkle\Core\Database\Connection;
use UserFrosting\Alert\AlertStream;

/**
 * Create Action for CRUD6 operations
 * 
 * Handles creating new records for any model based on JSON schema configuration.
 * Based on UserFrosting sprinkle-admin patterns.
 */
class Create
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
     * Create a new record
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
        
        $this->validateAccess($schema, 'create');
        
        $this->logger->debug("CRUD6: Creating new record for model: {$model}");
        
        // Get request data
        $data = $request->getParsedBody();
        
        // Validate input data
        $this->validateInputData($schema, $data);
        
        try {
            // Insert into database
            $table = $this->getTableName($schema);
            $insertData = $this->prepareInsertData($schema, $data);
            
            $recordId = $this->db->table($table)->insertGetId($insertData);
            
            $this->alert->addMessage('success', 'Record created successfully', [
                'model' => $model,
                'id' => $recordId
            ]);
            
            $this->logger->debug("CRUD6: Successfully created record with ID: {$recordId} for model: {$model}");
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to create record for model: {$model}. Error: " . $e->getMessage());
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
     * Validate access based on schema permissions
     */
    protected function validateAccess(array $schema, string $action = 'create'): void
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
        
        foreach ($fields as $fieldName => $fieldConfig) {
            // Check required fields
            if (($fieldConfig['required'] ?? false) && !isset($data[$fieldName])) {
                $errors[] = "Field '{$fieldName}' is required";
            }
        }
        
        if (count($errors) > 0) {
            throw new \UserFrosting\Framework\Exception\ValidationException($errors);
        }
    }

    /**
     * Prepare data for database insertion
     */
    protected function prepareInsertData(array $schema, array $data): array
    {
        $insertData = [];
        $fields = $schema['fields'];
        
        foreach ($fields as $fieldName => $fieldConfig) {
            // Skip auto-increment and computed fields
            if ($fieldConfig['auto_increment'] ?? false || 
                $fieldConfig['computed'] ?? false) {
                continue;
            }
            
            // Include field if present in data or has default value
            if (isset($data[$fieldName])) {
                $insertData[$fieldName] = $this->transformFieldValue($fieldConfig, $data[$fieldName]);
            } elseif (isset($fieldConfig['default'])) {
                $insertData[$fieldName] = $fieldConfig['default'];
            }
        }
        
        // Add timestamps if configured
        if ($schema['timestamps'] ?? false) {
            $now = date('Y-m-d H:i:s');
            $insertData['created_at'] = $now;
            $insertData['updated_at'] = $now;
        }
        
        return $insertData;
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