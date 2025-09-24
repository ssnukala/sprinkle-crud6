<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\I18n\Translator;
use Illuminate\Database\Connection;
use UserFrosting\Alert\AlertStream;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

class EditAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected Connection $db,
        protected AlertStream $alert,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);

        // For EditAction, the crudModel should contain the specific record since ID is in the route
        $primaryKey = $crudSchema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);

        $this->logger->debug("CRUD6: Edit request for record ID: {$recordId} model: {$crudSchema['model']}");

        try {
            $responseData = [
                'message' => $this->translator->translate('CRUD6.EDIT.SUCCESS', ['model' => $crudSchema['model']]),
                'model' => $crudSchema['model'],
                'id' => $recordId,
                'data' => $crudModel->toArray()
            ];

            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to edit record for model: {$crudSchema['model']}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);

            $errorData = [
                'error' => $this->translator->translate('CRUD6.EDIT.ERROR', ['model' => $crudSchema['model']]),
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
