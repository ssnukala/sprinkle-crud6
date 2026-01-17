<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Seeds;

use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;

/**
 * Seeder for the default CRUD6 sprinkle roles.
 * 
 * This seeder creates roles specific to the CRUD6 sprinkle itself, not for user-defined
 * models. User-defined model roles should be created via the application or dynamically
 * based on schema permissions.
 * 
 * The 'crud6-admin' role is a sprinkle-level role that provides administrative access
 * to CRUD6 functionality across all models.
 */
class DefaultRoles implements SeedInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $roles = $this->getRoles();

        foreach ($roles as $role) {
            // Don't save if already exist
            if (Role::where('slug', $role->slug)->first() == null) {
                $role->save();
            }
        }
    }

    /**
     * @return Role[] Roles to seed
     */
    protected function getRoles(): array
    {
        return [
            new Role([
                'slug'        => 'crud6-admin',
                'name'        => 'CRUD6 Administrator',
                'description' => 'This role is meant for "CRUD6 administrators", who can basically do anything with users in their own group, except other administrators of that group.',
            ]),
        ];
    }
}
