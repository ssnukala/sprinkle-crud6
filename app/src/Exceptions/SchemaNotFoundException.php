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

use UserFrosting\Framework\Exception\HttpException;

/**
 * Schema Not Found Exception
 * 
 * Thrown when a requested schema file cannot be found or loaded.
 */
class SchemaNotFoundException extends HttpException
{
    protected string $title = 'Schema Not Found';
    protected int $httpErrorCode = 404;
}