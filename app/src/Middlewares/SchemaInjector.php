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
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\CRUD6\Exceptions\SchemaNotFoundException;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;


/**
 * Schema Injector Middleware.
 * 
 * Loads and validates the JSON schema for the requested model, then injects it
 * into the request attributes for use by controllers. Follows the UserFrosting 6
 * middleware pattern.
 * 
 * Note: This middleware is less commonly used as CRUD6Injector handles both
 * schema and model injection. Use this when you only need schema without model.
 * 
 * Injected request attributes:
 * - crud6_model: The model name from the route
 * - crud6_schema: The loaded schema configuration array
 * 
 * @see \UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector
 */
class SchemaInjector implements MiddlewareInterface
{
    /**
     * Constructor for SchemaInjector.
     * 
     * @param SchemaService $schemaService Schema service for loading definitions
     * @param Translator    $translator    Translator for i18n messages
     */
    public function __construct(
        protected SchemaService $schemaService,
        protected Translator $translator,
    ) {}

    /**
     * Process the middleware.
     * 
     * Extracts model name from route, loads schema, and injects into request.
     * 
     * @param ServerRequestInterface  $request The HTTP request
     * @param RequestHandlerInterface $handler The request handler
     * 
     * @return ResponseInterface The HTTP response
     * 
     * @throws NotFoundException If route or model parameter not found
     * @throws SchemaNotFoundException If schema cannot be loaded
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if ($route === null) {
            throw new NotFoundException('Route not found');
        }

        $model = $route->getArgument('model');
        //echo "Line 62: CRUD6: SchemaInjector for model: {$model}\n";
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
            throw new SchemaNotFoundException("Line 62: Schema not found for model: {$model}");
        }

        return $handler->handle($request);
    }
}
