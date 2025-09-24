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
use UserFrosting\Sprinkle\Admin\Sprunje\UserSprunje;

class SprunjeAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected CRUD6Sprunje $sprunje,
        protected SchemaService $schemaService,
        protected UserSprunje $userSprunje,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }

    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        parent::__invoke($crudSchema, $crudModel, $request, $response);
        // get the request parameter "relation" if exists 
        // then look up if any class exists for that relation
        // if exists, then use that class to fetch the data
        // else use the default sprunje class
        // Base already has a method to get the parameter use that
        $relation = $this->getParameter($request, 'relation', 'NONE');
        $this->logger->debug("Line 41 : SprunjeAction::__invoke called for relation: {$relation}");
        if ($relation === 'users') {
            // use the UserSprunje
            $this->logger->debug("Line 44: CrudModel ID: {$crudModel->id}", $crudModel->toArray());
            $groupid = $this->getParameter($request, 'group_id', '-1');
            if ($groupid != '-1') {
                $params = $request->getQueryParams();
                $this->userSprunje->setOptions($params);
                $this->userSprunje->extendQuery(function ($query) use ($crudModel) {
                    return $query->where('group_id', $crudModel->id);
                });
            }

            // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
            // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
            return $this->userSprunje->toResponse($response);
        } else {
            // use the default sprunje
            // Sprunje-specific logic
            $modelName = $this->getModelNameFromRequest($request);
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
}
