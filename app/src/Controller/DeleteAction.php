<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Alert\AlertStream;
use UserFrosting\I18n\Translator;
use Illuminate\Database\Connection;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

class DeleteAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected AlertStream $alert,
        protected Translator $translator,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $modelName = $this->getModelNameFromRequest($request);
        $schema = $this->schemaService->getSchema($modelName);
        $this->validateAccess($modelName, 'delete');
        
        // For DeleteAction, the crudModel should contain the specific record since ID is in the route
        $primaryKey = $schema['primary_key'] ?? 'id';
        $recordId = $crudModel->getAttribute($primaryKey);
        
        $this->logger->debug("CRUD6: Deleting record ID: {$recordId} for model: {$schema['model']}");
        
        try {
            // Use the model instance for deletion instead of raw query builder
            if ($schema['soft_delete'] ?? false) {
                $success = $crudModel->softDelete();
                $this->logger->debug("CRUD6: Soft deleted record ID: {$recordId} for model: {$schema['model']}");
            } else {
                $success = $crudModel->delete();
                $this->logger->debug("CRUD6: Hard deleted record ID: {$recordId} for model: {$schema['model']}");
            }
            
            if (!$success) {
                $errorData = [
                    'error' => $this->translator->translate('CRUD6.DELETE.ERROR', ['model' => $schema['model']]),
                    'model' => $schema['model'],
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
            
            $responseData = [
                'message' => $this->translator->translate('CRUD6.DELETE.SUCCESS', ['model' => $schema['model']]),
                'model' => $schema['model'],
                'id' => $recordId,
                'soft_delete' => $schema['soft_delete'] ?? false
            ];
            
            $this->alert->addMessageTranslated('success', 'CRUD6.DELETE.SUCCESS', [
                'model' => $schema['title'] ?? $schema['model']
            ]);
            
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to delete record for model: {$schema['model']}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);
            
            $errorData = [
                'error' => $this->translator->translate('CRUD6.DELETE.ERROR', ['model' => $schema['model']]),
                'message' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
