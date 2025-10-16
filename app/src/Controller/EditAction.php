<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\I18n\Translator;
use Illuminate\Database\Connection;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Edit/read action for CRUD6 models.
 * 
 * Handles retrieval of a single record for any CRUD6 model.
 * Returns the record data for editing or viewing.
 * Follows the UserFrosting 6 action controller pattern from sprinkle-admin.
 * 
 * Route: GET /api/crud6/{model}/{id}
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserEditAction
 */
class EditAction extends Base
{
    /**
     * Constructor for EditAction.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger
     * @param Translator           $translator    Translator for i18n messages
     * @param Connection           $db            Database connection
     * @param AlertStream          $alert         Alert stream for user notifications
     * @param SchemaService        $schemaService Schema service
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected Connection $db,
        protected AlertStream $alert,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Invoke the edit action.
     * 
     * Returns the record data for editing or viewing.
     * 
     * @param array                  $crudSchema The schema configuration
     * @param CRUD6ModelInterface    $crudModel  The configured model instance with record loaded
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with record data or error
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);

        // For EditAction, the crudModel should contain the specific record since ID is in the route
        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->logger->debug("CRUD6: Edit request for record ID: {$recordId} model: {$crudSchema['model']}");

        try {
            // Get a display name for the model (title or capitalized model name)
            // For button labels, we want singular form like "Group" not "groups" or "Group Management"
            $modelDisplayName = $crudSchema['title'] ?? ucfirst($crudSchema['model']);
            // If title ends with "Management", extract the entity name
            if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
                $modelDisplayName = $matches[1];
            }
            
            $responseData = [
                'message' => $this->translator->translate('CRUD6.EDIT.SUCCESS', ['model' => $modelDisplayName]),
                'model' => $crudSchema['model'],
                'modelDisplayName' => $modelDisplayName,
                'id' => $recordId,
                'data' => $crudModel->toArray()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to edit record for model: {$crudSchema['model']}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);

            $errorData = [
                'error' => $this->translator->translate('CRUD6.EDIT.ERROR', ['model' => $crudSchema['model']]),
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
