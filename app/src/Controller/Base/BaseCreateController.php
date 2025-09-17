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
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Database\Connection;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Alert\AlertStream;

/**
 * Base Create Controller
 * 
 * Handles creating new records for any model based on JSON schema configuration.
 */
class BaseCreateController extends BaseController
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected AlertStream $alert
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    /**
     * Create a new record
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
            
            $insertId = $this->db->table($table)->insertGetId($insertData);
            
            $this->logger->debug("CRUD6: Created record with ID: {$insertId} for model: {$model}");
            
            // Success response
            $responseData = [
                'message' => "Record created successfully",
                'model' => $model,
                'id' => $insertId,
                'data' => $insertData
            ];
            
            $this->alert->addMessageTranslated('success', 'CRUD6.CREATE.SUCCESS', [
                'model' => $schema['title'] ?? $model
            ]);
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to create record for model: {$model}", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            $errorData = [
                'error' => 'Failed to create record',
                'message' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Validate input data against schema
     */
    protected function validateInputData(array $schema, array $data): void
    {
        $rules = $this->getValidationRules($schema);
        
        if (!empty($rules)) {
            // Create validation schema
            $requestSchema = new RequestSchema($rules);
            
            // Transform and validate data
            $transformer = new RequestDataTransformer($requestSchema);
            $transformedData = $transformer->transform($data);
            
            $validator = new ServerSideValidator($requestSchema);
            $errors = $validator->validate($transformedData);
            
            if (count($errors) > 0) {
                throw new \UserFrosting\Sprinkle\Core\Exceptions\ValidationException($errors);
            }
        }
    }

    /**
     * Prepare data for database insertion
     */
    protected function prepareInsertData(array $schema, array $data): array
    {
        $insertData = [];
        $fields = $this->getFields($schema);
        
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