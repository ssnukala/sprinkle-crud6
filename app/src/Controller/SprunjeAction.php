<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\Admin\Sprunje\UserSprunje;

/**
 * Sprunje action for CRUD6 models.
 * 
 * Handles listing, filtering, sorting, and pagination for any CRUD6 model.
 * Uses the Sprunje pattern from UserFrosting for data table operations.
 * Follows the UserFrosting 6 action controller pattern from sprinkle-admin.
 * 
 * Route: GET /api/crud6/{model}
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserListAction
 */
class SprunjeAction extends Base
{
    /**
     * Constructor for SprunjeAction.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger
     * @param Translator           $translator    Translator for i18n messages
     * @param CRUD6Sprunje         $sprunje       CRUD6 Sprunje for data operations
     * @param SchemaService        $schemaService Schema service
     * @param UserSprunje          $userSprunje   User Sprunje for relation queries
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected CRUD6Sprunje $sprunje,
        protected SchemaService $schemaService,
        protected UserSprunje $userSprunje,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Invoke the Sprunje action.
     * 
     * Returns paginated, filtered, and sorted data for the model.
     * Supports relation-specific queries based on schema detail configuration.
     * 
     * @param array                  $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface    $crudModel  The configured model instance (auto-injected)
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with Sprunje data
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
        $this->logger->debug("CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST START =====", [
            'model' => $crudSchema['model'],
            'uri' => (string) $request->getUri(),
            'query_params' => $request->getQueryParams(),
        ]);

        error_log(sprintf(
            "[CRUD6 SprunjeAction] ===== REQUEST START ===== model: %s, uri: %s, timestamp: %s",
            $crudSchema['model'],
            (string) $request->getUri(),
            date('Y-m-d H:i:s.u')
        ));

        try {
            // Get the relation parameter if it exists
            $relation = $this->getParameter($request, 'relation', 'NONE');
            
            error_log(sprintf(
                "[CRUD6 SprunjeAction] Relation parameter: %s, has_detail: %s, has_details: %s, has_relationships: %s",
                $relation,
                isset($crudSchema['detail']) ? 'yes' : 'no',
                isset($crudSchema['details']) ? 'yes' : 'no',
                isset($crudSchema['relationships']) ? 'yes' : 'no'
            ));
            
            $this->logger->debug("CRUD6 [SprunjeAction] Request parameters parsed", [
                'model' => $crudSchema['model'],
                'relation' => $relation,
                'has_detail_config' => isset($crudSchema['detail']),
                'has_details_array' => isset($crudSchema['details']),
            ]);
            
            // Check if this relation is configured in the schema's detail/details section
            // Support both singular 'detail' (legacy) and plural 'details' array
            $detailConfig = null;
            if (isset($crudSchema['details']) && is_array($crudSchema['details'])) {
                // Search through details array for matching model
                error_log(sprintf(
                    "[CRUD6 SprunjeAction] Searching details array for relation '%s', details count: %d",
                    $relation,
                    count($crudSchema['details'])
                ));
                foreach ($crudSchema['details'] as $config) {
                    error_log(sprintf(
                        "[CRUD6 SprunjeAction] Checking detail config: model=%s, matches=%s",
                        $config['model'] ?? 'null',
                        (isset($config['model']) && $config['model'] === $relation) ? 'YES' : 'no'
                    ));
                    if (isset($config['model']) && $config['model'] === $relation) {
                        $detailConfig = $config;
                        break;
                    }
                }
            } elseif (isset($crudSchema['detail']) && is_array($crudSchema['detail'])) {
                // Backward compatibility: support singular 'detail' object
                error_log(sprintf(
                    "[CRUD6 SprunjeAction] Checking singular detail config: model=%s",
                    $crudSchema['detail']['model'] ?? 'null'
                ));
                if (isset($crudSchema['detail']['model']) && $crudSchema['detail']['model'] === $relation) {
                    $detailConfig = $crudSchema['detail'];
                }
            }
            
            error_log(sprintf(
                "[CRUD6 SprunjeAction] Detail config found: %s, relation: %s",
                $detailConfig !== null ? 'YES' : 'NO',
                $relation
            ));
            
            if ($relation !== 'NONE' && $detailConfig !== null) {
                // Handle dynamic relation based on schema detail configuration
                $this->logger->debug("CRUD6 [SprunjeAction] Handling detail relation", [
                    'model' => $crudSchema['model'],
                    'relation' => $relation,
                    'detail_config' => $detailConfig,
                ]);
                
                // Load the related model's schema to get its configuration
                $relatedSchema = $this->schemaService->getSchema($relation);
                
                $this->logger->debug("CRUD6 [SprunjeAction] Related schema loaded", [
                    'relation' => $relation,
                    'related_table' => $relatedSchema['table'] ?? null,
                ]);
                
                // Get the foreign key from detail config
                $foreignKey = $detailConfig['foreign_key'] ?? 'id';
                
                // Get query parameters
                $params = $request->getQueryParams();
                
                // Check if there's a matching relationship definition (for many-to-many)
                $relationshipConfig = $this->findRelationshipConfig($crudSchema, $relation);
                
                $this->logger->debug("CRUD6 [SprunjeAction] Setting up relation sprunje", [
                    'relation' => $relation,
                    'foreign_key' => $foreignKey,
                    'parent_id' => $crudModel->id,
                    'has_relationship_config' => $relationshipConfig !== null,
                    'query_params' => $params,
                ]);
                
                // For 'users' relation, use UserSprunje for compatibility
                if ($relation === 'users') {
                    $this->logger->debug("CRUD6 [SprunjeAction] Using UserSprunje for users relation");
                    
                    $this->userSprunje->setOptions($params);
                    $this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                        return $query->where($foreignKey, $crudModel->id);
                    });
                    return $this->userSprunje->toResponse($response);
                }
                
                // For other relations, use CRUD6Sprunje with dynamic configuration
                $relatedModel = $this->schemaService->getModelInstance($relation);
                
                $sortableFields = $this->getSortableFieldsFromSchema($relatedSchema);
                $filterableFields = $this->getFilterableFieldsFromSchema($relatedSchema);
                $listFields = $detailConfig['list_fields'] ?? $this->getListableFieldsFromSchema($relatedSchema);
                
                $this->logger->debug("CRUD6 [SprunjeAction] Sprunje configuration prepared", [
                    'relation' => $relation,
                    'table' => $relatedModel->getTable(),
                    'sortable_fields' => $sortableFields,
                    'filterable_fields' => $filterableFields,
                    'list_fields' => $listFields,
                ]);
                
                // Setup sprunje with related model configuration
                $this->sprunje->setupSprunje(
                    $relatedModel->getTable(),
                    $sortableFields,
                    $filterableFields,
                    $listFields
                );
                
                $this->sprunje->setOptions($params);
                
                // Build the query based on relationship type
                if ($relationshipConfig !== null && $relationshipConfig['type'] === 'many_to_many') {
                    // Handle many-to-many relationship via pivot table
                    $this->logger->debug("CRUD6 [SprunjeAction] Using many-to-many relationship with pivot table", [
                        'pivot_table' => $relationshipConfig['pivot_table'] ?? null,
                        'foreign_key' => $relationshipConfig['foreign_key'] ?? null,
                        'related_key' => $relationshipConfig['related_key'] ?? null,
                    ]);
                    
                    error_log(sprintf(
                        "[CRUD6 SprunjeAction] Many-to-many relationship - relation: %s, pivot_table: %s, foreign_key: %s, related_key: %s, parent_id: %d",
                        $relation,
                        $relationshipConfig['pivot_table'] ?? 'null',
                        $relationshipConfig['foreign_key'] ?? 'null',
                        $relationshipConfig['related_key'] ?? 'null',
                        $crudModel->id
                    ));
                    
                    // Validate required relationship configuration
                    if (empty($relationshipConfig['pivot_table'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'pivot_table' configuration");
                    }
                    if (empty($relationshipConfig['foreign_key'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'foreign_key' configuration");
                    }
                    if (empty($relationshipConfig['related_key'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'related_key' configuration");
                    }
                    
                    // Use UserFrosting's built-in belongsToMany relationship method
                    // This leverages the framework's relationship handling instead of manual JOINs
                    $relatedClass = get_class($relatedModel);
                    
                    $this->logger->debug("CRUD6 [SprunjeAction] Creating dynamic belongsToMany relationship", [
                        'related_class' => $relatedClass,
                        'parent_class' => get_class($crudModel),
                    ]);
                    
                    $this->sprunje->extendQuery(function ($query) use (
                        $crudModel,
                        $relationshipConfig,
                        $relatedClass,
                        $relation
                    ) {
                        error_log(sprintf(
                            "[CRUD6 SprunjeAction] Creating belongsToMany relationship query - relation: %s",
                            $relation
                        ));
                        
                        // Create a dynamic belongsToMany relationship using UserFrosting's built-in method
                        $relationship = $crudModel->dynamicRelationship($relation, $relationshipConfig, $relatedClass);
                        
                        // Get the relationship query (this handles all the JOIN logic internally)
                        return $relationship->getQuery();
                    });
                } elseif ($relationshipConfig !== null && $relationshipConfig['type'] === 'belongs_to_many_through') {
                    // Handle belongs-to-many-through relationship (e.g., users -> roles -> permissions)
                    // This is completely generic - works for any through relationship defined in the schema
                    $this->logger->debug("CRUD6 [SprunjeAction] Using belongs-to-many-through relationship", [
                        'relation' => $relation,
                        'through' => $relationshipConfig['through'] ?? null,
                    ]);
                    
                    error_log(sprintf(
                        "[CRUD6 SprunjeAction] Belongs-to-many-through relationship - relation: %s, config: %s",
                        $relation,
                        json_encode($relationshipConfig)
                    ));
                    
                    $relatedClass = get_class($relatedModel);
                    
                    $this->sprunje->extendQuery(function ($query) use (
                        $crudModel,
                        $relationshipConfig,
                        $relatedClass,
                        $relation
                    ) {
                        error_log(sprintf(
                            "[CRUD6 SprunjeAction] Creating belongsToManyThrough relationship query - relation: %s",
                            $relation
                        ));
                        
                        // Use UserFrosting's belongsToManyThrough relationship (dynamic from schema)
                        $relationship = $crudModel->dynamicRelationship($relation, $relationshipConfig, $relatedClass);
                        return $relationship->getQuery();
                    });
                } else {
                    // Default: filter by foreign key (one-to-many relationship)
                    // This is the fallback for simple relationships not defined in the relationships array
                    $this->logger->debug("CRUD6 [SprunjeAction] Using direct foreign key relationship (one-to-many)");
                    
                    error_log(sprintf(
                        "[CRUD6 SprunjeAction] One-to-many relationship - relation: %s, foreign_key: %s, parent_id: %d",
                        $relation,
                        $foreignKey,
                        $crudModel->id
                    ));
                    
                    $this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                        return $query->where($foreignKey, $crudModel->id);
                    });
                }
                
                $this->logger->debug("CRUD6 [SprunjeAction] Relation sprunje configured, returning response", [
                    'relation' => $relation,
                    'parent_id' => $crudModel->id,
                ]);
                
                return $this->sprunje->toResponse($response);
            }
            
            // Default sprunje for main model listing
            $modelName = $this->getModelNameFromRequest($request);
            $params = $request->getQueryParams();
            
            $sortableFields = $this->getSortableFields($modelName);
            $filterableFields = $this->getFilterableFields($modelName);
            $listFields = $this->getListableFields($modelName);

            $this->logger->debug("CRUD6 [SprunjeAction] Setting up main model sprunje", [
                'model' => $modelName,
                'table' => $crudModel->getTable(),
                'sortable_fields' => $sortableFields,
                'filterable_fields' => $filterableFields,
                'list_fields' => $listFields,
                'query_params' => $params,
            ]);

            $this->sprunje->setupSprunje(
                $crudModel->getTable(),
                $sortableFields,
                $filterableFields,
                $listFields
            );

            $this->sprunje->setOptions($params);
            
            $this->logger->debug("CRUD6 [SprunjeAction] Main sprunje configured, returning response", [
                'model' => $modelName,
            ]);

            return $this->sprunje->toResponse($response);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST FAILED =====", [
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
     * Find a relationship configuration by name in the schema.
     * 
     * @param array  $schema        The schema configuration
     * @param string $relationName  The name of the relationship to find
     * 
     * @return array|null The relationship configuration or null if not found
     */
    protected function findRelationshipConfig(array $schema, string $relationName): ?array
    {
        $relationships = $schema['relationships'] ?? [];
        
        foreach ($relationships as $config) {
            if (isset($config['name']) && $config['name'] === $relationName) {
                return $config;
            }
        }
        
        return null;
    }
    
    /**
     * Get sortable fields from a schema array.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of sortable field names
     */
    protected function getSortableFieldsFromSchema(array $schema): array
    {
        $sortable = [];
        
        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['sortable']) && $fieldConfig['sortable'] === true) {
                    $sortable[] = $fieldName;
                }
            }
        }
        
        return $sortable;
    }
    
    /**
     * Get filterable fields from a schema array.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of filterable field names
     */
    protected function getFilterableFieldsFromSchema(array $schema): array
    {
        $filterable = [];
        
        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['filterable']) && $fieldConfig['filterable'] === true) {
                    $filterable[] = $fieldName;
                }
            }
        }
        
        return $filterable;
    }
    
    /**
     * Get listable fields from a schema array.
     * 
     * Only fields with explicit `listable: true` are included.
     * This prevents sensitive fields (password, timestamps, etc.) from being exposed by default.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of listable field names
     */
    protected function getListableFieldsFromSchema(array $schema): array
    {
        $listable = [];
        
        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                // Only include fields explicitly marked as listable: true
                if (isset($fieldConfig['listable']) && $fieldConfig['listable'] === true) {
                    $listable[] = $fieldName;
                }
            }
        }
        
        return $listable;
    }
}
