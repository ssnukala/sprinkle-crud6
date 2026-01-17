<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
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
use UserFrosting\Config\Config;

/**
 * CRUD6 Injector Middleware.
 * 
 * Route middleware to inject configured CRUD6 model when the model name is passed
 * via placeholder in the URL. Follows the UserFrosting 6 middleware pattern.
 * 
 * Supports database connection selection via @ syntax in the URL:
 * - /api/crud6/users - Uses default or schema-configured connection
 * - /api/crud6/users@db1 - Uses db1 connection (overrides schema)
 * 
 * Also supports path-based schema lookup:
 * - /api/crud6/users@db1 will first look for schema at schema://crud6/db1/users.json
 * - If not found, falls back to schema://crud6/users.json and applies db1 connection
 * 
 * For routes that include an ID, it will inject the specific record.
 * For routes without an ID, it will inject a configured model instance ready for operations.
 * 
 * @see \UserFrosting\Sprinkle\Core\Middlewares\Injector\AbstractInjector
 */
class CRUD6Injector extends AbstractInjector
{
    /**
     * @var string The primary attribute name for AbstractInjector auto-injection
     * This is the model instance that will be automatically injected into controller parameters
     */
    protected string $attribute = 'crudModel';

    /**
     * @var string Placeholder name for record ID in route
     */
    protected string $placeholder = 'id';

    /**
     * @var string Route parameter name for model
     */
    protected string $crud_slug = 'model';

    /**
     * @var string Request attribute name for injected model
     */
    protected string $model_attribute = 'crudModel';

    /**
     * @var string Request attribute name for injected schema
     */
    protected string $schema_attribute = 'crudSchema';


    protected bool $debugMode = false;

    /**
     * Constructor for CRUD6Injector.
     * 
     * @param CRUD6ModelInterface  $crudModel     CRUD6 model interface for cloning
     * @param DebugLoggerInterface $debugLogger   Debug logger for diagnostics
     * @param SchemaService        $schemaService Schema service for loading definitions
     */
    public function __construct(
        protected CRUD6ModelInterface $crudModel,
        protected DebugLoggerInterface $debugLogger,
        protected SchemaService $schemaService,
        protected Config $config
    ) {
        $this->debugMode = (bool) $this->config->get('crud6.debug_mode', false);
    }


    /**
     * Log debug message if debug mode is enabled.
     * 
     * Wrapper around DebugLoggerInterface that only logs when debug_mode config is true.
     * This prevents the need to check isDebugMode() before every logger->debug() call.
     * 
     * @param string $message Debug message
     * @param array  $context Context data for structured logging
     * 
     * @return void
     */
    protected function debugLog(string $message, array $context = []): void
    {
        if ($this->debugMode) {
            $this->debugLogger->debug($message, $context);
        } else {
            //$this->debugLogger->debug("[CRUD6 Base DebugLog] Debug mode disabled, skipping log for message: {$message}");
        }
    }


