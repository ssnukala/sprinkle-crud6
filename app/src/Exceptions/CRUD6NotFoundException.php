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
 * Group not found exception.
 */
final class CRUD6NotFoundException extends NotFoundException
{
    protected string|UserMessage $description = 'CRUD6.NOT_FOUND';
}
