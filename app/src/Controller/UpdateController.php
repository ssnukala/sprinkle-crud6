<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

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

class UpdateController extends Controller
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $recordId = $this->getRecordId($request);
        $this->validateAccess($schema, 'update');
        $this->logger->debug("CRUD6: Updating record ID: {$recordId} for model: {$model}");
        $data = $request->getParsedBody();
        $this->validateInputData($schema, $data);
        try {
            $table = $this->getTableName($schema);
            $primaryKey = $schema['primary_key'] ?? 'id';
            $existingRecord = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            if (!$existingRecord) {
                $errorData = [
                    'error' => 'Record not found',
                    'model' => $model,
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $updateData = $this->prepareUpdateData($schema, $data);
            $affectedRows = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->update($updateData);
            $this->logger->debug("CRUD6: Updated record ID: {$recordId} for model: {$model}", [
                'affected_rows' => $affectedRows,
                'update_data' => $updateData
            ]);
            $updatedRecord = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            $responseData = [
                'message' => "Record updated successfully",
                'model' => $model,
                'id' => $recordId,
                'data' => (array) $updatedRecord
            ];
            $this->alert->addMessageTranslated('success', 'CRUD6.UPDATE.SUCCESS', [
                'model' => $schema['title'] ?? $model
            ]);
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to update record for model: {$model}", [
                'error' => $e->getMessage(),
                'id' => $recordId,
                'data' => $data
            ]);
            $errorData = [
                'error' => 'Failed to update record',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    protected function validateInputData(array $schema, array $data): void
    {
        $rules = $this->getValidationRules($schema);
        if (!empty($rules)) {
            $requestSchema = new RequestSchema($rules);
            $transformer = new RequestDataTransformer($requestSchema);
            $transformedData = $transformer->transform($data);
            $validator = new ServerSideValidator($requestSchema);
            $errors = $validator->validate($transformedData);
            if (count($errors) > 0) {
                throw new \UserFrosting\Framework\Exception\ValidationException($errors);
            }
        }
    }

    protected function prepareUpdateData(array $schema, array $data): array
    {
        $updateData = [];
        $fields = $this->getFields($schema);
        foreach ($data as $fieldName => $value) {
            if (!isset($fields[$fieldName])) {
                continue;
            }
            $fieldConfig = $fields[$fieldName];
            if ($fieldConfig['readonly'] ?? false || $fieldConfig['auto_increment'] ?? false) {
                continue;
            }
            $updateData[$fieldName] = $this->transformFieldValue($fieldConfig, $value);
        }
        if ($schema['timestamps'] ?? false) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }
        return $updateData;
    }

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
                return $value;
            default:
                return (string) $value;
        }
    }
}
