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

use UserFrosting\Support\Exception\BadRequestException;

/**
 * Schema Validation Exception.
 * 
 * Thrown when a schema fails structural validation.
 * Extends UserFrosting's BadRequestException for consistency with framework patterns.
 * 
 * @see \UserFrosting\Support\Exception\BadRequestException
 */
class SchemaValidationException extends BadRequestException
{
    /**
     * {@inheritdoc}
     */
    protected $defaultMessage = 'SCHEMA.VALIDATION_FAILED';
    
    /**
     * {@inheritdoc}
     */
    protected int $httpErrorCode = 400;
}
