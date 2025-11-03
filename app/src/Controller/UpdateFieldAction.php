<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Util\ApiResponse;
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
        
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
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

        try {
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

            // Check if the field is not editable (readonly)
            // editable: false means the field is readonly
            if (isset($fieldConfig['editable']) && $fieldConfig['editable'] === false) {
                $this->logger->warning("CRUD6 [UpdateFieldAction] Attempt to update non-editable field", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                ]);
                throw new \RuntimeException("Field '{$fieldName}' is not editable and cannot be updated");
            }
            
            // Also check legacy readonly attribute for backward compatibility
            if (isset($fieldConfig['readonly']) && $fieldConfig['readonly'] === true) {
                $this->logger->warning("CRUD6 [UpdateFieldAction] Attempt to update readonly field (legacy attribute)", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                ]);
                throw new \RuntimeException("Field '{$fieldName}' is readonly and cannot be updated");
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

            // Create a validation schema for just this field
            $validationSchema = new RequestSchema([
                $fieldName => $fieldConfig['validation'] ?? []
            ]);

            // Validate the single field
            $validator = new ServerSideValidator($validationSchema, $this->translator);
            if ($validator->validate($params) === false) {
                $this->logger->error("CRUD6 [UpdateFieldAction] Validation failed", [
                    'model' => $crudSchema['model'],
                    'field' => $fieldName,
                    'errors' => $validator->errors(),
                ]);

                $e = new ValidationException();
                $e->addErrors($validator->errors());

                throw $e;
            }
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Validation passed", [
                'model' => $crudSchema['model'],
                'field' => $fieldName,
            ]);

            // Transform data
            $transformer = new RequestDataTransformer($validationSchema);
            $data = $transformer->transform($params);
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Data transformed", [
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
                    $crudModel->{$fieldName} = $data[$fieldName];
                    
                    $this->debugLog("CRUD6 [UpdateFieldAction] Field value updated", [
                        'model' => $crudSchema['model'],
                        'record_id' => $recordId,
                        'field' => $fieldName,
                        'old_value' => $oldValue,
                        'new_value' => $data[$fieldName],
                    ]);
                }

                $crudModel->save();
                
                $this->debugLog("CRUD6 [UpdateFieldAction] Model saved to database", [
                    'model' => $crudSchema['model'],
                    'record_id' => $recordId,
                    'field' => $fieldName,
                ]);

                // Get the current user for logging
                /** @var UserInterface */
                $currentUser = $this->authenticator->user();

                // Log activity
                $this->userActivityLogger->info(
                    "User {$currentUser->user_name} updated field '{$fieldName}' for {$crudSchema['model']} {$crudModel->id}.",
                    [
                        'type'    => 'update_field',
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
                
                $this->logger->error("CRUD6 [UpdateFieldAction] Transaction rolled back", [
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
            $message = $this->translator->translate('CRUD6.UPDATE_FIELD_SUCCESSFUL', [
                'model' => $crudSchema['title'] ?? $crudSchema['model'],
                'field' => $fieldConfig['label'] ?? $fieldName,
            ]);
            
            $this->debugLog("CRUD6 [UpdateFieldAction] Update field response prepared", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'field' => $fieldName,
                'message' => $message,
            ]);

            return ApiResponse::success($response, $message, $recordData);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [UpdateFieldAction] ===== UPDATE FIELD REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'record_id' => $recordId,
                'field' => $fieldName,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
