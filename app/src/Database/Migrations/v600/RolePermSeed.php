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

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultRoles;
use UserFrosting\Sprinkle\CRUD6\Database\Seeds\DefaultPermissions;
use UserFrosting\Sprinkle\Core\Database\Migration;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\RolesTable;
use UserFrosting\Sprinkle\Account\Database\Migrations\v400\PermissionRolesTable;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;

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
        // Skip this if table is not empty
        if (Permission::count() > 0) {
            // Add default permission via seed
            (new DefaultRoles())->run();
            (new DefaultPermissions())->run();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void {}
}
