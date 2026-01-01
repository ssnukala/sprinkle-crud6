<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\ProcessesRelationshipActions;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

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
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Receive the request, dispatch to the handler, and return the payload to
     * the response.
     *
     * @param array                  $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface    $crudModel  The configured model instance with record loaded (auto-injected)
     * @param ServerRequestInterface $request    The HTTP request
     * @param Response               $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, Response $response): Response
    {
        try {
            // Validate access permission for delete operation
            $this->validateAccess($crudSchema, 'delete');
            
            $primaryKey = $crudSchema['primary_key'] ?? 'id';
            $recordId = $crudModel->getAttribute($primaryKey);

            $this->debugLog("CRUD6 [DeleteAction] ===== DELETE REQUEST START =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
            ]);

            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            $this->handle($crudSchema, $crudModel);
            
            // Translate the model display name if it's a translation key
            $translatedModel = $this->translator->translate($modelDisplayName);

            // Write response with title and description
            $title = $this->translator->translate('CRUD6.DELETE.SUCCESS_TITLE');
            $description = $this->translator->translate('CRUD6.DELETE.SUCCESS', ['model' => $translatedModel]);
            
            $this->debugLog("CRUD6 [DeleteAction] Delete response prepared", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'title' => $title,
                'description' => $description,
            ]);

            return $this->jsonResponseWithTitle($response, $title, $description);
        } catch (ForbiddenException $e) {
            // Let ForbiddenException bubble up to framework's error handler
            throw $e;
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [DeleteAction] ===== DELETE REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId ?? 'unknown',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while deleting the record', 500);
        }
    }

    /**
     * Handle the request.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     */
    protected function handle(array $crudSchema, CRUD6ModelInterface $crudModel): void
    {
        // Access-controlled page based on the record.
        $this->validateAccess($crudSchema, 'delete');

        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        // Get current user. Won't be null, since it's AuthGuarded.
        /** @var UserInterface $currentUser */
        $currentUser = $this->authenticator->user();
        
        // Check if record is already soft-deleted
        if (method_exists($crudModel, 'trashed') && $crudModel->trashed()) {
            throw new \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException(
                'Resource not found or has already been deleted'
            );
        }

        $this->debugLog("CRUD6 [DeleteAction] Starting delete operation", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'user' => $currentUser->user_name,
            'user_id' => $currentUser->id,
            'soft_delete' => $crudSchema['soft_delete'] ?? false,
        ]);

        // Begin transaction - DB will be rolled back if an exception occurs
        $this->db->transaction(function () use ($crudSchema, $crudModel, $currentUser, $recordId) {
            // Determine if this is a soft delete operation
            $isSoftDelete = $crudSchema['soft_delete'] ?? false;
            
            // Cascade delete child records based on schema's "details" configuration
            // This prevents foreign key constraint violations
            // For soft deletes, child records that support soft delete will also be soft deleted
            $this->cascadeDeleteChildRecords($crudModel, $crudSchema, $this->schemaService, $isSoftDelete);

            // Process relationship actions for on_delete event (before deleting the record)
            $this->processRelationshipActions($crudModel, $crudSchema, [], 'on_delete');

            // Delete the record (supports soft delete if configured)
            if ($isSoftDelete) {
                $crudModel->softDelete();
                $this->debugLog("CRUD6 [DeleteAction] Soft deleted record", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                ]);
            } else {
                $crudModel->delete();
                $this->debugLog("CRUD6 [DeleteAction] Hard deleted record", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                ]);
            }

            // Create activity record
            $modelDisplayName = $this->getModelDisplayName($crudSchema);
            $this->userActivityLogger->info("User {$currentUser->user_name} deleted {$modelDisplayName} record.", [
                'type'    => "crud6_{$crudSchema['model']}_delete",
                'user_id' => $currentUser->id,
            ]);
        });

        $this->debugLog("CRUD6 [DeleteAction] Transaction completed successfully", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
        ]);
    }
}
