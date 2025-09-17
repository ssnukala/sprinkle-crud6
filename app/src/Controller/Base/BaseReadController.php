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

/**
 * Base Read Controller
 * 
 * Handles reading single records for any model based on JSON schema configuration.
 */
class BaseReadController extends BaseController
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    /**
     * Read a single record
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
                
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            // Convert to array and format fields
            $recordData = (array) $record;
            $formattedData = $this->formatRecordData($schema, $recordData);
            
            $responseData = [
                'model' => $model,
                'id' => $recordId,
                'data' => $formattedData
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to read record for model: {$model}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);
            
            $errorData = [
                'error' => 'Failed to read record',
                'message' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Format record data based on field configurations
     */
    protected function formatRecordData(array $schema, array $recordData): array
    {
        $formatted = [];
        $fields = $this->getFields($schema);
        
        foreach ($recordData as $fieldName => $value) {
            $fieldConfig = $fields[$fieldName] ?? [];
            $formatted[$fieldName] = $this->formatFieldValue($fieldConfig, $value);
        }
        
        return $formatted;
    }

    /**
     * Format field value based on field type
     */
    protected function formatFieldValue(array $fieldConfig, $value)
    {
        if ($value === null) {
            return null;
        }
        
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