<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Schema Injector Middleware
 * 
 * Loads and validates the JSON schema for the requested model,
 * then injects it into the request attributes for use by controllers.
 */
class SchemaInjector implements MiddlewareInterface
{
    public function __construct(
        protected SchemaService $schemaService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        
        if ($route === null) {
            throw new NotFoundException('Route not found');
        }

        $model = $route->getArgument('model');
        
        if ($model === null) {
            throw new NotFoundException('Model parameter not found in route');
        }

        try {
            $schema = $this->schemaService->getSchema($model);
            
            // Inject schema and model into request attributes
            $request = $request
                ->withAttribute('crud6_model', $model)
                ->withAttribute('crud6_schema', $schema);
                
        } catch (SchemaNotFoundException $e) {
            throw new SchemaNotFoundException("Schema not found for model: {$model}");
        }

        return $handler->handle($request);
    }
}