    /**
     * Returns the configured CRUD6 model instance for the specific record, or a configured empty model.
     * 
     * Loads schema, configures model, and optionally loads a specific record by ID.
     *
     * @param string|null $id The record ID, null for model-only injection
     *
     * @return CRUD6ModelInterface Configured model instance
     * 
     * @throws CRUD6NotFoundException If record with specified ID not found
     */
    protected function getInstance(?string $id): CRUD6ModelInterface
    {
        // Get the model name from the route
        $modelName = $this->currentModelName;

        // DEBUG: Log model instance creation
        $this->debugLog("CRUD6 [CRUD6Injector] Getting model instance", [
            'model' => $modelName,
            'connection' => $this->currentConnectionName,
            'id' => $id,
        ]);

        // DEBUG: Log before schema load
        $this->debugLog("[CRUD6 CRUD6Injector] Loading schema from SchemaService - model: %s, connection: %s", ['model' => $modelName, 'connection' => $this->currentConnectionName ?? 'null']);

        // Load schema and configure model - pass connection for path-based lookup
        $schema = $this->schemaService->getSchema($modelName, $this->currentConnectionName);

        // Store schema for reuse in process() to avoid duplicate loading
        $this->currentSchema = $schema;

        // DEBUG: Log after schema load
        $this->debugLog("[CRUD6 CRUD6Injector] Schema loaded in getInstance() and CACHED for reuse - model: %s, table: %s", ['model' => $modelName, 'table' => $schema['table'] ?? 'unknown']);

        $modelInstance = clone $this->crudModel;
        $modelInstance->configureFromSchema($schema);

        $this->debugLog("CRUD6 [CRUD6Injector] Schema loaded and model configured", [
            'model' => $modelName,
            'table' => $modelInstance->getTable(),
            'connection' => $modelInstance->getConnectionName(),
            'primary_key' => $schema['primary_key'] ?? 'id',
        ]);

        // Apply connection from URL if specified, overriding schema connection
        // Note: schema may already have connection set from path-based lookup
        if ($this->currentConnectionName !== null) {
            $modelInstance->setConnection($this->currentConnectionName);
            $this->debugLog("CRUD6 [CRUD6Injector] Connection overridden", [
                'model' => $modelName,
                'connection' => $this->currentConnectionName,
            ]);
        }

        // If no ID provided, return the configured empty model
        if ($id === null) {
            $this->debugLog("[CRUD6 CRUD6Injector] Returning configured empty model (no ID) - model: %s", ['model' => $modelName]);
            $this->debugLog("CRUD6 [CRUD6Injector] Returning configured empty model (no ID)", [
                'model' => $modelName,
            ]);
            return $modelInstance;
        }

        // Find the specific record
        $primaryKey = $schema['primary_key'] ?? 'id';
        $this->debugLog("CRUD6 [CRUD6Injector] Looking up record by ID", [
            'model' => $modelName,
            'id' => $id,
            'primary_key' => $primaryKey,
            'table' => $modelInstance->getTable(),
        ]);

        $record = $modelInstance->where($primaryKey, $id)->first();

        if (!$record) {
            $this->debugLog("[CRUD6 CRUD6Injector] Record not found", [
                'model' => $modelName,
                'id' => $id,
                'table' => $modelInstance->getTable(),
            ]);
            $this->debugLogger->error("CRUD6 [CRUD6Injector] Record not found", [
                'model' => $modelName,
                'id' => $id,
                'primary_key' => $primaryKey,
                'table' => $modelInstance->getTable(),
            ]);
            throw new CRUD6NotFoundException("No record found with ID '{$id}' in table '{$modelInstance->getTable()}'.");
        }

        $this->debugLog("[CRUD6 CRUD6Injector] Record loaded successfully - model: %s, id: %s", ['model' => $modelName, 'id' => $id]);

        $this->debugLog("CRUD6 [CRUD6Injector] Record found and loaded", [
            'model' => $modelName,
            'id' => $id,
            'record_data' => $record->toArray(),
        ]);

        return $record;
    }

    /**
     * Store the current model name for use in getInstance.
     * 
     * @var string|null
     */
    private ?string $currentModelName = null;

    /**
     * Store the current database connection name for use in getInstance.
     * 
     * @var string|null
     */
    private ?string $currentConnectionName = null;

    /**
     * Store the loaded schema to avoid duplicate loading.
     * 
     * Schema is loaded once in getInstance() and reused in process().
     * This eliminates the duplicate schema load that was happening on every request.
     * 
     * @var array|null
     */
    private ?array $currentSchema = null;

