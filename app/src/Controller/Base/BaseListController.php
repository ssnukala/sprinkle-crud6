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
use Slim\Views\Twig;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;

/**
 * Base List Controller
 * 
 * Handles listing records for any model based on JSON schema configuration.
 * Provides both web interface and API endpoints.
 */
class BaseListController extends BaseController
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Twig $view,
        protected CRUD6Sprunje $sprunje
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    /**
     * Render the list page for a model
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        
        $this->validateAccess($schema, 'read');
        
        $this->logger->debug("CRUD6: Rendering list page for model: {$model}");
        
        // Prepare template data
        $templateData = [
            'crud6' => [
                'model' => $model,
                'schema' => $schema,
                'title' => $schema['title'] ?? ucfirst($model) . ' Management',
                'description' => $schema['description'] ?? "Manage {$model} records",
            ]
        ];
        
        // Use model-specific template or fallback to generic
        $template = $schema['template'] ?? 'pages/crud6/list.html.twig';
        
        return $this->view->render($response, $template, $templateData);
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