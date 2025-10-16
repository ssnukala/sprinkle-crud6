<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Util\ApiResponse;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Support\Message\UserMessage;

/**
 * Processes the request to delete an existing CRUD6 model record.
 *
 * Deletes the specified record.
 * Before doing so, checks that:
 * 1. The user has permission to delete this record;
 * 2. The submitted data is valid.
 * This route requires authentication.
 *
 * Request type: DELETE
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\Group\GroupDeleteAction
 */
class DeleteAction extends Base
{
    /**
     * Inject dependencies.
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService,
        protected Translator $translator,
        protected Connection $db,
        protected UserActivityLogger $userActivityLogger,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Response $response): Response
    {
        $userMessage = $this->handle($crudSchema, $crudModel);

        // Message
        $message = $this->translator->translate($userMessage->message, $userMessage->parameters);

        // Write response
        $payload = new ApiResponse($message);
        $response->getBody()->write((string) $payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle the request.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     */
    protected function handle(array $crudSchema, CRUD6ModelInterface $crudModel): UserMessage
    {
        // Access-controlled page based on the record.
        $this->validateAccess($crudSchema, 'delete');

        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        // Get current user. Won't be null, since it's AuthGuarded.
        /** @var UserInterface $currentUser */
        $currentUser = $this->authenticator->user();

        $this->logger->debug("CRUD6: Deleting record ID: {$recordId} for model: {$crudSchema['model']}", [
            'user' => $currentUser->user_name,
        ]);

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($crudSchema, $crudModel, $currentUser, $recordId) {
            // Delete the record (supports soft delete if configured)
            if ($crudSchema['soft_delete'] ?? false) {
                $crudModel->softDelete();
                $this->logger->debug("CRUD6: Soft deleted record ID: {$recordId} for model: {$crudSchema['model']}");
            } else {
                $crudModel->delete();
                $this->logger->debug("CRUD6: Hard deleted record ID: {$recordId} for model: {$crudSchema['model']}");
            }

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            $this->userActivityLogger->info("User {$currentUser->user_name} deleted {$modelDisplayName} record.", [
                'type'    => "crud6_{$crudSchema['model']}_delete",
                'user_id' => $currentUser->id,
            ]);
        });

        $modelDisplayName = $this->getModelDisplayName($crudSchema);
        return new UserMessage('CRUD6.DELETE.SUCCESS', [
            'model' => $modelDisplayName,
        ]);
    }
}
