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
    }

    /**
     * Invoke the controller.
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        // Get the model name and field name from the route
        $modelName = $args['model'] ?? '';
        $id = $args['id'] ?? '';
        $fieldName = $args['field'] ?? '';

        // Load the schema for this model
        $schema = $this->schemaService->getSchema($modelName);

        // Check if this field exists and is editable in the schema
        if (!isset($schema['fields'][$fieldName])) {
            throw new \RuntimeException("Field '{$fieldName}' does not exist in schema for model '{$modelName}'");
        }

        $fieldConfig = $schema['fields'][$fieldName];

        // Check if the field is readonly
        if (isset($fieldConfig['readonly']) && $fieldConfig['readonly'] === true) {
            throw new \RuntimeException("Field '{$fieldName}' is readonly and cannot be updated");
        }

        // Get the current user
        /** @var UserInterface */
        $currentUser = $this->authenticator->user();

        // Access control check
        $updatePermission = $schema['permissions']['update'] ?? null;
        if ($updatePermission && !$this->authorizer->checkAccess($currentUser, $updatePermission)) {
            throw new ForbiddenException();
        }

        // Get the record model instance from request attribute (set by middleware)
        /** @var CRUD6ModelInterface */
        $record = $request->getAttribute('crud6');

        if ($record === null) {
            throw new \RuntimeException('CRUD6 record not found in request. Make sure CRUD6Injector middleware is active.');
        }

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
                $record->{$fieldName} = $data[$fieldName];
            }

            $record->save();

            // Log activity
            $this->userActivityLogger->info(
                "User {$currentUser->user_name} updated field '{$fieldName}' for {$modelName} {$id}.",
                [
                    'type'    => 'update_field',
                    'model'   => $modelName,
                    'id'      => $id,
                    'field'   => $fieldName,
                ]
            );

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

        // Get updated record data
        $recordData = $record->toArray();

        // Success message
        $message = $this->translator->translate('CRUD6.UPDATE_FIELD_SUCCESSFUL', [
            'model' => $schema['title'] ?? $modelName,
            'field' => $fieldConfig['label'] ?? $fieldName,
        ]);

        return ApiResponse::success($response, $message, $recordData);
    }
}
