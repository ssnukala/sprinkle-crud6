<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Util\ApiResponse;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Processes the request to read or update an existing CRUD6 model record.
 *
 * For GET requests: Returns the record data for viewing or editing.
 * For PUT requests: Updates the record with new data.
 * 
 * Processes the request from the record update form, checking that:
 * 1. The user has the necessary permissions to update the posted field(s);
 * 2. The submitted data is valid.
 * This route requires authentication.
 *
 * Request type: GET (read) or PUT (update)
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\Group\GroupEditAction
 */
class EditAction
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected Translator $translator,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected SchemaService $schemaService,
        protected UserActivityLogger $userActivityLogger,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
    ) {
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $method = $request->getMethod();
        
        // Handle GET request (read operation)
        if ($method === 'GET') {
            return $this->handleRead($crudSchema, $crudModel, $request, $response);
        }
        
        // Handle PUT request (update operation)
        if ($method === 'PUT') {
            return $this->handleUpdate($crudSchema, $crudModel, $request, $response);
        }
        
        // Method not allowed
        $response->getBody()->write(json_encode(['error' => 'Method not allowed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(405);
    }

    /**
     * Handle GET request to read a record.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Request             $request
     * @param Response            $response
     *
     * @return Response
     */
    protected function handleRead(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->logger->debug("CRUD6: Read request for record ID: {$recordId} model: {$crudSchema['model']}");

        try {
            // Get a display name for the model
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            
            $responseData = [
                'message' => $this->translator->translate('CRUD6.EDIT.SUCCESS', ['model' => $modelDisplayName]),
                'model' => $crudSchema['model'],
                'modelDisplayName' => $modelDisplayName,
                'id' => $recordId,
                'data' => $crudModel->toArray()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to read record for model: {$crudSchema['model']}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);

            $errorData = [
                'error' => $this->translator->translate('CRUD6.EDIT.ERROR', ['model' => $crudSchema['model']]),
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Handle PUT request to update a record.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Request             $request
     * @param Response            $response
     *
     * @return Response
     */
    protected function handleUpdate(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $this->validateAccess($crudSchema);
        $updatedModel = $this->handle($crudSchema, $crudModel, $request);

        // Get a display name for the model
        $modelDisplayName = $this->getModelDisplayName($crudSchema);

        // Message
        $message = $this->translator->translate('CRUD6.UPDATE.SUCCESS', ['model' => $modelDisplayName]);

        // Write response
        $payload = new ApiResponse($message);
        $response->getBody()->write((string) $payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the update request.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Request             $request
     *
     * @return CRUD6ModelInterface
     */
    protected function handle(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request): CRUD6ModelInterface
    {
        // Get PUT parameters
        $params = (array) $request->getParsedBody();

        // Load the request schema
        $requestSchema = $this->getRequestSchema($crudSchema);

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($requestSchema, $params);

        // Validate request data
        $this->validateData($requestSchema, $data);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->logger->debug("CRUD6: Update request for record ID: {$recordId} model: {$crudSchema['model']}", [
            'user' => $currentUser->user_name,
        ]);

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($crudSchema, $crudModel, $data, $currentUser, $recordId) {
            // Prepare update data
            $updateData = $this->prepareUpdateData($crudSchema, $data);
            
            // Update the record using query builder
            $table = $crudModel->getTable();
            $primaryKey = $crudSchema['primary_key'] ?? 'id';
            $this->db->table($table)->where($primaryKey, $recordId)->update($updateData);

            // Reload the model to get updated data
            $crudModel->refresh();

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            $this->userActivityLogger->info("User {$currentUser->user_name} updated {$modelDisplayName} record.", [
                'type'    => "crud6_{$crudSchema['model']}_update",
                'user_id' => $currentUser->id,
            ]);
        });

        return $crudModel;
    }

    /**
     * Validate access to the page.
     *
     * @param array $crudSchema The schema configuration
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(array $crudSchema): void
    {
        $permission = $crudSchema['permissions']['edit'] ?? "crud6.{$crudSchema['model']}.edit";
        
        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException();
        }
    }

    /**
     * Load the request schema from the CRUD6 schema.
     *
     * @param array $crudSchema The schema configuration
     *
     * @return RequestSchemaInterface
     */
    protected function getRequestSchema(array $crudSchema): RequestSchemaInterface
    {
        $validationRules = $this->getValidationRules($crudSchema);
        $requestSchema = new \UserFrosting\Fortress\RequestSchema($validationRules);

        return $requestSchema;
    }

    /**
     * Validate request PUT data.
     *
     * @param RequestSchemaInterface $schema
     * @param mixed[]                $data
     */
    protected function validateData(RequestSchemaInterface $schema, array $data): void
    {
        $errors = $this->validator->validate($schema, $data);
        if (count($errors) !== 0) {
            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }
    }

    /**
     * Get validation rules from the schema.
     * 
     * @param array $crudSchema The schema configuration
     * 
     * @return array<string, array> Validation rules for each field
     */
    protected function getValidationRules(array $crudSchema): array
    {
        $rules = [];
        foreach ($crudSchema['fields'] as $name => $field) {
            if (isset($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }
        return $rules;
    }

    /**
     * Prepare data for database update.
     * 
     * Transforms field values according to their types and filters out non-editable fields.
     * Handles timestamps if configured in schema.
     * 
     * @param array $crudSchema The schema configuration
     * @param array $data       The input data
     * 
     * @return array The prepared update data
     */
    protected function prepareUpdateData(array $crudSchema, array $data): array
    {
        $updateData = [];
        $fields = $crudSchema['fields'] ?? [];
        
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
        if ($crudSchema['timestamps'] ?? false) {
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
    protected function transformFieldValue(array $fieldConfig, $value): mixed
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

    /**
     * Get a display name for the model.
     * 
     * @param array $crudSchema The schema configuration
     * 
     * @return string The display name
     */
    protected function getModelDisplayName(array $crudSchema): string
    {
        $modelDisplayName = $crudSchema['title'] ?? ucfirst($crudSchema['model']);
        // If title ends with "Management", extract the entity name
        if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
            $modelDisplayName = $matches[1];
        }
        return $modelDisplayName;
    }
}
