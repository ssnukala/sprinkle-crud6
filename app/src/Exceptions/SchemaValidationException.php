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
 * Schema Validation Exception.
 * 
 * Thrown when a schema fails structural validation.
 * Extends UserFrosting's UserFacingException for consistency with framework patterns.
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\UserFacingException
 */
class SchemaValidationException extends UserFacingException
{
    /**
     * @var string Translation key for exception title
     */
    protected string $title = 'SCHEMA.VALIDATION_FAILED';
    
    /**
     * @var string|UserMessage Translation key or message for exception description
     */
    protected string|UserMessage $description = 'SCHEMA.VALIDATION_FAILED';
}
