<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

class SprunjeAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected CRUD6Sprunje $sprunje,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $modelName = $this->getModelNameFromRequest($request);
        $schema = $this->schemaService->getSchema($modelName);
        $this->validateAccess($modelName, 'read');
        
        $this->logger->debug("CRUD6: Sprunje action for model: {$schema['model']}");
        
        $params = $request->getQueryParams();
        
        $this->sprunje->setupSprunje(
            $crudModel->getTable(),
            $this->getSortableFields($modelName),
            $this->getFilterableFields($modelName),
            $this->getListableFields($modelName)
        );
        
        $this->sprunje->setOptions($params);
        
        return $this->sprunje->toResponse($response);
    }
}
