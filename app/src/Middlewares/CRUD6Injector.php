<?php

declare(strict_types=1);

/*
 * UserFrosting Admin Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-admin
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */


namespace UserFrosting\Sprinkle\CRUD6\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\Exceptions\CRUD6Exception;
use UserFrosting\Sprinkle\CRUD6\Exceptions\CRUD6NotFoundException;
use UserFrosting\Sprinkle\Core\Middlewares\Injector\AbstractInjector;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
//use UserFrosting\I18n\Translator;
//use Psr\Http\Message\ServerRequestInterface as Request;
//use Psr\Http\Server\RequestHandlerInterface as Handler;
//use Slim\Psr7\JsonResponse;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;


/**
 * Route middleware to inject group when its slug is passed via placeholder in the URL or request query.
 */
class CRUD6Injector extends AbstractInjector
{
    protected string $placeholder = 'id';
    protected string $crud_slug = 'model';
    protected string $attribute = 'crudModel';
    protected string $schema_attribute = 'crudSchema';

    public function __construct(
        protected CRUD6ModelInterface $crudModel,
        protected DebugLoggerInterface $debugLogger,
        protected SchemaService $schemaService,
    ) {}

    /**
     * Returns the CRUD6 instance.
     *
     * @param string|null $slug
     *
     * @return CRUD6ModelInterface
     */

    protected function getInstance(?string $slug): CRUD6ModelInterface
    {
        /*
        if (!$slug || !is_numeric($slug)) {
            throw new CRUD6Exception("Invalid or missing ID: '{$slug}'.");
        }*/

        $record = $this->crudModel->where($this->placeholder, $slug)->first();
        if (!$record) {
            throw new CRUD6NotFoundException("No record found with ID '{$slug}' in table '{$this->crudModel->getTable()}'.");
        }
        //$this->debugLogger->debug("Line 44 - CRUD5Injector: Getting id : $slug " . $this->placeholder . " Placeholer", $record->toArray());

        return $record;
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $crud_slug = $this->getParameter($request, $this->crud_slug);
        $id = $this->getParameter($request, $this->placeholder);

        if (!$this->validateSlug($crud_slug)) {
            throw new CRUD6Exception("Invalid CRUD slug: '{$crud_slug}'.");
        }

        // Clone the model to prevent modifying a shared instance
        $this->crudModel->setTable($crud_slug);
        //$modelInstance = clone $this->crudModel;
        //$modelInstance->setTable($crud_slug);

        $schema = $this->schemaService->getSchema($crud_slug);
        $request = $request->withAttribute($this->schema_attribute, $schema);

        $this->debugLogger->debug("CRUD6Injector: Table set to '{$crud_slug}'.");

        // ⚡ Return early if model injection is all that's needed
        if ($this->shouldInjectOnly($request)) {
            $this->debugLogger->debug('Model injected successfully');
            $modelInstance = clone $this->crudModel;
            $modelInstance->setTable($crud_slug);
            $request = $request->withAttribute($this->attribute, $modelInstance);
            return $handler->handle($request);
        } else {
            try {
                $instance = $this->getInstance($id);
                $request = $request->withAttribute($this->attribute, $instance);
            } catch (CRUD6NotFoundException $e) {
                $this->debugLogger->error("CRUD6Injector: Record not found with ID '{$id}' in table '{$crud_slug}'.");
            }

            return $handler->handle($request);
        }
    }

    protected function getParameter(ServerRequestInterface $request, string $key): ?string
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        return $route?->getArgument($key) ?? $request->getQueryParams()[$key] ?? null;
    }

    protected function validateSlug(string $slug): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $slug) === 1;
    }

    protected function shouldInjectOnly(ServerRequestInterface $request): bool
    {
        $injectonly  = $this->getParameter($request, 'inject_only');
        $this->debugLogger->debug("Line 121 : CRUD6Injector: Inject only: " . $injectonly);
        return $injectonly ? true : false;
    }
}
