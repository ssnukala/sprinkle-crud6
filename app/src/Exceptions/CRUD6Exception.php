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
 * Group related exceptions. The description is expected to be set by the controller.
 */
final class CRUD6Exception extends UserFacingException
{
    protected string $title = 'CRUD6.EXCEPTION';
    protected string|UserMessage $description = 'CRUD6.EXCEPTION';
}
