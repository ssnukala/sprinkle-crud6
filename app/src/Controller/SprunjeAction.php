<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Sprunje action for CRUD6 models.
 * 
 * Handles listing, filtering, sorting, and pagination for any CRUD6 model.
 * Uses the Sprunje pattern from UserFrosting for data table operations.
 * All Sprunje instances are dynamically configured based on schema.
 * 
 * Route: GET /api/crud6/{model}
 * 
 * @see \UserFrosting\Sprinkle\Admin\Controller\User\UserListAction
 */
class SprunjeAction extends Base
{
    /**
     * Constructor for SprunjeAction.
     * 
     * @param AuthorizationManager $authorizer    Authorization manager
     * @param Authenticator        $authenticator Authenticator for access control
     * @param DebugLoggerInterface $logger        Debug logger
     * @param Translator           $translator    Translator for i18n messages
     * @param CRUD6Sprunje         $sprunje       CRUD6 Sprunje for data operations
     * @param SchemaService        $schemaService Schema service
     */
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Translator $translator,
        protected CRUD6Sprunje $sprunje,
        protected SchemaService $schemaService,
        protected Config $config,
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService, $config);
    }

    /**
     * Invoke the Sprunje action.
     * 
     * Returns paginated, filtered, and sorted data for the model.
     * Supports relation-specific queries based on schema detail configuration.
     * 
     * @param array                  $crudSchema The schema configuration array (auto-injected)
     * @param CRUD6ModelInterface    $crudModel  The configured model instance (auto-injected)
     * @param ServerRequestInterface $request    The HTTP request
     * @param ResponseInterface      $response   The HTTP response
     * 
     * @return ResponseInterface JSON response with Sprunje data
     */
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            parent::__invoke($crudSchema, $crudModel, $request, $response);
            
            // Validate access permission for list operation
            $this->validateAccess($crudSchema, 'read');

            $this->debugLog("CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST START =====", [
                'model' => $crudSchema['model'],
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            // Get the relation parameter if it exists
            $relation = $this->getParameter($request, 'relation', 'NONE');

            // Log request info for debugging nested endpoint issues
            $this->debugLog("CRUD6 [SprunjeAction] Request received", [
                'uri' => (string) $request->getUri(),
                'model' => $crudSchema['model'],
                'relation' => $relation,
                'has_details' => isset($crudSchema['details']),
                'details_count' => isset($crudSchema['details']) ? count($crudSchema['details']) : 0,
            ]);

            $this->debugLog("CRUD6 [SprunjeAction] Relation parameter parsed", [
                'relation' => $relation,
                'has_detail' => isset($crudSchema['detail']) ? 'yes' : 'no',
                'has_details' => isset($crudSchema['details']) ? 'yes' : 'no',
                'has_relationships' => isset($crudSchema['relationships']) ? 'yes' : 'no',
            ]);

            // Check if this relation is configured in the schema's details section
            $detailConfig = null;
            if (isset($crudSchema['details']) && is_array($crudSchema['details'])) {
                // Search through details array for matching model
                $this->debugLog("CRUD6 [SprunjeAction] Searching details array for relation", [
                    'relation' => $relation,
                    'details_count' => count($crudSchema['details']),
                ]);

                foreach ($crudSchema['details'] as $config) {
                    $configModel = $config['model'] ?? 'null';
                    $matches = (isset($config['model']) && $config['model'] === $relation);

                    $this->debugLog("CRUD6 [SprunjeAction] Checking detail config", [
                        'config_model' => $configModel,
                        'matches' => $matches ? 'YES' : 'no',
                    ]);

                    if ($matches) {
                        $detailConfig = $config;
                        break;
                    }
                }
            }

            $this->debugLog("CRUD6 [SprunjeAction] Detail config search result", [
                'found' => $detailConfig !== null ? 'YES' : 'NO',
                'relation' => $relation,
            ]);

            // Log the result of detail config search for debugging
            // Also check for relationship config if detail config not found
            $relationshipConfig = null;
            if ($relation !== 'NONE') {
                if ($detailConfig !== null) {
                    $this->debugLog("CRUD6 [SprunjeAction] Detail config found", [
                        'relation' => $relation,
                        'model' => $crudSchema['model'],
                        'detail_config' => $detailConfig,
                    ]);
                } else {
                    // No detail config found, check for relationship config
                    $relationshipConfig = $this->findRelationshipConfig($crudSchema, $relation);
                    
                    $this->debugLog("CRUD6 [SprunjeAction] No detail config found, checking relationships", [
                        'relation' => $relation,
                        'model' => $crudSchema['model'],
                        'details_count' => isset($crudSchema['details']) ? count($crudSchema['details']) : 0,
                        'available_details' => isset($crudSchema['details']) ? array_column($crudSchema['details'], 'model') : [],
                        'has_relationship_config' => $relationshipConfig !== null,
                        'relationship_type' => $relationshipConfig['type'] ?? null,
                    ]);
                }
            }

            if ($relation !== 'NONE' && ($detailConfig !== null || $relationshipConfig !== null)) {
                // Handle dynamic relation based on schema detail or relationship configuration
                $this->debugLog("CRUD6 [SprunjeAction] Handling relation", [
                    'model' => $crudSchema['model'],
                    'relation' => $relation,
                    'has_detail_config' => $detailConfig !== null,
                    'has_relationship_config' => $relationshipConfig !== null,
                ]);

                // Load the related model's schema to get its configuration
                $relatedSchema = $this->schemaService->getSchema($relation);

                $this->debugLog("CRUD6 [SprunjeAction] Related schema loaded", [
                    'relation' => $relation,
                    'related_table' => $relatedSchema['table'] ?? null,
                ]);

                // Get the foreign key from detail config (if present)
                $foreignKey = $detailConfig['foreign_key'] ?? 'id';

                // Get query parameters
                $params = $request->getQueryParams();

                // If relationshipConfig wasn't found yet (i.e., we had detailConfig), check for it now
                // This allows both detail and relationship configs to coexist (e.g., simple foreign key + pivot table)
                if ($relationshipConfig === null) {
                    $relationshipConfig = $this->findRelationshipConfig($crudSchema, $relation);
                }

                $this->debugLog("CRUD6 [SprunjeAction] Setting up relation sprunje", [
                    'relation' => $relation,
                    'foreign_key' => $foreignKey,
                    'parent_id' => $crudModel->id,
                    'has_relationship_config' => $relationshipConfig !== null,
                    'query_params' => $params,
                ]);

                // Use CRUD6Sprunje with dynamic configuration from schema
                // Models are already injected based on schema through existing injector functionality
                $relatedModel = $this->schemaService->getModelInstance($relation);

                // Extract field arrays from schema
                $sortableFields = $this->getSortableFieldsFromSchema($relatedSchema);
                $filterableFields = $this->getFilterableFieldsFromSchema($relatedSchema);
                $listFields = $detailConfig['list_fields'] ?? $this->getListableFieldsFromSchema($relatedSchema);

                // CRITICAL: Filter out empty field names to prevent SQL errors like "table".""
                $sortableFields = $this->filterEmptyFieldNames($sortableFields);
                $filterableFields = $this->filterEmptyFieldNames($filterableFields);
                $listFields = $this->filterEmptyFieldNames($listFields);

                $this->debugLog("CRUD6 [SprunjeAction] Sprunje configuration prepared", [
                    'relation' => $relation,
                    'table' => $relatedModel->getTable(),
                    'sortable_fields' => $sortableFields,
                    'filterable_fields' => $filterableFields,
                    'list_fields' => $listFields,
                ]);

                // Setup sprunje with related model configuration
                $this->sprunje->setupSprunje(
                    $relatedModel->getTable(),
                    $sortableFields,
                    $filterableFields,
                    $listFields
                );

                $this->sprunje->setOptions($params);

                $debugMode = $this->debugMode; // Capture debug mode for use in closure

                // Build the query based on relationship type
                if ($relationshipConfig !== null && $relationshipConfig['type'] === 'many_to_many') {
                    // Handle many-to-many relationship via pivot table
                    $this->debugLog("CRUD6 [SprunjeAction] Using many-to-many relationship with pivot table", [
                        'relation' => $relation,
                        'pivot_table' => $relationshipConfig['pivot_table'] ?? null,
                        'foreign_key' => $relationshipConfig['foreign_key'] ?? null,
                        'related_key' => $relationshipConfig['related_key'] ?? null,
                        'parent_id' => $crudModel->id,
                    ]);

                    // Validate required relationship configuration
                    if (empty($relationshipConfig['pivot_table'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'pivot_table' configuration");
                    }
                    if (empty($relationshipConfig['foreign_key'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'foreign_key' configuration");
                    }
                    if (empty($relationshipConfig['related_key'])) {
                        throw new \RuntimeException("Many-to-many relationship '{$relation}' missing required 'related_key' configuration");
                    }

                    // Build manual JOIN query to avoid Eloquent's relationship methods
                    // Eloquent's belongsToMany creates fresh CRUD6Model instances with default table 'CRUD6_NOT_SET'
                    // We build the JOIN manually using the configured model's actual table names
                    $logger = $this->logger; // Capture logger for use in closure

                    $this->debugLog("CRUD6 [SprunjeAction] Building manual belongsToMany JOIN", [
                        'related_model_table' => $relatedModel->getTable(),
                        'parent_model_table' => $crudModel->getTable(),
                    ]);

                    $this->sprunje->extendQuery(function ($query) use (
                        $crudModel,
                        $relationshipConfig,
                        $relatedModel,
                        $logger,
                        $debugMode
                    ) {
                        if ($debugMode) {
                            $logger->debug("CRUD6 [SprunjeAction] Building manual belongsToMany query", [
                                'related_table' => $relatedModel->getTable(),
                                'pivot_table' => $relationshipConfig['pivot_table'],
                                'foreign_key' => $relationshipConfig['foreign_key'],
                                'related_key' => $relationshipConfig['related_key'],
                                'parent_id' => $crudModel->id,
                            ]);
                        } else {
                            //$logger->debug("CRUD6 [SprunjeAction] Debug mode is DISABLED inside belongsToMany closure");
                        }
                        // Build the query manually to avoid Eloquent creating unconfigured CRUD6Model instances
                        // Start with the related model's table
                        $relatedTable = $relatedModel->getTable();
                        $pivotTable = $relationshipConfig['pivot_table'];
                        $foreignKey = $relationshipConfig['foreign_key'];
                        $relatedKey = $relationshipConfig['related_key'];

                        // Join the pivot table with the related table
                        return $query->join(
                            $pivotTable,
                            "{$relatedTable}.id",
                            '=',
                            "{$pivotTable}.{$relatedKey}"
                        )->where("{$pivotTable}.{$foreignKey}", $crudModel->id);
                    });
                } elseif ($relationshipConfig !== null && $relationshipConfig['type'] === 'belongs_to_many_through') {
                    // Handle belongs-to-many-through relationship (e.g., users -> roles -> permissions)
                    // This is completely generic - works for any through relationship defined in the schema
                    $throughModelName = $relationshipConfig['through'] ?? null;

                    $this->debugLog("CRUD6 [SprunjeAction] Using belongs-to-many-through relationship", [
                        'relation' => $relation,
                        'through' => $throughModelName,
                        'config' => $relationshipConfig,
                    ]);

                    if (empty($throughModelName)) {
                        throw new \RuntimeException("belongs_to_many_through relationship '{$relation}' missing required 'through' configuration");
                    }

                    // Instantiate and configure the through model (e.g., "roles")
                    // This ensures the through model has its table name properly set
                    $throughModel = $this->schemaService->getModelInstance($throughModelName);

                    $this->debugLog("CRUD6 [SprunjeAction] Through model instantiated", [
                        'through_model' => $throughModelName,
                        'through_table' => $throughModel->getTable(),
                    ]);

                    $logger = $this->logger; // Capture logger for use in closure

                    $this->sprunje->extendQuery(function ($query) use (
                        $crudModel,
                        $relationshipConfig,
                        $relatedModel,
                        $throughModel,
                        $logger,
                        $debugMode
                    ) {
                        if ($debugMode) {
                            $logger->debug("CRUD6 [SprunjeAction] Building manual belongsToManyThrough query", [
                                'related_table' => $relatedModel->getTable(),
                                'through_table' => $throughModel->getTable(),
                                'first_pivot_table' => $relationshipConfig['first_pivot_table'],
                                'second_pivot_table' => $relationshipConfig['second_pivot_table'],
                                'parent_id' => $crudModel->id,
                            ]);
                        } else {
                            //$logger->debug("CRUD6 [SprunjeAction] Debug mode is DISABLED inside belongsToManyThrough closure");
                        }
                        // Build the query manually to avoid Eloquent creating unconfigured CRUD6Model instances
                        // This handles the double many-to-many relationship (e.g., users -> roles -> permissions)
                        $relatedTable = $relatedModel->getTable();
                        $secondPivotTable = $relationshipConfig['second_pivot_table'];
                        $secondForeignKey = $relationshipConfig['second_foreign_key'];
                        $secondRelatedKey = $relationshipConfig['second_related_key'];
                        $firstPivotTable = $relationshipConfig['first_pivot_table'];
                        $firstForeignKey = $relationshipConfig['first_foreign_key'];
                        $firstRelatedKey = $relationshipConfig['first_related_key'];

                        // Join chain: related_table -> second_pivot -> first_pivot
                        return $query
                            ->join(
                                $secondPivotTable,
                                "{$relatedTable}.id",
                                '=',
                                "{$secondPivotTable}.{$secondRelatedKey}"
                            )
                            ->join(
                                $firstPivotTable,
                                "{$firstPivotTable}.{$firstRelatedKey}",
                                '=',
                                "{$secondPivotTable}.{$secondForeignKey}"
                            )
                            ->where("{$firstPivotTable}.{$firstForeignKey}", $crudModel->id);
                    });
                } else {
                    // Default: filter by foreign key (one-to-many relationship)
                    // This is the fallback for simple relationships not defined in the relationships array
                    $this->debugLog("CRUD6 [SprunjeAction] Using direct foreign key relationship (one-to-many)", [
                        'relation' => $relation,
                        'foreign_key' => $foreignKey,
                        'parent_id' => $crudModel->id,
                        'related_table' => $relatedModel->getTable(),
                    ]);

                    // Qualify the foreign key with table name to avoid ambiguity when joins are present
                    $this->sprunje->extendQuery(function ($query) use ($crudModel, $foreignKey, $relatedModel) {
                        $relatedTable = $relatedModel->getTable();
                        $qualifiedForeignKey = strpos($foreignKey, '.') !== false 
                            ? $foreignKey 
                            : "{$relatedTable}.{$foreignKey}";
                        return $query->where($qualifiedForeignKey, $crudModel->id);
                    });
                }

                $this->debugLog("CRUD6 [SprunjeAction] Relation sprunje configured, returning response", [
                    'relation' => $relation,
                    'parent_id' => $crudModel->id,
                ]);

                return $this->sprunje->toResponse($response);
            }

            // Default sprunje for main model listing
            $modelName = $this->getModelNameFromRequest($request);
            $params = $request->getQueryParams();

            // Extract field arrays from schema
            $sortableFields = $this->getSortableFields($modelName);
            $filterableFields = $this->getFilterableFields($modelName);
            $listFields = $this->getListableFields($modelName);

            // CRITICAL: Filter out empty field names to prevent SQL errors like "table".""
            $sortableFields = $this->filterEmptyFieldNames($sortableFields);
            $filterableFields = $this->filterEmptyFieldNames($filterableFields);
            $listFields = $this->filterEmptyFieldNames($listFields);

            $this->debugLog("CRUD6 [SprunjeAction] Setting up main model sprunje", [
                'model' => $modelName,
                'table' => $crudModel->getTable(),
                'sortable_fields' => $sortableFields,
                'filterable_fields' => $filterableFields,
                'list_fields' => $listFields,
                'query_params' => $params,
            ]);

            $this->sprunje->setupSprunje(
                $crudModel->getTable(),
                $sortableFields,
                $filterableFields,
                $listFields
            );

            $this->sprunje->setOptions($params);

            $this->debugLog("CRUD6 [SprunjeAction] Main sprunje configured, returning response", [
                'model' => $modelName,
            ]);

            return $this->sprunje->toResponse($response);
        } catch (ForbiddenException $e) {
            // Let ForbiddenException bubble up to framework's error handler
            throw $e;
        } catch (NotFoundException $e) {
            // Resource not found - return 404
            return $this->jsonResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST FAILED =====", [
                'model' => $crudSchema['model'],
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->jsonResponse($response, 'An error occurred while fetching data', 500);
        }
    }

    /**
     * Find a relationship configuration by name in the schema.
     * 
     * @param array  $schema        The schema configuration
     * @param string $relationName  The name of the relationship to find
     * 
     * @return array|null The relationship configuration or null if not found
     */
    protected function findRelationshipConfig(array $schema, string $relationName): ?array
    {
        $relationships = $schema['relationships'] ?? [];

        foreach ($relationships as $config) {
            if (isset($config['name']) && $config['name'] === $relationName) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Get sortable fields from a schema array.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of sortable field names
     */
    protected function getSortableFieldsFromSchema(array $schema): array
    {
        $sortable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['sortable']) && $fieldConfig['sortable'] === true) {
                    $sortable[] = $fieldName;
                }
            }
        }

        return $sortable;
    }

    /**
     * Get filterable fields from a schema array.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of filterable field names
     */
    protected function getFilterableFieldsFromSchema(array $schema): array
    {
        $filterable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['filterable']) && $fieldConfig['filterable'] === true) {
                    $filterable[] = $fieldName;
                }
            }
        }

        return $filterable;
    }

    /**
     * Get listable fields from a schema array.
     * 
     * A field is considered listable if:
     * - It has `show_in` array containing 'list', OR
     * - It has explicit `listable: true`, OR
     * - Neither is specified AND it's not a sensitive field type
     * 
     * This prevents sensitive fields (password, timestamps, etc.) from being exposed by default.
     * 
     * @param array $schema The schema configuration
     * 
     * @return array List of listable field names
     */
    protected function getListableFieldsFromSchema(array $schema): array
    {
        $listable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                // Check if field should be shown in list context
                // Priority: show_in array > explicit listable flag > default (false for sensitive types)
                $isListable = false;
                
                if (isset($fieldConfig['show_in'])) {
                    // If show_in is defined, only include if 'list' is in the array
                    $isListable = in_array('list', $fieldConfig['show_in']);
                } elseif (isset($fieldConfig['listable'])) {
                    // If no show_in but explicit listable flag exists
                    $isListable = $fieldConfig['listable'] === true;
                }
                // Default: false - fields must be explicitly marked as listable
                // This prevents sensitive fields (password, tokens, etc.) from being exposed
                // and enforces secure-by-default behavior
                
                if ($isListable) {
                    $listable[] = $fieldName;
                }
            }
        }

        return $listable;
    }

    /**
     * Filter out empty or invalid field names from array.
     * 
     * Ensures all field names are non-empty strings to prevent SQL errors
     * like "table"."" with empty column names.
     * 
     * @param array $fields Array of field names (may contain empty strings or non-strings)
     * 
     * @return array Filtered array containing only valid non-empty string field names
     */
    protected function filterEmptyFieldNames(array $fields): array
    {
        $filtered = array_filter($fields, function($field) {
            return is_string($field) && trim($field) !== '';
        });

        // Log if any fields were filtered out
        $removedCount = count($fields) - count($filtered);
        if ($removedCount > 0) {
            $this->debugLog("CRUD6 [SprunjeAction] Filtered out empty field names", [
                'original_count' => count($fields),
                'filtered_count' => count($filtered),
                'removed_count' => $removedCount,
                'original_fields' => $fields,
                'filtered_fields' => array_values($filtered),
            ]);
        }

        return array_values($filtered); // Re-index array
    }
}