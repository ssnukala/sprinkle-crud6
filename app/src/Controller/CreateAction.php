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
 * Processes the request to create a new CRUD6 model record.
 *
 * Processes the request from the record creation form, checking that:
 * 1. The user has permission to create a new record;
 * 2. The submitted data is valid.
 * This route requires authentication.
 *
 * Request type: POST
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\Group\GroupCreateAction
 */
class CreateAction
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
     * @param CRUD6ModelInterface $crudModel The configured model instance
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $modelName = $this->getModelNameFromRequest($request);
        $schema = $this->schemaService->getSchema($modelName);
        
        $this->validateAccess($modelName, $schema);
        $record = $this->handle($crudModel, $schema, $request);

        // Get a display name for the model
        $modelDisplayName = $this->getModelDisplayName($schema);

        // Write response
        $message = $this->translator->translate('CRUD6.CREATE.SUCCESS', ['model' => $modelDisplayName]);
        $payload = new ApiResponse($message);
        $response->getBody()->write((string) $payload);

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    /**
     * Handle the request.
     *
     * @param CRUD6ModelInterface $crudModel The configured model instance
     * @param array               $schema    The schema configuration
     * @param Request             $request
     *
     * @return CRUD6ModelInterface
     */
    protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
    {
        // Get POST parameters.
        $params = (array) $request->getParsedBody();

        // Load the request schema
        $requestSchema = $this->getRequestSchema($schema);

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($requestSchema, $params);

        // Validate request data
        $this->validateData($requestSchema, $data);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        $this->logger->debug("CRUD6: Creating new record for model: {$schema['model']}", [
            'user' => $currentUser->user_name,
        ]);

        // All checks passed! Log events/activities and create record
        // Begin transaction - DB will be rolled back if an exception occurs
        $record = $this->db->transaction(function () use ($crudModel, $schema, $data, $currentUser) {
            // Prepare insert data
            $insertData = $this->prepareInsertData($schema, $data);
            
            // Insert the record
            $table = $crudModel->getTable();
            $primaryKey = $schema['primary_key'] ?? 'id';
            $insertId = $this->db->table($table)->insertGetId($insertData, $primaryKey);
            
            // Load the created record into the model
            $crudModel = $crudModel->newQuery()->find($insertId);

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($schema);
            $this->userActivityLogger->info("User {$currentUser->user_name} created {$modelDisplayName} record.", [
                'type'    => "crud6_{$schema['model']}_create",
                'user_id' => $currentUser->id,
            ]);

            return $crudModel;
        });

        return $record;
    }

    /**
     * Validate access to the page.
     *
     * @param string $modelName The model name
     * @param array  $schema    The schema configuration
     *
     * @throws ForbiddenException
     */
    protected function validateAccess(string $modelName, array $schema): void
    {
        $permission = $schema['permissions']['create'] ?? "crud6.{$modelName}.create";
        
        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException();
        }
    }

    /**
     * Load the request schema from the CRUD6 schema.
     *
     * @param array $schema The schema configuration
     *
     * @return RequestSchemaInterface
     */
    protected function getRequestSchema(array $schema): RequestSchemaInterface
    {
        $validationRules = $this->getValidationRules($schema);
        $requestSchema = new \UserFrosting\Fortress\RequestSchema($validationRules);

        return $requestSchema;
    }

    /**
     * Validate request POST data.
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
     * @param array $schema The schema configuration
     * 
     * @return array<string, array> Validation rules for each field
     */
    protected function getValidationRules(array $schema): array
    {
        $rules = [];
        foreach ($schema['fields'] as $name => $field) {
            if (isset($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }
        return $rules;
    }

    /**
     * Prepare data for database insertion.
     * 
     * Transforms field values according to their types and applies defaults.
     * Handles timestamps if configured in schema.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The prepared insert data
     */
    protected function prepareInsertData(array $schema, array $data): array
    {
        $insertData = [];
        $fields = $schema['fields'] ?? [];
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
     * Get model name from the request route.
     * 
     * @param Request $request The HTTP request
     * 
     * @return string The model name from route parameters
     */
    protected function getModelNameFromRequest(Request $request): string
    {
        $routeContext = \Slim\Routing\RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $routeParams = $route?->getArguments() ?? [];
        return $routeParams['model'] ?? '';
    }

    /**
     * Get a display name for the model.
     * 
     * @param array $schema The schema configuration
     * 
     * @return string The display name
     */
    protected function getModelDisplayName(array $schema): string
    {
        $modelDisplayName = $schema['title'] ?? ucfirst($schema['model']);
        // If title ends with "Management", extract the entity name
        if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
            $modelDisplayName = $matches[1];
        }
        return $modelDisplayName;
    }
}
