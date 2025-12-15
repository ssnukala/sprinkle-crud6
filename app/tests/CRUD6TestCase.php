<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests;

use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Sprinkle\CRUD6\Testing\WithDatabaseSeeds;
use UserFrosting\Testing\TestCase;

/**
 * CRUD6 Test Case Base Class.
 * 
 * Base test case with CRUD6 as main sprinkle.
 * All CRUD6 tests should extend this class to ensure proper sprinkle loading.
 * 
 * This base class includes WithDatabaseSeeds trait to ensure tests that use
 * RefreshDatabase also get necessary seed data automatically.
 * 
 * Follows UserFrosting 6 testing patterns from sprinkle-admin and sprinkle-account.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Tests\AdminTestCase
 * @see \UserFrosting\Sprinkle\Account\Tests\AccountTestCase
 */
class CRUD6TestCase extends TestCase
{
    use WithDatabaseSeeds;

    /**
     * @var string Main sprinkle class for CRUD6 tests
     */
    protected string $mainSprinkle = CRUD6::class;
}
