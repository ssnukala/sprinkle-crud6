<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
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
class CreateAction extends Base
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService,
        protected Translator $translator,
        protected Connection $db,
        protected UserActivityLogger $userActivityLogger,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param CRUD6ModelInterface $crudModel  The configured model instance (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        // Get schema from request attribute (set by CRUD6Injector middleware)
        $crudSchema = $request->getAttribute('crudSchema');
        
        $this->logger->debug("CRUD6 [CreateAction] ===== CREATE REQUEST START =====", [
            'model' => $crudSchema['model'],
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        try {
            $this->validateAccess($crudSchema, 'create');
            $this->logger->debug("CRUD6 [CreateAction] Access validated for create operation", [
                'model' => $crudSchema['model'],
            ]);

            $record = $this->handle($crudModel, $crudSchema, $request);

            // Get a display name for the model
            $modelDisplayName = $this->getModelDisplayName($crudSchema);

            // Write response with title and description
            $title = $this->translator->translate('CRUD6.CREATE.SUCCESS_TITLE');
            $description = $this->translator->translate('CRUD6.CREATE.SUCCESS', ['model' => $modelDisplayName]);
            $payload = new ApiResponse($title, $description);
            
            $this->logger->debug("CRUD6 [CreateAction] Response prepared successfully", [
                'model' => $crudSchema['model'],
                'title' => $title,
                'description' => $description,
                'status' => 201,
            ]);

            $response->getBody()->write((string) $payload);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [CreateAction] ===== CREATE REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
        
        $this->logger->debug("CRUD6 [CreateAction] Request parameters received", [
            'model' => $schema['model'],
            'params' => $params,
            'param_count' => count($params),
        ]);

        // Load the request schema
        $requestSchema = $this->getRequestSchema($schema);

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($requestSchema, $params);
        
        $this->logger->debug("CRUD6 [CreateAction] Data transformed", [
            'model' => $schema['model'],
            'transformed_data' => $data,
        ]);

        // Validate request data
        $this->validateData($requestSchema, $data);
        
        $this->logger->debug("CRUD6 [CreateAction] Data validation passed", [
            'model' => $schema['model'],
        ]);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        $this->logger->debug("CRUD6 [CreateAction] Creating new record for model", [
            'model' => $schema['model'],
            'user' => $currentUser->user_name,
            'user_id' => $currentUser->id,
        ]);

        // All checks passed! Log events/activities and create record
        // Begin transaction - DB will be rolled back if an exception occurs
        $record = $this->db->transaction(function () use ($crudModel, $schema, $data, $currentUser) {
            // Prepare insert data
            $insertData = $this->prepareInsertData($schema, $data);
            
            $this->logger->debug("CRUD6 [CreateAction] Insert data prepared", [
                'model' => $schema['model'],
                'insert_data' => $insertData,
                'table' => $crudModel->getTable(),
            ]);
            
            // Insert the record
            $table = $crudModel->getTable();
            $primaryKey = $schema['primary_key'] ?? 'id';
            $insertId = $this->db->table($table)->insertGetId($insertData, $primaryKey);
            
            $this->logger->debug("CRUD6 [CreateAction] Record inserted into database", [
                'model' => $schema['model'],
                'table' => $table,
                'insert_id' => $insertId,
                'primary_key' => $primaryKey,
            ]);
            
            // Load the created record into the model
            $crudModel = $crudModel->newQuery()->find($insertId);
            
            $this->logger->debug("CRUD6 [CreateAction] Created record loaded from database", [
                'model' => $schema['model'],
                'record_data' => $crudModel ? $crudModel->toArray() : null,
            ]);

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($schema);
            $this->userActivityLogger->info("User {$currentUser->user_name} created {$modelDisplayName} record.", [
                'type'    => "crud6_{$schema['model']}_create",
                'user_id' => $currentUser->id,
            ]);

            return $crudModel;
        });
        
        $this->logger->debug("CRUD6 [CreateAction] Transaction completed successfully", [
            'model' => $schema['model'],
        ]);

        return $record;
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
        $requestSchema = new RequestSchema($validationRules);

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
        $this->logger->debug("CRUD6 [CreateAction] Starting validation", [
            'data' => $data,
        ]);

        $errors = $this->validator->validate($schema, $data);
        if (count($errors) !== 0) {
            $this->logger->error("CRUD6 [CreateAction] Validation failed", [
                'errors' => $errors,
                'error_count' => count($errors),
            ]);

            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }

        $this->logger->debug("CRUD6 [CreateAction] Validation successful", [
            'data_validated' => true,
        ]);
    }
}
