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
     * @var string[] List of sortable fields
     */
    // TODO : Need to set this dynamically using the yaml schema
    protected array $sortable = ["name"];

    /**
     * @var string[] List of filterable fields
     */
    protected array $filterable = [];

    /**
     * @var string[] List of listable/visible fields
     */
    protected array $listable = [];

    /**
     * @var string[] List of searchable fields (for global search)
     */
    protected array $searchable = [];

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
     * @param string   $name       The model/table name
     * @param string[] $sortable   List of sortable field names
     * @param string[] $filterable List of filterable field names
     * @param string[] $listable   List of listable/visible field names
     * @param string[] $searchable List of searchable field names (for global search)
     * 
     * @return void
     */
    public function setupSprunje($name, $sortable = [], $filterable = [], $listable = [], $searchable = []): void
    {
        //$this->debugLogger->debug("Line 45: CRUD6 Sprunje: {" . $name . "} Model table is " . $this->model->getTable(), ['sortable' => $sortable, "filterable" => $filterable]);
        $this->model->setTable($name);
        //$this->debugLogger->debug("Line 47: CRUD6 Sprunje: {" . $name . "} Model table is " . $this->model->getTable(), ['sortable' => $sortable, "filterable" => $filterable]);
        $this->name = $name;
        $this->sortable = $sortable;
        $this->filterable = $filterable;
        $this->listable = $listable;
        $this->searchable = $searchable;

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
     * Apply filtering logic to the query.
     * 
     * This method intercepts the "search" parameter and applies OR filtering
     * across all searchable fields. This is in addition to any specific field
     * filters that are applied by the parent class.
     * 
     * @param mixed $query The query builder instance
     * 
     * @return static
     */
    protected function applyTransformations($query): static
    {
        // First apply parent transformations (filters, sorts, etc.)
        parent::applyTransformations($query);

        // Handle global search if search parameter is present
        if (isset($this->options['search']) && !empty($this->options['search'])) {
            $searchTerm = $this->options['search'];
            
            // Apply search to all searchable fields using OR logic
            if (!empty($this->searchable)) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    foreach ($this->searchable as $field) {
                        $subQuery->orWhere($field, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }

        return $this;
    }
}
