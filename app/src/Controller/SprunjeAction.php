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
     * @param array                  $crudSchema The schema configuration
     * @param CRUD6ModelInterface    $crudModel  The configured model instance
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with Sprunje data
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
        // Get the relation parameter if it exists
        $relation = $this->getParameter($request, 'relation', 'NONE');
        $this->logger->debug("SprunjeAction::__invoke called for relation: {$relation}");
        
        // Check if this relation is configured in the schema's detail section
        $detailConfig = $crudSchema['detail'] ?? null;
        
        if ($relation !== 'NONE' && $detailConfig && $detailConfig['model'] === $relation) {
            // Handle dynamic relation based on schema detail configuration
            $this->logger->debug("Handling detail relation: {$relation}", $detailConfig);
            
            // Load the related model's schema to get its configuration
            $relatedSchema = $this->schemaService->getSchema($relation);
            
            // Get the foreign key from detail config
            $foreignKey = $detailConfig['foreign_key'] ?? 'id';
            
            // Get query parameters
            $params = $request->getQueryParams();
            
            // For 'users' relation, use UserSprunje for compatibility
            if ($relation === 'users') {
                $this->userSprunje->setOptions($params);
                $this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                    return $query->where($foreignKey, $crudModel->id);
                });
                return $this->userSprunje->toResponse($response);
            }
            
            // For other relations, use CRUD6Sprunje with dynamic configuration
            $relatedModel = $this->schemaService->getModelInstance($relation);
            
            // Setup sprunje with related model configuration
            $this->sprunje->setupSprunje(
                $relatedModel->getTable(),
                $this->getSortableFieldsFromSchema($relatedSchema),
                $this->getFilterableFieldsFromSchema($relatedSchema),
                $detailConfig['list_fields'] ?? $this->getListableFieldsFromSchema($relatedSchema),
                $this->getSearchableFieldsFromSchema($relatedSchema)
            );
            
            $this->sprunje->setOptions($params);
            
            // Filter by parent record's ID using the foreign key
            $this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                return $query->where($foreignKey, $crudModel->id);
            });
            
            return $this->sprunje->toResponse($response);
        }
        
        // Default sprunje for main model listing
        $modelName = $this->getModelNameFromRequest($request);
        $params = $request->getQueryParams();

        $this->sprunje->setupSprunje(
            $crudModel->getTable(),
            $this->getSortableFields($modelName),
            $this->getFilterableFields($modelName),
            $this->getListableFields($modelName),
            $this->getSearchableFields($modelName)
        );

        $this->sprunje->setOptions($params);

        return $this->sprunje->toResponse($response);
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
