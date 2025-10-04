<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Exceptions;

use UserFrosting\Sprinkle\Core\Exceptions\NotFoundException;

/**
 * Schema Not Found Exception.
 * 
 * Thrown when a requested schema file cannot be found or loaded.
 * Used by SchemaService when attempting to load non-existent schema definitions.
 * 
 * Follows UserFrosting 6 exception pattern from sprinkle-core.
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException
 */
class SchemaNotFoundException extends NotFoundException
{
    /**
     * @var string Translation key for exception title
     */
    protected string $title = 'Schema Not Found';
}