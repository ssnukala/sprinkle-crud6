<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Database\Connection;
use UserFrosting\Alert\AlertStream;

class EditAction extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
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
        $this->validateAccess($schema, 'edit');
        $this->logger->debug("CRUD6: Edit request for record ID: {$recordId} model: {$model}");
        try {
            $table = $this->getTableName($schema);
            $primaryKey = $schema['primary_key'] ?? 'id';
            $record = $this->db->table($table)
                ->where($primaryKey, $recordId)
                ->first();
            if (!$record) {
                $errorData = [
                    'error' => $this->translator->translate('CRUD6.EDIT.NOT_FOUND', ['model' => $model]),
                    'model' => $model,
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $responseData = [
                'message' => $this->translator->translate('CRUD6.EDIT.SUCCESS', ['model' => $model]),
                'model' => $model,
                'id' => $recordId,
                'data' => (array) $record
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to edit record for model: {$model}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);
            $errorData = [
                'error' => $this->translator->translate('CRUD6.EDIT.ERROR', ['model' => $model]),
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
