<?php

/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Database\Migration;

/**
 * Commerce Roles table migration
 * Adds the commerce-admin role for all commerce CRUD operations
 * Version 6.0.0
 *
 * See https://laravel.com/docs/5.4/migrations#tables
 * @extends Migration
 * @author Srinivas Nukala
 */
class CommerceRolesTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if ($this->schema->hasTable('roles')) {
            // Add default roles
            //
            $roles = [
                'commerce-admin' => new Role([
                    'slug' => 'commerce-admin',
                    'name' => 'Commerce Admin',
                    'description' => 'Commerce Admin Role for managing all commerce-related operations including orders, products, catalogs, and categories.'
                ])
            ];

            foreach ($roles as $slug => $role) {
                if (!Role::where('slug', $slug)->first()) {
                    $role->save();
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        Role::whereIn('slug', ['commerce-admin'])->delete();
    }
}
