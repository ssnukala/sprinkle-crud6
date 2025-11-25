<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Core\Util\ApiResponse;
use UserFrosting\Sprinkle\CRUD6\Controller\Traits\HandlesErrorLogging;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Base controller for CRUD6 operations.
 * 
 * Provides common functionality for all CRUD6 controllers following the UserFrosting 6
 * action-based controller pattern from sprinkle-admin. Controllers receive a configured
 * CRUD6ModelInterface instance that contains the schema configuration and database access.
 * 
 * All CRUD6 controllers should extend this base class and implement their specific
 * action in the __invoke method.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserCreateAction
 */
abstract class Base
{
    use HandlesErrorLogging;

    /**
     * @var array<string, array> Cache of loaded schemas by model name
     */
    protected array $cachedSchema = [];

    /**
     * @var array<string, mixed> Route parameters extracted from request
     */
    protected array $routeParams = [];

    protected bool $debugMode = false;
    /**
     * Constructor for base CRUD6 controller.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager for permission checks
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger for diagnostics
     * @param SchemaService        $schemaService Schema service for loading model definitions
     * @param Config               $config        Configuration repository
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService,
        protected Config $config
    ) {
        //$this->logger->debug("[CRUD6 Base Constructor] Initialized - Config Array", $this->config->get('crud6'));
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
            $this->logger->debug($message, $context);
        } else {
            //$this->logger->debug("[CRUD6 Base DebugLog] Debug mode disabled, skipping log for message: {$message}");
        }
    }


    /**
     * Common invoke logic for CRUD6 controllers.
     * 
     * Sets up common state for all CRUD6 operations including schema caching
     * and access validation. Child controllers should call this parent method
     * before their specific logic.
     * 
     * @param array                  $crudSchema The schema configuration array
     * @param CRUD6ModelInterface    $crudModel  The configured model instance
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface The HTTP response
     * 
     * @throws ForbiddenException If user lacks required permissions
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Log that controller was successfully invoked with parameters
        $this->debugLog("CRUD6 [Base] Controller __invoke called", [
            'controller_class' => get_class($this),
            'schema_model' => $crudSchema['model'] ?? 'UNKNOWN',
            'model_class' => get_class($crudModel),
            'model_table' => $crudModel->getTable(),
            'request_uri' => (string) $request->getUri(),
            'request_method' => $request->getMethod(),
            'schema_keys' => array_keys($crudSchema),
        ]);

        // Common logic here, e.g. logging, validation, etc.
        $modelName = $this->getModelNameFromRequest($request);
        $this->cachedSchema[$modelName] = $crudSchema;
        $this->validateAccess($modelName, 'read');

        $this->debugLog("CRUD6 [Base] Common initialization complete", [
            'model' => $modelName,
            'cached_schema_count' => count($this->cachedSchema),
        ]);

        // You can set up other shared state here
        return $response;
    }

    /**
     * Validate user access permissions for CRUD operations.
     * 
     * Checks if the current user has permission to perform the specified action
     * on the model. Permission names follow the pattern: crud6.{model}.{action}
     * or can be customized in the schema's permissions configuration.
     *
     * @param string|array $modelNameOrSchema The model name or schema array
     * @param string       $action             The action to validate (read, create, edit, delete)
     * 
     * @return void
     * 
     * @throws ForbiddenException If user lacks the required permission
     */
    protected function validateAccess(string|array $modelNameOrSchema, string $action = 'read'): void
    {
        $schema = is_string($modelNameOrSchema)
            ? $this->getSchema($modelNameOrSchema)
            : $modelNameOrSchema;

        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";

        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
        }
    }

    /**
     * Get the schema for the model name.
     * 
     * Uses caching to avoid repeatedly loading the same schema.
     * 
     * @param string $modelName The model name
     * 
     * @return array<string, mixed> The schema configuration array
     */
    protected function getSchema(string $modelName): array
    {
        if (!isset($this->cachedSchema[$modelName])) {
            $this->cachedSchema[$modelName] = $this->schemaService->getSchema($modelName);
        }
        return $this->cachedSchema[$modelName];
    }

    /**
     * Get the schema fields from the model name.
     * 
     * Returns the cached fields if schema is already loaded.
     * 
     * @param string $modelName The model name
     * 
     * @return array<string, array> The fields configuration
     */
    protected function getFields(string $modelName): array
    {
        $schema = $this->getSchema($modelName);
        return $schema['fields'] ?? [];
    }

    /**
     * Get sortable fields from the model schema.
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of sortable field names
     */
    protected function getSortableFields(string $modelName): array
    {
        $sortable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            if ($field['sortable'] ?? false) {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }

    /**
     * Get filterable fields from the model schema.
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of filterable field names
     */
    protected function getFilterableFields(string $modelName): array
    {
        $filterable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            if ($field['filterable'] ?? false) {
                $filterable[] = $name;
            }
        }
        return $filterable;
    }

    /**
     * Get listable fields from the model schema.
     * 
     * Only fields with explicit `listable: true` are included.
     * This prevents sensitive fields (password, timestamps, etc.) from being exposed by default.
     * 
     * @param string $modelName The model name
     * 
     * @return string[] Array of listable field names
     */
    protected function getListableFields(string $modelName): array
    {
        $listable = [];
        $fields = $this->getFields($modelName);

        foreach ($fields as $name => $field) {
            // Only include fields explicitly marked as listable: true
            if (isset($field['listable']) && $field['listable'] === true) {
                $listable[] = $name;
            }
        }
        return $listable;
    }

    /**
     * Get editable fields from the model schema.
     * 
     * Fields are considered editable if:
     * - They have `editable: true` explicitly set, OR
     * - They don't have `readonly: true`, `auto_increment: true`, or `computed: true`
     * 
     * @param string|array $modelNameOrSchema The model name or schema array
     * 
     * @return string[] Array of editable field names
     */
    protected function getEditableFields(string|array $modelNameOrSchema): array
    {
        $schema = is_string($modelNameOrSchema)
            ? $this->schemaService->getSchema($modelNameOrSchema)
            : $modelNameOrSchema;

        $editable = [];
        foreach ($schema['fields'] ?? [] as $name => $field) {
            // Check if field is explicitly marked as editable
            if (isset($field['editable'])) {
                if ($field['editable'] === true) {
                    $editable[] = $name;
                }
                continue;
            }

            // If no explicit editable attribute, check for non-editable flags
            if ($field['readonly'] ?? false) {
                continue;
            }
            if ($field['auto_increment'] ?? false) {
                continue;
            }
            if ($field['computed'] ?? false) {
                continue;
            }

            // Field is editable by default
            $editable[] = $name;
        }

        return $editable;
    }

    /**
     * Get validation rules from the model schema.
     * 
     * This method returns validation rules only for editable fields.
     * Fields that are editable but have no validation rules will be included
     * in the request schema but with empty validation rules.
     * 
     * @param string|array $modelNameOrSchema The model name or schema array
     * 
     * @return array<string, array> Validation rules for each editable field
     */
    protected function getValidationRules(string|array $modelNameOrSchema): array
    {
        $schema = is_string($modelNameOrSchema)
            ? $this->schemaService->getSchema($modelNameOrSchema)
            : $modelNameOrSchema;

        $editableFields = $this->getEditableFields($schema);

        $rules = [];
        foreach ($schema['fields'] ?? [] as $name => $field) {
            // Only include validation rules for editable fields
            if (in_array($name, $editableFields)) {
                // Include the field even if it has no validation rules
                $rules[$name] = $field['validation'] ?? [];
            }
        }
        return $rules;
    }

    /**
     * Get a display name for the model.
     * 
     * Extracts a clean display name from the schema, removing "Management" suffix if present.
     * 
     * @param array $schema The schema configuration
     * 
     * @return string The display name
     */
    protected function getModelDisplayName(array $schema): string
    {
        $modelDisplayName = $schema['title'] ?? ucfirst($schema['model']);
        // If title ends with "Management", extract the entity name
        if (preg_match('/^(.+)\s+Management$/i', $modelDisplayName, $matches)) {
            $modelDisplayName = $matches[1];
        }
        return $modelDisplayName;
    }

    /**
     * Transform field value based on its type.
     * 
     * Converts values to appropriate PHP/database types based on field configuration.
     * Password fields are NOT hashed here - they must be hashed by child controllers
     * that have access to the Hasher service (CreateAction, EditAction).
     * 
     * @param array $fieldConfig Field configuration from schema
     * @param mixed $value       The value to transform
     * 
     * @return mixed The transformed value
     */
    protected function transformFieldValue(array $fieldConfig, mixed $value): mixed
    {
        $type = $fieldConfig['type'] ?? 'string';
        
        // Handle textarea with row/column specification (e.g., "textarea-r5c60")
        if (preg_match('/^(?:text|textarea)(?:-r\d+)?(?:c\d+)?$/', $type)) {
            return (string) $value;
        }
        
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'float':
            case 'decimal':
                return (float) $value;
            case 'boolean':
            case 'boolean-yn':
            case 'boolean-toggle':
                // All boolean variants are stored as boolean in database
                return (bool) $value;
            case 'json':
                return is_string($value) ? $value : json_encode($value);
            case 'date':
            case 'datetime':
                return $value;
            case 'password':
                // Password fields should be hashed by the controller before calling this method
                // Return the value as-is here (should already be hashed)
                return (string) $value;
            case 'phone':
            case 'zip':
            case 'email':
            case 'url':
                // These are string types with specific formatting/validation
                return (string) $value;
            default:
                return (string) $value;
        }
    }

    /**
     * Hash password fields in data.
     * 
     * This method should be overridden by child controllers that have access
     * to the Hasher service (CreateAction, EditAction) to hash password fields
     * before they are stored in the database.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The data with password fields hashed
     */
    protected function hashPasswordFields(array $schema, array $data): array
    {
        // Base implementation does nothing - child controllers with Hasher service
        // should override this method to hash password fields
        return $data;
    }

    /**
     * Check if a field is virtual (not a database column).
     * 
     * Virtual fields are used for UI/relationship management but don't map
     * to actual database columns. A field is considered virtual if:
     * - It has `computed: true` attribute (calculated/virtual fields)
     * - It has a virtual field type like 'multiselect' (for many-to-many relationships)
     * 
     * Virtual fields are excluded from INSERT and UPDATE database operations.
     * 
     * @param array $fieldConfig The field configuration
     * 
     * @return bool True if field is virtual, false otherwise
     */
    protected function isVirtualField(array $fieldConfig): bool
    {
        // Check if field is explicitly marked as computed/calculated
        if ($fieldConfig['computed'] ?? false) {
            return true;
        }
        
        // Check if field type is a virtual type
        $virtualFieldTypes = ['multiselect'];
        $fieldType = $fieldConfig['type'] ?? '';
        
        return in_array($fieldType, $virtualFieldTypes, true);
    }

    /**
     * Prepare data for database insertion.
     * 
     * Transforms field values according to their types and applies defaults.
     * Handles timestamps if configured in schema.
     * Password fields are hashed via hashPasswordFields() hook method.
     * Excludes virtual fields (like multiselect) that don't map to database columns.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The prepared insert data
     */
    protected function prepareInsertData(array $schema, array $data): array
    {
        // Hash password fields first (if child controller has Hasher service)
        $data = $this->hashPasswordFields($schema, $data);
        
        $insertData = [];
        $fields = $schema['fields'] ?? [];
        foreach ($fields as $fieldName => $fieldConfig) {
            // Skip auto-increment, computed, and virtual fields
            if ($fieldConfig['auto_increment'] ?? false || 
                $fieldConfig['computed'] ?? false ||
                $this->isVirtualField($fieldConfig)) {
                continue;
            }
            if (isset($data[$fieldName])) {
                $insertData[$fieldName] = $this->transformFieldValue($fieldConfig, $data[$fieldName]);
            } elseif (isset($fieldConfig['default'])) {
                $insertData[$fieldName] = $fieldConfig['default'];
            }
        }
        if ($schema['timestamps'] ?? false) {
            $now = date('Y-m-d H:i:s');
            $insertData['created_at'] = $now;
            $insertData['updated_at'] = $now;
        }
        return $insertData;
    }

    /**
     * Prepare data for database update.
     * 
     * Transforms field values according to their types and filters out non-editable fields.
     * Uses getEditableFields() to determine which fields can be updated.
     * Handles timestamps if configured in schema.
     * Password fields are hashed via hashPasswordFields() hook method.
     * Excludes virtual fields (like multiselect) that don't map to database columns.
     * 
     * @param array $schema The schema configuration
     * @param array $data   The input data
     * 
     * @return array The prepared update data
     */
    protected function prepareUpdateData(array $schema, array $data): array
    {
        // Hash password fields first (if child controller has Hasher service)
        $data = $this->hashPasswordFields($schema, $data);
        
        $updateData = [];
        $editableFields = $this->getEditableFields($schema);
        $fields = $schema['fields'] ?? [];

        foreach ($editableFields as $fieldName) {
            if (isset($data[$fieldName]) && isset($fields[$fieldName])) {
                // Skip virtual fields
                if ($this->isVirtualField($fields[$fieldName])) {
                    continue;
                }
                $updateData[$fieldName] = $this->transformFieldValue($fields[$fieldName], $data[$fieldName]);
            }
        }

        // Update timestamp if configured
        if ($schema['timestamps'] ?? false) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
        }

        return $updateData;
    }

    /**
     * Get a parameter from the request route.
     * 
     * Caches route parameters on first access for performance.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * @param string                 $key     The parameter key
     * @param mixed                  $default Default value if not found
     * 
     * @return mixed The parameter value or default
     */
    protected function getParameter(ServerRequestInterface $request, string $key, $default = null): mixed
    {
        // if routeParams are already set (injected), use them first if not fetch from request and set routeParams and return
        if (count($this->routeParams) === 0) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
            $this->routeParams = $route?->getArguments() ?? [];
        }
        $routeParam = $this->routeParams[$key] ?? $default;
        return $routeParam;
    }

    /**
     * Get model name from the request route.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * 
     * @return string The model name from route parameters
     */
    protected function getModelNameFromRequest(ServerRequestInterface $request): string
    {
        return $this->getParameter($request, 'model'); // to set routeParams if not already set
    }

    /**
     * Get the schema from the request attributes.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * 
     * @return array<string, mixed> The schema configuration array
     */
    protected function getSchemaFromRequest(ServerRequestInterface $request): array
    {
        return $this->getParameter($request, 'crudSchema'); // to set routeParams if not already set
    }

    /**
     * Create a JSON success response following UserFrosting 6 pattern.
     * 
     * This method creates a standardized JSON response using ApiResponse class.
     * Use this for successful API operations that return a message to the client.
     * 
     * @param ResponseInterface $response The HTTP response object
     * @param string            $message  The success message (used as description)
     * @param int               $status   HTTP status code (default: 200)
     * 
     * @return ResponseInterface The response with JSON body and headers
     * 
     * @see \UserFrosting\Sprinkle\Core\Util\ApiResponse
     */
    protected function jsonResponse(ResponseInterface $response, string $message, int $status = 200): ResponseInterface
    {
        $payload = new ApiResponse($message);
        $response->getBody()->write((string) $payload);
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * Create a JSON success response with title and description.
     * 
     * Use this for operations that need both a title and description in the response,
     * such as create/update operations that display success modals.
     * 
     * @param ResponseInterface $response    The HTTP response object
     * @param string            $title       The response title
     * @param string            $description The response description
     * @param int               $status      HTTP status code (default: 200)
     * 
     * @return ResponseInterface The response with JSON body and headers
     */
    protected function jsonResponseWithTitle(ResponseInterface $response, string $title, string $description, int $status = 200): ResponseInterface
    {
        $payload = new ApiResponse($title, $description);
        $response->getBody()->write((string) $payload);
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * Create a JSON response with custom data.
     * 
     * Use this for API endpoints that need to return custom data structures
     * beyond the standard ApiResponse format (e.g., Sprunje results, record data).
     * 
     * @param ResponseInterface $response The HTTP response object
     * @param array             $data     The data to encode as JSON
     * @param int               $status   HTTP status code (default: 200)
     * 
     * @return ResponseInterface The response with JSON body and headers
     */
    protected function jsonDataResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}