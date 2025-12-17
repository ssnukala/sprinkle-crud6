<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Config\Config;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authenticate\Hasher;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\HashesPasswords;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\ProcessesRelationshipActions;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\TransformsData;
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
    use ProcessesRelationshipActions;
    use TransformsData;
    use HashesPasswords;
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService,
        protected Config $config,
        protected Translator $translator,
        protected Connection $db,
        protected UserActivityLogger $userActivityLogger,
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected Hasher $hasher,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param array               $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface $crudModel  The configured model instance (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        
        $this->debugLog("CRUD6 [CreateAction] ===== CREATE REQUEST START =====", [
            'model' => $crudSchema['model'],
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        try {
            $this->validateAccess($crudSchema, 'create');
            $this->debugLog("CRUD6 [CreateAction] Access validated for create operation", [
                'model' => $crudSchema['model'],
            ]);

            $record = $this->handle($crudModel, $crudSchema, $request);

            // Get a display name for the model
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            
            // Translate the model display name if it's a translation key
            $translatedModel = $this->translator->translate($modelDisplayName);

            // Write response with title and description
            $title = $this->translator->translate('CRUD6.CREATE.SUCCESS_TITLE');
            $description = $this->translator->translate('CRUD6.CREATE.SUCCESS', ['model' => $translatedModel]);
            
            // Include the created record data in response
            $primaryKey = $crudSchema['primary_key'] ?? 'id';
            $recordData = $record->toArray();
            
            $this->debugLog("CRUD6 [CreateAction] Response prepared successfully", [
                'model' => $crudSchema['model'],
                'title' => $title,
                'description' => $description,
                'status' => 201,
                'record_id' => $record->{$primaryKey} ?? null,
            ]);

            // Return response with title, description, AND created record data
            $payload = [
                'title' => $title,
                'description' => $description,
                'data' => $recordData,
            ];
            
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (ForbiddenException $e) {
            // Let ForbiddenException bubble up to framework's error handler
            // which provides the proper translated permission error message
            throw $e;
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [CreateAction] ===== CREATE REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while creating the record', 500);
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
        
        $this->debugLog("CRUD6 [CreateAction] Request parameters received", [
            'model' => $schema['model'],
            'params' => $params,
            'param_count' => count($params),
        ]);

        // Transform and validate data using TransformsData trait
        $data = $this->transformAndValidate($schema, $params);
        
        $this->debugLog("CRUD6 [CreateAction] Data transformed and validated", [
            'model' => $schema['model'],
            'data' => $data,
        ]);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        $this->debugLog("CRUD6 [CreateAction] Creating new record for model", [
            'model' => $schema['model'],
            'user' => $currentUser->user_name,
            'user_id' => $currentUser->id,
        ]);

        // All checks passed! Log events/activities and create record
        // Begin transaction - DB will be rolled back if an exception occurs
        $record = $this->db->transaction(function () use ($crudModel, $schema, $data, $currentUser) {
            // Prepare insert data
            $insertData = $this->prepareInsertData($schema, $data);
            
            $this->debugLog("CRUD6 [CreateAction] Insert data prepared", [
                'model' => $schema['model'],
                'insert_data' => $insertData,
                'table' => $crudModel->getTable(),
            ]);
            
            // Insert the record
            $table = $crudModel->getTable();
            $primaryKey = $schema['primary_key'] ?? 'id';
            $insertId = $this->db->table($table)->insertGetId($insertData, $primaryKey);
            
            // Load the created record into the model
            $crudModel = $crudModel->newQuery()->find($insertId);

            // Process relationship actions for on_create event
            $this->processRelationshipActions($crudModel, $schema, $data, 'on_create');

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($schema);
            $this->userActivityLogger->info("User {$currentUser->user_name} created {$modelDisplayName} record.", [
                'type'    => "crud6_{$schema['model']}_create",
                'user_id' => $currentUser->id,
            ]);

            return $crudModel;
        });
        
        $this->debugLog("CRUD6 [CreateAction] Transaction completed successfully", [
            'model' => $schema['model'],
        ]);

        return $record;
    }
}
