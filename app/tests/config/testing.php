<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Test environment configuration.
 * 
 * This configuration is loaded during PHPUnit tests to ensure
 * proper test environment setup.
 * 
 * IMPORTANT: This fixes the SQL error "no such column: groups." that occurs when
 * User::factory()->create() triggers the AssignDefaultGroups listener with an
 * empty/null default group configuration.
 * 
 * Solution: Disable automatic group assignment in tests by setting group to null.
 * Tests that need groups should explicitly assign them using $user->groups()->attach().
 */
return [
    'site' => [
        'registration' => [
            'user_defaults' => [
                // Disable automatic group assignment for factory-created users
                // This prevents SQL errors when the groups table is queried with an empty column name
                // Set to null to skip the AssignDefaultGroups listener logic
                'group' => null,
                // Also disable role assignment to avoid similar issues
                'roles' => [],
            ],
        ],
    ],
];
