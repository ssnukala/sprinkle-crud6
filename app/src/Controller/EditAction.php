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
use UserFrosting\Sprinkle\Account\Authenticate\Hasher;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Util\ApiResponse;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\ProcessesRelationshipActions;
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
class EditAction extends Base
{
    use ProcessesRelationshipActions;
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
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        
        $method = $request->getMethod();
        
        $this->debugLog("CRUD6 [EditAction] ===== REQUEST START =====", [
            'model' => $crudSchema['model'],
            'method' => $method,
            'uri' => (string) $request->getUri(),
            'record_id' => $crudModel->getAttribute($crudSchema['primary_key'] ?? 'id'),
        ]);

        try {
            // Handle GET request (read operation)
            if ($method === 'GET') {
                $this->debugLog("CRUD6 [EditAction] Processing GET request (read)", [
                    'model' => $crudSchema['model'],
                ]);
                return $this->handleRead($crudSchema, $crudModel, $request, $response);
            }
            
            // Handle PUT request (update operation)
            if ($method === 'PUT') {
                $this->debugLog("CRUD6 [EditAction] Processing PUT request (update)", [
                    'model' => $crudSchema['model'],
                ]);
                return $this->handleUpdate($crudSchema, $crudModel, $request, $response);
            }
            
            // Method not allowed
            $this->logger->warning("CRUD6 [EditAction] Method not allowed", [
                'model' => $crudSchema['model'],
                'method' => $method,
                'allowed_methods' => ['GET', 'PUT'],
            ]);

            $response->getBody()->write(json_encode(['error' => 'Method not allowed']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(405);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] ===== REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'method' => $method,
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

        $this->debugLog("CRUD6 [EditAction] Read request for record", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'primary_key' => $primaryKey,
        ]);

        try {
            // Get a display name for the model
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            
            $recordData = $crudModel->toArray();
            
            $this->debugLog("CRUD6 [EditAction] Record data retrieved", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'data_keys' => array_keys($recordData),
                'data_count' => count($recordData),
                'data' => $recordData,
            ]);
            
            // Load relationship details if defined in schema
            $details = [];
            if (isset($crudSchema['details']) && is_array($crudSchema['details'])) {
                $details = $this->loadDetailsFromSchema($crudSchema, $crudModel, $recordId);
            }
            
            $responseData = [
                'message' => $this->translator->translate('CRUD6.EDIT.SUCCESS', ['model' => $modelDisplayName]),
                'model' => $crudSchema['model'],
                'modelDisplayName' => $modelDisplayName,
                'id' => $recordId,
                'data' => $recordData
            ];
            
            // Add details to response if loaded
            if (!empty($details)) {
                $responseData['details'] = $details;
            }

            $this->debugLog("CRUD6 [EditAction] Read response prepared", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'response_keys' => array_keys($responseData),
            ]);

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] Failed to read record", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
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
        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->debugLog("CRUD6 [EditAction] Update request starting", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
        ]);

        $this->validateAccess($crudSchema, 'edit');
        
        $this->debugLog("CRUD6 [EditAction] Access validated for update", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
        ]);

        $updatedModel = $this->handle($crudSchema, $crudModel, $request);

        // Get a display name for the model
        $modelDisplayName = $this->getModelDisplayName($crudSchema);

        // Write response with title and description
        $title = $this->translator->translate('CRUD6.UPDATE.SUCCESS_TITLE');
        $description = $this->translator->translate('CRUD6.UPDATE.SUCCESS', ['model' => $modelDisplayName]);
        $payload = new ApiResponse($title, $description);
        
        $this->debugLog("CRUD6 [EditAction] Update response prepared", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'title' => $title,
            'description' => $description,
        ]);

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
        
        $this->debugLog("CRUD6 [EditAction] Update parameters received", [
            'model' => $crudSchema['model'],
            'params' => $params,
            'param_count' => count($params),
        ]);

        // Load the request schema
        $requestSchema = $this->getRequestSchema($crudSchema);

        // Whitelist and set parameter defaults
        $data = $this->transformer->transform($requestSchema, $params);
        
        $this->debugLog("CRUD6 [EditAction] Data transformed", [
            'model' => $crudSchema['model'],
            'transformed_data' => $data,
        ]);

        // Validate request data
        $this->validateData($requestSchema, $data);
        
        $this->debugLog("CRUD6 [EditAction] Data validation passed", [
            'model' => $crudSchema['model'],
        ]);

        // Get current user. Won't be null, as AuthGuard prevent it
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->debugLog("CRUD6 [EditAction] Starting database update", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'user' => $currentUser->user_name,
            'user_id' => $currentUser->id,
        ]);

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($crudSchema, $crudModel, $data, $currentUser, $recordId) {
            // Prepare update data
            $updateData = $this->prepareUpdateData($crudSchema, $data);
            
            $this->debugLog("CRUD6 [EditAction] Update data prepared", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'update_data' => $updateData,
                'table' => $crudModel->getTable(),
            ]);
            
            // Update the record using query builder
            $table = $crudModel->getTable();
            $primaryKey = $crudSchema['primary_key'] ?? 'id';
            $affected = $this->db->table($table)->where($primaryKey, $recordId)->update($updateData);
            
            $this->debugLog("CRUD6 [EditAction] Database update executed", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'table' => $table,
                'affected_rows' => $affected,
            ]);

            // Reload the model to get updated data
            $crudModel->refresh();
            
            $this->debugLog("CRUD6 [EditAction] Model refreshed after update", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'updated_data' => $crudModel->toArray(),
            ]);

            // Process relationship actions for on_update event
            $this->processRelationshipActions($crudModel, $crudSchema, $data, 'on_update');

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            $this->userActivityLogger->info("User {$currentUser->user_name} updated {$modelDisplayName} record.", [
                'type'    => "crud6_{$crudSchema['model']}_update",
                'user_id' => $currentUser->id,
            ]);
        });
        
        $this->debugLog("CRUD6 [EditAction] Transaction completed successfully", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
        ]);

        return $crudModel;
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
        $requestSchema = new RequestSchema($validationRules);

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
        $this->debugLog("CRUD6 [EditAction] Starting validation", [
            'data' => $data,
        ]);

        $errors = $this->validator->validate($schema, $data);
        if (count($errors) !== 0) {
            $this->logger->error("CRUD6 [EditAction] Validation failed", [
                'errors' => $errors,
                'error_count' => count($errors),
            ]);

            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }

        $this->debugLog("CRUD6 [EditAction] Validation successful", [
            'data_validated' => true,
        ]);
    }

    /**
     * Hash password fields in the data.
     * 
     * Iterates through schema fields and hashes any field with type 'password'
     * using UserFrosting's Hasher service before storing to database.
     * Only hashes non-empty password values to support optional password updates.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The data with password fields hashed
     */
    protected function hashPasswordFields(array $schema, array $data): array
    {
        $fields = $schema['fields'] ?? [];
        
        foreach ($fields as $fieldName => $fieldConfig) {
            // Check if field is a password type and has a value in the data
            if (($fieldConfig['type'] ?? '') === 'password' && isset($data[$fieldName]) && !empty($data[$fieldName])) {
                // Hash the password using UserFrosting's Hasher service
                $data[$fieldName] = $this->hasher->hash($data[$fieldName]);
                
                $this->debugLog("CRUD6 [EditAction] Password field hashed", [
                    'field' => $fieldName,
                ]);
            }
        }
        
        return $data;
    }

    /**
     * Load relationship details from schema configuration.
     * 
     * Parses the details section from JSON schema and queries many_to_many relationships.
     * Applies field filtering (list_fields) and returns formatted response.
     * 
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param mixed               $recordId   The record ID
     * 
     * @return array Array of details data keyed by relationship name
     */
    protected function loadDetailsFromSchema(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId): array
    {
        $details = [];
        $detailsConfig = $crudSchema['details'] ?? [];
        $relationships = $crudSchema['relationships'] ?? [];
        
        $this->debugLog("CRUD6 [EditAction] Loading details from schema", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'details_count' => count($detailsConfig),
            'relationships_count' => count($relationships),
        ]);
        
        // Build a lookup map of relationships by name for quick access
        $relationshipMap = [];
        foreach ($relationships as $rel) {
            if (isset($rel['name'])) {
                $relationshipMap[$rel['name']] = $rel;
            }
        }
        
        // Process each detail configuration
        foreach ($detailsConfig as $detailConfig) {
            $relatedModel = $detailConfig['model'] ?? null;
            $listFields = $detailConfig['list_fields'] ?? [];
            $title = $detailConfig['title'] ?? ucfirst($relatedModel);
            
            if (!$relatedModel) {
                $this->debugLog("CRUD6 [EditAction] Skipping detail with no model", [
                    'detail_config' => $detailConfig,
                ]);
                continue;
            }
            
            // Find the corresponding relationship configuration
            $relationship = $relationshipMap[$relatedModel] ?? null;
            
            if (!$relationship) {
                $this->debugLog("CRUD6 [EditAction] No relationship found for detail", [
                    'related_model' => $relatedModel,
                ]);
                continue;
            }
            
            // Query the relationship based on type
            $rows = $this->queryRelationship($crudSchema, $crudModel, $recordId, $relationship, $listFields);
            
            $details[$relatedModel] = [
                'title' => $title,
                'rows' => $rows,
                'count' => count($rows),
            ];
            
            $this->debugLog("CRUD6 [EditAction] Loaded detail", [
                'related_model' => $relatedModel,
                'row_count' => count($rows),
                'title' => $title,
            ]);
        }
        
        return $details;
    }

    /**
     * Query a relationship to get related records.
     * 
     * Supports many_to_many relationships through pivot tables.
     * Applies field filtering and returns array of related records.
     * 
     * @param array               $crudSchema    The schema configuration
     * @param CRUD6ModelInterface $crudModel     The configured model instance
     * @param mixed               $recordId      The record ID
     * @param array               $relationship  The relationship configuration
     * @param array               $listFields    Fields to include in results
     * 
     * @return array Array of related records
     */
    protected function queryRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, array $relationship, array $listFields): array
    {
        $type = $relationship['type'] ?? null;
        $relatedModel = $relationship['name'] ?? null;
        
        $this->debugLog("CRUD6 [EditAction] Querying relationship", [
            'type' => $type,
            'related_model' => $relatedModel,
            'record_id' => $recordId,
            'list_fields' => $listFields,
        ]);
        
        // For now, support many_to_many relationships
        if ($type === 'many_to_many') {
            return $this->queryManyToManyRelationship($crudSchema, $crudModel, $recordId, $relationship, $listFields);
        }
        
        // TODO: Support other relationship types (belongs_to_many_through, etc.)
        $this->debugLog("CRUD6 [EditAction] Unsupported relationship type", [
            'type' => $type,
            'related_model' => $relatedModel,
        ]);
        
        return [];
    }

    /**
     * Query a many_to_many relationship through a pivot table.
     * 
     * @param array               $crudSchema    The schema configuration
     * @param CRUD6ModelInterface $crudModel     The configured model instance
     * @param mixed               $recordId      The record ID
     * @param array               $relationship  The relationship configuration
     * @param array               $listFields    Fields to include in results
     * 
     * @return array Array of related records
     */
    protected function queryManyToManyRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, array $relationship, array $listFields): array
    {
        $pivotTable = $relationship['pivot_table'] ?? null;
        $foreignKey = $relationship['foreign_key'] ?? null;
        $relatedKey = $relationship['related_key'] ?? null;
        $relatedModel = $relationship['name'] ?? null;
        
        if (!$pivotTable || !$foreignKey || !$relatedKey || !$relatedModel) {
            $this->logger->error("CRUD6 [EditAction] Invalid many_to_many relationship configuration", [
                'relationship' => $relationship,
            ]);
            return [];
        }
        
        try {
            // Load the related model's schema to get the table name
            $relatedSchema = $this->schemaService->getSchema($relatedModel);
            $relatedTable = $relatedSchema['table'] ?? $relatedModel;
            $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
            
            $this->debugLog("CRUD6 [EditAction] Query many_to_many relationship", [
                'pivot_table' => $pivotTable,
                'foreign_key' => $foreignKey,
                'related_key' => $relatedKey,
                'related_table' => $relatedTable,
                'related_primary_key' => $relatedPrimaryKey,
                'list_fields' => $listFields,
            ]);
            
            // Build the query
            // SELECT related_table.* FROM related_table
            // INNER JOIN pivot_table ON pivot_table.related_key = related_table.id
            // WHERE pivot_table.foreign_key = recordId
            
            $query = $this->db->table($relatedTable)
                ->join($pivotTable, "{$pivotTable}.{$relatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
                ->where("{$pivotTable}.{$foreignKey}", $recordId);
            
            // Apply field filtering if list_fields is specified
            if (!empty($listFields)) {
                // Add the primary key if not in list
                if (!in_array($relatedPrimaryKey, $listFields)) {
                    $listFields[] = $relatedPrimaryKey;
                }
                
                // Prefix table name to fields
                $selectFields = array_map(function($field) use ($relatedTable) {
                    return "{$relatedTable}.{$field}";
                }, $listFields);
                
                $query->select($selectFields);
            } else {
                // Select all fields from related table
                $query->select("{$relatedTable}.*");
            }
            
            $results = $query->get();
            
            $this->debugLog("CRUD6 [EditAction] Many_to_many query executed", [
                'related_model' => $relatedModel,
                'record_id' => $recordId,
                'row_count' => count($results),
            ]);
            
            // Convert to array
            return json_decode(json_encode($results), true);
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] Failed to query many_to_many relationship", [
                'relationship' => $relationship,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}
