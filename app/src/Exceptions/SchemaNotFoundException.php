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
 * Thrown when a requested schema file cannot be found or loaded. This exception
 * is used by SchemaService and SchemaLoader when attempting to load non-existent
 * schema definitions from the schema directory.
 * 
 * This typically occurs when:
 * - The JSON schema file does not exist in the expected location
 * - The schema file path is misconfigured
 * - The model name in the URL does not match any schema file
 * 
 * Follows UserFrosting 6 exception pattern from sprinkle-core.
 * 
 * @example
 * ```php
 * // Thrown when schema file doesn't exist
 * throw new SchemaNotFoundException("Schema file not found for model: {$model}");
 * ```
 * 
 * @see \UserFrosting\Sprinkle\Core\Exceptions\NotFoundException
 * @see \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService
 * @see \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader
 */
class SchemaNotFoundException extends NotFoundException
{
    /**
     * @var string Translation key for exception title
     */
    protected string $title = 'Schema Not Found';
}