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

/**
 * Schema Not Found Exception
 * 
 * Thrown when a requested schema file cannot be found or loaded.
 * This exception represents a 404-type error when a schema cannot be found.
 */
class SchemaNotFoundException extends \RuntimeException
{
    protected string $title = 'Schema Not Found';
    protected int $httpErrorCode = 404;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP error code associated with this exception
     */
    public function getHttpErrorCode(): int
    {
        return $this->httpErrorCode;
    }

    /**
     * Get the title of this exception
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}