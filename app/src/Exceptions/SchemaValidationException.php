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
 * Thrown when a schema fails structural validation. This exception is raised by
 * SchemaValidator when a schema is missing required fields or has invalid structure.
 * 
 * Common validation failures include:
 * - Missing required fields (model, table, fields)
 * - Model name mismatch between schema and request
 * - Empty or invalid fields array
 * - Invalid permission configuration
 * 
 * Extends UserFrosting's UserFacingException for consistency with framework patterns.
 * 
 * @example
 * ```php
 * // Thrown when required field is missing
 * throw new SchemaValidationException(
 *     "Schema for model '{$model}' is missing required field: {$field}"
 * );
 * ```
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\UserFacingException
 * @see \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator
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
