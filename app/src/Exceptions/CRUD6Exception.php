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

use UserFrosting\Sprinkle\Core\Exceptions\UserFacingException;
use UserFrosting\Support\Message\UserMessage;

/**
 * CRUD6 Exception.
 * 
 * Base exception for CRUD6-related errors. Used for general CRUD6 operations
 * that fail due to invalid input, configuration issues, or operational errors.
 * The description is expected to be set by the controller or service throwing the exception.
 * 
 * Follows UserFrosting 6 exception pattern from sprinkle-core.
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\UserFacingException
 */
final class CRUD6Exception extends UserFacingException
{
    /**
     * @var string Translation key for exception title
     */
    protected string $title = 'CRUD6.EXCEPTION';
    
    /**
     * @var string|UserMessage Translation key or message for exception description
     */
    protected string|UserMessage $description = 'CRUD6.EXCEPTION';
}
