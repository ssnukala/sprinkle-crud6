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
use UserFrosting\Sprinkle\Core\I18n\Translator;

class CreateAction extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected AlertStream $alert,
        protected Translator $translator
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $this->validateAccess($schema, 'create');
        $this->logger->debug("CRUD6: Creating new record for model: {$model}");
        $data = $request->getParsedBody();
        $this->validateInputData($schema, $data);
        try {
            $table = $this->getTableName($schema);
            $insertData = $this->prepareInsertData($schema, $data);
            $insertId = $this->db->table($table)->insertGetId($insertData);
            $this->logger->debug("CRUD6: Created record with ID: {$insertId} for model: {$model}");
            $responseData = [
                'message' => $this->translator->translate('CRUD6.CREATE.SUCCESS', ['model' => $schema['title'] ?? $model]),
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
                'error' => $this->translator->translate('CRUD6.CREATE.ERROR', ['model' => $model]),
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

    protected function prepareInsertData(array $schema, array $data): array
    {
        $insertData = [];
        $fields = $this->getFields($schema);
        foreach ($fields as $fieldName => $fieldConfig) {
            if ($fieldConfig['auto_increment'] ?? false || $fieldConfig['computed'] ?? false) {
                continue;
            }
            if (isset($data[$fieldName])) {
                $insertData[$fieldName] = $this->transformFieldValue($fieldConfig, $data[$fieldName]);
            } elseif (isset($fieldConfig['default'])) {
                $insertData[$fieldName] = $fieldConfig['default'];
            }
        }
        if ($schema['timestamps'] ?? false) {
            $now = date('Y-m-d H:i:s');
            $insertData['created_at'] = $now;
            $insertData['updated_at'] = $now;
        }
        return $insertData;
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
