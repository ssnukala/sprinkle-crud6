<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authenticate\Hasher;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\HashesPasswords;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\TransformsData;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Processes the request to update a single field of an existing CRUD6 model record.
 *
 * This allows for partial updates of a record, updating only a specific field
 * without having to send the entire record data.
 * 
 * Processes the request from the field update form, checking that:
 * 1. The user has the necessary permissions to update the posted field;
 * 2. The submitted data is valid.
 * This route requires authentication.
 *
 * Request type: PUT
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserUpdateFieldAction
 * @see \UserFrosting\Sprinkle\Admin\Controller\Role\RoleUpdateFieldAction
 */
class UpdateFieldAction extends Base
{
    use TransformsData;
    use HashesPasswords;
    
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
        protected Connection $db,
        protected Hasher $hasher,
        protected ServerSideValidator $validator,
        protected RequestDataTransformer $transformer,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Invoke the controller.
     *
     * @param array               $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded (auto-injected)
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        try {
            parent::__invoke($crudSchema, $crudModel, $request, $response);
            
            // Validate access permission for update operation
            $this->validateAccess($crudSchema, 'edit');
            
            // Get the field name from the route
            $fieldName = $this->getParameter($request, 'field');
            $primaryKey = $crudSchema['primary_key'] ?? 'id';
            $recordId = $crudModel->getAttribute($primaryKey);

            $this->debugLog("CRUD6 [UpdateFieldAction] ===== UPDATE FIELD REQUEST START =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'field' => $fieldName,
                'uri' => (string) $request->getUri(),
            ]);

            // Check if this field exists and is editable in the schema
            if (!isset($crudSchema['fields'][$fieldName])) {
                $this->logger->error("CRUD6 [UpdateFieldAction] Field does not exist", [
                    'model' => $crudSchema['model'],
                    'field' => $fieldName,
                    'available_fields' => array_keys($crudSchema['fields'] ?? []),
                ]);
                throw new \RuntimeException("Field '{$fieldName}' does not exist in schema for model '{$crudSchema['model']}'");
            }

            $fieldConfig = $crudSchema['fields'][$fieldName];

            // Check if the field is not editable
            if (isset($fieldConfig['editable']) && $fieldConfig['editable'] === false) {
                $this->logger->warning("Line:105 CRUD6 [UpdateFieldAction] Attempt to update non-editable field", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                ]);
                throw new \RuntimeException("Field '{$fieldName}' is not editable and cannot be updated");
            }

            // Access control check
            $this->validateAccess($crudSchema, 'update');
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Access validated", [
                'model' => $crudSchema['model'],
                'field' => $fieldName,
            ]);

            // The record is already loaded by the middleware into $crudModel

            // Get PUT parameters
            $params = (array) $request->getParsedBody();
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Request parameters received", [
                'model' => $crudSchema['model'],
                'field' => $fieldName,
                'params' => $params,
            ]);

            // Transform and validate single field using TransformsData trait
            $data = $this->transformAndValidateField($fieldName, $fieldConfig, $params, $crudSchema);
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Data transformed and validated", [
                'model' => $crudSchema['model'],
                'field' => $fieldName,
                'transformed_data' => $data,
            ]);

            // Begin transaction
            $this->db->beginTransaction();

            try {
                // Only update the specific field
                if (array_key_exists($fieldName, $data)) {
                    $oldValue = $crudModel->{$fieldName};
                    $newValue = $data[$fieldName];
                    
                    // Hash password fields before saving using HashesPasswords trait helper
                    if (($fieldConfig['type'] ?? '') === 'password' && !empty($newValue)) {
                        $newValue = $this->hashPassword($newValue);
                        $this->debugLog("CRUD6 [UpdateFieldAction] Password field hashed", [
                            'field' => $fieldName,
                        ]);
                    }
                    
                    $crudModel->{$fieldName} = $newValue;
                    
                    $this->debugLog("CRUD6 [UpdateFieldAction] Field value updated", [
                        'model' => $crudSchema['model'],
                        'record_id' => $recordId,
                        'field' => $fieldName,
                        'old_value' => $oldValue,
                        'new_value' => ($fieldConfig['type'] ?? '') === 'password' ? '[REDACTED]' : $newValue,
                    ]);
                }

                $crudModel->save();

                // Get the current user for logging
                /** @var UserInterface */
                $currentUser = $this->authenticator->user();

                // Log activity
                $this->userActivityLogger->info(
                    "User {$currentUser->user_name} updated field '{$fieldName}' for {$crudSchema['model']} {$crudModel->id}.",
                    [
                        'type'    => 'update_field',
                        'user_id' => $currentUser->id,
                        'model'   => $crudSchema['model'],
                        'id'      => $crudModel->id,
                        'field'   => $fieldName,
                    ]
                );

                $this->db->commit();
                
                $this->debugLog("CRUD6 [UpdateFieldAction] Transaction committed", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                ]);
            } catch (\Exception $e) {
                $this->db->rollBack();
                
                $this->logger->error("Line:203 CRUD6 [UpdateFieldAction] Transaction rolled back", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                ]);

                throw $e;
            }

            // Get updated record data
            $recordData = $crudModel->toArray();
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Record data retrieved", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'field' => $fieldName,
                'updated_value' => $recordData[$fieldName] ?? null,
            ]);

            // Success message
            // Translate the model title and field label first, then pass to the success message
            $modelTitle = $crudSchema['title'] ?? $crudSchema['model'];
            $fieldLabel = $fieldConfig['label'] ?? $fieldName;
            
            // If they look like translation keys, translate them
            $translatedModel = $this->translator->translate($modelTitle);
            $translatedField = $this->translator->translate($fieldLabel);
            
            $message = $this->translator->translate('CRUD6.UPDATE_FIELD_SUCCESSFUL', [
                'model' => $translatedModel,
                'field' => $translatedField,
            ]);
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Update field response prepared", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'field' => $fieldName,
                'message' => $message,
            ]);

            return $this->jsonResponse($response, $message);
        } catch (ForbiddenException $e) {
            // User lacks permission - return 403
            return $this->jsonResponse($response, $e->getMessage(), 403);
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [UpdateFieldAction] ===== UPDATE FIELD REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId ?? 'unknown',
                'field' => $fieldName ?? 'unknown',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while updating the field', 500);
        }
    }
}
