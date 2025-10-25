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
     * @param CRUD6ModelInterface    $crudModel  The configured model instance (auto-injected)
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with Sprunje data
     */
    public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Get schema from request attribute (set by CRUD6Injector middleware)
        $crudSchema = $request->getAttribute('crudSchema');
        
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
        $this->logger->debug("CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST START =====", [
            'model' => $crudSchema['model'],
            'uri' => (string) $request->getUri(),
            'query_params' => $request->getQueryParams(),
        ]);

        try {
            // Get the relation parameter if it exists
            $relation = $this->getParameter($request, 'relation', 'NONE');
            
            $this->logger->debug("CRUD6 [SprunjeAction] Request parameters parsed", [
                'model' => $crudSchema['model'],
                'relation' => $relation,
                'has_detail_config' => isset($crudSchema['detail']),
            ]);
            
            // Check if this relation is configured in the schema's detail section
            $detailConfig = $crudSchema['detail'] ?? null;
            
            if ($relation !== 'NONE' && $detailConfig && $detailConfig['model'] === $relation) {
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
                
                $this->logger->debug("CRUD6 [SprunjeAction] Setting up relation sprunje", [
                    'relation' => $relation,
                    'foreign_key' => $foreignKey,
                    'parent_id' => $crudModel->id,
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
                $searchableFields = $this->getSearchableFieldsFromSchema($relatedSchema);
                
                $this->logger->debug("CRUD6 [SprunjeAction] Sprunje configuration prepared", [
                    'relation' => $relation,
                    'table' => $relatedModel->getTable(),
                    'sortable_fields' => $sortableFields,
                    'filterable_fields' => $filterableFields,
                    'list_fields' => $listFields,
                    'searchable_fields' => $searchableFields,
                ]);
                
                // Setup sprunje with related model configuration
                $this->sprunje->setupSprunje(
                    $relatedModel->getTable(),
                    $sortableFields,
                    $filterableFields,
                    $listFields,
                    $searchableFields
                );
                
                $this->sprunje->setOptions($params);
                
                // Filter by parent record's ID using the foreign key
                $this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                    return $query->where($foreignKey, $crudModel->id);
                });
                
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
            $searchableFields = $this->getSearchableFields($modelName);

            $this->logger->debug("CRUD6 [SprunjeAction] Setting up main model sprunje", [
                'model' => $modelName,
                'table' => $crudModel->getTable(),
                'sortable_fields' => $sortableFields,
                'filterable_fields' => $filterableFields,
                'list_fields' => $listFields,
                'searchable_fields' => $searchableFields,
                'query_params' => $params,
            ]);

            $this->sprunje->setupSprunje(
                $crudModel->getTable(),
                $sortableFields,
                $filterableFields,
                $listFields,
                $searchableFields
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
     * @param array $schema The schema configuration
     * 
     * @return array List of listable field names
     */
    protected function getListableFieldsFromSchema(array $schema): array
    {
        $listable = [];
        
        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                // Check if explicitly set to listable, or default to true if not readonly
                $isListable = isset($fieldConfig['listable']) 
                    ? $fieldConfig['listable'] 
                    : !($fieldConfig['readonly'] ?? false);
                    
                if ($isListable) {
                    $listable[] = $fieldName;
                }
            }
        }
        
        return $listable;
    }

    /**
     * Get searchable fields from a schema array.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of searchable field names
     */
    protected function getSearchableFieldsFromSchema(array $schema): array
    {
        $searchable = [];
        
        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['searchable']) && $fieldConfig['searchable'] === true) {
                    $searchable[] = $fieldName;
                }
            }
        }
        
        return $searchable;
    }
}
