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
 * Used by controllers and middleware when attempting to access non-existent records.
 * 
 * Follows UserFrosting 6 exception pattern from sprinkle-core.
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException
 */
final class CRUD6NotFoundException extends NotFoundException
{
    /**
     * @var string|UserMessage Translation key or message for exception description
     */
    protected string|UserMessage $description = 'CRUD6.NOT_FOUND';
}
