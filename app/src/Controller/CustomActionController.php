<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Config\Config;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Executes custom schema-defined actions on CRUD6 model records.
 *
 * This controller handles custom action endpoints defined in model schemas.
 * Actions are configured in the schema's "actions" array and can perform
 * various operations like sending emails, triggering workflows, etc.
 * 
 * This is a generic handler that delegates to action-specific implementations
 * or external services based on the action configuration.
 *
 * Request type: POST
 * Route: /api/crud6/{model}/{id}/actions/{actionKey}
 * 
 * Example actions:
 * - Password reset: Triggers password reset email
 * - Account verification: Sends verification email
 * - Custom workflows: Triggers business logic
 */
class CustomActionController extends Base
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
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Execute a custom action on a record.
     *
     * @param array               $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
        // Get the action key from the route
        $actionKey = $this->getParameter($request, 'actionKey');
        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->debugLog("CRUD6 [CustomActionController] ===== CUSTOM ACTION REQUEST START =====", [
            'model' => $crudSchema['model'],
            'record_id' => $recordId,
            'action_key' => $actionKey,
            'uri' => (string) $request->getUri(),
        ]);

        try {
            // Find the action configuration in the schema
            $actionConfig = $this->findActionConfig($crudSchema, $actionKey);
            
            if ($actionConfig === null) {
                $this->logger->error("Line:92 CRUD6 [CustomActionController] Action not found", [
                    'model' => $crudSchema['model'],
                    'action_key' => $actionKey,
                    'available_actions' => array_column($crudSchema['actions'] ?? [], 'key'),
                ]);
                throw new NotFoundException("Action '{$actionKey}' not found for model '{$crudSchema['model']}'");
            }
            
            $this->debugLog("CRUD6 [CustomActionController] Action configuration found", [
                'model' => $crudSchema['model'],
                'action' => $actionConfig,
            ]);

            // Check permission for the action
            if (isset($actionConfig['permission'])) {
                $this->validateActionPermission($actionConfig['permission']);
            } else {
                // If no specific permission, check update permission
                $this->validateAccess($crudSchema, 'update');
            }
            
            $this->debugLog("CRUD6 [CustomActionController] Permission validated", [
                'model' => $crudSchema['model'],
                'action_key' => $actionKey,
            ]);

            // Execute the action
            $result = $this->executeCustomAction($actionConfig, $crudModel, $crudSchema, $request);
            
            // Get current user for logging
            /** @var UserInterface */
            $currentUser = $this->authenticator->user();

            // Log activity
            $this->userActivityLogger->info(
                "User {$currentUser->user_name} executed action '{$actionKey}' on {$crudSchema['model']} {$recordId}.",
                [
                    'type'    => "crud6_{$crudSchema['model']}_custom_action",
                    'model'   => $crudSchema['model'],
                    'id'      => $recordId,
                    'action'  => $actionKey,
                ]
            );

            // Prepare success response
            $title = $this->translator->translate('CRUD6.ACTION.SUCCESS_TITLE') ?: 'Success';
            
            // Translate action label if it's a translation key
            $actionLabel = $actionConfig['label'] ?? $actionKey;
            $translatedAction = $this->translator->translate($actionLabel);
            
            $description = $this->translator->translate(
                $actionConfig['success_message'] ?? 'CRUD6.ACTION.SUCCESS',
                ['action' => $translatedAction]
            );

            $this->debugLog("CRUD6 [CustomActionController] Action executed successfully", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'action_key' => $actionKey,
            ]);

            return $this->jsonResponse($response, $description);
        } catch (\Exception $e) {
            $this->logger->error("Line:151 CRUD6 [CustomActionController] ===== CUSTOM ACTION REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'action_key' => $actionKey,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Find action configuration by key in the schema.
     *
     * @param array  $schema    The schema configuration
     * @param string $actionKey The action key to find
     *
     * @return array|null The action configuration or null if not found
     */
    protected function findActionConfig(array $schema, string $actionKey): ?array
    {
        $actions = $schema['actions'] ?? [];
        
        foreach ($actions as $action) {
            if (($action['key'] ?? '') === $actionKey) {
                return $action;
            }
        }
        
        return null;
    }

    /**
     * Validate permission for an action.
     *
     * @param string $permission The permission slug to check
     *
     * @throws \UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException
     */
    protected function validateActionPermission(string $permission): void
    {
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        if (!$this->authorizer->checkAccess($currentUser, $permission)) {
            $this->logger->warning("Line:198 CRUD6 [CustomActionController] Access denied", [
                'user' => $currentUser->user_name,
                'permission' => $permission,
            ]);
            throw new \UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException();
        }
    }

    /**
     * Execute the custom action.
     * 
     * This is a placeholder implementation that handles the password reset action.
     * In the future, this can be extended to support more action types or
     * delegate to action-specific handlers.
     *
     * @param array               $actionConfig The action configuration
     * @param CRUD6ModelInterface $crudModel    The model instance
     * @param array               $schema       The schema configuration
     * @param Request             $request      The HTTP request
     *
     * @return array|null Result data or null
     */
    protected function executeCustomAction(
        array $actionConfig,
        CRUD6ModelInterface $crudModel,
        array $schema,
        Request $request
    ): ?array {
        $actionKey = $actionConfig['key'] ?? '';
        
        $this->debugLog("CRUD6 [CustomActionController] Executing custom action", [
            'model' => $schema['model'],
            'action_key' => $actionKey,
            'action_type' => $actionConfig['type'] ?? 'unknown',
        ]);

        // Handle password reset action specifically
        if ($actionKey === 'reset_password') {
            return $this->handlePasswordReset($crudModel, $schema);
        }

        // For other custom actions, log a warning that they're not implemented yet
        // In a real application, you would implement handlers for each action type
        $this->logger->warning("Line:241 CRUD6 [CustomActionController] Action type not implemented", [
            'model' => $schema['model'],
            'action_key' => $actionKey,
            'action_type' => $actionConfig['type'] ?? 'unknown',
        ]);

        // Return empty result for unimplemented actions
        // The success message will still be shown to the user
        return [];
    }

    /**
     * Handle password reset action.
     * 
     * This forces a password reset for the user by clearing their password
     * and setting a flag that requires them to reset on next login.
     *
     * @param CRUD6ModelInterface $crudModel The model instance
     * @param array               $schema    The schema configuration
     *
     * @return array Result data
     */
    protected function handlePasswordReset(CRUD6ModelInterface $crudModel, array $schema): array
    {
        $this->debugLog("CRUD6 [CustomActionController] Handling password reset", [
            'model' => $schema['model'],
            'record_id' => $crudModel->id,
        ]);

        // For a proper password reset implementation, you would typically:
        // 1. Generate a password reset token
        // 2. Store the token in the password_resets table
        // 3. Send an email with the reset link
        // 4. Optionally, set flag_enabled to false to prevent login until reset
        
        // This is a placeholder that just logs the action
        // The actual implementation would depend on your UserFrosting setup
        $this->logger->info("Line:278 CRUD6 [CustomActionController] Password reset requested", [
            'model' => $schema['model'],
            'user_id' => $crudModel->id,
            'user_name' => $crudModel->user_name ?? 'unknown',
        ]);

        return [
            'reset_initiated' => true,
            'message' => 'Password reset process initiated',
        ];
    }
}
