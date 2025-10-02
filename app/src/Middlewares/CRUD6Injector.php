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
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\Exceptions\CRUD6Exception;
use UserFrosting\Sprinkle\CRUD6\Exceptions\CRUD6NotFoundException;
use UserFrosting\Sprinkle\Core\Middlewares\Injector\AbstractInjector;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Route middleware to inject configured CRUD6 model when the model name is passed via placeholder in the URL.
 * 
 * Supports database connection selection via @ syntax in the URL:
 * - /api/crud6/users - Uses default or schema-configured connection
 * - /api/crud6/users@db1 - Uses db1 connection (overrides schema)
 * 
 * For routes that include an ID, it will inject the specific record.
 * For routes without an ID, it will inject a configured model instance ready for operations.
 */
class CRUD6Injector extends AbstractInjector
{
    protected string $placeholder = 'id';
    protected string $crud_slug = 'model';
    protected string $model_attribute = 'crudModel';
    protected string $schema_attribute = 'crudSchema';

    public function __construct(
        protected CRUD6ModelInterface $crudModel,
        protected DebugLoggerInterface $debugLogger,
        protected SchemaService $schemaService,
    ) {}

    /**
     * Returns the configured CRUD6 model instance for the specific record, or a configured empty model.
     *
     * @param string|null $id The record ID, null for model-only injection
     *
     * @return CRUD6ModelInterface
     */
    protected function getInstance(?string $id): CRUD6ModelInterface
    {
        // Get the model name from the route
        $modelName = $this->currentModelName;

        // Load schema and configure model
        $schema = $this->schemaService->getSchema($modelName);
        $modelInstance = clone $this->crudModel;
        $modelInstance->configureFromSchema($schema);

        // Apply connection from URL if specified, overriding schema connection
        if ($this->currentConnectionName !== null) {
            $modelInstance->setConnection($this->currentConnectionName);
            $this->debugLogger->debug("CRUD6Injector: Set connection '{$this->currentConnectionName}' for model '{$modelName}'.");
        }

        //$this->debugLogger->debug("CRUD6Injector: Configured model for '{$modelName}' with table '{$modelInstance->getTable()}'.");

        // If no ID provided, return the configured empty model
        if ($id === null) {
            return $modelInstance;
        }

        // Find the specific record
        $primaryKey = $schema['primary_key'] ?? 'id';
        $record = $modelInstance->where($primaryKey, $id)->first();

        if (!$record) {
            throw new CRUD6NotFoundException("No record found with ID '{$id}' in table '{$modelInstance->getTable()}'.");
        }

        //$this->debugLogger->debug("CRUD6Injector: Found record with ID '{$id}' in table '{$modelInstance->getTable()}'.");

        return $record;
    }

    /**
     * Store the current model name for use in getInstance.
     */
    private ?string $currentModelName = null;

    /**
     * Store the current database connection name for use in getInstance.
     */
    private ?string $currentConnectionName = null;

    /**
     * Override the process method to handle both ID-based and model-only injection.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $modelParam = $route?->getArgument($this->crud_slug);

        if ($modelParam === null) {
            throw new CRUD6Exception("Model parameter not found in route.");
        }

        // Parse model name and optional connection (e.g., "users@db1")
        $this->parseModelAndConnection($modelParam);

        if (!$this->validateModelName($this->currentModelName)) {
            throw new CRUD6Exception("Invalid model name: '{$this->currentModelName}'.");
        }

        $id = $this->getIdFromRoute($request);

        // Get configured model instance
        $instance = $this->getInstance($id);

        // Get schema
        $schema = $this->schemaService->getSchema($this->currentModelName);
        //$this->debugLogger->debug("CRUD6Injector: Loaded schema for model '{$this->currentModelName}'.", ['schema' => $schema]);
        // Inject both model and schema
        $request = $request
            ->withAttribute($this->model_attribute, $instance)
            ->withAttribute($this->schema_attribute, $schema);

        return $handler->handle($request);
    }

    /**
     * Parse model name and optional database connection from the parameter.
     * Format: "model" or "model@connection"
     *
     * @param string $modelParam The model parameter from the route
     */
    protected function parseModelAndConnection(string $modelParam): void
    {
        if (str_contains($modelParam, '@')) {
            [$modelName, $connectionName] = explode('@', $modelParam, 2);
            $this->currentModelName = $modelName;
            $this->currentConnectionName = $connectionName;
            $this->debugLogger->debug("CRUD6Injector: Parsed model '{$modelName}' with connection '{$connectionName}'.");
        } else {
            $this->currentModelName = $modelParam;
            $this->currentConnectionName = null;
        }
    }

    /**
     * Validate model name format.
     */
    protected function validateModelName(string $modelName): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $modelName) === 1;
    }
}
