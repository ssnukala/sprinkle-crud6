<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
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
     * @param SchemaService        $schemaService Schema service
     * @param Config               $config        Configuration repository
     * @param Translator           $translator    Translator for i18n messages
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService,
        protected Config $config,
        protected Translator $translator,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
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
        try {
            // Validate access permission for schema endpoint
            $this->validateAccess($crudSchema, 'read');
            
            // Get context parameter from query string
            $queryParams = $request->getQueryParams();
            $context = $queryParams['context'] ?? null;
            $includeRelated = filter_var($queryParams['include_related'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $relatedContext = $queryParams['related_context'] ?? 'list';
            
            $this->debugLog("CRUD6 [ApiAction] ===== SCHEMA API REQUEST =====", [
                'model' => $crudSchema['model'],
                'context' => $context ?? 'null/full',
                'include_related' => $includeRelated ? 'true' : 'false',
                'related_context' => $includeRelated ? $relatedContext : 'n/a',
                'uri' => (string) $request->getUri(),
            ]);

            // Filter schema based on context, optionally including related schemas
            $this->debugLog("CRUD6 [ApiAction] Filtering schema for context", [
                'context' => $context ?? 'null/full',
                'include_related' => $includeRelated ? 'true' : 'false',
            ]);

            // Use filterSchemaWithRelated if include_related is requested
            if ($includeRelated) {
                $filteredSchema = $this->schemaService->filterSchemaWithRelated(
                    $crudSchema,
                    $context,
                    true,
                    $relatedContext
                );
            } else {
                $filteredSchema = $this->schemaService->filterSchemaForContext($crudSchema, $context);
            }

        // Translate all translatable fields in the schema (labels, titles, etc.)
        $filteredSchema = $this->schemaService->translateSchema($filteredSchema);

        $this->debugLog("CRUD6 [ApiAction] Schema filtered and translated", [
            'field_count' => count($filteredSchema['fields'] ?? []),
            'has_contexts' => isset($filteredSchema['contexts']) ? 'yes' : 'no',
            'has_related_schemas' => isset($filteredSchema['related_schemas']) ? 'yes' : 'no',
            'related_count' => isset($filteredSchema['related_schemas']) ? count($filteredSchema['related_schemas']) : 0,
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

        // Build response with schema fields at root level for direct access
        // This allows frontend to access $response.table, $response.fields, etc.
        $responseData = array_merge(
            [
                'message' => $this->translator->translate('CRUD6.API.SUCCESS', ['model' => $modelDisplayName]),
                'modelDisplayName' => $modelDisplayName,
                'breadcrumb' => [
                    'modelTitle' => $filteredSchema['title'] ?? ucfirst($filteredSchema['model']),
                    'singularTitle' => $filteredSchema['singular_title'] ?? ($filteredSchema['title'] ?? ucfirst($filteredSchema['model']))
                ]
            ],
            $filteredSchema // Merge schema fields at root level
        );

            $this->debugLog("CRUD6 [ApiAction] ===== SCHEMA API RESPONSE =====", [
                'model' => $filteredSchema['model'],
                'context' => $context ?? 'null/full',
                'response_size' => strlen(json_encode($responseData)) . ' bytes',
            ]);

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (ForbiddenException $e) {
            // User lacks permission - return 403
            return $this->jsonResponse($response, $e->getMessage(), 403);
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            // Log unexpected errors and return 500
            $this->logger->error("CRUD6 [ApiAction] Unexpected error: " . $e->getMessage(), [
                'model' => $crudSchema['model'] ?? 'unknown',
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while processing the schema request', 500);
        }
    }
}
