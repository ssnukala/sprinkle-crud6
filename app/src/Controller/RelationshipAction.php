<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Illuminate\Database\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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

/**
 * Manages many-to-many relationships for CRUD6 models.
 *
 * This controller handles attaching and detaching related records through
 * pivot tables for many-to-many relationships.
 * 
 * POST attaches related records
 * DELETE detaches related records
 * 
 * This route requires authentication and proper permissions.
 *
 * Request type: POST (attach) or DELETE (detach)
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserManageRolesAction
 */
class RelationshipAction extends Base
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
        // Get the model name and relationship name from the route
        $modelName = $args['model'] ?? '';
        $id = $args['id'] ?? '';
        $relationName = $args['relation'] ?? '';
        
        // Determine if this is attach (POST) or detach (DELETE)
        $method = $request->getMethod();
        $isAttach = ($method === 'POST');

        // Load the schema for this model
        $schema = $this->schemaService->getSchema($modelName);

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

        // Check if relationship is defined in schema
        $relationships = $schema['relationships'] ?? [];
        $relationshipConfig = null;
        
        foreach ($relationships as $config) {
            if ($config['name'] === $relationName && $config['type'] === 'many_to_many') {
                $relationshipConfig = $config;
                break;
            }
        }

        if ($relationshipConfig === null) {
            throw new \RuntimeException("Many-to-many relationship '{$relationName}' not found in schema for model '{$modelName}'");
        }

        // Get POST/DELETE parameters - should contain array of IDs to attach/detach
        $params = (array) $request->getParsedBody();
        $relatedIds = $params['ids'] ?? [];

        if (!is_array($relatedIds) || empty($relatedIds)) {
            throw new \InvalidArgumentException('No IDs provided for relationship operation');
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Get pivot table name and key names from relationship config
            $pivotTable = $relationshipConfig['pivot_table'] ?? null;
            $foreignKey = $relationshipConfig['foreign_key'] ?? $modelName . '_id';
            $relatedKey = $relationshipConfig['related_key'] ?? $relationName . '_id';

            if ($pivotTable === null) {
                throw new \RuntimeException("Pivot table not specified for relationship '{$relationName}'");
            }

            if ($isAttach) {
                // Attach: Insert records into pivot table
                foreach ($relatedIds as $relatedId) {
                    $this->db->table($pivotTable)->insertOrIgnore([
                        $foreignKey => $id,
                        $relatedKey => $relatedId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $action = 'attached';
                $messageKey = 'CRUD6.RELATIONSHIP.ATTACH_SUCCESS';
            } else {
                // Detach: Delete records from pivot table
                $this->db->table($pivotTable)
                    ->where($foreignKey, $id)
                    ->whereIn($relatedKey, $relatedIds)
                    ->delete();

                $action = 'detached';
                $messageKey = 'CRUD6.RELATIONSHIP.DETACH_SUCCESS';
            }

            // Log activity
            $this->userActivityLogger->info(
                "User {$currentUser->user_name} {$action} {$relationName} for {$modelName} {$id}.",
                [
                    'type'     => 'relationship_' . $action,
                    'model'    => $modelName,
                    'id'       => $id,
                    'relation' => $relationName,
                    'count'    => count($relatedIds),
                ]
            );

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();

            throw $e;
        }

        // Success message
        $message = $this->translator->translate($messageKey, [
            'model'    => $schema['title'] ?? $modelName,
            'relation' => $relationshipConfig['title'] ?? $relationName,
            'count'    => count($relatedIds),
        ]);

        return ApiResponse::success($response, $message);
    }
}
