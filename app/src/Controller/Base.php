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
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Exceptions\ForbiddenException;
use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;

/**
 * Base controller for CRUD6 operations
 * 
 * Provides common functionality for all CRUD6 controllers.
 * Controllers receive a configured CRUD6ModelInterface instance that contains
 * the schema configuration and database access.
 */
abstract class Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger
    ) {}

    /**
     * Validate user access permissions for CRUD operations.
     *
     * @param CRUD6ModelInterface $crudModel The configured model instance
     * @param string $action The action to validate (read, create, edit, delete)
     */
    protected function validateAccess(CRUD6ModelInterface $crudModel, string $action = 'read'): void
    {
        $schema = $crudModel->getSchema();
        $permission = $schema['permissions'][$action] ?? "crud6.{$schema['model']}.{$action}";
        
        if (!$this->authenticator->checkAccess($permission)) {
            throw new ForbiddenException("Access denied for {$action} on {$schema['model']}");
        }
    }

    /**
     * Get the table name from the model.
     */
    protected function getTableName(CRUD6ModelInterface $crudModel): string
    {
        return $crudModel->getTable();
    }

    /**
     * Get the schema fields from the model.
     */
    protected function getFields(CRUD6ModelInterface $crudModel): array
    {
        $schema = $crudModel->getSchema();
        return $schema['fields'] ?? [];
    }

    /**
     * Get sortable fields from the model schema.
     */
    protected function getSortableFields(CRUD6ModelInterface $crudModel): array
    {
        $sortable = [];
        $fields = $this->getFields($crudModel);
        
        foreach ($fields as $name => $field) {
            if ($field['sortable'] ?? false) {
                $sortable[] = $name;
            }
        }
        return $sortable;
    }

    /**
     * Get filterable fields from the model schema.
     */
    protected function getFilterableFields(CRUD6ModelInterface $crudModel): array
    {
        $filterable = [];
        $fields = $this->getFields($crudModel);
        
        foreach ($fields as $name => $field) {
            if ($field['filterable'] ?? false) {
                $filterable[] = $name;
            }
        }
        return $filterable;
    }

    protected function getValidationRules(array $schema): array
    {
        $rules = [];
        foreach ($schema['fields'] as $name => $field) {
            if (isset($field['validation'])) {
                $rules[$name] = $field['validation'];
            }
        }
        return $rules;
    }
}