    /**
     * Override the process method to handle both ID-based and model-only injection.
     * 
     * Parses route parameters, validates model name, loads schema, and injects
     * both the model instance and schema into the request.
     * 
     * @param ServerRequestInterface  $request The HTTP request
     * @param RequestHandlerInterface $handler The request handler
     * 
     * @return ResponseInterface The HTTP response
     * 
     * @throws CRUD6Exception If model parameter missing or invalid
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        // Log middleware entry
        $this->debugLog("[CRUD6 CRUD6Injector] ===== MIDDLEWARE PROCESS START =====", [
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
            'timestamp' => date('Y-m-d H:i:s.u'),
        ]);

        $this->debugLog("CRUD6 [CRUD6Injector] Middleware process details", [
            'uri' => (string) $request->getUri(),
            'method' => $request->getMethod(),
        ]);

        $modelParam = $route?->getArgument($this->crud_slug);

        if ($modelParam === null) {
            $this->debugLog("[CRUD6 CRUD6Injector] ERROR: Model parameter not found in route", []);
            $this->debugLogger->error("CRUD6 [CRUD6Injector] Model parameter not found in route", [
                'uri' => (string) $request->getUri(),
                'route_args' => $route?->getArguments(),
            ]);
            throw new CRUD6Exception("Model parameter not found in route.");
        }

        // Parse model name and optional connection (e.g., "users@db1")
        $this->parseModelAndConnection($modelParam);

        if (!$this->validateModelName($this->currentModelName)) {
            $this->debugLog("[CRUD6 CRUD6Injector] ERROR: Invalid model name - %s", ['model' => $this->currentModelName]);
            $this->debugLogger->error("CRUD6 [CRUD6Injector] Invalid model name", [
                'model_param' => $modelParam,
                'parsed_model' => $this->currentModelName,
            ]);
            throw new CRUD6Exception("Invalid model name: '{$this->currentModelName}'.");
        }

        $id = $this->getIdFromRoute($request);

        $this->debugLog("[CRUD6 CRUD6Injector] Route parsed - model: %s, connection: %s, id: %s", ['model' => $this->currentModelName, 'connection' => $this->currentConnectionName ?? 'null', 'id' => $id ?? 'null']);

        $this->debugLog("CRUD6 [CRUD6Injector] Route parsed", [
            'model' => $this->currentModelName,
            'connection' => $this->currentConnectionName,
            'id' => $id,
        ]);

        // Get configured model instance
        $this->debugLog("[CRUD6 CRUD6Injector] Calling getInstance() - model: %s, id: %s", ['model' => $this->currentModelName, 'id' => $id ?? 'null']);
        $instance = $this->getInstance($id);

        // DEBUG: Reuse cached schema instead of loading again (FIX for duplicate load)
        $this->debugLog("[CRUD6 CRUD6Injector] ✅ REUSING cached schema from getInstance() - model: %s (avoiding duplicate load)", ['model' => $this->currentModelName]);

        // Reuse schema that was loaded in getInstance() instead of loading again
        // This eliminates the duplicate schema load that was happening on every request
        $schema = $this->currentSchema;

        if ($schema === null) {
            // This should never happen, but log if it does
            $this->debugLog("[CRUD6 CRUD6Injector] ⚠️ WARNING: Schema cache was null, falling back to loading - model: %s", ['model' => $this->currentModelName]);
            $schema = $this->schemaService->getSchema($this->currentModelName, $this->currentConnectionName);
        }

        $this->debugLog("[CRUD6 CRUD6Injector] Schema ready for injection - model: %s, table: %s", ['model' => $this->currentModelName, 'table' => $schema['table'] ?? 'unknown']);

        $this->debugLog("CRUD6 [CRUD6Injector] Injecting model and schema into request", [
            'model' => $this->currentModelName,
            'id' => $id,
            'schema_keys' => array_keys($schema),
        ]);

        // Inject both model and schema
        $request = $request
            ->withAttribute($this->model_attribute, $instance)
            ->withAttribute($this->schema_attribute, $schema);

        $this->debugLog("[CRUD6 CRUD6Injector] Request attributes set - model_attr: %s, schema_attr: %s", ['model_attribute' => $this->model_attribute, 'schema_attribute' => $this->schema_attribute]);

        $this->debugLog("CRUD6 [CRUD6Injector] Request attributes set", [
            'model' => $this->currentModelName,
            'model_attribute_name' => $this->model_attribute,
            'schema_attribute_name' => $this->schema_attribute,
            'model_class' => get_class($instance),
            'model_table' => $instance->getTable(),
            'has_model_attribute' => $request->getAttribute($this->model_attribute) !== null,
            'has_schema_attribute' => $request->getAttribute($this->schema_attribute) !== null,
        ]);

        $this->debugLog("[CRUD6 CRUD6Injector] ===== MIDDLEWARE PROCESS COMPLETE ===== model: %s", ['model' => $this->currentModelName]);

        $this->debugLog("CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS COMPLETE =====", [
            'model' => $this->currentModelName,
        ]);

        try {
            $response = $handler->handle($request);

            $this->debugLog("[CRUD6 CRUD6Injector] Controller completed", [
                'status' => $response->getStatusCode(),
            ]);

            $this->debugLog("CRUD6 [CRUD6Injector] Controller invocation successful", [
                'model' => $this->currentModelName,
                'response_status' => $response->getStatusCode(),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->debugLog("[CRUD6 CRUD6Injector] ERROR: Controller failed", [
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            $this->debugLogger->error("CRUD6 [CRUD6Injector] Controller invocation failed", [
                'model' => $this->currentModelName,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse model name and optional database connection from the parameter.
     * 
     * Supports format: "model" or "model@connection"
     * Sets currentModelName and currentConnectionName for use in getInstance.
     *
     * @param string $modelParam The model parameter from the route
     * 
     * @return void
     */
    protected function parseModelAndConnection(string $modelParam): void
    {
        if (str_contains($modelParam, '@')) {
            [$modelName, $connectionName] = explode('@', $modelParam, 2);
            $this->currentModelName = $modelName;
            $this->currentConnectionName = $connectionName;
            $this->debugLog("CRUD6 [CRUD6Injector] Parsed model with connection", [
                'model' => $modelName,
                'connection' => $connectionName,
            ]);
        } else {
            $this->currentModelName = $modelParam;
            $this->currentConnectionName = null;
            $this->debugLog("CRUD6 [CRUD6Injector] Parsed model (no connection override)", [
                'model' => $modelParam,
            ]);
        }
    }

    /**
     * Validate model name format.
     * 
     * Only allows alphanumeric characters and underscores to prevent
     * path traversal or injection attacks.
     * 
     * @param string $modelName The model name to validate
     * 
     * @return bool True if valid, false otherwise
     */
    protected function validateModelName(string $modelName): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $modelName) === 1;
    }
}