<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;

/**
 * Base List Controller
 * 
 * Handles listing records for any model based on JSON schema configuration.
 * Provides API endpoints for data listing.
 */
class BaseListController extends BaseController
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected CRUD6Sprunje $sprunje
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    /**
     * API endpoint for listing records with pagination, sorting, and filtering
     */
    public function apiList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        
        $this->validateAccess($schema, 'read');
        
        $this->logger->debug("CRUD6: API list for model: {$model}");
        
        // Get query parameters
        $params = $request->getQueryParams();
        
        // Setup sprunje with schema configuration
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