<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Config\Config;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Manages many-to-many relationships for CRUD6 models.
 *
 * This controller handles attaching and detaching related records through
 * pivot tables for many-to-many relationships.
 * 
 * POST attaches related records
 * DELETE detaches related records
 * 
 * This route requires authentication and proper permissions.
 *
 * Request type: POST (attach) or DELETE (detach)
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserManageRolesAction
 */
class RelationshipAction extends Base
{
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
        protected UserActivityLogger $userActivityLogger,
        protected Connection $db,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Invoke the controller.
     *
     * @param array               $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        try {
            parent::__invoke($crudSchema, $crudModel, $request, $response);
            
            // Validate access permission for relationship management
            $this->validateAccess($crudSchema, 'edit');
            
            // Get the relationship name from the route
            $relationName = $this->getParameter($request, 'relation');
            
            // Determine the HTTP method
            $method = $request->getMethod();
            
            // Handle GET request - retrieve relationship data
            if ($method === 'GET') {
                return $this->handleGetRelationship($crudSchema, $crudModel, $request, $response, $relationName);
        }
        
        // Determine if this is attach (POST) or detach (DELETE)
        $isAttach = ($method === 'POST');

        // Access validation is done in __invoke() method

        // The record is already loaded by the middleware into $crudModel

        // Check if relationship is defined in schema
        $relationships = $crudSchema['relationships'] ?? [];
        $relationshipConfig = null;
        
        // First, find any relationship with the given name
        foreach ($relationships as $config) {
            if ($config['name'] === $relationName) {
                $relationshipConfig = $config;
                break;
            }
        }

        if ($relationshipConfig === null) {
            throw new \RuntimeException("Relationship '{$relationName}' not found in schema for model '{$crudSchema['model']}'");
        }

        // Check if the relationship type supports attach/detach operations
        $relationshipType = $relationshipConfig['type'] ?? 'unknown';
        if ($relationshipType !== 'many_to_many') {
            throw new \RuntimeException(
                "Relationship '{$relationName}' is type '{$relationshipType}' which does not support attach/detach operations. " .
                "Only 'many_to_many' relationships can be modified via POST/DELETE. " .
                "Use GET to retrieve data for other relationship types."
            );
        }

        // Get POST/DELETE parameters - should contain array of IDs to attach/detach
        $params = (array) $request->getParsedBody();
        $relatedIds = $params['ids'] ?? [];

        if (!is_array($relatedIds) || empty($relatedIds)) {
            throw new \InvalidArgumentException('No IDs provided for relationship operation. Expected payload format: {"ids": [1, 2, 3]}');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Get pivot table name and key names from relationship config
            $pivotTable = $relationshipConfig['pivot_table'] ?? null;
            $foreignKey = $relationshipConfig['foreign_key'] ?? $crudSchema['model'] . '_id';
            $relatedKey = $relationshipConfig['related_key'] ?? $relationName . '_id';

            if ($pivotTable === null) {
                throw new \RuntimeException("Pivot table not specified for relationship '{$relationName}'");
            }

            if ($isAttach) {
                // Attach: Insert records into pivot table
                foreach ($relatedIds as $relatedId) {
                    $this->db->table($pivotTable)->insertOrIgnore([
                        $foreignKey => $crudModel->id,
                        $relatedKey => $relatedId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

                $action = 'attached';
                $messageKey = 'CRUD6.RELATIONSHIP.ATTACH_SUCCESS';
            } else {
                // Detach: Delete records from pivot table
                $this->db->table($pivotTable)
                    ->where($foreignKey, $crudModel->id)
                    ->whereIn($relatedKey, $relatedIds)
                    ->delete();

                $action = 'detached';
                $messageKey = 'CRUD6.RELATIONSHIP.DETACH_SUCCESS';
            }

            // Get the current user for logging
            /** @var UserInterface */
            $currentUser = $this->authenticator->user();

            // Log activity
            $this->userActivityLogger->info(
                "User {$currentUser->user_name} {$action} {$relationName} for {$crudSchema['model']} {$crudModel->id}.",
                [
                    'type'     => 'relationship_' . $action,
                    'user_id'  => $currentUser->id,
                    'model'    => $crudSchema['model'],
                    'id'       => $crudModel->id,
                    'relation' => $relationName,
                    'count'    => count($relatedIds),
                ]
            );

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

            // Success message
            // Translate model and relation titles if they are translation keys
            $modelTitle = $crudSchema['title'] ?? $crudSchema['model'];
            $relationTitle = $relationshipConfig['title'] ?? $relationName;
            $translatedModel = $this->translator->translate($modelTitle);
            $translatedRelation = $this->translator->translate($relationTitle);
            
            $message = $this->translator->translate($messageKey, [
                'model'    => $translatedModel,
                'relation' => $translatedRelation,
                'count'    => count($relatedIds),
            ]);

            return $this->jsonResponse($response, $message);
        } catch (ForbiddenException $e) {
            // Let ForbiddenException bubble up to framework's error handler
            throw $e;
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [RelationshipAction] ===== REQUEST FAILED =====", [
                'model' => $crudSchema['model'] ?? 'unknown',
                'relation' => $relationName ?? 'unknown',
                'method' => $method ?? 'unknown',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while managing the relationship', 500);
        }
    }

    /**
     * Handle GET request to retrieve relationship data.
     * 
     * Supports pagination, sorting, and filtering.
     * 
     * @param array               $crudSchema     The schema configuration
     * @param CRUD6ModelInterface $crudModel      The configured model instance
     * @param Request             $request        The HTTP request
     * @param Response            $response       The HTTP response
     * @param string              $relationName   The relationship name
     * 
     * @return Response JSON response with relationship data
     */
    protected function handleGetRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response, string $relationName): Response
    {
        // Access control check for read
        $this->validateAccess($crudSchema, 'read');
        
        // Find the relationship configuration
        $relationships = $crudSchema['relationships'] ?? [];
        $relationshipConfig = null;
        
        foreach ($relationships as $config) {
            if ($config['name'] === $relationName) {
                $relationshipConfig = $config;
                break;
            }
        }
        
        if ($relationshipConfig === null) {
            $errorData = [
                'error' => "Relationship '{$relationName}' not found in schema for model '{$crudSchema['model']}'"
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Get query parameters for pagination, sorting, filtering
        $queryParams = $request->getQueryParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($queryParams['per_page'] ?? 10)));
        $sortField = $queryParams['sort'] ?? null;
        $sortDirection = strtoupper($queryParams['direction'] ?? 'ASC');
        $search = $queryParams['search'] ?? null;
        $listFields = $queryParams['fields'] ?? [];
        
        // If list_fields not provided in query, check details section of schema
        if (empty($listFields)) {
            $detailsConfig = $crudSchema['details'] ?? [];
            foreach ($detailsConfig as $detail) {
                if ($detail['model'] === $relationName) {
                    $listFields = $detail['list_fields'] ?? [];
                    break;
                }
            }
        }
        
        $this->logger->debug("Line:237 CRUD6 [RelationshipAction] GET relationship", [
            'model' => $crudSchema['model'],
            'record_id' => $crudModel->id,
            'relationship' => $relationName,
            'type' => $relationshipConfig['type'] ?? 'unknown',
            'page' => $page,
            'per_page' => $perPage,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
            'search' => $search,
        ]);
        
        // Query based on relationship type
        $type = $relationshipConfig['type'] ?? null;
        
        try {
            if ($type === 'many_to_many') {
                $result = $this->getManyToManyRelationship($crudSchema, $crudModel, $relationshipConfig, $listFields, $page, $perPage, $sortField, $sortDirection, $search);
            } elseif ($type === 'belongs_to_many_through') {
                $result = $this->getBelongsToManyThroughRelationship($crudSchema, $crudModel, $relationshipConfig, $listFields, $page, $perPage, $sortField, $sortDirection, $search);
            } else {
                $errorData = [
                    'error' => "Unsupported relationship type '{$type}' for relationship '{$relationName}'"
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Return data in Sprunje-compatible format for UFSprunjeTable
            // UFSprunjeTable expects: { rows: [], count: number, count_filtered: number }
            $responseData = [
                'rows' => $result['rows'],
                'count' => $result['total'],           // Total count without filters
                'count_filtered' => $result['filtered'],  // Count with current filters/search (before pagination)
                // Additional metadata (not used by UFSprunjeTable but useful for debugging)
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $result['total_pages'],
                'relationship' => $relationName,
                'title' => $relationshipConfig['title'] ?? ucfirst($relationName),
                'type' => $type,
            ];
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error("Line:284 CRUD6 [RelationshipAction] Failed to get relationship", [
                'model' => $crudSchema['model'],
                'record_id' => $crudModel->id,
                'relationship' => $relationName,
                'error' => $e->getMessage(),
            ]);
            
            $errorData = [
                'error' => 'Failed to retrieve relationship data',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get many_to_many relationship data with pagination.
     * 
     * @param array               $crudSchema        The schema configuration
     * @param CRUD6ModelInterface $crudModel         The configured model instance
     * @param array               $relationshipConfig The relationship configuration
     * @param array               $listFields        Fields to include
     * @param int                 $page              Page number
     * @param int                 $perPage           Items per page
     * @param string|null         $sortField         Sort field
     * @param string              $sortDirection     Sort direction (ASC/DESC)
     * @param string|null         $search            Search term
     * 
     * @return array Result with rows, count, total, total_pages
     */
    protected function getManyToManyRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, array $relationshipConfig, array $listFields, int $page, int $perPage, ?string $sortField, string $sortDirection, ?string $search): array
    {
        $pivotTable = $relationshipConfig['pivot_table'] ?? null;
        $foreignKey = $relationshipConfig['foreign_key'] ?? null;
        $relatedKey = $relationshipConfig['related_key'] ?? null;
        $relatedModel = $relationshipConfig['name'] ?? null;
        
        if (!$pivotTable || !$foreignKey || !$relatedKey || !$relatedModel) {
            throw new \RuntimeException("Invalid many_to_many relationship configuration");
        }
        
        // Load the related model's schema
        $relatedSchema = $this->schemaService->getSchema($relatedModel);
        $relatedTable = $relatedSchema['table'] ?? $relatedModel;
        $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
        
        // Build the base query
        $query = $this->db->table($relatedTable)
            ->join($pivotTable, "{$pivotTable}.{$relatedKey}", '=', "{$relatedTable}.{$relatedPrimaryKey}")
            ->where("{$pivotTable}.{$foreignKey}", $crudModel->id);
        
        // Get total count BEFORE applying search filters (unfiltered total)
        $totalCount = $query->count();
        
        // Apply search if provided
        if ($search && !empty($listFields)) {
            $query->where(function($q) use ($search, $listFields, $relatedTable) {
                foreach ($listFields as $field) {
                    $q->orWhere("{$relatedTable}.{$field}", 'LIKE', "%{$search}%");
                }
            });
        }
        
        // Get filtered count AFTER applying search but BEFORE pagination
        $filteredCount = $query->count();
        
        $this->logger->debug("Line:351 CRUD6 [RelationshipAction] Many-to-many counts", [
            'model' => $crudSchema['model'],
            'record_id' => $crudModel->id,
            'relationship' => $relatedModel,
            'total_count' => $totalCount,
            'filtered_count' => $filteredCount,
            'has_search' => !empty($search),
        ]);
        
        // Apply sorting
        if ($sortField) {
            $query->orderBy("{$relatedTable}.{$sortField}", $sortDirection);
        } else {
            $query->orderBy("{$relatedTable}.{$relatedPrimaryKey}", 'ASC');
        }
        
        // Apply field filtering
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
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $query->offset($offset)->limit($perPage);
        
        $results = $query->get();
        $rows = json_decode(json_encode($results), true);
        
        return [
            'rows' => $rows,
            'count' => count($rows),              // Current page row count
            'total' => $totalCount,                // Total unfiltered count
            'filtered' => $filteredCount,          // Total filtered count (before pagination)
            'total_pages' => (int) ceil($filteredCount / $perPage),  // Pages based on filtered count
        ];
    }

    /**
     * Get belongs_to_many_through relationship data with pagination.
     * 
     * @param array               $crudSchema        The schema configuration
     * @param CRUD6ModelInterface $crudModel         The configured model instance
     * @param array               $relationshipConfig The relationship configuration
     * @param array               $listFields        Fields to include
     * @param int                 $page              Page number
     * @param int                 $perPage           Items per page
     * @param string|null         $sortField         Sort field
     * @param string              $sortDirection     Sort direction (ASC/DESC)
     * @param string|null         $search            Search term
     * 
     * @return array Result with rows, count, total, total_pages
     */
    protected function getBelongsToManyThroughRelationship(array $crudSchema, CRUD6ModelInterface $crudModel, array $relationshipConfig, array $listFields, int $page, int $perPage, ?string $sortField, string $sortDirection, ?string $search): array
    {
        // belongs_to_many_through uses an intermediate model
        // e.g., Country -> User -> Post (get posts through users)
        
        $throughModel = $relationshipConfig['through'] ?? null;
        $foreignKey = $relationshipConfig['foreign_key'] ?? null; // e.g., country_id in users table
        $throughKey = $relationshipConfig['through_key'] ?? null; // e.g., user_id in posts table
        $relatedModel = $relationshipConfig['name'] ?? null;
        
        if (!$throughModel || !$foreignKey || !$throughKey || !$relatedModel) {
            throw new \RuntimeException("Invalid belongs_to_many_through relationship configuration");
        }
        
        // Load schemas
        $throughSchema = $this->schemaService->getSchema($throughModel);
        $throughTable = $throughSchema['table'] ?? $throughModel;
        $throughPrimaryKey = $throughSchema['primary_key'] ?? 'id';
        
        $relatedSchema = $this->schemaService->getSchema($relatedModel);
        $relatedTable = $relatedSchema['table'] ?? $relatedModel;
        $relatedPrimaryKey = $relatedSchema['primary_key'] ?? 'id';
        
        // Build the query
        // SELECT related.* FROM related
        // INNER JOIN through ON through.id = related.through_key
        // WHERE through.foreign_key = crudModel.id
        
        $query = $this->db->table($relatedTable)
            ->join($throughTable, "{$throughTable}.{$throughPrimaryKey}", '=', "{$relatedTable}.{$throughKey}")
            ->where("{$throughTable}.{$foreignKey}", $crudModel->id);
        
        // Get total count BEFORE applying search filters (unfiltered total)
        $totalCount = $query->count();
        
        // Apply search if provided
        if ($search && !empty($listFields)) {
            $query->where(function($q) use ($search, $listFields, $relatedTable) {
                foreach ($listFields as $field) {
                    $q->orWhere("{$relatedTable}.{$field}", 'LIKE', "%{$search}%");
                }
            });
        }
        
        // Get filtered count AFTER applying search but BEFORE pagination
        $filteredCount = $query->count();
        
        $this->logger->debug("Line:458 CRUD6 [RelationshipAction] Belongs-to-many-through counts", [
            'model' => $crudSchema['model'],
            'record_id' => $crudModel->id,
            'relationship' => $relatedModel,
            'total_count' => $totalCount,
            'filtered_count' => $filteredCount,
            'has_search' => !empty($search),
        ]);
        
        // Apply sorting
        if ($sortField) {
            $query->orderBy("{$relatedTable}.{$sortField}", $sortDirection);
        } else {
            $query->orderBy("{$relatedTable}.{$relatedPrimaryKey}", 'ASC');
        }
        
        // Apply field filtering
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
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $query->offset($offset)->limit($perPage);
        
        $results = $query->get();
        $rows = json_decode(json_encode($results), true);
        
        return [
            'rows' => $rows,
            'count' => count($rows),              // Current page row count
            'total' => $totalCount,                // Total unfiltered count
            'filtered' => $filteredCount,          // Total filtered count (before pagination)
            'total_pages' => (int) ceil($filteredCount / $perPage),  // Pages based on filtered count
        ];
    }
}
