<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Sprunje;

use Illuminate\Database\Eloquent\Model;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * CRUD6 Sprunje - Dynamic data listing for CRUD6 models.
 * 
 * Provides dynamic listing, filtering, sorting, and pagination for any CRUD6 model
 * based on schema configuration. Follows the UserFrosting 6 Sprunje pattern from
 * sprinkle-admin.
 * 
 * @see \UserFrosting\Sprinkle\Core\Sprunje\Sprunje
 */
class CRUD6Sprunje extends Sprunje
{
    /**
     * @var string The name of the model/table for this Sprunje
     */
    protected string $name = 'TO_BE_SET';

    /**
     * @var string[] List of sortable fields (populated dynamically from schema via setupSprunje)
     */
    protected array $sortable = [];

    /**
     * @var string[] List of filterable fields (used for global search)
     */
    protected array $filterable = [];

    /**
     * @var string[] List of listable/visible fields
     */
    protected array $listable = [];

    /**
     * Constructor for CRUD6Sprunje.
     * 
     * @param CRUD6ModelInterface   $model        The CRUD6 model instance
     * @param Request              $request      The HTTP request
     * @param DebugLoggerInterface $debugLogger  Debug logger for diagnostics
     */
    public function __construct(
        protected CRUD6ModelInterface $model,
        protected Request $request,
        protected DebugLoggerInterface $debugLogger
    ) {
        parent::__construct();
    }

    /**
     * Configure the Sprunje with dynamic settings from schema.
     * 
     * Filterable fields are used for global text search functionality.
     * This aligns with UserFrosting's Sprunje pattern where filterable
     * is the standard property for searchable/filterable fields.
     * 
     * @param string   $name       The model/table name
     * @param string[] $sortable   List of sortable field names
     * @param string[] $filterable List of filterable field names (for global search)
     * @param string[] $listable   List of listable/visible field names
     * 
     * @return void
     */
    public function setupSprunje($name, $sortable = [], $filterable = [], $listable = []): void
    {
        $this->model->setTable($name);
        $this->name = $name;
        $this->sortable = $sortable;
        $this->filterable = $filterable;
        $this->listable = $listable;

        $query = $this->baseQuery();

        if (is_a($query, Model::class)) {
            $query = $query->newQuery();
        }

        $this->query = $query;
    }

    /**
     * Get the base query for the Sprunje.
     * 
     * Returns the configured CRUD6 model instance that will be used for queries.
     * 
     * @return CRUD6ModelInterface The model instance for building queries
     */
    protected function baseQuery()
    {
        // @phpstan-ignore-next-line Model implement Model.
        //$this->debugLogger->debug("Line 53: CRUD6 Sprunje:  Model table is " . $this->model->getTable());
        return $this->model;
    }

    /**
     * Apply global search filter across filterable fields.
     * 
     * This filter method is automatically called by the Sprunje when the "search"
     * parameter is present in the request. It applies OR filtering across all
     * fields defined in the $filterable array.
     * 
     * Field names are qualified with the table name to avoid ambiguity when
     * joins are present in the query.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query      The query builder
     * @param string                                 $value      The search term
     * 
     * @return \Illuminate\Database\Eloquent\Builder The modified query
     */
    protected function filterSearch($query, $value)
    {
        // Only apply search if we have filterable fields and non-empty search value
        if (empty($this->filterable) || trim($value) === '') {
            return $query;
        }

        // Get the table name for qualifying field names
        $tableName = $this->name;

        // Filter out empty field names before applying search
        $validFields = array_filter($this->filterable, function($field) {
            return !empty(trim($field));
        });

        // If no valid filterable fields after filtering, return query unchanged
        if (empty($validFields)) {
            return $query;
        }

        // Apply search to all valid filterable fields using OR logic
        return $query->where(function ($subQuery) use ($value, $tableName, $validFields) {
            $isFirst = true;
            foreach ($validFields as $field) {
                // Qualify field with table name if not already qualified
                $qualifiedField = strpos($field, '.') !== false 
                    ? $field 
                    : "{$tableName}.{$field}";
                
                if ($isFirst) {
                    $subQuery->where($qualifiedField, 'LIKE', "%{$value}%");
                    $isFirst = false;
                } else {
                    $subQuery->orWhere($qualifiedField, 'LIKE', "%{$value}%");
                }
            }
        });
    }

    /**
     * Transform a single model instance for output.
     * 
     * Filters the model's attributes to only include fields marked as listable
     * in the schema. This prevents sensitive fields (like password) from being
     * exposed in list views.
     * 
     * @param \Illuminate\Database\Eloquent\Model $item The model instance to transform
     * 
     * @return array The filtered attribute array
     */
    protected function transform($item): array
    {
        // Get all attributes from the model
        $attributes = $item->toArray();
        
        // If listable fields are defined, filter to only those fields
        if (!empty($this->listable)) {
            $filtered = [];
            foreach ($this->listable as $field) {
                if (array_key_exists($field, $attributes)) {
                    $filtered[$field] = $attributes[$field];
                }
            }
            return $filtered;
        }
        
        // If no listable fields defined, return all attributes
        return $attributes;
    }
}
