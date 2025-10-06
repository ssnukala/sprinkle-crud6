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
            
            // Use UserSprunje for users relation, otherwise use default
            if ($relation === 'users') {
                $params = $request->getQueryParams();
                $this->userSprunje->setOptions($params);
                
                // Get the foreign key from detail config
                $foreignKey = $detailConfig['foreign_key'] ?? 'group_id';
                
                $this->userSprunje->extendQuery(function ($query) use ($crudModel, $foreignKey) {
                    return $query->where($foreignKey, $crudModel->id);
                });
                
                return $this->userSprunje->toResponse($response);
            }
        }
        
        // Default sprunje for main model listing
        $modelName = $this->getModelNameFromRequest($request);
        $params = $request->getQueryParams();

        $this->sprunje->setupSprunje(
            $crudModel->getTable(),
            $this->getSortableFields($modelName),
            $this->getFilterableFields($modelName),
            $this->getListableFields($modelName)
        );

        $this->sprunje->setOptions($params);

        return $this->sprunje->toResponse($response);
    }
}
