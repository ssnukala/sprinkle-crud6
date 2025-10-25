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
        
        // Get the relationship name from the route
        $relationName = $this->getParameter($request, 'relation');
        
        // Determine if this is attach (POST) or detach (DELETE)
        $method = $request->getMethod();
        $isAttach = ($method === 'POST');

        // Access control check
        $this->validateAccess($crudSchema, 'update');

        // The record is already loaded by the middleware into $crudModel

        // Check if relationship is defined in schema
        $relationships = $crudSchema['relationships'] ?? [];
        $relationshipConfig = null;
        
        foreach ($relationships as $config) {
            if ($config['name'] === $relationName && $config['type'] === 'many_to_many') {
                $relationshipConfig = $config;
                break;
            }
        }

        if ($relationshipConfig === null) {
            throw new \RuntimeException("Many-to-many relationship '{$relationName}' not found in schema for model '{$crudSchema['model']}'");
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
            $foreignKey = $relationshipConfig['foreign_key'] ?? $crudSchema['model'] . '_id';
            $relatedKey = $relationshipConfig['related_key'] ?? $relationName . '_id';

            if ($pivotTable === null) {
                throw new \RuntimeException("Pivot table not specified for relationship '{$relationName}'");
            }

            if ($isAttach) {
                // Attach: Insert records into pivot table
                foreach ($relatedIds as $relatedId) {
                    $this->db->table($pivotTable)->insertOrIgnore([
                        $foreignKey => $crudModel->id,
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
                    ->where($foreignKey, $crudModel->id)
                    ->whereIn($relatedKey, $relatedIds)
                    ->delete();

                $action = 'detached';
                $messageKey = 'CRUD6.RELATIONSHIP.DETACH_SUCCESS';
            }

            // Get the current user for logging
            /** @var UserInterface */
            $currentUser = $this->authenticator->user();

            // Log activity
            $this->userActivityLogger->info(
                "User {$currentUser->user_name} {$action} {$relationName} for {$crudSchema['model']} {$crudModel->id}.",
                [
                    'type'     => 'relationship_' . $action,
                    'model'    => $crudSchema['model'],
                    'id'       => $crudModel->id,
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
            'model'    => $crudSchema['title'] ?? $crudSchema['model'],
            'relation' => $relationshipConfig['title'] ?? $relationName,
            'count'    => count($relatedIds),
        ]);

        return ApiResponse::success($response, $message);
    }
}
