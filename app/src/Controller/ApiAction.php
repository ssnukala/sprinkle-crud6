<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * API meta/schema endpoint action for CRUD6.
 * 
 * Returns schema information and metadata for a CRUD6 model.
 * Follows the UserFrosting 6 action controller pattern from sprinkle-admin.
 * 
 * Route: GET /api/crud6/{model}/schema
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserPageAction
 */
class ApiAction extends Base
{
    /**
     * Constructor for ApiAction.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger
     * @param Translator           $translator    Translator for i18n messages
     * @param SchemaService        $schemaService Schema service
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Invoke the API schema action.
     * 
     * Returns schema information and metadata for the requested model.
     * Supports context-based filtering via query parameter to return only relevant data.
     * 
     * Query parameters:
     * - context: Filter schema for specific use case (list|form|detail|meta)
     *   - list: Only listable fields for table views
     *   - form: Only editable fields with validation for forms
     *   - detail: Full field information for detail pages
     *   - meta: Just model metadata (minimal)
     *   - Omit for full schema (not recommended, for backward compatibility)
     * 
     * @param array                  $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface    $crudModel  The configured model instance (auto-injected)
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with schema data
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        
        //$modelName = $this->getModelNameFromRequest($request);
        ///$schema = $this->getSchema($modelName);
        //$this->validateAccess($modelName, 'read');

        // Get context parameter from query string
        $queryParams = $request->getQueryParams();
        $context = $queryParams['context'] ?? null;
        
        $this->debugLog("CRUD6 [ApiAction] ===== SCHEMA API REQUEST =====", [
            'model' => $crudSchema['model'],
            'context' => $context ?? 'null/full',
            'uri' => (string) $request->getUri(),
        ]);

        // Filter schema based on context
        $this->debugLog("CRUD6 [ApiAction] Filtering schema for context", [
            'context' => $context ?? 'null/full',
        ]);

        $filteredSchema = $this->schemaService->filterSchemaForContext($crudSchema, $context);

        $this->debugLog("CRUD6 [ApiAction] Schema filtered", [
            'field_count' => count($filteredSchema['fields'] ?? []),
            'has_contexts' => isset($filteredSchema['contexts']) ? 'yes' : 'no',
        ]);

        // Get a display name for the model (title or capitalized model name)
        // For button labels, we want singular form like "Group" not "groups" or "Group Management"
        $modelDisplayName = $filteredSchema['title'] ?? ucfirst($filteredSchema['model']);
        // If title ends with "Management", extract the entity name
        if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
            $modelDisplayName = $matches[1];
        }

        // Log context filtering for debugging
        if ($context !== null) {
            $this->debugLog("CRUD6: Schema filtered for context '{$context}' - model: {$filteredSchema['model']}");
        }

        $responseData = [
            'message' => $this->translator->translate('CRUD6.API.SUCCESS', ['model' => $modelDisplayName]),
            'model' => $filteredSchema['model'],
            'modelDisplayName' => $modelDisplayName,
            'schema' => $filteredSchema
        ];

        $this->debugLog("CRUD6 [ApiAction] ===== SCHEMA API RESPONSE =====", [
            'model' => $filteredSchema['model'],
            'context' => $context ?? 'null/full',
            'response_size' => strlen(json_encode($responseData)) . ' bytes',
        ]);

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
