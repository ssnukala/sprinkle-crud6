<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

class ApiAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $modelName = $this->getModelNameFromRequest($request);
        $schema = $this->schemaService->getSchema($modelName);
        $this->validateAccess($modelName, 'read');

        $this->logger->debug("CRUD6: API request for model: {$schema['model']}");

        $responseData = [
            'message' => $this->translator->translate('CRUD6.API.SUCCESS', ['model' => $schema['title'] ?? $schema['model']]),
            'model' => $schema['model'],
            'schema' => $schema
        ];

        $response->getBody()->write(json_encode($responseData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
