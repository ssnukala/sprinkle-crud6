<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Database\Connection;
use UserFrosting\Alert\AlertStream;

class DeleteController extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected AlertStream $alert
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $recordId = $this->getRecordId($request);
        $this->validateAccess($schema, 'delete');
        $this->logger->debug("CRUD6: Deleting record ID: {$recordId} for model: {$model}");
        try {
            $table = $this->getTableName($schema);
            $primaryKey = $schema['primary_key'] ?? 'id';
            $existingRecord = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            if (!$existingRecord) {
                $errorData = [
                    'error' => 'Record not found',
                    'model' => $model,
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            if ($schema['soft_delete'] ?? false) {
                $deleteData = [
                    'deleted_at' => date('Y-m-d H:i:s')
                ];
                $affectedRows = $this->db->table($table)
                    ->where($primaryKey, $recordId)
                    ->update($deleteData);
                $this->logger->debug("CRUD6: Soft deleted record ID: {$recordId} for model: {$model}");
            } else {
                $affectedRows = $this->db->table($table)
                    ->where($primaryKey, $recordId)
                    ->delete();
                $this->logger->debug("CRUD6: Hard deleted record ID: {$recordId} for model: {$model}");
            }
            if ($affectedRows === 0) {
                $errorData = [
                    'error' => 'No records were deleted',
                    'model' => $model,
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            $responseData = [
                'message' => "Record deleted successfully",
                'model' => $model,
                'id' => $recordId,
                'soft_delete' => $schema['soft_delete'] ?? false
            ];
            $this->alert->addMessageTranslated('success', 'CRUD6.DELETE.SUCCESS', [
                'model' => $schema['title'] ?? $model
            ]);
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to delete record for model: {$model}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);
            $errorData = [
                'error' => 'Failed to delete record',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
