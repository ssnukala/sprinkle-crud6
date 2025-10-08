<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud6
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Seeds;

use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\PermissionInterface;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;

/**
 * Seeder for the default permissions.
 */
class DefaultPermissions implements SeedInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // Get and save permissions
        $permissions = $this->getPermissions();
        $this->savePermissions($permissions);

        // Add default mappings to permissions
        $this->syncPermissionsRole($permissions);
    }

    /**
     * @return Permission[] Permissions to seed
     */
    protected function getPermissions(): array
    {
        $defaultRoleIds = [
            'crud6-admin' => Role::where('slug', 'crud6-admin')->first()->id, // @phpstan-ignore-line Eloquent doesn't push model to first()
        ];

        return [
            'create_crud6' => new Permission([
                'slug'        => 'create_crud6',
                'name'        => 'Create crud6',
                'conditions'  => 'always()',
                'description' => 'Create a new crud6.',
            ]),
            'delete_crud6' => new Permission([
                'slug'        => 'delete_crud6',
                'name'        => 'Delete crud6',
                'conditions'  => 'always()',
                'description' => 'Delete a crud6.',
            ]),
            'update_crud6_field' => new Permission([
                'slug'        => 'update_crud6_field',
                'name'        => 'Edit crud6',
                'conditions'  => 'always()',
                'description' => 'Edit basic properties of any crud6.',
            ]),
            'uri_crud6' => new Permission([
                'slug'        => 'uri_crud6',
                'name'        => 'View crud6',
                'conditions'  => 'always()',
                'description' => 'View the crud6 page of any crud6.',
            ]),
            'uri_crud6_list' => new Permission([
                'slug'        => 'uri_crud6_list',
                'name'        => 'crud6 management page',
                'conditions'  => 'always()',
                'description' => 'View a page containing a list of crud6s.',
            ]),
            'view_crud6_field' => new Permission([
                'slug'        => 'view_crud6_field',
                'name'        => 'View crud6',
                'conditions'  => 'always()',
                'description' => 'View certain properties of any crud6.',
            ]),
        ];
    }

    /**
     * Save permissions.
     *
     * @param array<string, PermissionInterface> $permissions
     */
    protected function savePermissions(array &$permissions): void
    {
        /** @var PermissionInterface $permission */
        foreach ($permissions as $slug => $permission) {
            // Trying to find if the permission already exists
            $existingPermission = Permission::where([
                'slug'       => $permission->slug,
                'conditions' => $permission->conditions,
            ])->first();

            // Don't save if already exist, use existing permission reference
            // otherwise to re-sync permissions and roles
            if ($existingPermission == null) {
                $permission->save();
            } else {
                $permissions[$slug] = $existingPermission;
            }
        }
    }

    /**
     * Sync permissions with default roles.
     *
     * @param Permission[] $permissions
     */
    protected function syncPermissionsRole(array $permissions): void
    {
        /** @var Role|null */
        $roleSiteAdmin = Role::where('slug', 'site-admin')->first();
        if ($roleSiteAdmin !== null) {
            $roleSiteAdmin->permissions()->sync([
                $permissions['create_crud6']->id,
                $permissions['delete_crud6']->id,
                $permissions['update_crud6_field']->id,
                $permissions['uri_crud6']->id,
                $permissions['uri_crud6_list']->id,
                $permissions['view_crud6_field']->id,
            ]);
        }

        /** @var Role|null */
        $rolecrud6Admin = Role::where('slug', 'crud6-admin')->first();
        if ($rolecrud6Admin !== null) {
            $rolecrud6Admin->permissions()->sync([
                $permissions['create_crud6']->id,
                $permissions['delete_crud6']->id,
                $permissions['update_crud6_field']->id,
                $permissions['uri_crud6']->id,
                $permissions['uri_crud6_list']->id,
                $permissions['view_crud6_field']->id,
            ]);
        }
    }
}
