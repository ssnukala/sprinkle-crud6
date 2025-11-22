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
        
        // Log edit action requests
        $this->debugLog("CRUD6 [EditAction] Request received", [
            'method' => $method,
            'uri' => (string) $request->getUri(),
            'model' => $crudSchema['model'],
            'record_id' => $crudModel->getAttribute($crudSchema['primary_key'] ?? 'id'),
        ]);
        
        $this->debugLog("CRUD6 [EditAction] ===== REQUEST START =====", [
            'model' => $crudSchema['model'],
            'method' => $method,
            'uri' => (string) $request->getUri(),
            'record_id' => $crudModel->getAttribute($crudSchema['primary_key'] ?? 'id'),
        ]);

        try {
            // Handle GET request (read operation)
            if ($method === 'GET') {
                $this->debugLog("CRUD6 [EditAction] Processing GET request", [
                    'model' => $crudSchema['model'],
                    'record_id' => $crudModel->getAttribute($crudSchema['primary_key'] ?? 'id'),
                ]);
                return $this->handleRead($crudSchema, $crudModel, $request, $response);
            }
            
            // Handle PUT request (update operation)
            if ($method === 'PUT') {
                $this->debugLog("CRUD6 [EditAction] Processing PUT request", [
                    'model' => $crudSchema['model'],
                    'record_id' => $crudModel->getAttribute($crudSchema['primary_key'] ?? 'id'),
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
     * Phase 3 Optimization: Uses schema consolidation to batch-load all related schemas
     * in a single call, minimizing database queries. Implements comprehensive error handling 
     * to ensure partial failures don't break the entire response.
     * 
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param mixed               $recordId   The record ID
     * 
     * @return array Array of details data keyed by relationship name
     * 
     * @throws \RuntimeException If critical errors occur during relationship loading
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
        
        // Early return if no details configured
        if (empty($detailsConfig)) {
            return $details;
        }
        
        // Build a lookup map of relationships by name for quick access
        $relationshipMap = [];
        foreach ($relationships as $rel) {
            if (isset($rel['name'])) {
                $relationshipMap[$rel['name']] = $rel;
            }
        }
        
        // Phase 3 Optimization: Pre-load all related schemas in a single batch
        // This avoids multiple getSchema() calls and uses the schema consolidation feature
        $relatedSchemas = $this->schemaService->loadRelatedSchemas($crudSchema, 'list');
        
        $this->debugLog("CRUD6 [EditAction] Pre-loaded related schemas", [
            'count' => count($relatedSchemas),
            'models' => array_keys($relatedSchemas),
        ]);
        
        // Process each detail configuration
        foreach ($detailsConfig as $detailConfig) {
            $relatedModel = $detailConfig['model'] ?? null;
            $listFields = $detailConfig['list_fields'] ?? [];
            $title = $detailConfig['title'] ?? ucfirst($relatedModel);
            $foreignKey = $detailConfig['foreign_key'] ?? null;
            
            if (!$relatedModel) {
                $this->debugLog("CRUD6 [EditAction] Skipping detail with no model", [
                    'detail_config' => $detailConfig,
                ]);
                continue;
            }
            
            // Query the detail with comprehensive error handling
            try {
                $rows = [];
                
                // Check if this is a has-many relationship via foreign_key
                if ($foreignKey !== null) {
                    // This is a has-many relationship (e.g., activities where user_id = current_user.id)
                    $this->debugLog("CRUD6 [EditAction] Loading has-many relationship via foreign_key", [
                        'related_model' => $relatedModel,
                        'foreign_key' => $foreignKey,
                        'record_id' => $recordId,
                    ]);
                    
                    // Get pre-loaded schema or null if not available
                    $relatedSchema = $relatedSchemas[$relatedModel] ?? null;
                    
                    // Query has-many relationship
                    $rows = $this->queryHasManyRelationship($crudSchema, $crudModel, $recordId, $relatedModel, $foreignKey, $listFields, $relatedSchema);
                } else {
                    // This is a many-to-many relationship through pivot table
                    // Find the corresponding relationship configuration
                    $relationship = $relationshipMap[$relatedModel] ?? null;
                    
                    if (!$relationship) {
                        $this->logger->warning("CRUD6 [EditAction] No relationship or foreign_key found for detail", [
                            'related_model' => $relatedModel,
                            'available_relationships' => array_keys($relationshipMap),
                            'detail_config' => $detailConfig,
                        ]);
                        continue;
                    }
                    
                    // Get pre-loaded schema or null if not available
                    $relatedSchema = $relatedSchemas[$relatedModel] ?? null;
                    
                    // Query the relationship
                    $rows = $this->queryRelationship($crudSchema, $crudModel, $recordId, $relationship, $listFields, $relatedSchema);
                }
                
                $details[$relatedModel] = [
                    'title' => $title,
                    'rows' => $rows,
                    'count' => count($rows),
                ];
                
                $this->debugLog("CRUD6 [EditAction] Loaded detail successfully", [
                    'related_model' => $relatedModel,
                    'row_count' => count($rows),
                    'title' => $title,
                    'via_foreign_key' => $foreignKey !== null,
                ]);
            } catch (\Exception $e) {
                // Log error but continue loading other relationships
                $this->logger->error("CRUD6 [EditAction] Failed to load detail", [
                    'related_model' => $relatedModel,
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                ]);
                
                // Include empty result to maintain consistency
                $details[$relatedModel] = [
                    'title' => $title,
                    'rows' => [],
                    'count' => 0,
                    'error' => 'Failed to load relationship data',
                ];
            }
        }
        
        return $details;
    }

    /**
     * Query a relationship to get related records.
     * 
     * Routes to the appropriate query method based on relationship type.
     * Supports many_to_many and belongs_to_many_through relationships.
     * Applies field filtering and returns array of related records.
     * 
     * Phase 3: Enhanced with comprehensive error handling, validation, and
     * schema consolidation optimization (accepts pre-loaded schema).
     * 
     * @param array               $crudSchema     The schema configuration
     * @param CRUD6ModelInterface $crudModel      The configured model instance
     * @param mixed               $recordId       The record ID
     * @param array               $relationship   The relationship configuration
     * @param array               $listFields     Fields to include in results
     * @param array|null          $relatedSchema  Pre-loaded related schema (optimization)
     * 
     * @return array Array of related records
     * 
     * @throws \RuntimeException If relationship type is invalid or query fails critically
     */
    protected function queryRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, array $relationship, array $listFields, ?array $relatedSchema = null): array
    {
        $type = $relationship['type'] ?? null;
        $relatedModel = $relationship['name'] ?? null;
        
        // Validate relationship configuration
        if (!$type) {
            $this->logger->error("CRUD6 [EditAction] Relationship missing type", [
                'relationship' => $relationship,
            ]);
            throw new \RuntimeException("Relationship configuration missing 'type' field");
        }
        
        if (!$relatedModel) {
            $this->logger->error("CRUD6 [EditAction] Relationship missing name", [
                'relationship' => $relationship,
            ]);
            throw new \RuntimeException("Relationship configuration missing 'name' field");
        }
        
        $this->debugLog("CRUD6 [EditAction] Querying relationship", [
            'type' => $type,
            'related_model' => $relatedModel,
            'record_id' => $recordId,
            'list_fields' => $listFields,
            'pre_loaded_schema' => $relatedSchema !== null,
        ]);
        
        // Support many_to_many relationships
        if ($type === 'many_to_many') {
            return $this->queryManyToManyRelationship($crudSchema, $crudModel, $recordId, $relationship, $listFields, $relatedSchema);
        }
        
        // Support belongs_to_many_through relationships
        if ($type === 'belongs_to_many_through') {
            return $this->queryBelongsToManyThroughRelationship($crudSchema, $crudModel, $recordId, $relationship, $listFields, $relatedSchema);
        }
        
        // Unsupported relationship type
        $this->logger->warning("CRUD6 [EditAction] Unsupported relationship type", [
            'type' => $type,
            'related_model' => $relatedModel,
            'supported_types' => ['many_to_many', 'belongs_to_many_through'],
        ]);
        
        return [];
    }

    /**
     * Query a many_to_many relationship through a pivot table.
     * 
     * Executes an optimized JOIN query to retrieve related records through a pivot table.
     * Phase 3: Enhanced with validation, error handling, query optimization, and
     * schema consolidation (uses pre-loaded schema when available).
     * 
     * @param array               $crudSchema     The schema configuration
     * @param CRUD6ModelInterface $crudModel      The configured model instance
     * @param mixed               $recordId       The record ID
     * @param array               $relationship   The relationship configuration with pivot_table, foreign_key, related_key
     * @param array               $listFields     Fields to include in results (empty = all fields)
     * @param array|null          $relatedSchema  Pre-loaded related schema (optimization - avoids getSchema call)
     * 
     * @return array Array of related records
     * 
     * @throws \RuntimeException If schema cannot be loaded
     */
    protected function queryManyToManyRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, array $relationship, array $listFields, ?array $relatedSchema = null): array
    {
        $pivotTable = $relationship['pivot_table'] ?? null;
        $foreignKey = $relationship['foreign_key'] ?? null;
        $relatedKey = $relationship['related_key'] ?? null;
        $relatedModel = $relationship['name'] ?? null;
        
        // Validate required configuration
        if (!$pivotTable || !$foreignKey || !$relatedKey || !$relatedModel) {
            $this->logger->error("CRUD6 [EditAction] Invalid many_to_many relationship configuration", [
                'relationship' => $relationship,
                'missing_fields' => array_filter([
                    'pivot_table' => !$pivotTable,
                    'foreign_key' => !$foreignKey,
                    'related_key' => !$relatedKey,
                    'name' => !$relatedModel,
                ]),
            ]);
            return [];
        }
        
        try {
            // Phase 3 Optimization: Use pre-loaded schema if available, otherwise load it
            if ($relatedSchema === null) {
                $relatedSchema = $this->schemaService->getSchema($relatedModel);
            }
            
            $relatedTable = $relatedSchema['table'] ?? $relatedModel;
            $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
            
            $this->debugLog("CRUD6 [EditAction] Query many_to_many relationship", [
                'pivot_table' => $pivotTable,
                'foreign_key' => $foreignKey,
                'related_key' => $relatedKey,
                'related_table' => $relatedTable,
                'related_primary_key' => $relatedPrimaryKey,
                'list_fields' => $listFields,
                'schema_pre_loaded' => $relatedSchema !== null,
            ]);
            
            // Build the optimized query with INNER JOIN
            // SELECT related_table.* FROM related_table
            // INNER JOIN pivot_table ON pivot_table.related_key = related_table.id
            // WHERE pivot_table.foreign_key = recordId
            
            $query = $this->db->table($relatedTable)
                ->join($pivotTable, "{$pivotTable}.{$relatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
                ->where("{$pivotTable}.{$foreignKey}", $recordId);
            
            // Apply field filtering if list_fields is specified
            if (!empty($listFields)) {
                // Ensure primary key is always included
                if (!in_array($relatedPrimaryKey, $listFields)) {
                    $listFields[] = $relatedPrimaryKey;
                }
                
                // Prefix table name to fields to avoid ambiguity
                $selectFields = array_map(function($field) use ($relatedTable) {
                    return "{$relatedTable}.{$field}";
                }, $listFields);
                
                $query->select($selectFields);
            } else {
                // Select all fields from related table
                $query->select("{$relatedTable}.*");
            }
            
            // Execute query
            $results = $query->get();
            
            $this->debugLog("CRUD6 [EditAction] Many_to_many query executed", [
                'related_model' => $relatedModel,
                'record_id' => $recordId,
                'row_count' => count($results),
            ]);
            
            // Convert Collection to array
            return $results->toArray();
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] Failed to query many_to_many relationship", [
                'relationship' => $relationship,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw if critical schema error, otherwise return empty
            if ($e instanceof \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException) {
                throw $e;
            }
            
            return [];
        }
    }

    /**
     * Query a belongs_to_many_through relationship.
     * 
     * Handles complex many-to-many-through relationships that traverse two pivot tables.
     * For example: permissions -> roles -> users uses two pivot tables (permission_roles and role_users).
     * 
     * Schema structure:
     * - first_pivot_table: The first pivot table to join (e.g., permission_roles)
     * - first_foreign_key: The foreign key in first pivot table pointing to source (e.g., permission_id)
     * - first_related_key: The key in first pivot table pointing to intermediate model (e.g., role_id)
     * - second_pivot_table: The second pivot table to join (e.g., role_users)
     * - second_foreign_key: The foreign key in second pivot table pointing to intermediate model (e.g., role_id)
     * - second_related_key: The key in second pivot table pointing to target (e.g., user_id)
     * 
     * Phase 3: Enhanced with schema consolidation optimization - uses pre-loaded schema.
     * 
     * @param array               $crudSchema     The schema configuration
     * @param CRUD6ModelInterface $crudModel      The configured model instance
     * @param mixed               $recordId       The record ID
     * @param array               $relationship   The relationship configuration
     * @param array               $listFields     Fields to include in results
     * @param array|null          $relatedSchema  Pre-loaded related schema (optimization)
     * 
     * @return array Array of related records
     */
    protected function queryBelongsToManyThroughRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, array $relationship, array $listFields, ?array $relatedSchema = null): array
    {
        $throughModel = $relationship['through'] ?? null;
        $relatedModel = $relationship['name'] ?? null;
        
        // Get pivot table configuration
        $firstPivotTable = $relationship['first_pivot_table'] ?? null;
        $firstForeignKey = $relationship['first_foreign_key'] ?? null;
        $firstRelatedKey = $relationship['first_related_key'] ?? null;
        $secondPivotTable = $relationship['second_pivot_table'] ?? null;
        $secondForeignKey = $relationship['second_foreign_key'] ?? null;
        $secondRelatedKey = $relationship['second_related_key'] ?? null;
        
        // Validate required configuration - check all required fields
        $requiredFields = [
            'through' => $throughModel,
            'name' => $relatedModel,
            'first_pivot_table' => $firstPivotTable,
            'first_foreign_key' => $firstForeignKey,
            'first_related_key' => $firstRelatedKey,
            'second_pivot_table' => $secondPivotTable,
            'second_foreign_key' => $secondForeignKey,
            'second_related_key' => $secondRelatedKey,
        ];
        
        // Check for missing fields (null or not set) - using === null to allow zero/empty string values
        $missingFields = array_keys(array_filter($requiredFields, fn($value) => $value === null));
        
        if (!empty($missingFields)) {
            $this->logger->error("CRUD6 [EditAction] Invalid belongs_to_many_through relationship configuration", [
                'relationship' => $relationship,
                'missing_fields' => $missingFields,
            ]);
            return [];
        }
        
        try {
            // Phase 3 Optimization: Use pre-loaded schema if available
            $throughSchema = $this->schemaService->getSchema($throughModel);
            $throughTable = $throughSchema['table'] ?? $throughModel;
            $throughPrimaryKey = $throughSchema['primary_key'] ?? 'id';
            
            if ($relatedSchema === null) {
                $relatedSchema = $this->schemaService->getSchema($relatedModel);
            }
            $relatedTable = $relatedSchema['table'] ?? $relatedModel;
            $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
            
            $this->debugLog("CRUD6 [EditAction] Query belongs_to_many_through relationship", [
                'through_model' => $throughModel,
                'through_table' => $throughTable,
                'related_model' => $relatedModel,
                'related_table' => $relatedTable,
                'first_pivot_table' => $firstPivotTable,
                'second_pivot_table' => $secondPivotTable,
                'record_id' => $recordId,
            ]);
            
            // Build the complex query with two pivot table joins
            // Example for permissions -> roles -> users:
            // SELECT users.* FROM users
            // INNER JOIN role_users ON role_users.user_id = users.id
            // INNER JOIN roles ON roles.id = role_users.role_id
            // INNER JOIN permission_roles ON permission_roles.role_id = roles.id
            // WHERE permission_roles.permission_id = recordId
            
            $query = $this->db->table($relatedTable)
                // Join second pivot table to related table
                ->join($secondPivotTable, "{$secondPivotTable}.{$secondRelatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
                // Join intermediate (through) table to second pivot table
                ->join($throughTable, "{$throughTable}.{$throughPrimaryKey}", '=', "{$secondPivotTable}.{$secondForeignKey}")
                // Join first pivot table to intermediate table
                ->join($firstPivotTable, "{$firstPivotTable}.{$firstRelatedKey}", '=', "{$throughTable}.{$throughPrimaryKey}")
                // Filter by source record ID in first pivot table
                ->where("{$firstPivotTable}.{$firstForeignKey}", $recordId);
            
            // Apply field filtering if list_fields is specified
            if (!empty($listFields)) {
                if (!in_array($relatedPrimaryKey, $listFields)) {
                    $listFields[] = $relatedPrimaryKey;
                }
                $selectFields = array_map(function($field) use ($relatedTable) {
                    return "{$relatedTable}.{$field}";
                }, $listFields);
                $query->select($selectFields);
            } else {
                $query->select("{$relatedTable}.*");
            }
            
            // Add distinct to avoid duplicates from multiple join paths
            $query->distinct();
            
            // Execute query
            $results = $query->get();
            
            $this->debugLog("CRUD6 [EditAction] Belongs_to_many_through query executed", [
                'related_model' => $relatedModel,
                'record_id' => $recordId,
                'row_count' => count($results),
            ]);
            
            // Convert Collection to array
            return $results->toArray();
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] Failed to query belongs_to_many_through relationship", [
                'relationship' => $relationship,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [];
        }
    }

    /**
     * Query a has-many relationship via foreign key.
     * 
     * Handles simple has-many relationships where records in the related table
     * reference the current record via a foreign key field.
     * For example: activities table has a user_id column that references users.id
     * 
     * **Security Note**: All parameters are safely bound using Laravel's query builder
     * parameter binding, which automatically escapes values to prevent SQL injection.
     * The `where()` method uses prepared statements with bound parameters.
     * 
     * @param array               $crudSchema     The schema configuration
     * @param CRUD6ModelInterface $crudModel      The configured model instance
     * @param mixed               $recordId       The record ID (safely bound as parameter)
     * @param string              $relatedModel   The name of the related model
     * @param string              $foreignKey     The foreign key column name in the related table
     * @param array               $listFields     Fields to include in results
     * @param array|null          $relatedSchema  Pre-loaded related schema (optimization)
     * 
     * @return array Array of related records
     * 
     * @throws \RuntimeException If schema cannot be loaded
     */
    protected function queryHasManyRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, $recordId, string $relatedModel, string $foreignKey, array $listFields, ?array $relatedSchema = null): array
    {
        try {
            // Load related schema if not pre-loaded
            if ($relatedSchema === null) {
                $relatedSchema = $this->schemaService->getSchema($relatedModel);
            }
            
            $relatedTable = $relatedSchema['table'] ?? $relatedModel;
            $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
            
            $this->debugLog("CRUD6 [EditAction] Query has-many relationship", [
                'related_model' => $relatedModel,
                'related_table' => $relatedTable,
                'foreign_key' => $foreignKey,
                'record_id' => $recordId,
                'list_fields' => $listFields,
                'schema_pre_loaded' => $relatedSchema !== null,
            ]);
            
            // Build simple query: SELECT * FROM related_table WHERE foreign_key = recordId
            $query = $this->db->table($relatedTable)
                ->where($foreignKey, $recordId);
            
            // Apply field filtering if list_fields is specified
            if (!empty($listFields)) {
                // Ensure primary key is always included
                if (!in_array($relatedPrimaryKey, $listFields)) {
                    $listFields[] = $relatedPrimaryKey;
                }
                
                // Select specified fields
                $query->select($listFields);
            } else {
                // Select all fields
                $query->select('*');
            }
            
            // Execute query
            $results = $query->get();
            
            $this->debugLog("CRUD6 [EditAction] Has-many query executed", [
                'related_model' => $relatedModel,
                'record_id' => $recordId,
                'foreign_key' => $foreignKey,
                'row_count' => count($results),
            ]);
            
            // Convert Collection to array
            return $results->toArray();
            
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [EditAction] Failed to query has-many relationship", [
                'related_model' => $relatedModel,
                'foreign_key' => $foreignKey,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw if critical schema error, otherwise return empty
            if ($e instanceof \UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException) {
                throw $e;
            }
            
            return [];
        }
    }
}
