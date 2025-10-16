<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\I18n\Translator;
use Illuminate\Database\Connection;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\ServerSideValidator;

/**
 * Update action for CRUD6 models.
 * 
 * Handles updating a single record for any CRUD6 model.
 * Validates input data and updates the record in the database.
 * Follows the UserFrosting 6 action controller pattern from sprinkle-admin.
 * 
 * Route: PUT /api/crud6/{model}/{id}
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserUpdateAction
 */
class UpdateAction extends Base
{
    /**
     * Constructor for UpdateAction.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger
     * @param Translator           $translator    Translator for i18n messages
     * @param Connection           $db            Database connection
     * @param AlertStream          $alert         Alert stream for user notifications
     * @param SchemaService        $schemaService Schema service
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected Connection $db,
        protected AlertStream $alert,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Invoke the update action.
     * 
     * Updates an existing record in the database for the specified model.
     * Validates input data and handles timestamps if configured.
     * 
     * @param array                  $crudSchema The schema configuration
     * @param CRUD6ModelInterface    $crudModel  The configured model instance with record loaded
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with update result or error
     * 
     * @throws \UserFrosting\Framework\Exception\ValidationException On validation failure
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);

        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);
        
        $this->validateAccess($crudSchema['model'], 'edit');
        
        $this->logger->debug("CRUD6: Update request for record ID: {$recordId} model: {$crudSchema['model']}");

        $data = $request->getParsedBody();
        $this->validateInputData($crudSchema['model'], $data);

        try {
            $table = $crudModel->getTable();
            $updateData = $this->prepareUpdateData($crudSchema, $data);
            
            $this->db->table($table)->where($primaryKey, $recordId)->update($updateData);

            $this->logger->debug("CRUD6: Updated record ID: {$recordId} for model: {$crudSchema['model']}");

            // Get a display name for the model
            $modelDisplayName = $crudSchema['title'] ?? ucfirst($crudSchema['model']);
            if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
                $modelDisplayName = $matches[1];
            }

            // Return response with title and description fields expected by frontend
            $responseData = [
                'title' => $this->translator->translate('CRUD6.UPDATE.SUCCESS_TITLE'),
                'description' => $this->translator->translate('CRUD6.UPDATE.SUCCESS', ['model' => $modelDisplayName]),
                'message' => $this->translator->translate('CRUD6.UPDATE.SUCCESS', ['model' => $modelDisplayName]),
                'model' => $crudSchema['model'],
                'id' => $recordId
            ];

            $this->alert->addMessageTranslated('success', 'CRUD6.UPDATE.SUCCESS', [
                'model' => $modelDisplayName
            ]);

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to update record for model: {$crudSchema['model']}", [
                'error' => $e->getMessage(),
                'id' => $recordId,
                'data' => $data
            ]);

            $errorData = [
                'title' => $this->translator->translate('CRUD6.UPDATE.ERROR_TITLE'),
                'description' => $this->translator->translate('CRUD6.UPDATE.ERROR', ['model' => $crudSchema['model']]),
                'error' => $this->translator->translate('CRUD6.UPDATE.ERROR', ['model' => $crudSchema['model']]),
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Validate input data against schema rules.
     * 
     * @param string $modelName The model name
     * @param array  $data      The input data to validate
     * 
     * @return void
     * 
     * @throws \UserFrosting\Framework\Exception\ValidationException On validation failure
     */
    protected function validateInputData(string $modelName, array $data): void
    {
        $rules = $this->getValidationRules($modelName);
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

    /**
     * Prepare data for database update.
     * 
     * Transforms field values according to their types and filters out non-editable fields.
     * Handles timestamps if configured in schema.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The prepared update data
     */
    protected function prepareUpdateData(array $schema, array $data): array
    {
        $updateData = [];
        $fields = $schema['fields'] ?? [];
        
        foreach ($fields as $fieldName => $fieldConfig) {
            // Skip auto-increment, computed, and non-editable fields
            if ($fieldConfig['auto_increment'] ?? false) {
                continue;
            }
            if ($fieldConfig['computed'] ?? false) {
                continue;
            }
            if (($fieldConfig['editable'] ?? true) === false) {
                continue;
            }
            
            if (isset($data[$fieldName])) {
                $updateData[$fieldName] = $this->transformFieldValue($fieldConfig, $data[$fieldName]);
            }
        }
        
        // Update timestamp if configured
        if ($schema['timestamps'] ?? false) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $updateData;
    }

    /**
     * Transform field value based on its type.
     * 
     * Converts values to appropriate PHP/database types based on field configuration.
     * 
     * @param array $fieldConfig Field configuration from schema
     * @param mixed $value       The value to transform
     * 
     * @return mixed The transformed value
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
                return $value;
            default:
                return (string) $value;
        }
    }
}
