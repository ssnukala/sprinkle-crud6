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
        protected Translator $translator,
        protected UserActivityLogger $userActivityLogger,
        protected Connection $db,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    /**
     * Invoke the controller.
     *
     * @param array               $crudSchema The schema configuration
     * @param CRUD6ModelInterface $crudModel  The configured model instance with record loaded
     * @param Request             $request
     * @param Response            $response
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        
        // Get the field name from the route
        $fieldName = $this->getParameter($request, 'field');

        // Check if this field exists and is editable in the schema
        if (!isset($crudSchema['fields'][$fieldName])) {
            throw new \RuntimeException("Field '{$fieldName}' does not exist in schema for model '{$crudSchema['model']}'");
        }

        $fieldConfig = $crudSchema['fields'][$fieldName];

        // Check if the field is readonly
        if (isset($fieldConfig['readonly']) && $fieldConfig['readonly'] === true) {
            throw new \RuntimeException("Field '{$fieldName}' is readonly and cannot be updated");
        }

        // Access control check
        $this->validateAccess($crudSchema, 'update');

        // The record is already loaded by the middleware into $crudModel

        // Get PUT parameters
        $params = (array) $request->getParsedBody();

        // Create a validation schema for just this field
        $validationSchema = new RequestSchema([
            $fieldName => $fieldConfig['validation'] ?? []
        ]);

        // Validate the single field
        $validator = new ServerSideValidator($validationSchema, $this->translator);
        if ($validator->validate($params) === false) {
            $e = new ValidationException();
            $e->addErrors($validator->errors());

            throw $e;
        }

        // Transform data
        $transformer = new RequestDataTransformer($validationSchema);
        $data = $transformer->transform($params);

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Only update the specific field
            if (array_key_exists($fieldName, $data)) {
                $crudModel->{$fieldName} = $data[$fieldName];
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
                    'model'   => $crudSchema['model'],
                    'id'      => $crudModel->id,
                    'field'   => $fieldName,
                ]
            );

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

        // Get updated record data
        $recordData = $crudModel->toArray();

        // Success message
        $message = $this->translator->translate('CRUD6.UPDATE_FIELD_SUCCESSFUL', [
            'model' => $crudSchema['title'] ?? $crudSchema['model'],
            'field' => $fieldConfig['label'] ?? $fieldName,
        ]);

        return ApiResponse::success($response, $message, $recordData);
    }
}
