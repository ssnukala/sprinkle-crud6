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
use UserFrosting\Support\Message\UserMessage;

/**
 * CRUD6 Not Found Exception.
 * 
 * Thrown when a CRUD6 resource (record, model instance, etc.) cannot be found.
 * This exception is used by controllers and middleware when attempting to access
 * non-existent records or when required route parameters are missing.
 * 
 * This exception extends UserFrosting's NotFoundException, which typically results
 * in a 404 HTTP response to the client.
 * 
 * Follows UserFrosting 6 exception pattern from sprinkle-core.
 * 
 * @example
 * ```php
 * // Thrown when record doesn't exist
 * throw new CRUD6NotFoundException("No record found with ID '{$id}' in table '{$table}'");
 * ```
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException
 * @see \UserFrosting\Sprinkle\CRUD6\Middlewares\CRUD6Injector
 */
final class CRUD6NotFoundException extends NotFoundException
{
    /**
     * @var string|UserMessage Translation key or message for exception description
     */
    protected string|UserMessage $description = 'CRUD6.NOT_FOUND';
}
