<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Database\Connection;

class ReadController extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

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
            case 'datetime':
                return $value;
            default:
                return (string) $value;
        }
    }
}
