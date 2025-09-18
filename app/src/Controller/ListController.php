<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;

class ListController extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected CRUD6Sprunje $sprunje
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    public function apiList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $this->validateAccess($schema, 'read');
        $this->logger->debug("CRUD6: API list for model: {$model}");
        $params = $request->getQueryParams();
        $this->sprunje->setupSprunje(
            $this->getTableName($schema),
            $this->getSortableFields($schema),
            $this->getFilterableFields($schema),
            $schema
        );
        $this->sprunje->setOptions($params);
        return $this->sprunje->toResponse($response);
    }
}
