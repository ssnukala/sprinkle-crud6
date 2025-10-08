<?php

declare(strict_types=1);

/*
 * UserFrosting Account Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-account
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-account/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600;

use UserFrosting\Sprinkle\Core\Database\Migration;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\RolesTable;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\PermissionRolesTable;

/**
 * Permissions table migration
 * Permissions now replace the 'authorize_group' and 'authorize_user' tables.
 * Also, they now map many-to-many to roles.
 * Version 4.0.0.
 */
class PermissionsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static $dependencies = [
        RolesTable::class,
        PermissionRolesTable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        // Note: Seeds should be run via `php bakery seed` command after migrations
        // This migration does not seed data to avoid dependency issues
        // Run seeds in this order:
        // 1. Account sprinkle seeds (DefaultGroups, DefaultPermissions, DefaultRoles, UpdatePermissions)
        // 2. CRUD6 sprinkle seeds (DefaultRoles, DefaultPermissions)
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void {}
}
