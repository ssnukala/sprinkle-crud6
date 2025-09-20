<?php

declare(strict_types=1);

/*
 * UserFrosting CRID5 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud5
 * @copyright Copyright (c) 2022 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-admin/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Sprunje;

use Illuminate\Database\Eloquent\Model;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Implements Sprunje for the groups API.
 */
class CRUD6Sprunje extends Sprunje
{
    protected string $name = 'TO_BE_SET';

    // TODO : Need to set this dynamically using the yaml schema
    protected array $sortable = ["name"];

    protected array $filterable = [];

    public function __construct(
        protected CRUD6ModelInterface $model,
        protected Request $request,
        protected DebugLoggerInterface $debugLogger
    ) {
        parent::__construct();
    }


    public function setupSprunje($name, $sortable = [], $filterable = []): void
    {
        $this->debugLogger->debug("Line 45: CRUD5 Sprunje: {" . $name . "} Model table is " . $this->model->getTable(), ['sortable' => $sortable, "filterable" => $filterable]);
        $this->model->setTable($name);
        $this->debugLogger->debug("Line 47: CRUD5 Sprunje: {" . $name . "} Model table is " . $this->model->getTable(), ['sortable' => $sortable, "filterable" => $filterable]);
        $this->name = $name;
        $this->sortable = $sortable;
        $this->filterable = $filterable;

        $query = $this->baseQuery();

        if (is_a($query, Model::class)) {
            $query = $query->newQuery();
        }

        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function baseQuery()
    {
        // @phpstan-ignore-next-line Model implement Model.
        $this->debugLogger->debug("Line 53: CRUD5 Sprunje:  Model table is " . $this->model->getTable());
        return $this->model;
    }
}
